<?php

namespace App\Services;

use App\Domain\Actor;
use App\Domain\AnswerFeedback;
use App\Domain\QuestionSelector;
use App\Domain\ScoringPolicy;
use App\Exceptions\ApiException;
use App\Models\Activity;
use App\Models\Device;
use App\Models\GameMatch;
use App\Models\GameMode;
use App\Models\MapRegion;
use App\Models\MatchAnswer;
use App\Models\Phase;
use App\Models\Question;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Motor do jogo — porte do MatchService da API Node.
 *
 * RN02 início exige modo + fase · RN03 uma pergunta ativa · RN04/RN05 respostas são regiões do mapa
 * RN06/RN07 acerto/erro · RN08 pontuação · RN09 tentativas · RN10 próxima só depois de encerrar
 * RN11 desbloqueio de fases · RN12/RN13 cronômetro e tempo esgotado · RN14 progresso salvo a cada resposta
 * RN16 sem repetição imediata · RN17 feedback em toda resposta
 */
class MatchService
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly ScoringPolicy $scoring,
        private readonly QuestionSelector $selector,
        private readonly AchievementService $achievements,
    ) {}

    // ─── Consultas ──────────────────────────────────────────────────────────

    /** @return array{0: Collection<int, GameMatch>, 1: int} */
    public function list(Actor $actor, array $filter, int $page, int $limit): array
    {
        $query = GameMatch::query()->orderByDesc('id');
        if ($actor->isGuest()) {
            $query->where('guest_id', $actor->guestId);
        } elseif (! $actor->isStaff()) {
            if (isset($filter['playerId']) && (int) $filter['playerId'] !== $actor->playerId()) {
                throw ApiException::forbidden('Você só pode listar as suas partidas.');
            }
            $query->where('player_id', $actor->playerId());
        } elseif (isset($filter['playerId'])) {
            $query->where('player_id', $filter['playerId']);
        }
        foreach (['status' => 'status', 'modeId' => 'mode_id', 'phaseId' => 'phase_id', 'activityId' => 'activity_id'] as $in => $col) {
            if (isset($filter[$in])) {
                $query->where($col, $filter[$in]);
            }
        }
        $total = (clone $query)->count();

        return [$query->forPage($page, $limit)->get(), $total];
    }

    public function details(Actor $actor, int $id): array
    {
        return $this->toView($this->loadAccessible($actor, $id));
    }

    public function listAnswers(Actor $actor, int $id): Collection
    {
        $this->loadAccessible($actor, $id);

        return MatchAnswer::query()->where('match_id', $id)->orderBy('id')->get();
    }

    // ─── Comandos ───────────────────────────────────────────────────────────

    public function start(Actor $actor, array $input): array
    {
        if ($actor->type === Actor::DEVICE) {
            throw ApiException::forbidden('O mapa não inicia partidas.');
        }

        $activity = null;
        if (! empty($input['activityId'])) {
            $activity = Activity::query()->visibleTo($actor->player)->find($input['activityId']);
            if (! $activity) {
                throw ApiException::rule('ACTIVITY_NOT_FOUND', "A atividade {$input['activityId']} não existe ou não está disponível para você.");
            }
        }

        $modeId = $input['modeId'] ?? $activity?->mode_id;
        $phaseId = $activity?->phase_id ?? ($input['phaseId'] ?? null);
        if (! $modeId || ! $phaseId) {
            throw ApiException::rule('MODE_AND_PHASE_REQUIRED', 'Escolha um modo de jogo e uma fase (ou uma atividade) para começar. RN02');
        }

        $mode = GameMode::query()->find($modeId);
        $phase = Phase::query()->find($phaseId);
        if (! $mode) {
            throw ApiException::rule('MODE_NOT_FOUND', "O modo de jogo {$modeId} não existe.");
        }
        if (! $phase) {
            throw ApiException::rule('PHASE_NOT_FOUND', "A fase {$phaseId} não existe.");
        }
        if ($mode->code === GameMode::CLASSROOM && ! $actor->isStaff()) {
            throw ApiException::forbidden('O modo Sala de Aula é iniciado pelo professor.');
        }

        $this->progress->assertPhaseUnlocked($actor, $phase->id);

        $match = GameMatch::query()->create([
            'player_id' => $actor->playerId(),
            'guest_id' => $actor->isGuest() ? $actor->guestId : null,
            'mode_id' => $mode->id,
            'phase_id' => $phase->id,
            'activity_id' => $activity?->id,
            'status' => GameMatch::IN_PROGRESS,
            'started_at' => now(),
        ]);

        return $this->toView($match->refresh());
    }

    public function nextQuestion(Actor $actor, int $id): array
    {
        $match = $this->loadAccessible($actor, $id);
        $this->assertInProgress($match);
        $mode = $match->mode;

        if ($mode->code === GameMode::EXPLORATION) {
            throw ApiException::rule('EXPLORATION_HAS_NO_QUESTIONS', 'No modo Exploração não há perguntas: toque no mapa para conhecer cada área.');
        }

        $match = $this->resolveExpiredQuestion($match, $mode);
        if ($match->active_question_id !== null) {
            throw ApiException::conflict('A pergunta atual precisa ser respondida, pulada ou expirar antes da próxima. RN10', 'QUESTION_STILL_ACTIVE');
        }
        if ($match->status !== GameMatch::IN_PROGRESS) {
            return $this->finishedResult($match);
        }

        $askedIds = MatchAnswer::query()->where('match_id', $match->id)
            ->orderBy('id')->pluck('question_id')->unique()->values()->all();
        $question = $this->selector->select($this->candidates($match), $askedIds, ! $mode->is_scored);

        if (! $question) {
            $match->update(['status' => GameMatch::COMPLETED, 'ended_at' => now()]);
            $this->afterCompletion($match);

            return $this->finishedResult($match->refresh());
        }

        $activated = DB::transaction(function () use ($match, $question) {
            $locked = GameMatch::query()->lockForUpdate()->find($match->id);
            if ($locked->active_question_id !== null) {
                return null;
            }
            $locked->update([
                'active_question_id' => $question->id,
                'question_started_at' => now(),
                'attempts_on_question' => 0,
            ]);

            return $locked;
        });
        if (! $activated) {
            throw ApiException::conflict('Outra pergunta foi ativada ao mesmo tempo.', 'QUESTION_STILL_ACTIVE');
        }

        return [
            'finished' => false,
            'question' => $this->activeQuestionView($question, $activated, $mode),
            'match' => $this->toView($activated),
        ];
    }

    public function answer(Actor $actor, int $id, int $regionId, string $source = 'SCREEN'): array
    {
        $region = MapRegion::query()->find($regionId);
        if (! $region) {
            throw ApiException::rule('UNKNOWN_REGION', "A região {$regionId} não está cadastrada no mapa. RN05");
        }

        return DB::transaction(function () use ($actor, $id, $region, $source) {
            [$match, $mode, $question] = $this->loadActiveContext($actor, $id, lock: true);
            $elapsed = $this->elapsedMs($match->question_started_at);

            if ($this->isExpired($mode, $elapsed)) {
                return $this->closeQuestion($match, $mode, $question, 'TIMEOUT', $source, $region, $elapsed);
            }

            if (AnswerFeedback::isCorrect($question->answer_region_id, $region->id, $region->ancestorIds())) {
                return $this->closeQuestion($match, $mode, $question, 'CORRECT', $source, $region, $elapsed);
            }

            $attemptNumber = $match->attempts_on_question + 1;
            if ($mode->max_attempts !== null && $attemptNumber >= $mode->max_attempts) {
                return $this->closeQuestion($match, $mode, $question, 'WRONG', $source, $region, $elapsed);
            }

            return $this->registerWrongAttempt($match, $mode, $question, $source, $region, $elapsed);
        });
    }

    public function skip(Actor $actor, int $id): array
    {
        return DB::transaction(function () use ($actor, $id) {
            [$match, $mode, $question] = $this->loadActiveContext($actor, $id, lock: true);
            $elapsed = $this->elapsedMs($match->question_started_at);
            $result = $this->isExpired($mode, $elapsed) ? 'TIMEOUT' : 'SKIPPED';

            return $this->closeQuestion($match, $mode, $question, $result, 'SCREEN', null, $elapsed);
        });
    }

    public function finish(Actor $actor, int $id, string $status = GameMatch::COMPLETED): array
    {
        $match = $this->loadAccessible($actor, $id);
        $this->assertInProgress($match);
        $match = $this->resolveExpiredQuestion($match, $match->mode);
        if ($match->status !== GameMatch::IN_PROGRESS) {
            return $this->toView($match);
        }

        $match->update([
            'status' => $status,
            'ended_at' => now(),
            'active_question_id' => null,
            'question_started_at' => null,
            'attempts_on_question' => 0,
        ]);
        if ($status === GameMatch::COMPLETED) {
            $this->afterCompletion($match);
        } else {
            Device::query()->where('bound_match_id', $match->id)->update(['bound_match_id' => null]);
        }

        return $this->toView($match->refresh());
    }

    public function remove(int $id): void
    {
        $match = GameMatch::query()->find($id);
        if (! $match) {
            throw ApiException::notFound('Partida', $id);
        }
        $match->delete();
    }

    // ─── Processamento de respostas ─────────────────────────────────────────

    /** Encerra a pergunta ativa com resultado final (acerto, erro, tempo esgotado ou pulada). */
    private function closeQuestion(GameMatch $match, GameMode $mode, Question $question, string $result, string $source, ?MapRegion $touched, ?int $elapsed): array
    {
        $attemptNumber = $match->attempts_on_question + 1;
        $correct = $result === 'CORRECT';
        $streak = $correct ? $match->streak + 1 : 0;
        $points = $correct
            ? $this->scoring->calculate($question->difficulty, $mode, $attemptNumber, $elapsed, $streak)
            : ScoringPolicy::ZERO;

        $answered = $match->answered_questions + 1;
        $reachedLimit = $mode->questions_per_match !== null && $answered >= $mode->questions_per_match;

        MatchAnswer::query()->create([
            'match_id' => $match->id,
            'question_id' => $question->id,
            'touched_region_id' => $touched?->id,
            'result' => $result,
            'attempt_number' => $attemptNumber,
            'response_time_ms' => $elapsed,
            'points_earned' => $points['total'],
            'source' => $source,
            'answered_at' => now(),
        ]);

        $match->update([
            'total_score' => $match->total_score + $points['total'],
            'correct_count' => $match->correct_count + ($correct ? 1 : 0),
            'wrong_count' => $match->wrong_count + ($result === 'WRONG' ? 1 : 0),
            'streak' => $streak,
            'best_streak' => max($match->best_streak, $streak),
            'answered_questions' => $answered,
            'active_question_id' => null,
            'question_started_at' => null,
            'attempts_on_question' => 0,
        ] + ($reachedLimit ? ['status' => GameMatch::COMPLETED, 'ended_at' => now()] : []));

        if ($correct && $touched && $match->player) {
            $this->achievements->markExplored($match->player, $touched);
        }
        $newAchievements = $reachedLimit ? $this->afterCompletion($match) : [];

        $correctRegion = $question->answerRegion;

        return [
            'result' => $result,
            'correct' => $correct,
            'attemptNumber' => $attemptNumber,
            'attemptsLeft' => 0,
            'questionClosed' => true,
            'points' => $points,
            'responseTimeMs' => $elapsed,
            'touchedRegion' => $touched?->toRef(),
            'correctRegion' => $correctRegion ? $correctRegion->toRef() + ['isoCode' => $correctRegion->iso_code] : null,
            'feedback' => AnswerFeedback::build($result, true, $correctRegion?->name ?? 'desconhecida'),
            'match' => $this->summary($match, $mode),
            'newAchievements' => $newAchievements,
        ];
    }

    /** Resposta errada com tentativas restantes: a pergunta continua ativa. RN07, RN09 */
    private function registerWrongAttempt(GameMatch $match, GameMode $mode, Question $question, string $source, MapRegion $touched, int $elapsed): array
    {
        $attemptNumber = $match->attempts_on_question + 1;
        MatchAnswer::query()->create([
            'match_id' => $match->id,
            'question_id' => $question->id,
            'touched_region_id' => $touched->id,
            'result' => 'WRONG',
            'attempt_number' => $attemptNumber,
            'response_time_ms' => $elapsed,
            'points_earned' => 0,
            'source' => $source,
            'answered_at' => now(),
        ]);
        $match->update([
            'wrong_count' => $match->wrong_count + 1,
            'streak' => 0,
            'attempts_on_question' => $attemptNumber,
        ]);

        return [
            'result' => 'WRONG',
            'correct' => false,
            'attemptNumber' => $attemptNumber,
            'attemptsLeft' => $mode->max_attempts === null ? null : $mode->max_attempts - $attemptNumber,
            'questionClosed' => false,
            'points' => ScoringPolicy::ZERO,
            'responseTimeMs' => $elapsed,
            'touchedRegion' => $touched->toRef(),
            'correctRegion' => null,
            'feedback' => AnswerFeedback::build('WRONG', false, ''),
            'match' => $this->summary($match, $mode),
            'newAchievements' => [],
        ];
    }

    /** RN13: pergunta expirada é encerrada como TIMEOUT antes de qualquer outra ação. */
    private function resolveExpiredQuestion(GameMatch $match, GameMode $mode): GameMatch
    {
        if ($match->active_question_id === null || $match->question_started_at === null) {
            return $match;
        }
        $elapsed = $this->elapsedMs($match->question_started_at);
        if (! $this->isExpired($mode, $elapsed)) {
            return $match;
        }
        DB::transaction(function () use ($match, $mode, $elapsed) {
            $locked = GameMatch::query()->lockForUpdate()->find($match->id);
            if ($locked->active_question_id !== $match->active_question_id) {
                return; // já resolvida por outra requisição
            }
            $question = Question::query()->find($locked->active_question_id);
            $this->closeQuestion($locked, $mode, $question, 'TIMEOUT', 'SCREEN', null, $elapsed);
        });

        return $match->refresh();
    }

    /** Ao concluir: confere conquistas do jogador. Retorna as novas. */
    private function afterCompletion(GameMatch $match): array
    {
        Device::query()->where('bound_match_id', $match->id)->update(['bound_match_id' => null]);
        $player = $match->player;

        return $player ? $this->achievements->sync($player) : [];
    }

    // ─── Auxiliares ─────────────────────────────────────────────────────────

    /** @return Collection<int, Question> */
    private function candidates(GameMatch $match): Collection
    {
        if ($match->activity_id) {
            return Question::query()->where('is_active', true)
                ->whereIn('id', fn ($q) => $q->select('question_id')->from('activity_question')->where('activity_id', $match->activity_id))
                ->get();
        }

        return Question::query()->where('is_active', true)->where('phase_id', $match->phase_id)->get();
    }

    /** @return array{0: GameMatch, 1: GameMode, 2: Question} */
    private function loadActiveContext(Actor $actor, int $id, bool $lock = false): array
    {
        $match = $lock ? GameMatch::query()->lockForUpdate()->find($id) : GameMatch::query()->find($id);
        if (! $match) {
            throw ApiException::notFound('Partida', $id);
        }
        if (! $actor->canAccessMatch($match)) {
            throw ApiException::forbidden('Esta partida pertence a outro jogador.');
        }
        $this->assertInProgress($match);
        if ($match->active_question_id === null || $match->question_started_at === null) {
            throw ApiException::conflict('Não há pergunta ativa. Chame POST /matches/{id}/next-question primeiro.', 'NO_ACTIVE_QUESTION');
        }
        $question = Question::query()->with('answerRegion')->find($match->active_question_id);
        if (! $question) {
            throw ApiException::notFound('Pergunta', $match->active_question_id);
        }

        return [$match, $match->mode, $question];
    }

    private function loadAccessible(Actor $actor, int $id): GameMatch
    {
        $match = GameMatch::query()->with(['mode', 'phase'])->find($id);
        if (! $match) {
            throw ApiException::notFound('Partida', $id);
        }
        if (! $actor->canAccessMatch($match)) {
            throw ApiException::forbidden('Esta partida pertence a outro jogador.');
        }

        return $match;
    }

    private function assertInProgress(GameMatch $match): void
    {
        if ($match->status !== GameMatch::IN_PROGRESS) {
            $label = $match->status === GameMatch::COMPLETED ? 'concluída' : 'abandonada';
            throw ApiException::conflict("Esta partida já foi {$label}.", 'MATCH_NOT_IN_PROGRESS');
        }
    }

    private function elapsedMs(Carbon $startedAt): int
    {
        return max(0, (int) round(now()->getTimestampMs() - $startedAt->getTimestampMs()));
    }

    private function isExpired(GameMode $mode, int $elapsedMs): bool
    {
        return $mode->is_timed && $mode->time_limit_seconds && $elapsedMs > $mode->time_limit_seconds * 1000;
    }

    private function finishedResult(GameMatch $match): array
    {
        return ['finished' => true, 'question' => null, 'match' => $this->toView($match)];
    }

    public function summary(GameMatch $match, GameMode $mode): array
    {
        return [
            'id' => $match->id,
            'status' => $match->status,
            'totalScore' => $match->total_score,
            'correctCount' => $match->correct_count,
            'wrongCount' => $match->wrong_count,
            'streak' => $match->streak,
            'answeredQuestions' => $match->answered_questions,
            'questionsPerMatch' => $this->questionsTarget($match, $mode),
        ];
    }

    /** Quantas perguntas a partida terá (limite do modo ou total disponível). */
    public function questionsTarget(GameMatch $match, GameMode $mode): ?int
    {
        $available = $this->candidates($match)->count();
        if ($mode->questions_per_match !== null) {
            return min($mode->questions_per_match, max(1, $available));
        }

        return $mode->is_scored ? $available : null;
    }

    public function activeQuestionView(Question $question, GameMatch $match, GameMode $mode): array
    {
        $startedAt = $match->question_started_at ?? now();
        $limit = $mode->is_timed ? $mode->time_limit_seconds : null;

        return [
            'id' => $question->id,
            'prompt' => $question->prompt,
            'difficulty' => $question->difficulty,
            'categoryId' => $question->category_id,
            'audioPath' => $question->audio_path,
            'startedAt' => $startedAt->toIso8601ZuluString('millisecond'),
            'expiresAt' => $limit ? $startedAt->copy()->addSeconds($limit)->toIso8601ZuluString('millisecond') : null,
            'timeLimitSeconds' => $limit,
            'attemptsUsed' => $match->attempts_on_question,
            'attemptsLeft' => $mode->max_attempts === null ? null : $mode->max_attempts - $match->attempts_on_question,
        ];
    }

    public function toView(GameMatch $match): array
    {
        $match->loadMissing(['mode', 'phase', 'activity']);
        $mode = $match->mode;
        $question = $match->active_question_id ? Question::query()->find($match->active_question_id) : null;

        return $match->toApi() + [
            'mode' => ['id' => $mode->id, 'code' => $mode->code, 'name' => $mode->name, 'questionsPerMatch' => $this->questionsTarget($match, $mode)],
            'phase' => ['id' => $match->phase_id, 'number' => $match->phase?->number ?? 0, 'title' => $match->phase?->title ?? ''],
            'activity' => $match->activity ? ['id' => $match->activity->id, 'title' => $match->activity->title, 'focusRegionId' => $match->activity->focus_region_id] : null,
            'activeQuestion' => $question ? $this->activeQuestionView($question, $match, $mode) : null,
        ];
    }
}
