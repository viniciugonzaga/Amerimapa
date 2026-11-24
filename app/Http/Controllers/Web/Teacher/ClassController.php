<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Turmas e alunos. */
class ClassController extends Controller
{
    public function __construct(private readonly StatsService $stats) {}

    public function index(Request $request)
    {
        return view('teacher.classes.index', ['classes' => $this->stats->classesOf($request->user())]);
    }

    public function store(Request $request)
    {
        $data = $this->validateClass($request);
        $class = SchoolClass::query()->create($data + ['teacher_id' => $request->user()->id]);

        return redirect()->route('teacher.classes.show', $class)->with('status', 'Turma criada!');
    }

    public function show(Request $request, SchoolClass $class)
    {
        $this->authorizeClass($request, $class);
        $ids = $class->students()->pluck('id')->all();

        return view('teacher.classes.show', [
            'class' => $class,
            'report' => $this->stats->classReport($ids),
            'activities' => $class->activities()->withCount('questions')->get(),
            'chart' => $this->stats->dailyAccuracy(fn ($q) => $q->whereIn('player_id', $ids)),
        ]);
    }

    public function update(Request $request, SchoolClass $class)
    {
        $this->authorizeClass($request, $class);
        $class->update($this->validateClass($request));

        return back()->with('status', 'Turma atualizada!');
    }

    public function destroy(Request $request, SchoolClass $class)
    {
        $this->authorizeClass($request, $class);
        $class->delete();

        return redirect()->route('teacher.classes.index')->with('status', 'Turma removida. Os alunos continuam cadastrados, sem turma.');
    }

    public function storeStudent(Request $request, SchoolClass $class)
    {
        $this->authorizeClass($request, $class);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255|unique:players,email',
            'password' => 'required|string|min:8|max:72',
        ], [], ['name' => 'nome', 'email' => 'e-mail', 'password' => 'senha']);
        User::query()->create([
            'name' => $data['name'], 'email' => mb_strtolower($data['email']), 'password_hash' => $data['password'],
            'role' => User::STUDENT, 'school_class_id' => $class->id, 'avatar' => User::AVATARS[array_rand(User::AVATARS)],
        ]);

        return back()->with('status', 'Aluno(a) cadastrado(a) na turma!');
    }

    public function updateStudent(Request $request, User $student)
    {
        $this->authorizeStudent($request, $student);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:255', Rule::unique('players', 'email')->ignore($student->id)],
            'school_class_id' => 'nullable|integer|exists:school_classes,id',
            'password' => 'nullable|string|min:8|max:72',
        ]);
        if (! empty($data['school_class_id'])) {
            $this->authorizeClass($request, SchoolClass::query()->findOrFail($data['school_class_id']));
        }
        $update = ['name' => $data['name'], 'email' => mb_strtolower($data['email']), 'school_class_id' => $data['school_class_id'] ?? null];
        if (! empty($data['password'])) {
            $update['password_hash'] = $data['password'];
        }
        $student->update($update);

        return back()->with('status', 'Dados do aluno atualizados!');
    }

    public function destroyStudent(Request $request, User $student)
    {
        $this->authorizeStudent($request, $student);
        $student->delete();

        return back()->with('status', 'Aluno removido.');
    }

    private function validateClass(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:80',
            'grade' => 'nullable|string|max:40',
            'color' => ['required', Rule::in(SchoolClass::COLORS)],
        ], [], ['name' => 'nome', 'grade' => 'ano/série']);
    }

    private function authorizeClass(Request $request, SchoolClass $class): void
    {
        $user = $request->user();
        abort_unless($user->role === User::ADMIN || $class->teacher_id === $user->id, 403);
    }

    private function authorizeStudent(Request $request, User $student): void
    {
        abort_unless($student->isStudent(), 404);
        $user = $request->user();
        abort_unless($user->role === User::ADMIN || ($student->schoolClass && $student->schoolClass->teacher_id === $user->id), 403);
    }
}
