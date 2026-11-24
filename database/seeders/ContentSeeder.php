<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Activity;
use App\Models\Category;
use App\Models\GameMode;
use App\Models\MapRegion;
use App\Models\Phase;
use App\Models\Question;
use Illuminate\Database\Seeder;

/**
 * Conteúdo pedagógico: 4 regiões e 35 países das Américas (com capitais e pinos),
 * categorias, 4 fases, 5 modos de jogo, ~84 perguntas, conquistas e atividades.
 *
 * A ordem dos países é a mesma do seed da API Node, então os pinos do hardware
 * (5A:00 ... 5C:10) continuam iguais e o firmware do ESP32 não muda.
 * Idempotente: rodar duas vezes não duplica nada.
 */
class ContentSeeder extends Seeder
{
    public const REGIONS = [
        'América do Norte' => 'Canadá, Estados Unidos e México.',
        'América Central' => 'O istmo que liga a América do Norte à do Sul.',
        'Caribe' => 'Países-ilha do Mar do Caribe.',
        'América do Sul' => 'O subcontinente ao sul das Américas.',
    ];

    /** [iso, nome, região, capital, idioma, dificuldade, curiosidade] */
    public const COUNTRIES = [
        ['CAN', 'Canadá', 'América do Norte', 'Ottawa', 'inglês e francês', 'EASY', 'É o maior país das Américas em área.'],
        ['USA', 'Estados Unidos', 'América do Norte', 'Washington, D.C.', 'inglês', 'EASY', null],
        ['MEX', 'México', 'América do Norte', 'Cidade do México', 'espanhol', 'EASY', null],
        ['BLZ', 'Belize', 'América Central', 'Belmopan', 'inglês', 'MEDIUM', 'Único país da América Central cuja língua oficial é o inglês.'],
        ['GTM', 'Guatemala', 'América Central', 'Cidade da Guatemala', 'espanhol', 'MEDIUM', null],
        ['HND', 'Honduras', 'América Central', 'Tegucigalpa', 'espanhol', 'MEDIUM', null],
        ['SLV', 'El Salvador', 'América Central', 'San Salvador', 'espanhol', 'MEDIUM', null],
        ['NIC', 'Nicarágua', 'América Central', 'Manágua', 'espanhol', 'MEDIUM', null],
        ['CRI', 'Costa Rica', 'América Central', 'San José', 'espanhol', 'MEDIUM', null],
        ['PAN', 'Panamá', 'América Central', 'Cidade do Panamá', 'espanhol', 'MEDIUM', 'Tem o Canal do Panamá, que liga os oceanos Atlântico e Pacífico.'],
        ['CUB', 'Cuba', 'Caribe', 'Havana', 'espanhol', 'EASY', 'É a maior ilha do Caribe.'],
        ['JAM', 'Jamaica', 'Caribe', 'Kingston', 'inglês', 'MEDIUM', null],
        ['HTI', 'Haiti', 'Caribe', 'Porto Príncipe', 'francês e crioulo haitiano', 'MEDIUM', 'Divide a ilha de Hispaniola com a República Dominicana.'],
        ['DOM', 'República Dominicana', 'Caribe', 'Santo Domingo', 'espanhol', 'MEDIUM', 'Divide a ilha de Hispaniola com o Haiti.'],
        ['BHS', 'Bahamas', 'Caribe', 'Nassau', 'inglês', 'MEDIUM', null],
        ['TTO', 'Trinidad e Tobago', 'Caribe', 'Porto de Espanha', 'inglês', 'HARD', null],
        ['BRB', 'Barbados', 'Caribe', 'Bridgetown', 'inglês', 'HARD', null],
        ['LCA', 'Santa Lúcia', 'Caribe', 'Castries', 'inglês', 'HARD', null],
        ['GRD', 'Granada', 'Caribe', 'Saint George\'s', 'inglês', 'HARD', null],
        ['VCT', 'São Vicente e Granadinas', 'Caribe', 'Kingstown', 'inglês', 'HARD', null],
        ['ATG', 'Antígua e Barbuda', 'Caribe', 'Saint John\'s', 'inglês', 'HARD', null],
        ['DMA', 'Dominica', 'Caribe', 'Roseau', 'inglês', 'HARD', null],
        ['KNA', 'São Cristóvão e Névis', 'Caribe', 'Basseterre', 'inglês', 'HARD', 'É o menor país soberano das Américas.'],
        ['BRA', 'Brasil', 'América do Sul', 'Brasília', 'português', 'EASY', 'É o maior país da América do Sul.'],
        ['ARG', 'Argentina', 'América do Sul', 'Buenos Aires', 'espanhol', 'EASY', null],
        ['CHL', 'Chile', 'América do Sul', 'Santiago', 'espanhol', 'EASY', 'É um país longo e estreito, onde fica o Deserto do Atacama.'],
        ['URY', 'Uruguai', 'América do Sul', 'Montevidéu', 'espanhol', 'MEDIUM', null],
        ['PRY', 'Paraguai', 'América do Sul', 'Assunção', 'espanhol e guarani', 'MEDIUM', null],
        ['BOL', 'Bolívia', 'América do Sul', 'Sucre', 'espanhol e 36 línguas indígenas', 'MEDIUM', 'Sucre é a capital constitucional; La Paz é a sede do governo.'],
        ['PER', 'Peru', 'América do Sul', 'Lima', 'espanhol, quíchua e aimará', 'EASY', null],
        ['ECU', 'Equador', 'América do Sul', 'Quito', 'espanhol', 'MEDIUM', 'É dono das Ilhas Galápagos.'],
        ['COL', 'Colômbia', 'América do Sul', 'Bogotá', 'espanhol', 'EASY', null],
        ['VEN', 'Venezuela', 'América do Sul', 'Caracas', 'espanhol', 'EASY', null],
        ['GUY', 'Guiana', 'América do Sul', 'Georgetown', 'inglês', 'MEDIUM', null],
        ['SUR', 'Suriname', 'América do Sul', 'Paramaribo', 'holandês', 'MEDIUM', 'Único país da América do Sul cuja língua oficial é o holandês.'],
    ];

