<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Administração de professores (somente ADMIN). */
class TeacherAdminController extends Controller
{
    public function index(Request $request)
    {
        $this->onlyAdmin($request);

        return view('teacher.teachers', [
            'teachers' => User::query()->whereIn('role', [User::TEACHER, User::ADMIN])->withCount('teachingClasses')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->onlyAdmin($request);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255|unique:players,email',
            'password' => 'required|string|min:8|max:72',
            'role' => ['required', Rule::in([User::TEACHER, User::ADMIN])],
        ], [], ['name' => 'nome', 'email' => 'e-mail', 'password' => 'senha']);
        User::query()->create([
            'name' => $data['name'], 'nickname' => 'Prof. '.explode(' ', trim($data['name']))[0],
            'email' => mb_strtolower($data['email']), 'password_hash' => $data['password'],
            'role' => $data['role'], 'avatar' => 'professora',
        ]);

        return back()->with('status', 'Professor(a) cadastrado(a)!');
    }

    public function update(Request $request, User $teacher)
    {
        $this->onlyAdmin($request);
        abort_unless($teacher->isStaff(), 404);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:255', Rule::unique('players', 'email')->ignore($teacher->id)],
            'role' => ['required', Rule::in([User::TEACHER, User::ADMIN])],
            'password' => 'nullable|string|min:8|max:72',
        ]);
        if ($teacher->id === $request->user()->id && $data['role'] !== User::ADMIN) {
            return back()->withErrors(['role' => 'Você não pode tirar o seu próprio acesso de administrador.']);
        }
        $update = ['name' => $data['name'], 'email' => mb_strtolower($data['email']), 'role' => $data['role']];
        if (! empty($data['password'])) {
            $update['password_hash'] = $data['password'];
        }
        $teacher->update($update);

        return back()->with('status', 'Dados atualizados!');
    }

    public function destroy(Request $request, User $teacher)
    {
        $this->onlyAdmin($request);
        abort_unless($teacher->isStaff(), 404);
        if ($teacher->id === $request->user()->id) {
            return back()->withErrors(['email' => 'Você não pode excluir a sua própria conta.']);
        }
        $teacher->delete(); // turmas ficam sem professor (nullOnDelete)

        return back()->with('status', 'Professor(a) removido(a). As turmas dele(a) ficaram sem professor.');
    }

    private function onlyAdmin(Request $request): void
    {
        abort_unless($request->user()->role === User::ADMIN, 403);
    }
}
