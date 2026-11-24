<?php

namespace Database\Seeders;

use App\Domain\ScoringPolicy;
use App\Models\Activity;
use App\Models\Device;
use App\Models\GameMatch;
use App\Models\GameMode;
use App\Models\MatchAnswer;
use App\Models\MapRegion;
use App\Models\PlayerPreference;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dados de demonstração: admin, Prof. Mariana, 5 turmas, 48 alunos (incluindo a Sofia),
 * partidas dos últimos 7 dias, conquistas e o mapa "map-01".
 * Senha de todas as contas de demonstração: amerimapa123
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'amerimapa123';

    private const FIRST = ['Lucas', 'Ana', 'Pedro', 'Julia', 'Gabriel', 'Laura', 'Miguel', 'Helena', 'Arthur', 'Alice', 'Heitor', 'Manuela', 'Davi', 'Valentina', 'Bernardo', 'Lívia', 'Théo', 'Beatriz', 'Samuel', 'Isadora', 'Enzo', 'Clara', 'Rafael', 'Luísa'];

    private const LAST = ['Oliveira', 'Souza', 'Lima', 'Santos', 'Pereira', 'Costa', 'Rodrigues', 'Martins', 'Ferreira', 'Alves', 'Ribeiro', 'Carvalho'];

    public function run(): void
    {
        mt_srand(2026);

        User::query()->updateOrCreate(['email' => config('amerimapa.seed_admin_email')], [
            'name' => 'Administração AmeriMapa', 'role' => User::ADMIN, 'password_hash' => config('amerimapa.seed_admin_password'), 'avatar' => 'estrela',
        ]);
        $teacher = User::query()->updateOrCreate(['email' => 'mariana@amerimapa.com'], [
            'name' => 'Mariana Rigueiro', 'nickname' => 'Prof. Mariana', 'role' => User::TEACHER, 'password_hash' => self::PASSWORD, 'avatar' => 'flor',
        ]);

        $classes = [];
        foreach ([['Turma 1', '4º ano', 'blue', 12], ['Turma 2', '3º ano', 'green', 10], ['Turma 3', '5º ano', 'orange', 8], ['Turma 4', '6º ano', 'pink', 9], ['Turma 5', '7º ano', 'purple', 9]] as [$name, $grade, $color, $size]) {
            $classes[] = [SchoolClass::query()->updateOrCreate(['name' => $name, 'teacher_id' => $teacher->id], ['grade' => $grade, 'color' => $color]), $size];
        }

        // Alunos
        $sofia = User::query()->updateOrCreate(['email' => 'sofia@amerimapa.com'], [
            'name' => 'Sofia Almeida', 'nickname' => 'Sofia', 'role' => User::STUDENT, 'password_hash' => self::PASSWORD,
            'avatar' => 'sol', 'school_class_id' => $classes[1][0]->id,
        ]);
        PlayerPreference::query()->updateOrCreate(['player_id' => $sofia->id], PlayerPreference::DEFAULTS);

        $students = [];
        $n = 0;
        foreach ($classes as $ci => [$class, $size]) {
            for ($i = 0; $i < $size - ($ci === 1 ? 1 : 0); $i++, $n++) {
                $name = self::FIRST[$n % count(self::FIRST)].' '.self::LAST[($n * 7 + $ci) % count(self::LAST)];
                $email = 'aluno'.($n + 1).'@amerimapa.com';
                $students[] = User::query()->updateOrCreate(['email' => $email], [
                    'name' => $name, 'role' => User::STUDENT, 'password_hash' => self::PASSWORD,
                    'avatar' => User::AVATARS[$n % count(User::AVATARS)], 'school_class_id' => $class->id,
                ]);
            }
        }

        // Atividades criadas pela professora para turmas específicas
        $quiz = GameMode::byCode('QUIZ');
        $sa = MapRegion::query()->where('name', 'América do Sul')->first();
        $extra = Activity::query()->updateOrCreate(['title' => 'Vizinhos do Brasil', 'school_class_id' => $classes[1][0]->id], [
            'description' => 'Quais países fazem fronteira com o Brasil?', 'phase_id' => $sa->questions()->first()?->phase_id ?? 2,
            'mode_id' => $quiz->id, 'focus_region_id' => $sa->id, 'created_by' => $teacher->id, 'icon' => 'flag',
            'estimated_minutes' => 5, 'reward_points' => 100,
        ]);
        $neighbors = MapRegion::query()->whereIn('iso_code', ['ARG', 'URY', 'PRY', 'BOL', 'PER', 'COL', 'VEN', 'GUY', 'SUR'])->pluck('id');
        $extra->update(['phase_id' => Question::query()->whereIn('answer_region_id', $neighbors)->min('phase_id')]);
        $extra->questions()->sync(Question::query()->whereIn('answer_region_id', $neighbors)->where('phase_id', $extra->phase_id)->pluck('id'));

        // Partidas simuladas (só se ainda não houver)
        if (GameMatch::query()->exists()) {
            $this->syncAchievements();

            return;
        }
        $general = Activity::query()->whereNull('school_class_id')->orderBy('phase_id')->orderBy('id')->get();

        // Sofia: boa aluna, quase tudo concluído
        foreach ([[6, 0], [5, 1], [5, 2], [4, 1], [3, 3], [2, 1], [1, 4], [0, 1], [0, 2]] as [$daysAgo, $idx]) {
            $this->simulate($sofia, $general[$idx % $general->count()], $quiz, $daysAgo, 0.75 + (6 - $daysAgo) * 0.03);
        }
        $this->simulate($sofia, $extra, $quiz, 1, 0.9);
        $this->simulate($sofia, $general[1], GameMode::byCode('CHALLENGE'), 0, 1.0);

        foreach ($students as $i => $student) {
            $skill = 0.35 + (mt_rand(0, 55) / 100);
            $count = mt_rand(1, 4);
            for ($k = 0; $k < $count; $k++) {
                $activity = $general[min($k, $general->count() - 1)];
                $this->simulate($student, $activity, $quiz, mt_rand(0, 6), min(1, $skill + $k * 0.04));
            }
        }

        Device::query()->updateOrCreate(['device_id' => config('amerimapa.default_device_id')], [
            'name' => 'Mapa da sala 1', 'firmware_version' => '1.0.0', 'sensors_ok' => true, 'battery_level' => 78,
            'last_seen_at' => now()->subMinutes(2), 'announced_online' => false,
        ]);

        $this->syncAchievements();
    }

    private function syncAchievements(): void
    {
        $service = app(AchievementService::class);
        User::query()->where('role', User::STUDENT)->each(fn (User $u) => $service->sync($u));
    }

    /** Cria uma partida concluída com respostas coerentes com as regras de pontuação. */
    private function simulate(User $player, Activity $activity, GameMode $mode, int $daysAgo, float $skill): void
    {
        $questions = $activity->questions()->with('answerRegion')->get()->shuffle();
        $limit = $mode->questions_per_match ?? $questions->count();
        $questions = $questions->take($limit);
        if ($questions->isEmpty()) {
            return;
        }

        $scoring = new ScoringPolicy;
        $start = Carbon::today()->subDays($daysAgo)->setTime(mt_rand(8, 16), mt_rand(0, 59));
        $time = $start->copy();
        $match = GameMatch::query()->create([
            'player_id' => $player->id, 'mode_id' => $mode->id, 'phase_id' => $activity->phase_id,
            'activity_id' => $activity->id, 'status' => GameMatch::IN_PROGRESS, 'started_at' => $start,
        ]);

        $streak = $best = $score = $correct = $wrong = 0;
        $wrongPool = MapRegion::query()->where('type', 'COUNTRY')->pluck('id')->all();
        foreach ($questions as $q) {
            $attempts = $mode->max_attempts ?? 3;
            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                $ms = mt_rand(2500, 14000);
                $time->addMilliseconds($ms + 1500);
                $hit = (mt_rand(0, 1000) / 1000) < ($attempt === 1 ? $skill : min(1, $skill + 0.2));
                $last = $attempt === $attempts;
                if ($hit) {
                    $streak++;
                    $best = max($best, $streak);
                    $pts = $scoring->calculate($q->difficulty, $mode, $attempt, $ms, $streak);
                    $score += $pts['total'];
                    $correct++;
                    $this->answer($match, $q, $q->answer_region_id, 'CORRECT', $attempt, $ms, $pts['total'], $time);
                    DB::table('explored_regions')->insertOrIgnore(['player_id' => $player->id, 'region_id' => $q->answer_region_id, 'first_explored_at' => $time]);
                    if ($q->answerRegion?->parent_id) {
                        DB::table('explored_regions')->insertOrIgnore(['player_id' => $player->id, 'region_id' => $q->answerRegion->parent_id, 'first_explored_at' => $time]);
                    }
                    break;
                }
                $streak = 0;
                $wrong++;
                $this->answer($match, $q, $wrongPool[array_rand($wrongPool)], 'WRONG', $attempt, $ms, 0, $time);
                if ($last) {
                    break;
                }
            }
        }

        $match->update([
            'status' => GameMatch::COMPLETED, 'total_score' => $score, 'correct_count' => $correct, 'wrong_count' => $wrong,
            'streak' => $streak, 'best_streak' => $best, 'answered_questions' => $questions->count(), 'ended_at' => $time,
        ]);
    }

    private function answer(GameMatch $match, Question $q, int $regionId, string $result, int $attempt, int $ms, int $points, Carbon $at): void
    {
        MatchAnswer::query()->create([
            'match_id' => $match->id, 'question_id' => $q->id, 'touched_region_id' => $regionId, 'result' => $result,
            'attempt_number' => $attempt, 'response_time_ms' => $ms, 'points_earned' => $points,
            'source' => mt_rand(0, 3) ? 'MAP' : 'SCREEN', 'answered_at' => $at->copy(),
        ]);
    }
}
