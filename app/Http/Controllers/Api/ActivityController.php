<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\Activity;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Atividades (missões). Alunos veem as da sua turma + as gerais. */
class ActivityController extends ApiController
{
    private const MAP = ['phaseId' => 'phase_id', 'modeId' => 'mode_id', 'focusRegionId' => 'focus_region_id', 'schoolClassId' => 'school_class_id', 'estimatedMinutes' => 'estimated_minutes', 'rewardPoints' => 'reward_points', 'isActive' => 'is_active'];

    public function index(Request $request, StatsService $stats)
    {
        $actor = $this->optionalActor($request);

        return $this->ok($stats->activitiesFor($actor?->player, $actor)->map(fn (Activity $a) => $a->toApi() + [
            'phase' => $a->phase?->toApi(),
            'mode' => $a->mode?->toApi(),
            'bestPercent' => $a->best_percent,
            'completed' => $a->completed,
            'unlocked' => $a->unlocked,
        ])->values());
    }

    public function show(Request $request, int $id)
    {
        $actor = $this->optionalActor($request);
        $activity = Activity::query()->visibleTo($actor?->player)->with(['phase', 'mode'])->withCount('questions')->find($id)
            ?? throw ApiException::notFound('Atividade', $id);
        $data = $activity->toApi() + ['phase' => $activity->phase?->toApi(), 'mode' => $activity->mode?->toApi()];
        if ($actor?->isStaff()) {
            $data['questionIds'] = $activity->questions()->pluck('questions.id');
        }

        return $this->ok($data);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules('required'));
        $activity = Activity::query()->create($this->snake(collect($data)->except('questionIds')->all(), self::MAP)
            + ['created_by' => $this->actor($request)->playerId()]);
        $activity->questions()->sync($data['questionIds']);

        return $this->created($activity->loadCount('questions')->toApi());
    }

    public function update(Request $request, int $id)
    {
        $activity = Activity::query()->find($id) ?? throw ApiException::notFound('Atividade', $id);
        $data = $request->validate($this->rules('sometimes'));
        $this->requireAny($data);
        $activity->update($this->snake(collect($data)->except('questionIds')->all(), self::MAP));
        if (isset($data['questionIds'])) {
            $activity->questions()->sync($data['questionIds']);
        }

        return $this->ok($activity->refresh()->loadCount('questions')->toApi());
    }

    public function destroy(int $id)
    {
        $activity = Activity::query()->find($id) ?? throw ApiException::notFound('Atividade', $id);
        $activity->matches()->exists() ? $activity->update(['is_active' => false]) : $activity->delete();

        return $this->noContent();
    }

    private function rules(string $req): array
    {
        return [
            'title' => "{$req}|string|max:120",
            'description' => 'sometimes|nullable|string|max:255',
            'phaseId' => "{$req}|integer|exists:phases,id",
            'modeId' => "{$req}|integer|exists:game_modes,id",
            'focusRegionId' => 'sometimes|nullable|integer|exists:map_regions,id',
            'schoolClassId' => 'sometimes|nullable|integer|exists:school_classes,id',
            'estimatedMinutes' => 'sometimes|integer|min:1|max:120',
            'rewardPoints' => 'sometimes|integer|min:0|max:10000',
            'icon' => ['sometimes', Rule::in(Activity::ICONS)],
            'isActive' => 'sometimes|boolean',
            'questionIds' => "{$req}|array|min:1",
            'questionIds.*' => 'integer|exists:questions,id',
        ];
    }
}
