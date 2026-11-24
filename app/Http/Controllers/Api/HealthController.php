<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends ApiController
{
    public function __invoke()
    {
        try {
            DB::select('SELECT 1');
            $db = 'up';
        } catch (Throwable) {
            $db = 'down';
        }

        return $this->ok(['status' => $db === 'up' ? 'ok' : 'degraded', 'database' => $db, 'time' => now()->toIso8601ZuluString()]);
    }
}
