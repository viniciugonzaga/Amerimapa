# =====================================================================
# AmeriMapa — corrige o projeto no Windows
# Uso (na pasta do projeto, no PowerShell):
#   powershell -ExecutionPolicy Bypass -File .\corrigir-projeto.ps1
# =====================================================================
$ErrorActionPreference = "Continue"
Set-Location $PSScriptRoot
Write-Host "1/5 Removendo arquivos antigos que conflitam com o projeto novo..." -ForegroundColor Cyan
$antigos = @(
  ".npmrc",
  "AGENTS.md",
  "CLAUDE.md",
  "app\Http\Controllers\Auth\AuthenticatedSessionController.php",
  "app\Http\Controllers\Auth\ConfirmablePasswordController.php",
  "app\Http\Controllers\Auth\EmailVerificationNotificationController.php",
  "app\Http\Controllers\Auth\EmailVerificationPromptController.php",
  "app\Http\Controllers\Auth\NewPasswordController.php",
  "app\Http\Controllers\Auth\PasswordController.php",
  "app\Http\Controllers\Auth\PasswordResetLinkController.php",
  "app\Http\Controllers\Auth\RegisteredUserController.php",
  "app\Http\Controllers\Auth\VerifyEmailController.php",
  "app\Http\Controllers\DashboardController.php",
  "app\Http\Controllers\JogadorController.php",
  "app\Http\Controllers\PartidaController.php",
  "app\Http\Controllers\ProfileController.php",
  "app\Http\Requests\Auth\LoginRequest.php",
  "app\Http\Requests\ProfileUpdateRequest.php",
  "app\Models\AudioFeedback.php",
  "app\Models\Categoria.php",
  "app\Models\Fase.php",
  "app\Models\HardwareSensor.php",
  "app\Models\Jogador.php",
  "app\Models\ModoJogo.php",
  "app\Models\Partida.php",
  "app\Models\Pergunta.php",
  "app\Models\RegiaoMapa.php",
  "app\Models\RespostaPartida.php",
  "app\Models\TentativaResposta.php",
  "app\View\Components\AppLayout.php",
  "app\View\Components\GuestLayout.php",
  "database\amerimapa_mysql.sql",
  "database\factories\UserFactory.php",
  "database\migrations\2026_11_17_180541_create_hardware_sensors_table.php",
  "database\migrations\2026_11_17_180542_create_jogadores_table.php",
  "database\migrations\2026_11_17_180543_create_modos_jogo_table.php",
  "database\migrations\2026_11_17_180545_create_fases_table.php",
  "database\migrations\2026_11_17_180546_create_audio_feedbacks_table.php",
  "database\migrations\2026_11_17_180547_create_regioes_mapa_table.php",
  "database\migrations\2026_11_17_180548_create_partidas_table.php",
  "database\migrations\2026_11_17_180550_create_categorias_table.php",
  "database\migrations\2026_11_17_180551_create_perguntas_table.php",
  "database\migrations\2026_11_17_180552_create_tentativas_respostas_table.php",
  "database\migrations\2026_11_17_181923_create_hardware_sensors_table.php",
  "database\migrations\2026_11_17_181924_create_jogadores_table.php",
  "database\migrations\2026_11_17_181926_create_modos_jogo_table.php",
  "database\migrations\2026_11_17_181927_create_fases_table.php",
  "database\migrations\2026_11_17_181928_create_audio_feedbacks_table.php",
  "database\migrations\2026_11_17_181929_create_regioes_mapa_table.php",
  "database\migrations\2026_11_17_181931_create_partidas_table.php",
  "database\migrations\2026_11_17_181932_create_categorias_table.php",
  "database\migrations\2026_11_17_181933_create_perguntas_table.php",
  "database\migrations\2026_11_17_181935_create_tentativas_respostas_table.php",
  "package-lock.json",
  "package.json",
  "postcss.config.js",
  "resources\css\app.css",
  "resources\js\app.js",
  "resources\views\Jogadores\index.blade.php",
  "resources\views\auth\confirm-password.blade.php",
  "resources\views\auth\forgot-password.blade.php",
  "resources\views\auth\reset-password.blade.php",
  "resources\views\auth\verify-email.blade.php",
  "resources\views\components\application-logo.blade.php",
  "resources\views\components\auth-session-status.blade.php",
  "resources\views\components\danger-button.blade.php",
  "resources\views\components\dropdown-link.blade.php",
  "resources\views\components\dropdown.blade.php",
  "resources\views\components\footer.blade.php",
  "resources\views\components\input-error.blade.php",
  "resources\views\components\input-label.blade.php",
  "resources\views\components\loader.blade.php",
  "resources\views\components\modal.blade.php",
  "resources\views\components\nav-link.blade.php",
  "resources\views\components\nav.blade.php",
  "resources\views\components\primary-button.blade.php",
  "resources\views\components\responsive-nav-link.blade.php",
  "resources\views\components\secondary-button.blade.php",
  "resources\views\components\text-input.blade.php",
  "resources\views\dashboard.blade.php",
  "resources\views\home.blade.php",
  "resources\views\layouts\app.blade.php",
  "resources\views\layouts\navigation.blade.php",
  "resources\views\profile\edit.blade.php",
  "resources\views\profile\partials\delete-user-form.blade.php",
  "resources\views\profile\partials\update-password-form.blade.php",
  "resources\views\profile\partials\update-profile-information-form.blade.php",
  "resources\views\welcome.blade.php",
  "routes\auth.php",
  "tailwind.config.js",
  "tests\Feature\Auth\AuthenticationTest.php",
  "tests\Feature\Auth\EmailVerificationTest.php",
  "tests\Feature\Auth\PasswordConfirmationTest.php",
  "tests\Feature\Auth\PasswordResetTest.php",
  "tests\Feature\Auth\PasswordUpdateTest.php",
  "tests\Feature\Auth\RegistrationTest.php",
  "tests\Feature\ExampleTest.php",
  "tests\Feature\ProfileTest.php",
  "tests\Unit\ExampleTest.php",
  "vite.config.js"
)
foreach ($f in $antigos) { if (Test-Path $f) { Remove-Item -Force $f; Write-Host "  removido: $f" } }
foreach ($d in @("app\Http\Controllers\Auth","app\Http\Requests\Auth","app\Http\Requests","app\View\Components","app\View","resources\views\Jogadores","resources\views\profile\partials","resources\views\profile","resources\css","resources\js","tests\Feature\Auth")) {
  if ((Test-Path $d) -and -not (Get-ChildItem $d -Recurse -File)) { Remove-Item -Recurse -Force $d }
}

