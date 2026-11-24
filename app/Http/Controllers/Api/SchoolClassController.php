<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\SchoolClass;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Turmas (professor/admin). */
class SchoolClassController extends ApiController
{
    public function index(Request $request, StatsService $stats)
    {
        return $this->ok($stats->classesOf($this->actor($request)->player)->map->toApi());
    }

    public function show(Request $request, int $id)
    {
        $class = $this->findOwned($request, $id);

        return $this->ok($class->toApi() + ['students' => $class->students()->orderBy('name')->get()->map->toApi()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'grade' => 'sometimes|nullable|string|max:40',
            'color' => ['sometimes', Rule::in(SchoolClass::COLORS)],
        ]);
        $class = SchoolClass::query()->create($data + ['teacher_id' => $this->actor($request)->playerId()]);

        return $this->created($class->toApi());
    }

    public function update(Request $request, int $id)
    {
        $class = $this->findOwned($request, $id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:80',
            'grade' => 'sometimes|nullable|string|max:40',
            'color' => ['sometimes', Rule::in(SchoolClass::COLORS)],
        ]);
        $this->requireAny($data);
        $class->update($data);

        return $this->ok($class->refresh()->toApi());
    }

    public function destroy(Request $request, int $id)
    {
        $this->findOwned($request, $id)->delete();

        return $this->noContent();
    }

    private function findOwned(Request $request, int $id): SchoolClass
    {
        $actor = $this->actor($request);
        $class = SchoolClass::query()->find($id) ?? throw ApiException::notFound('Turma', $id);
        if (! $actor->isAdmin() && $class->teacher_id !== $actor->playerId()) {
            throw ApiException::forbidden('Esta turma pertence a outro professor.');
        }

        return $class;
    }
}
