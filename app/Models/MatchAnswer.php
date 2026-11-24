<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma linha por tentativa de resposta (telemetria). RN06, RN07, RN13 */
class MatchAnswer extends Model
{
    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['match_id', 'question_id', 'touched_region_id', 'result', 'attempt_number', 'response_time_ms', 'points_earned', 'source', 'answered_at'];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function touchedRegion(): BelongsTo
    {
        return $this->belongsTo(MapRegion::class, 'touched_region_id');
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'matchId' => $this->match_id,
            'questionId' => $this->question_id,
            'touchedRegionId' => $this->touched_region_id,
            'result' => $this->result,
            'attemptNumber' => $this->attempt_number,
            'responseTimeMs' => $this->response_time_ms,
            'pointsEarned' => $this->points_earned,
            'source' => $this->source,
            'answeredAt' => optional($this->answered_at)->toIso8601ZuluString('millisecond'),
        ];
    }
}
