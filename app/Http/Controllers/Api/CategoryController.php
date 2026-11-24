<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends ApiController
{
    public function index()
    {
        return $this->ok(Category::query()->orderBy('name')->get()->map->toApi());
    }

    public function show(int $id)
    {
        return $this->ok($this->find($id)->toApi());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80|unique:categories,name',
            'description' => 'sometimes|nullable|string|max:255',
        ]);

        return $this->created(Category::query()->create($data)->toApi());
    }

    public function update(Request $request, int $id)
    {
        $category = $this->find($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($id)],
            'description' => 'sometimes|nullable|string|max:255',
        ]);
        $this->requireAny($data);
        $category->update($data);

        return $this->ok($category->refresh()->toApi());
    }

    public function destroy(int $id)
    {
        $category = $this->find($id);
        if ($category->questions()->exists()) {
            throw ApiException::conflict('Há perguntas nesta categoria.', 'CATEGORY_IN_USE');
        }
        $category->delete();

        return $this->noContent();
    }

    private function find(int $id): Category
    {
        return Category::query()->find($id) ?? throw ApiException::notFound('Categoria', $id);
    }
}
