<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Device;
use App\Models\GameEvent;
use App\Models\User;
use App\Services\HardwareService;
use App\Services\StatsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly StatsService $stats) {}

    public function index(Request $request)
    {
        $days = StatsService::period($request->query('periodo'));

        return view('teacher.dashboard', ['o' => $this->stats->teacherOverview($request->user(), $days), 'days' => $days]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $teacher = $request->user();
        $classes = $this->stats->classesOf($teacher);
        $classIds = $classes->pluck('id');

        return view('teacher.search', [
            'q' => $q,
            'classes' => $q === '' ? collect() : $classes->filter(fn ($c) => str_contains(mb_strtolower($c->name.' '.$c->grade), mb_strtolower($q))),
            'students' => $q === '' ? collect() : User::query()->where('role', User::STUDENT)
                ->when($teacher->role !== User::ADMIN, fn ($w) => $w->whereIn('school_class_id', $classIds))
                ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                ->with('schoolClass')->limit(20)->get(),
            'activities' => $q === '' ? collect() : Activity::query()->where('title', 'like', "%{$q}%")->limit(20)->get(),
        ]);
    }

    public function markNotificationsRead(Request $request)
    {
        $request->session()->put('notifications_seen_at', now()->toIso8601String());

        return back();
    }

    public function device(HardwareService $hardware)
    {
        $hardware->checkConnectivity();

        return view('teacher.device', [
            'devices' => Device::query()->orderBy('device_id')->get(),
            'events' => GameEvent::query()->latest('id')->limit(25)->get(),
            'deviceKeyHint' => substr((string) config('amerimapa.device_key'), 0, 4).'••••',
        ]);
    }
}
