<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PlayController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\StudentController;
use App\Http\Controllers\Web\Teacher\ActivityController;
use App\Http\Controllers\Web\Teacher\ClassController;
use App\Http\Controllers\Web\Teacher\DashboardController;
use App\Http\Controllers\Web\Teacher\QuestionController;
use App\Http\Controllers\Web\Teacher\RegionController;
use App\Http\Controllers\Web\Teacher\ReportController;
use Illuminate\Support\Facades\Route;

// ─── Entrada ────────────────────────────────────────────────────────────────
Route::get('/', [AuthController::class, 'home'])->name('home');
Route::get('/paginas/{slug}', [PageController::class, 'show'])->name('page')->whereIn('slug', ['termos', 'privacidade', 'suporte']);
Route::get('/docs/api', [PageController::class, 'apiDocs'])->name('docs.api');

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/entrar', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/cadastro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/esqueci-senha', [AuthController::class, 'forgot'])->name('password.request');
});
Route::post('/visitante', [AuthController::class, 'guest'])->name('guest.start');
Route::post('/sair', [AuthController::class, 'logout'])->name('logout');

// ─── Jogo: aluno, professor ou visitante (RN01) ─────────────────────────────
Route::middleware('player.or.guest')->group(function () {
    Route::get('/atividades', [PlayController::class, 'activities'])->name('activities');
    Route::get('/jogar', [PlayController::class, 'play'])->name('play');
    Route::get('/explorar', [PlayController::class, 'explore'])->name('explore');
    Route::get('/partidas/{match}/resultado', [PlayController::class, 'result'])->name('match.result')->whereNumber('match');
});

// ─── Aluno ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'player.or.guest'])->group(function () {
    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/perfil/acessibilidade', [ProfileController::class, 'preferences'])->name('profile.preferences');
    Route::put('/perfil/senha', [ProfileController::class, 'password'])->name('profile.password');

    Route::middleware('role:student')->group(function () {
        Route::get('/inicio', [StudentController::class, 'home'])->name('student.home');
        Route::get('/progresso', [StudentController::class, 'progress'])->name('progress');
        Route::get('/conquistas', [StudentController::class, 'achievements'])->name('achievements');
    });

    // ─── Professor ──────────────────────────────────────────────────────────
    Route::prefix('professor')->name('teacher.')->middleware('role:staff')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/buscar', [DashboardController::class, 'search'])->name('search');
        Route::get('/dispositivo', [DashboardController::class, 'device'])->name('device');

        Route::get('/turmas', [ClassController::class, 'index'])->name('classes.index');
        Route::post('/turmas', [ClassController::class, 'store'])->name('classes.store');
        Route::get('/turmas/{class}', [ClassController::class, 'show'])->name('classes.show');
        Route::put('/turmas/{class}', [ClassController::class, 'update'])->name('classes.update');
        Route::delete('/turmas/{class}', [ClassController::class, 'destroy'])->name('classes.destroy');
        Route::post('/turmas/{class}/alunos', [ClassController::class, 'storeStudent'])->name('classes.students.store');
        Route::put('/alunos/{student}', [ClassController::class, 'updateStudent'])->name('students.update');
        Route::delete('/alunos/{student}', [ClassController::class, 'destroyStudent'])->name('students.destroy');

        Route::get('/atividades', [ActivityController::class, 'index'])->name('activities.index');
        Route::get('/atividades/nova', [ActivityController::class, 'create'])->name('activities.create');
        Route::post('/atividades', [ActivityController::class, 'store'])->name('activities.store');
        Route::get('/atividades/{activity}/editar', [ActivityController::class, 'edit'])->name('activities.edit');
        Route::put('/atividades/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/atividades/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

        Route::get('/perguntas', [QuestionController::class, 'index'])->name('questions.index');
        Route::get('/perguntas/nova', [QuestionController::class, 'create'])->name('questions.create');
        Route::post('/perguntas', [QuestionController::class, 'store'])->name('questions.store');
        Route::get('/perguntas/{question}/editar', [QuestionController::class, 'edit'])->name('questions.edit');
        Route::put('/perguntas/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/perguntas/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

        Route::get('/regioes', [RegionController::class, 'index'])->name('regions.index');
        Route::post('/regioes', [RegionController::class, 'store'])->name('regions.store');
        Route::put('/regioes/{region}', [RegionController::class, 'update'])->name('regions.update');

        Route::get('/desempenho', [ReportController::class, 'performance'])->name('performance');
        Route::get('/relatorios', [ReportController::class, 'reports'])->name('reports');
        Route::get('/relatorios/exportar', [ReportController::class, 'export'])->name('reports.export');
    });
});
