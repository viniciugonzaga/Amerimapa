<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\MapRegion;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Regiões do mapa. RN05, RN19, RN22 */
class RegionController extends ApiController
{
    private const MAP = ['hardwarePin' => 'hardware_pin', 'parentId' => 'parent_id', 'isoCode' => 'iso_code', 'shortDescription' => 'short_description'];

    public function index(Request $request)
    {
        $f = $request->validate([
            'type' => ['sometimes', Rule::in(MapRegion::TYPES)],
            'parentId' => 'sometimes|integer',
            'hasPin' => 'sometimes|in:true,false,1,0',
            'search' => 'sometimes|string|max:100',
        ]);
        $q = MapRegion::query()->orderBy('type')->orderBy('name');
        if (isset($f['type'])) {
            $q->where('type', $f['type']);
        }
        if (isset($f['parentId'])) {
            $q->where('parent_id', $f['parentId']);
        }
        if (isset($f['hasPin'])) {
            in_array($f['hasPin'], ['true', '1'], true) ? $q->whereNotNull('hardware_pin') : $q->whereNull('hardware_pin');
        }
        if (isset($f['search'])) {
            $q->where('name', 'like', "%{$f['search']}%");
        }

        return $this->ok($q->get()->map->toApi());
    }

    public function show(int $id)
    {
        return $this->ok($this->find($id)->toApi());
    }

    public function byPin(string $pin)
    {
        $region = MapRegion::query()->where('hardware_pin', $pin)->first()
            ?? throw ApiException::notFound("Região com pino \"{$pin}\"");

        return $this->ok($region->toApi());
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(true));
        $region = MapRegion::query()->create($this->snake($data, self::MAP));

        return $this->created($region->toApi());
    }

    public function update(Request $request, int $id)
    {
        $region = $this->find($id);
        $data = $request->validate($this->rules(false, $id));
        $this->requireAny($data);
        if (($data['parentId'] ?? null) === $id) {
            throw ApiException::rule('INVALID_PARENT', 'Uma região não pode ser filha dela mesma.');
        }
        $region->update($this->snake($data, self::MAP));

        return $this->ok($region->refresh()->toApi());
    }

    public function destroy(int $id)
    {
        $region = $this->find($id);
        if ($region->children()->exists() || $region->questions()->exists()) {
            throw ApiException::conflict('A região tem sub-regiões ou perguntas ligadas a ela.', 'REGION_IN_USE');
        }
        $region->delete();

        return $this->noContent();
    }

    /** Modo Exploração pela tela: registra a região como conhecida e devolve as informações. */
    public function explore(Request $request, int $id, AchievementService $achievements)
    {
        $region = $this->find($id);
        $actor = $this->actor($request);
        $new = $actor->isPlayer() ? $achievements->markExplored($actor->player, $region) : [];

        return $this->ok(['region' => $region->toApi() + ['parentName' => $region->parent?->name], 'newAchievements' => $new]);
    }

    private function rules(bool $creating, ?int $id = null): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return [
            'name' => "{$req}|string|max:120",
            'type' => [$req, Rule::in(MapRegion::TYPES)],
            'hardwarePin' => ['sometimes', 'nullable', 'regex:/^[A-Za-z0-9:_-]{1,20}$/', Rule::unique('map_regions', 'hardware_pin')->ignore($id)],
            'parentId' => 'sometimes|nullable|integer|exists:map_regions,id',
            'isoCode' => ['sometimes', 'nullable', 'string', 'size:3', Rule::unique('map_regions', 'iso_code')->ignore($id)],
            'capital' => 'sometimes|nullable|string|max:120',
            'language' => 'sometimes|nullable|string|max:120',
            'shortDescription' => 'sometimes|nullable|string|max:255',
            'info' => 'sometimes|nullable|string|max:5000',
        ];
    }

    private function find(int $id): MapRegion
    {
        return MapRegion::query()->find($id) ?? throw ApiException::notFound('Região', $id);
    }
}