    public const CHALLENGES = [
        ['Encontre o maior país das Américas em área', 'Canadá', 'MEDIUM'],
        ['Encontre o país cortado por um canal que liga os oceanos Atlântico e Pacífico', 'Panamá', 'HARD'],
        ['Encontre o país onde fica o Deserto do Atacama', 'Chile', 'HARD'],
        ['Encontre o único país da América do Sul que fala holandês', 'Suriname', 'HARD'],
        ['Encontre o único país da América Central que fala inglês', 'Belize', 'HARD'],
        ['Encontre o país que divide a ilha de Hispaniola com o Haiti', 'República Dominicana', 'HARD'],
        ['Encontre o país dono das Ilhas Galápagos', 'Equador', 'HARD'],
        ['Encontre o maior país da América do Sul', 'Brasil', 'MEDIUM'],
        ['Encontre o país cuja sede do governo, La Paz, é uma das capitais mais altas do mundo', 'Bolívia', 'HARD'],
        ['Encontre o menor país soberano das Américas', 'São Cristóvão e Névis', 'HARD'],
    ];

    private int $audioTrack = 100;

    public function run(): void
    {
        $categories = $this->categories();
        $phases = $this->phases();
        $this->modes();
        $regions = $this->regions();
        $this->questions($regions, $phases, $categories);
        $this->achievements();
        $this->activities($regions, $phases);
    }

    /** "<endereço MPR121>:<eletrodo>" — índice 13 -> "5B:01" (3 placas cobrem 35 países). */
    public static function pinForIndex(int $index): string
    {
        $boards = ['5A', '5B', '5C', '5D'];

        return $boards[intdiv($index, 12)].':'.str_pad((string) ($index % 12), 2, '0', STR_PAD_LEFT);
    }

    private function categories(): array
    {
        $ids = [];
        foreach ([
            'Regiões' => 'Grandes regiões das Américas.',
            'Países' => 'Localizar países no mapa.',
            'Capitais' => 'Capitais dos países.',
            'Desafios' => 'Curiosidades, cultura e perguntas mais difíceis.',
        ] as $name => $description) {
            $ids[$name] = Category::query()->updateOrCreate(['name' => $name], ['description' => $description])->id;
        }

        return $ids;
    }

