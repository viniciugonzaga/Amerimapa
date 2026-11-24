<?php

namespace App\Http\Controllers\Web\Teacher;

use App\Http\Controllers\Controller;
use App\Models\MapRegion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Regiões do mapa e o pino de hardware de cada uma (RN19). */
class RegionController extends Controller
{
    public function index()
    {
        return view('teacher.regions.index', [
            'groups' => MapRegion::query()->where('type', 'REGION')->with(['children' => fn ($q) => $q->orderBy('hardware_pin')])->orderBy('id')->get(),
            'orphans' => MapRegion::query()->where('type', '!=', 'REGION')->whereNull('parent_id')->get(),
            'parents' => MapRegion::query()->whereIn('type', ['REGION', 'COUNTRY'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        MapRegion::query()->create($this->validated($request));

        return back()->with('status', 'Região cadastrada!');
    }

    public function update(Request $request, MapRegion $region)
    {
        $region->update($this->validated($request, $region->id));

        return back()->with('status', "Região {$region->name} atualizada!");
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(MapRegion::TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:map_regions,id', Rule::notIn([$id])],
            'hardware_pin' => ['nullable', 'regex:/^[A-Za-z0-9:_-]{1,20}$/', Rule::unique('map_regions', 'hardware_pin')->ignore($id)],
            'iso_code' => ['nullable', 'string', 'size:3', Rule::unique('map_regions', 'iso_code')->ignore($id)],
            'capital' => 'nullable|string|max:120',
            'language' => 'nullable|string|max:120',
            'info' => 'nullable|string|max:5000',
        ], [], ['name' => 'nome', 'hardware_pin' => 'pino', 'iso_code' => 'código ISO']);
    }
}
