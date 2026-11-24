<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\GameMatch;
use App\Models\PlayerPreference;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\ProgressService;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlayerController extends ApiController
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly AchievementService $achievements,
        private readonly StatsService $stats,
    ) {}

    public function index(Request $request)
    {
        [$page, $limit] = $this->pagination($request);
        $f = $request->validate([
            'role' => ['sometimes', Rule::in(User::ROLES)],
            'search' => 'sometimes|string|max:100',
            'schoolClassId' => 'sometimes|integer',
        ]);
        $q = User::query()->orderBy('name');
        if (isset($f['role'])) {
            $q->where('role', $f['role']);
        }
        if (isset($f['schoolClassId'])) {
            $q->where('school_class_id', $f['schoolClassId']);
        }
        if (isset($f['search'])) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$f['search']}%")->orWhere('email', 'like', "%{$f['search']}%"));
        }
        $total = (clone $q)->count();

        return $this->ok($q->forPage($page, $limit)->get()->map->toApi(), $this->meta($page, $limit, $total));
    }

    /** Professor/admin cadastra aluno (ou outro professor, se admin). */
    public function store(Request $request)
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:72',
            'role' => ['sometimes', Rule::in(User::ROLES)],
            'schoolClassId' => 'sometimes|nullable|integer|exists:school_classes,id',
        ]);
        $role = $data['role'] ?? User::STUDENT;
        if ($role !== User::STUDENT && ! $actor->isAdmin()) {
            throw ApiException::forbidden('Apenas administradores criam professores/administradores.');
        }
        $email = mb_strtolower(trim($data['email']));
        if (User::query()->where('email', $email)->exists()) {
            throw ApiException::conflict('Este e-mail já está cadastrado.', 'EMAIL_ALREADY_IN_USE');
        }
        $player = User::query()->create([
            'name' => $data['name'], 'email' => $email, 'password_hash' => $data['password'],
            'role' => $role, 'school_class_id' => $data['schoolClassId'] ?? null,
        ]);

        return $this->created($player->toApi());
    }

    public function show(Request $request, int $id)
    {
        return $this->ok($this->findAccessible($request, $id)->toApi());
    }

    public function update(Request $request, int $id)
    {
        $actor = $this->actor($request);
        $player = $this->findAccessible($request, $id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'nickname' => 'sometimes|nullable|string|max:60',
            'email' => 'sometimes|email|max:255',
            'password' => 'sometimes|string|min:8|max:72',
            'avatar' => ['sometimes', Rule::in(User::AVATARS)],
            'role' => ['sometimes', Rule::in(User::ROLES)],
            'schoolClassId' => 'sometimes|nullable|integer|exists:school_classes,id',
        ]);
        $this->requireAny($data);
        if ((isset($data['role']) && ! $actor->isAdmin()) || (array_key_exists('schoolClassId', $data) && ! $actor->isStaff())) {
            throw ApiException::forbidden('Você não pode alterar o perfil de acesso ou a turma.');
        }
        if (isset($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
            if (User::query()->where('email', $data['email'])->where('id', '!=', $player->id)->exists()) {
                throw ApiException::conflict('Este e-mail já está cadastrado.', 'EMAIL_ALREADY_IN_USE');
            }
        }
        $player->update($this->snake($data, ['password' => 'password_hash', 'schoolClassId' => 'school_class_id']));

        return $this->ok($player->refresh()->toApi());
    }

    public function destroy(Request $request, int $id)
    {
        $actor = $this->actor($request);
        $player = User::query()->find($id) ?? throw ApiException::notFound('Jogador', $id);
        if ($actor->playerId() !== $player->id && ! $actor->isAdmin() && ! ($actor->isStaff() && $player->isStudent())) {
            throw ApiException::forbidden();
        }
        $player->delete();

        return $this->noContent();
    }

    public function progress(Request $request, int $id)
    {
        $p = $this->progress->playerProgress($this->findAccessible($request, $id));
        $p['phases'] = array_map([ProgressService::class, 'phaseProgressToApi'], $p['phases']);

        return $this->ok($p);
    }

    public function matches(Request $request, int $id)
    {
        $this->findAccessible($request, $id);
        [$page, $limit] = $this->pagination($request);
        $q = GameMatch::query()->where('player_id', $id)->orderByDesc('id');
        $total = (clone $q)->count();

        return $this->ok($q->forPage($page, $limit)->get()->map->toApi(), $this->meta($page, $limit, $total));
    }

    public function achievements(Request $request, int $id)
    {
        return $this->ok($this->achievements->forPlayer($this->findAccessible($request, $id)));
    }

    /** Resumo usado pela tela Início/Progresso. */
    public function summary(Request $request, int $id)
    {
        $s = $this->stats->studentSummary($this->findAccessible($request, $id));

        return $this->ok([
            'points' => $s['points'],
            'accuracy' => $s['accuracy'],
            'activitiesDone' => $s['activitiesDone'],
            'activitiesTotal' => $s['activitiesTotal'],
            'overallPercent' => $s['overallPercent'],
            'achievementsUnlocked' => $s['achievementsUnlocked'],
            'evolution' => $s['evolution'],
            'nextMission' => $s['nextMission']?->toApi(),
            'review' => $s['review']?->toApi(),
            'activities' => $s['activities']->map(fn ($a) => $a->toApi() + [
                'bestPercent' => $a->best_percent, 'completed' => $a->completed, 'unlocked' => $a->unlocked,
            ])->values(),
        ]);
    }

    public function preferences(Request $request, int $id)
    {
        return $this->ok($this->findAccessible($request, $id)->prefs()->toApi());
    }

    public function updatePreferences(Request $request, int $id)
    {
        $player = $this->findAccessible($request, $id);
        $data = $request->validate([
            'largeFont' => 'sometimes|boolean',
            'highContrast' => 'sometimes|boolean',
            'audioInstructions' => 'sometimes|boolean',
            'reducedMotion' => 'sometimes|boolean',
        ]);
        $this->requireAny($data);
        $prefs = PlayerPreference::query()->firstOrNew(['player_id' => $player->id]);
        $prefs->fill($prefs->getAttributes() + PlayerPreference::DEFAULTS);
        $prefs->fill($this->snake($data, [
            'largeFont' => 'large_font', 'highContrast' => 'high_contrast',
            'audioInstructions' => 'audio_instructions', 'reducedMotion' => 'reduced_motion',
        ]));
        $prefs->save();

        return $this->ok($prefs->toApi());
    }

    /** O próprio jogador ou professor/admin. */
    private function findAccessible(Request $request, int $id): User
    {
        $actor = $this->actor($request);
        if (! ($actor->isPlayer() && $actor->playerId() === $id) && ! $actor->isStaff()) {
            throw ApiException::forbidden('Você só pode acessar os seus próprios dados.');
        }

        return User::query()->find($id) ?? throw ApiException::notFound('Jogador', $id);
    }
}
