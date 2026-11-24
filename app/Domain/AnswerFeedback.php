<?php

namespace App\Domain;

/**
 * RN17/RN18: toda resposta gera retorno em vários canais — texto na tela,
 * faixa de áudio no DFPlayer e cor de LED no mapa.
 * Faixas 0001-0099 do cartão microSD são de feedback; narrações começam em 0100.
 */
final class AnswerFeedback
{
    public const AUDIO = [
        'CORRECT' => 'mp3/0001.mp3',
        'WRONG' => 'mp3/0002.mp3',
        'TIMEOUT' => 'mp3/0003.mp3',
        'TRY_AGAIN' => 'mp3/0004.mp3',
        'SKIPPED' => 'mp3/0005.mp3',
    ];

    /** @return array{message:string,audioPath:string,led:string} */
    public static function build(string $result, bool $questionClosed, string $correctRegionName): array
    {
        return match ($result) {
            'CORRECT' => ['message' => 'Muito bem! Você acertou!', 'audioPath' => self::AUDIO['CORRECT'], 'led' => 'GREEN'],
            'WRONG' => $questionClosed
                ? ['message' => "Não foi dessa vez. A resposta certa era {$correctRegionName}.", 'audioPath' => self::AUDIO['WRONG'], 'led' => 'RED']
                : ['message' => 'Quase! Tente de novo.', 'audioPath' => self::AUDIO['TRY_AGAIN'], 'led' => 'YELLOW'],
            'TIMEOUT' => ['message' => "O tempo acabou! A resposta certa era {$correctRegionName}.", 'audioPath' => self::AUDIO['TIMEOUT'], 'led' => 'RED'],
            default => ['message' => "Pergunta pulada. A resposta certa era {$correctRegionName}.", 'audioPath' => self::AUDIO['SKIPPED'], 'led' => 'YELLOW'],
        };
    }

    /** Correto quando toca a área esperada ou uma área dentro dela. RN06, RN22 */
    public static function isCorrect(int $expectedRegionId, int $touchedRegionId, array $touchedAncestorIds): bool
    {
        return $touchedRegionId === $expectedRegionId || in_array($expectedRegionId, $touchedAncestorIds, true);
    }

    /** "mp3/0042.mp3" -> 42 */
    public static function trackNumber(?string $audioPath): ?int
    {
        return $audioPath && preg_match('/(\d+)\.mp3$/i', $audioPath, $m) ? (int) $m[1] : null;
    }
}
