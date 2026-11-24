<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** RN01: jogar como visitante ou com perfil cadastrado (só o perfil salva progresso). */
class AuthController extends ApiController
{
    public function __construct(private readonly TokenService $tokens) {}

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:72',
        ]);
        $email = mb_strtolower(trim($data['email']));
        if (User::query()->where('email', $email)->exists()) {
            throw ApiException::conflict('Este e-mail já está cadastrado.', 'EMAIL_ALREADY_IN_USE');
        }
        $player = User::query()->create([
            'name' => trim($data['name']),
            'email' => $email,
            'password_hash' => $data['password'],
            'role' => User::STUDENT,
        ]);

        return $this->created($this->session($player));
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $player = User::query()->where('email', mb_strtolower(trim($data['email'])))->first();
        if (! $player || ! Hash::check($data['password'], $player->password_hash)) {
            // Mesma mensagem para e-mail inexistente e senha errada.
            throw ApiException::unauthorized('E-mail ou senha inválidos.');
        }
        $player->forceFill(['last_login_at' => now()])->save();

        return $this->ok($this->session($player));
    }

    public function guest()
    {
        [$token, $guestId] = $this->tokens->issueForGuest();

        return $this->created(['token' => $token, 'tokenType' => 'Bearer', 'guest' => ['guestId' => $guestId]]);
    }

    public function me(Request $request)
    {
        $actor = $this->actor($request);
        if ($actor->isGuest()) {
            return $this->ok(['type' => 'GUEST', 'guest' => ['guestId' => $actor->guestId]]);
        }

        return $this->ok(['type' => 'PLAYER', 'player' => $actor->player->toApi(), 'preferences' => $actor->player->prefs()->toApi()]);
    }

    public function logout(Request $request)
    {
        $this->tokens->revoke($request->bearerToken());

        return $this->noContent();
    }

    private function session(User $player): array
    {
        return ['token' => $this->tokens->issueForPlayer($player), 'tokenType' => 'Bearer', 'player' => $player->toApi()];
    }
}
