<?php

namespace App\Domain;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Escolhe a próxima pergunta. RN16: a mesma pergunta não aparece logo após ser
 * respondida. Modos pontuados nunca repetem dentro da partida; modos de prática
 * podem repetir depois que todas foram feitas. Retorna null quando acabou.
 */
final class QuestionSelector
{
    /** @var callable(int):int */
    private $random;

    public function __construct(?callable $random = null)
    {
        $this->random = $random ?? fn (int $max) => random_int(0, $max);
    }

    /**
     * @param  Collection<int, Question>  $candidates
     * @param  int[]  $askedIds  em ordem (mais antiga primeiro)
     */
    public function select(Collection $candidates, array $askedIds, bool $allowRepeats): ?Question
    {
        if ($candidates->isEmpty()) {
            return null;
        }
        $asked = array_flip($askedIds);
        $pool = $candidates->reject(fn (Question $q) => isset($asked[$q->id]))->values();

        if ($pool->isEmpty()) {
            if (! $allowRepeats) {
                return null;
            }
            $last = end($askedIds);
            $pool = $candidates->reject(fn (Question $q) => $q->id === $last)->values();
            if ($pool->isEmpty()) {
                $pool = $candidates->values();
            }
        }

        return $pool[($this->random)($pool->count() - 1)];
    }
}