    private function phases(): array
    {
        $ids = [];
        foreach ([
            [1, 'Regiões', 'América do Norte, Central, Caribe e América do Sul.', 0],
            [2, 'Países', 'Encontre cada país no mapa.', 30],
            [3, 'Informações', 'Capitais, idiomas e outras curiosidades.', 50],
            [4, 'Desafios', 'Perguntas mais difíceis e complexas.', 80],
        ] as [$number, $title, $description, $min]) {
            $ids[$number] = Phase::query()->updateOrCreate(['number' => $number], [
                'title' => $title, 'description' => $description, 'min_unlock_score' => $min,
            ])->id;
        }

        return $ids;
    }

    private function modes(): void
    {
        $modes = [
            ['EXPLORATION', 'Exploração', 'Conheça o mapa sem pressão: toque em qualquer área para aprender sobre ela.', false, null, null, null, false, false, false],
            ['TRAINING', 'Treino', 'Pratique com tentativas ilimitadas e sem pontuação competitiva.', false, null, null, null, false, false, false],
            ['QUIZ', 'Quiz', 'Perguntas respondidas no mapa, duas tentativas por pergunta.', false, null, 2, 5, true, false, true],
            ['CHALLENGE', 'Desafio', 'Mais difícil: uma tentativa, 20 segundos por pergunta, bônus de velocidade e sequência.', true, 20, 1, 10, true, true, true],
            ['CLASSROOM', 'Sala de Aula', 'Sessão conduzida pelo professor com a turma: 30 segundos por pergunta.', true, 30, 1, 10, true, false, false],
        ];
        foreach ($modes as [$code, $name, $desc, $timed, $limit, $attempts, $perMatch, $scored, $speed, $streak]) {
            GameMode::query()->updateOrCreate(['code' => $code], [
                'name' => $name, 'description' => $desc, 'is_timed' => $timed, 'time_limit_seconds' => $limit,
                'max_attempts' => $attempts, 'questions_per_match' => $perMatch, 'is_scored' => $scored,
                'speed_bonus' => $speed, 'streak_bonus' => $streak,
            ]);
        }
    }

    private function regions(): array
    {
        $ids = [];
        foreach (self::REGIONS as $name => $description) {
            $ids[$name] = MapRegion::query()->updateOrCreate(
                ['name' => $name, 'parent_id' => null],
                ['type' => 'REGION', 'short_description' => $description],
            )->id;
        }
        foreach (self::COUNTRIES as $i => [$iso, $name, $region, $capital, $language, , $info]) {
            $ids[$name] = MapRegion::query()->updateOrCreate(['iso_code' => $iso], [
                'name' => $name,
                'type' => 'COUNTRY',
                'parent_id' => $ids[$region],
                'hardware_pin' => self::pinForIndex($i),
                'capital' => $capital,
                'language' => $language,
                'short_description' => 'Capital: '.$capital.'. Idioma oficial: '.$language.'.',
                'info' => $info,
            ])->id;
        }

        return $ids;
    }

    private function question(string $prompt, string $difficulty, int $regionId, int $phaseId, int $categoryId): void
    {
        $q = Question::query()->firstOrNew(['prompt' => $prompt, 'phase_id' => $phaseId]);
        $q->fill(['difficulty' => $difficulty, 'answer_region_id' => $regionId, 'category_id' => $categoryId]);
        if (! $q->exists) {
            $q->audio_path = sprintf('mp3/%04d.mp3', $this->audioTrack);
        }
        $this->audioTrack++;
        $q->save();
    }

    private function questions(array $regions, array $phases, array $categories): void
    {
        $article = fn (string $name) => match (true) {
            in_array($name, ['Estados Unidos'], true) => 'os',
            in_array($name, ['Bahamas'], true) => 'as',
            in_array($name, ['Argentina', 'Bolívia', 'Colômbia', 'Venezuela', 'Guiana', 'Guatemala', 'Nicarágua', 'Costa Rica', 'Jamaica', 'República Dominicana', 'Granada', 'Dominica', 'Santa Lúcia', 'Antígua e Barbuda', 'América do Norte', 'América Central', 'América do Sul'], true) => 'a',
            in_array($name, ['Belize', 'Cuba', 'Honduras', 'Barbados', 'Trinidad e Tobago', 'El Salvador', 'São Cristóvão e Névis', 'São Vicente e Granadinas'], true) => '',
            default => 'o',
        };
        $find = fn (string $name) => trim('Encontre '.$article($name).' '.$name);

        foreach (array_keys(self::REGIONS) as $name) {
            $this->question($find($name), 'EASY', $regions[$name], $phases[1], $categories['Regiões']);
        }
        foreach (self::COUNTRIES as [, $name, , , , $difficulty]) {
            $this->question($find($name), $difficulty, $regions[$name], $phases[2], $categories['Países']);
        }
        foreach (self::COUNTRIES as [, $name, , $capital, , $difficulty]) {
            $harder = $difficulty === 'EASY' ? 'MEDIUM' : 'HARD';
            $this->question("Toque no país cuja capital é {$capital}", $harder, $regions[$name], $phases[3], $categories['Capitais']);
        }
        foreach (self::CHALLENGES as [$prompt, $answer, $difficulty]) {
            $this->question($prompt, $difficulty, $regions[$answer], $phases[4], $categories['Desafios']);
        }
    }

