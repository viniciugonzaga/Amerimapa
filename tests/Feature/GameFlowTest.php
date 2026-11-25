<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\GameMatch;
use App\Models\MapRegion;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameFlowTest extends TestCase
{
    use RefreshDatabase;

    private const DEVICE = ['X-Device-Key' => 'chave-de-teste', 'X-Device-Id' => 'map-01'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['amerimapa.device_key' => 'chave-de-teste']);
        $this->seed(ContentSeeder::class);
    }

    private function studentToken(): string
    {
        return $this->postJson('/api/v1/auth/register', ['name' => 'Sofia Almeida', 'email' => 'sofia@teste.com', 'password' => 'segredo123'])
            ->assertCreated()->json('data.token');
    }

    public function test_full_match_answering_on_screen(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->studentToken()];
        $activity = Activity::query()->where('title', 'Regiões da América')->first();

        $matchId = $this->postJson('/api/v1/matches', ['activityId' => $activity->id], $h)->assertCreated()->json('data.id');

        for ($i = 0; $i < 4; $i++) {
            $q = $this->postJson("/api/v1/matches/{$matchId}/next-question", [], $h)->assertOk()->json('data');
            if ($q['finished']) {
                break;
            }
            $this->postJson("/api/v1/matches/{$matchId}/next-question", [], $h)->assertStatus(409)->assertJsonPath('error.code', 'QUESTION_STILL_ACTIVE');
            $answer = \App\Models\Question::find($q['question']['id'])->answer_region_id;
            $this->postJson("/api/v1/matches/{$matchId}/answers", ['regionId' => $answer], $h)
                ->assertOk()->assertJsonPath('data.result', 'CORRECT')->assertJsonPath('data.points.base', 10);
        }

        $match = GameMatch::find($matchId);
        $this->assertSame(4, $match->correct_count);
        $this->postJson("/api/v1/matches/{$matchId}/next-question", [], $h)->assertOk()->assertJsonPath('data.finished', true);
        $this->assertSame('COMPLETED', $match->refresh()->status);
    }

    public function test_quiz_allows_two_attempts_then_closes_question(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->studentToken()];
        $quiz = \App\Models\GameMode::byCode('QUIZ');
        $phase1 = \App\Models\Phase::where('number', 1)->first();
        $matchId = $this->postJson('/api/v1/matches', ['modeId' => $quiz->id, 'phaseId' => $phase1->id], $h)->json('data.id');
        $qid = $this->postJson("/api/v1/matches/{$matchId}/next-question", [], $h)->json('data.question.id');
        $wrong = MapRegion::where('type', 'REGION')->where('id', '!=', \App\Models\Question::find($qid)->answer_region_id)->first()->id;

        $this->postJson("/api/v1/matches/{$matchId}/answers", ['regionId' => $wrong], $h)
            ->assertJsonPath('data.questionClosed', false)->assertJsonPath('data.attemptsLeft', 1)->assertJsonPath('data.feedback.led', 'YELLOW');
        $this->postJson("/api/v1/matches/{$matchId}/answers", ['regionId' => $wrong], $h)
            ->assertJsonPath('data.questionClosed', true)->assertJsonPath('data.result', 'WRONG')->assertJsonPath('data.feedback.led', 'RED');
    }

    public function test_locked_phase_is_rejected_for_students(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->studentToken()];
        $phase4 = \App\Models\Phase::where('number', 4)->first();
        $this->postJson('/api/v1/matches', ['modeId' => \App\Models\GameMode::byCode('QUIZ')->id, 'phaseId' => $phase4->id], $h)
            ->assertStatus(422)->assertJsonPath('error.code', 'PHASE_LOCKED');
    }

    public function test_esp32_touch_answers_bound_match(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->postJson('/api/v1/auth/guest')->json('data.token')];
        $this->postJson('/api/v1/hardware/heartbeat', ['firmwareVersion' => '1.0.0'], self::DEVICE)->assertOk();

        $phase2 = \App\Models\Phase::where('number', 2)->first();
        $matchId = $this->postJson('/api/v1/matches', ['modeId' => \App\Models\GameMode::byCode('QUIZ')->id, 'phaseId' => $phase2->id], $h)->json('data.id');
        $this->postJson('/api/v1/hardware/bind-match', ['deviceId' => 'map-01', 'matchId' => $matchId], $h)->assertOk()->assertJsonPath('data.boundMatchId', $matchId);
        $qid = $this->postJson("/api/v1/matches/{$matchId}/next-question", [], $h)->json('data.question.id');
        $pin = \App\Models\Question::find($qid)->answerRegion->hardware_pin;

        $this->postJson('/api/v1/hardware/touches', ['pin' => $pin], self::DEVICE)
            ->assertOk()->assertJsonPath('data.action', 'ANSWERED')->assertJsonPath('data.device.led', 'GREEN')->assertJsonPath('data.device.audioTrack', 1);

        $this->getJson("/api/v1/hardware/events/poll?deviceId=map-01&matchId={$matchId}&after=0", $h)
            ->assertOk()->assertJsonFragment(['type' => 'answer.evaluated']);
        $this->postJson('/api/v1/hardware/touches', ['pin' => 'ZZ:99'], self::DEVICE)->assertStatus(422)->assertJsonPath('error.code', 'UNKNOWN_PIN');
        $this->postJson('/api/v1/hardware/touches', ['pin' => '5A:00'], ['X-Device-Key' => 'errada', 'X-Device-Id' => 'x'])->assertStatus(401);
    }

    public function test_offline_map_cannot_be_bound(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->studentToken()];
        $matchId = $this->postJson('/api/v1/matches', ['activityId' => Activity::first()->id], $h)->json('data.id');
        $this->postJson('/api/v1/hardware/bind-match', ['deviceId' => 'map-99', 'matchId' => $matchId], $h)
            ->assertStatus(503)->assertJsonPath('error.code', 'DEVICE_OFFLINE');
    }

    public function test_questions_are_staff_only_and_validation_format(): void
    {
        $h = ['Authorization' => 'Bearer '.$this->studentToken()];
        $this->getJson('/api/v1/questions', $h)->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
        $this->postJson('/api/v1/auth/login', ['email' => 'x'])->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->getJson('/api/v1/matches')->assertStatus(401);
    }

    public function test_web_pages_render_for_student_and_teacher(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $student = User::where('email', 'sofia@amerimapa.com')->first();
        foreach (['/inicio', '/atividades', '/progresso', '/conquistas', '/perfil', '/explorar', '/jogar?atividade=1'] as $url) {
            $r = $this->actingAs($student)->get($url); $this->assertSame(200, $r->status(), $url.' -> '.$r->headers->get('Location'));
        }
        $teacher = User::where('email', 'mariana@amerimapa.com')->first();
        foreach (['/professor', '/professor/turmas', '/professor/atividades', '/professor/atividades/nova', '/professor/perguntas', '/professor/regioes', '/professor/desempenho', '/professor/relatorios', '/professor/dispositivo'] as $url) {
            $r = $this->actingAs($teacher)->get($url); $this->assertSame(200, $r->status(), $url.' -> '.$r->headers->get('Location'));
        }
        $this->app['auth']->guard()->logout();
        $this->get('/entrar')->assertOk();
    }

    public function test_new_screens_and_flows(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $admin = User::where('role', 'ADMIN')->first();
        $this->actingAs($admin)->get('/professor/professores')->assertOk()->assertSee('Professores');
        $this->actingAs($admin)->post('/professor/professores', ['name' => 'João Silva', 'email' => 'joao@x.com', 'password' => 'segredo123', 'role' => 'TEACHER'])->assertRedirect();
        $this->assertDatabaseHas('players', ['email' => 'joao@x.com', 'role' => 'TEACHER']);

        $teacher = User::where('email', 'mariana@amerimapa.com')->first();
        $this->actingAs($teacher)->get('/professor/professores')->assertForbidden();
        $this->actingAs($teacher)->get('/professor?periodo=30')->assertOk()->assertSee('Notificações');

        $this->app['auth']->guard()->logout();
        $this->post('/esqueci-senha', ['email' => 'sofia@amerimapa.com'])->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'sofia@amerimapa.com']);
        $this->actingAs($teacher)->get('/professor')->assertSee('pediu uma nova senha');

        $this->app['auth']->guard()->logout();
        $this->post('/entrar', ['email' => 'sofia@amerimapa.com', 'password' => 'amerimapa123', 'role' => 'teacher'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/entrar', ['email' => 'sofia@amerimapa.com', 'password' => 'amerimapa123', 'role' => 'student'])->assertRedirect('/inicio');
        $this->get('/conquistas')->assertOk()->assertSee('Ranking da minha turma');
        $this->get('/progresso?periodo=15')->assertOk();
        $this->get('/perfil')->assertOk()->assertSee('images/avatars/menina.png', false)->assertSee('images/Amerimapa.png', false);
    }
}