Write-Host "2/5 Criando pastas que o Laravel precisa..." -ForegroundColor Cyan
foreach ($d in @("bootstrap\cache","storage\app\public","storage\app\private","storage\framework\cache\data","storage\framework\sessions","storage\framework\views","storage\framework\testing","storage\logs")) {
  New-Item -ItemType Directory -Force $d | Out-Null
}
Remove-Item -Force bootstrap\cache\*.php -ErrorAction SilentlyContinue

Write-Host "3/5 Tirando o atributo 'somente leitura' (o OneDrive costuma colocar e o PHP recusa)..." -ForegroundColor Cyan
attrib -R "bootstrap\*" /S /D | Out-Null
attrib -R "storage\*" /S /D | Out-Null
attrib -R "bootstrap" | Out-Null
attrib -R "storage" | Out-Null

Write-Host "4/5 Testando se o PHP consegue escrever..." -ForegroundColor Cyan
$ok = php -r "echo is_writable('bootstrap/cache') && is_writable('storage/framework/views') ? 'OK' : 'FALHOU';"
Write-Host "  resultado: $ok"
if ($ok -ne "OK") {
  Write-Host "  O OneDrive ainda bloqueia. Mova a pasta para fora dele, por exemplo C:\projetos\Amerimapa, e rode este script de novo." -ForegroundColor Yellow
  exit 1
}

Write-Host "5/5 Dependências e caches..." -ForegroundColor Cyan
composer install
if (-not (Test-Path ".env")) { Copy-Item ".env.example" ".env"; php artisan key:generate }
php artisan optimize:clear
Write-Host ""
Write-Host "Pronto! Agora rode:  php artisan serve   e abra http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "Login: sofia@amerimapa.com / amerimapa123  ·  mariana@amerimapa.com / amerimapa123"