    private function achievements(): void
    {
        $list = [
            ['EXPLORADOR', 'Explorador', 'Complete 3 atividades', 'compass', 'orange', 'MATCHES_COMPLETED', 3],
            ['GEOGRAFO', 'Geógrafo', 'Alcance 1.000 pontos', 'globe', 'blue', 'TOTAL_POINTS', 1000],
            ['CAPITAO', 'Capitão', 'Acerte 10 perguntas seguidas', 'crown', 'green', 'BEST_STREAK', 10],
            ['CURIOSO', 'Curioso', 'Explore 5 países', 'search', 'yellow', 'COUNTRIES_EXPLORED', 5],
            ['DESBRAVADOR', 'Desbravador', 'Conheça as 4 regiões da América', 'book', 'purple', 'REGIONS_EXPLORED', 4],
            ['MESTRE_MAPAS', 'Mestre dos mapas', 'Conclua as 4 fases do jogo', 'pin', 'red', 'PHASES_COMPLETED', 4],
        ];
        foreach ($list as $i => [$code, $name, $desc, $icon, $color, $rule, $target]) {
            Achievement::query()->updateOrCreate(['code' => $code], [
                'name' => $name, 'description' => $desc, 'icon' => $icon, 'color' => $color,
                'rule' => $rule, 'target' => $target, 'sort_order' => $i + 1,
            ]);
        }
    }

    /** Atividades gerais (sem turma): visíveis para todos os alunos e visitantes. */
    private function activities(array $regions, array $phases): void
    {
        $quiz = GameMode::byCode('QUIZ')->id;
        $byRegion = fn (string $region, array $phaseNumbers) => Question::query()
            ->whereIn('phase_id', array_map(fn ($n) => $phases[$n], $phaseNumbers))
            ->whereIn('answer_region_id', MapRegion::query()->find($regions[$region])->subtreeIds())
            ->pluck('id')->all();

        $list = [
            ['Regiões da América', 'Encontre as quatro grandes regiões no mapa tátil.', 1, null, 'globe', 3, 50,
                Question::query()->where('phase_id', $phases[1])->pluck('id')->all()],
            ['América do Sul: países e capitais', 'Encontre os países no mapa tátil.', 2, 'América do Sul', 'map', 5, 100,
                $byRegion('América do Sul', [2, 3])],
            ['América do Norte e Central', 'Localize os países do norte e do istmo.', 2, null, 'compass', 5, 100,
                array_merge($byRegion('América do Norte', [2]), $byRegion('América Central', [2]))],
            ['Ilhas do Caribe', 'As ilhas são pequenas: toque com atenção!', 2, 'Caribe', 'pin', 6, 120,
                $byRegion('Caribe', [2])],
            ['Capitais das Américas', 'Qual país tem esta capital?', 3, null, 'flag', 8, 150,
                Question::query()->where('phase_id', $phases[3])->pluck('id')->all()],
            ['Grandes desafios', 'Curiosidades para quem já é craque.', 4, null, 'star', 8, 200,
                Question::query()->where('phase_id', $phases[4])->pluck('id')->all()],
        ];
        foreach ($list as [$title, $desc, $phase, $focus, $icon, $minutes, $reward, $questionIds]) {
            $activity = Activity::query()->updateOrCreate(['title' => $title, 'school_class_id' => null], [
                'description' => $desc, 'phase_id' => $phases[$phase], 'mode_id' => $quiz,
                'focus_region_id' => $focus ? $regions[$focus] : null, 'icon' => $icon,
                'estimated_minutes' => $minutes, 'reward_points' => $reward, 'is_active' => true,
            ]);
            $activity->questions()->sync($questionIds);
        }
    }
}
