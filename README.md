# 🌎 AmeriMapa

Jogo educacional de geografia em que um **mapa tátil das Américas** (ESP32 + sensores capacitivos MPR121 + DFPlayer) é o controle do jogo.
Este repositório junta tudo em **um único projeto Laravel**:

- a **API REST** do repositório do Matheus (`amerimapa-api`, Node/Express/Prisma) portada para Laravel, com **o mesmo contrato** (`/api/v1`, mesmos endpoints, mesmos códigos de erro, mesmos pinos do hardware) — o firmware do ESP32 não muda;
- a base Laravel do repositório do Vinícius, agora com **todas as telas** (aluno, professor, login, visitante);
- o **banco** do documento "Regras do jogo" (modelo físico), estendido para turmas, atividades, conquistas, acessibilidade e o dispositivo IoT.

```
MAPA → SENSORES (MPR121) → ESP32 → API Laravel (/api/v1) → MySQL → FEEDBACK (tela · áudio DFPlayer · LEDs)
```

Equipe: Sofia de Oliveira · Vinícius Gonzaga · Mariana Rigueiro · Matheus Mendonça · Kevin Silva

---

## 1. Requisitos

| Ferramenta | Versão |
|---|---|
| PHP | 8.3+ (extensões: `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `intl`) |
| Composer | 2.x |
| MySQL | 8.x (ou MariaDB 10.6+) — para testes rápidos também funciona com SQLite |

**Não precisa de Node/npm**: o front é Blade + CSS + JavaScript puros em `public/`.

## 2. Comandos para rodar (Windows + XAMPP/phpMyAdmin)

O `.env` já vem pronto para o MySQL do XAMPP: banco `amerimapa`, usuário `root`, senha vazia, `127.0.0.1:3306`.
Se o seu MySQL tiver senha, preencha `DB_PASSWORD` no `.env`.

1. No XAMPP, ligue **Apache** e **MySQL**.
2. No phpMyAdmin (http://localhost/phpmyadmin) crie o banco **`amerimapa`** com agrupamento **`utf8mb4_unicode_ci`**.
3. Escolha **um** jeito de criar as tabelas:

   **Opção A — pelo Laravel (recomendado):**
   ```powershell
   composer install
   php artisan migrate --seed
   ```

   **Opção B — importando no phpMyAdmin:** selecione o banco `amerimapa` → aba **Importar** →
   arquivo `database/amerimapa_completo.sql` → Executar. Já vem com estrutura + todos os dados de demonstração.
   Depois rode só `composer install`.

4. Suba o servidor:
   ```powershell
   php artisan serve
   ```
   Abra **http://127.0.0.1:8000**.

Para recomeçar o banco do zero a qualquer momento: `php artisan migrate:fresh --seed`.

> Se trocar de máquina, gere uma nova chave com `php artisan key:generate`. Não suba o `.env` para o GitHub (ele já está no `.gitignore`).

### Contas de demonstração (senha `amerimapa123`)

| Perfil | E-mail |
|---|---|
| Aluna (Sofia Almeida, Turma 2) | `sofia@amerimapa.com` |
| Professora (Prof. Mariana, 5 turmas) | `mariana@amerimapa.com` |
| Administrador | `admin@amerimapa.com` (definido em `SEED_ADMIN_*`) |
| 47 alunos de exemplo | `aluno1@amerimapa.com` … `aluno47@amerimapa.com` |

Também dá para **jogar como visitante** (RN01 — progresso não é salvo).

### Sem o mapa físico? Use o simulador

Em outro terminal, com uma atividade aberta no navegador:

```bash
php artisan amerimapa:mapa --auto          # "toca" sozinho no país certo (~75% de acerto)
php artisan amerimapa:mapa --auto --acerto=50
php artisan amerimapa:mapa                 # interativo: digite um pino (5B:11), ISO (BRA) ou nome (Brasil)
```

O simulador usa exatamente o mesmo serviço dos endpoints `POST /hardware/heartbeat` e `POST /hardware/touches`.

### Testes

```bash
php vendor/bin/phpunit
```

12 testes (pontuação, tentativas, fase bloqueada, fluxo completo da partida, toque do ESP32, mapa offline, permissões, renderização de todas as telas).

---

## 3. Telas

**Entrada:** login/landing (a aba Aluno/Professor é conferida no login, lembrar-me, criar conta, jogar como visitante), cadastro, esqueci a senha (o pedido aparece nas notificações do professor da turma), termos, privacidade, suporte, documentação da API (`/docs/api`).

**Aluno** (navegação inferior: Início · Atividade · Progresso · Conquistas · Perfil):

| Tela | Rota | O que tem |
|---|---|---|
| Início | `/inicio` | saudação, próxima missão, progresso geral, conquistas, últimas partidas |
| Atividades | `/atividades` | missões (bloqueio por fase — RN11), modos de jogo por fase |
| Jogar | `/jogar` | pergunta, barra de perguntas, status do mapa físico, mapa SVG clicável com zoom na região, lista acessível, ouvir instruções, cronômetro (Desafio), feedback colorido + voz, pontos |
| Exploração | `/explorar` | toque em qualquer país (tela ou mapa tátil) e veja capital, idioma e curiosidade |
| Resultado | `/partidas/{id}/resultado` | pontos, acertos, sequência, resposta por pergunta, novas conquistas |
| Progresso | `/progresso` | anel de progresso, atividades concluídas, evolução (7/15/30 dias), "Vamos revisar?", fases |
| Conquistas | `/conquistas` | 6 emblemas com progresso + ranking da turma |
| Perfil e acessibilidade | `/perfil` | cartão do perfil com "Editar perfil" (avatar ilustrado, apelido, e-mail, senha), fonte maior, alto contraste, áudio, reduzir animações, tutorial em áudio |

**Professor** (menu lateral): Visão geral (KPIs, gráfico por turma com período 7/15/30 dias, notificações no sino, atividades recentes, turmas, acesso rápido, dispositivo IoT, ranking), Turmas e alunos (CRUD + cadastro de alunos e troca de senha), Atividades do mapa (CRUD com seletor de perguntas + **Modo Sala de Aula**), Perguntas (CRUD), Regiões e pinos (trocar o pino de cada país), Desempenho e ranking (onde a turma mais erra), Relatórios (exportação CSV para Excel), Dispositivo IoT (status em tempo real e últimos eventos), busca. O **administrador** tem também a tela **Professores** (cadastrar, editar, trocar senha e remover professores).

## 4. Regras de negócio → código

| Regra | Onde |
|---|---|
| RN01 visitante × cadastrado | `AuthController::guest`, `Actor`, partidas com `guest_id` |
| RN02 modo + fase | `MatchService::start` |
| RN03 / RN10 uma pergunta ativa | `matches.active_question_id` + `QUESTION_STILL_ACTIVE` |
| RN04 / RN05 / RN19 resposta pelo mapa, pino único | `HardwareService::touch`, `map_regions.hardware_pin` (unique) |
| RN06 / RN07 / RN17 acerto, erro, feedback | `AnswerFeedback` (mensagem + faixa do DFPlayer + cor do LED) |
| RN08 pontuação 10/20/30 + bônus | `ScoringPolicy` |
| RN09 tentativas | `game_modes.max_attempts` (NULL = ilimitado no Treino) |
| RN11 progressão | `ProgressionPolicy` + `phases.min_unlock_score` |
| RN12 / RN13 cronômetro e tempo esgotado | `MatchService::resolveExpiredQuestion` (TIMEOUT) |
| RN14 progresso salvo | a cada resposta (`match_answers`) |
| RN15 cadastro de perguntas | `questions` + validação |
| RN16 sem repetição imediata | `QuestionSelector` |
| RN18 acessibilidade | `player_preferences`, voz (Web Speech), alto contraste, fonte, lista acessível no jogo |
| RN20 falha de comunicação | heartbeat + `devices.last_seen_at`, eventos `device.offline`, bind recusado com `DEVICE_OFFLINE` |
| RN21 ranking só com partidas concluídas | `LeaderboardController`, `StatsService::ranking` |
| RN22 conteúdo expansível | regiões hierárquicas (`parent_id`, tipo `STATE`), atividades montadas pelo professor |

## 5. Banco de dados

Migrations em `database/migrations`. Para o phpMyAdmin há o `database/amerimapa_completo.sql` (estrutura + dados de demonstração).

| Tabela | Do documento | Observação |
|---|---|---|
| `players` | JOGADORES | + apelido, avatar, turma, papel (STUDENT/TEACHER/ADMIN) |
| `map_regions` | REGIOES_MAPA | + hierarquia, ISO (liga ao desenho SVG), capital, idioma |
| `categories`, `phases`, `questions`, `game_modes` | CATEGORIA, FASES, PERGUNTAS, MODOS_JOGO | iguais à API do Matheus |
| `matches`, `match_answers` | PARTIDAS, RESPOSTAS_PARTIDAS (TENTATIVA_RESPOSTA) | uma linha por tentativa, tempo em ms |
| `school_classes`, `activities`, `activity_question` | — | turmas e atividades do professor |
| `achievements`, `player_achievements`, `explored_regions` | — | conquistas |
| `player_preferences` | — | acessibilidade |
| `devices`, `game_events` | HARDWARE_SENSOR | estado dos mapas e eventos em tempo real |
| `access_tokens` | — | tokens Bearer da API (só o hash) |

Os nomes de tabelas/colunas seguem o `schema.prisma` do Matheus (inglês, snake_case); o JSON da API usa camelCase, como na versão Node.

## 6. API (`/api/v1`)

Lista completa em **`/docs/api`**. Resumo do fluxo de jogo:

```
POST /auth/login | /auth/guest          → token
POST /matches { activityId }            → partida
POST /hardware/bind-match { deviceId, matchId }   (opcional: respostas pelo mapa físico)
POST /matches/{id}/next-question        → pergunta ativa
POST /matches/{id}/answers { regionId } → feedback   (ou o toque do ESP32)
GET  /hardware/events/poll?matchId=..&after=..     → eventos em tempo real
```

### Contrato do ESP32 (igual ao da API Node)

Headers: `X-Device-Key: <DEVICE_API_KEY do .env>` e `X-Device-Id: map-01`.

| Chamada | Corpo | Resposta útil |
|---|---|---|
| `POST /api/v1/hardware/heartbeat` (a cada ~5 s) | `{"firmwareVersion":"1.0.0","sensorsOk":true,"batteryLevel":80}` | `boundMatchId` |
| `POST /api/v1/hardware/touches` (a cada toque) | `{"pin":"5B:11"}` | `data.device.led` (`GREEN`/`RED`/`YELLOW`), `data.device.audioTrack` (faixa do DFPlayer) |

Pinos: `5A:00…5A:02` América do Norte · `5A:03…5A:09` América Central · `5A:10…5B:10` Caribe · `5B:11…5C:10` América do Sul (veja/edite em **Professor → Regiões e pinos**). O sketch Arduino de exemplo continua válido: `docs/ESP32_INTEGRATION.md` do repositório `amerimapa-api`.

## 7. Estrutura

```
app/
  Domain/            regras puras: ScoringPolicy, ProgressionPolicy, QuestionSelector, AnswerFeedback, Actor
  Services/          MatchService (motor do jogo), HardwareService, ProgressService, AchievementService, StatsService, TokenService, EventBus
  Http/Controllers/Api/   API /api/v1 (contrato do Matheus)
  Http/Controllers/Web/   telas (aluno, jogo, perfil) e Web/Teacher (painel do professor)
  Http/Middleware/   AuthenticateApi, RequireStaff, AuthenticateDevice, EnsureRole, EnsurePlayerOrGuest
  Console/Commands/SimulateMap.php   simulador do mapa (php artisan amerimapa:mapa)
  Models/            Eloquent (GameMatch = tabela matches; "match" é palavra reservada no PHP)
database/migrations, database/seeders (ContentSeeder = conteúdo, DemoSeeder = demonstração), database/amerimapa_completo.sql
resources/views/     layouts, components, partials (mapa SVG das Américas), auth, student, teacher, pages
public/css/amerimapa.css, public/js/amerimapa.js   front sem build (cliente da API, mapa, gráficos, voz)
routes/api.php, routes/web.php
tests/Unit, tests/Feature
```

### Imagens

Ficam em `public/images/`: `Amerimapa.png` (logo usado no topo, menu lateral e rodapé), `Amerimapa-icone.png` (ícone da aba), as ilustrações `ilustracao-*.png` e os avatares `avatars/menina.png`, `menino.png`, `professora.png`.
Elas foram recortadas das telas do protótipo; para trocar por versões em alta resolução basta substituir os arquivos **com o mesmo nome**.

O desenho do mapa (`resources/views/partials/americas-map.blade.php`) foi gerado a partir dos contornos do projeto *mledoze/countries* (licença ODbL).

## 8. Produção

- Troque `DEVICE_API_KEY`, `SEED_ADMIN_PASSWORD` e use `APP_ENV=production`, `APP_DEBUG=false`.
- Use Nginx/Apache + PHP-FPM (o polling do mapa precisa de mais de um worker).
- `php artisan migrate --force && php artisan db:seed --class=ContentSeeder --force` (sem dados de demonstração).
- `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
