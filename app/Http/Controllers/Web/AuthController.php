<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PlayerPreference;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(private readonly TokenService $tokens) {}

    /** "/" leva cada perfil para a sua tela inicial. */
    public function home(Request $request)
    {
        $user = $request->user();
        if ($user) {
            return redirect()->route($user->isStaff() ? 'teacher.dashboard' : 'student.home');
        }
        if ($request->session()->has('guest_id')) {
            return redirect()->route('activities');
        }

        return redirect()->route('login');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => ['nullable', Rule::in(['student', 'teacher'])],
        ], [], ['email' => 'e-mail', 'password' => 'senha']);

        if (! Auth::attempt(['email' => mb_strtolower(trim($data['email'])), 'password' => $data['password']], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'E-mail ou senha inválidos.'])->onlyInput('email', 'role');
        }

        $user = $request->user();
        $role = $data['role'] ?? null;
        if (($role === 'teacher' && ! $user->isStaff()) || ($role === 'student' && $user->isStaff())) {
            Auth::logout();

            return back()->withErrors(['email' => $role === 'teacher'
                ? 'Esta conta é de aluno. Selecione "Aluno" para entrar.'
                : 'Esta conta é de professor. Selecione "Professor" para entrar.'])->onlyInput('email', 'role');
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->forget('guest_id');
        $request->session()->put('api_token', $this->tokens->issueForPlayer($user));

        return redirect()->intended(route($user->isStaff() ? 'teacher.dashboard' : 'student.home'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /** Cadastro de aluno. Professores são criados pelo administrador. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255|unique:players,email',
            'password' => 'required|string|min:8|max:72|confirmed',
        ], [], ['name' => 'nome', 'email' => 'e-mail', 'password' => 'senha']);

        $user = User::query()->create([
            'name' => trim($data['name']),
            'nickname' => Str::before(trim($data['name']), ' '),
            'email' => mb_strtolower(trim($data['email'])),
            'password_hash' => $data['password'],
            'role' => User::STUDENT,
            'avatar' => ['menina', 'menino'][random_int(0, 1)],
        ]);
        PlayerPreference::query()->create(['player_id' => $user->id] + PlayerPreference::DEFAULTS);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('guest_id');
        $request->session()->put('api_token', $this->tokens->issueForPlayer($user));

        return redirect()->route('student.home')->with('status', 'Conta criada! Bem-vindo(a) ao AmeriMapa.');
    }

    /** RN01: jogar sem cadastro (progresso não é salvo). */
    public function guest(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('home');
        }
        [$token, $guestId] = $this->tokens->issueForGuest();
        $request->session()->put('guest_id', $guestId);
        $request->session()->put('api_token', $token);

        return redirect()->route('activities');
    }

    public function forgot()
    {
        return view('auth.forgot');
    }

    /**
     * Sem e-mail configurado na escola: o pedido aparece nas notificações
     * do professor da turma, que define a nova senha em Turmas e alunos.
     */
    public function requestReset(Request $request)
    {
        $data = $request->validate(['email' => 'required|email'], [], ['email' => 'e-mail']);
        $email = mb_strtolower(trim($data['email']));
        if (User::query()->where('email', $email)->exists()) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => hash('sha256', Str::random(40)), 'created_at' => now()],
            );
        }

        // Mesma resposta exista ou não o e-mail (não revela contas).
        return back()->with('status', 'Pedido enviado! Seu professor ou professora vai criar uma nova senha para você.');
    }

    public function logout(Request $request)
    {
        $this->tokens->revoke($request->session()->get('api_token'));
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
