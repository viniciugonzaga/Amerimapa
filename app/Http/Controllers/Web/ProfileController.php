<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PlayerPreference;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/** Meu perfil e acessibilidade (aluno e professor). */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('schoolClass');

        return view('student.profile', [
            'user' => $user,
            'layout' => $user->isStaff() ? 'layouts.teacher' : 'layouts.student',
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'nickname' => 'nullable|string|max:60',
            'email' => ['required', 'email', 'max:255', Rule::unique('players', 'email')->ignore($user->id)],
            'avatar' => ['required', Rule::in(User::AVATARS)],
        ], [], ['name' => 'nome', 'nickname' => 'apelido', 'email' => 'e-mail']);
        $data['email'] = mb_strtolower(trim($data['email']));
        $user->update($data);

        return back()->with('status', 'Alterações salvas!');
    }

    public function preferences(Request $request)
    {
        $prefs = PlayerPreference::query()->firstOrNew(['player_id' => $request->user()->id]);
        $prefs->fill([
            'large_font' => $request->boolean('large_font'),
            'high_contrast' => $request->boolean('high_contrast'),
            'audio_instructions' => $request->boolean('audio_instructions'),
            'reduced_motion' => $request->boolean('reduced_motion'),
        ])->save();

        if ($request->expectsJson()) {
            return response()->json(['data' => $prefs->toApi()]);
        }

        return back()->with('status', 'Preferências de acessibilidade salvas!');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|max:72|confirmed',
        ], [], ['current_password' => 'senha atual', 'password' => 'nova senha']);
        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password_hash)) {
            return back()->withErrors(['current_password' => 'A senha atual não confere.']);
        }
        $user->update(['password_hash' => $data['password']]);

        return back()->with('status', 'Senha alterada!');
    }
}
