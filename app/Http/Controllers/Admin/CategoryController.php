<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $category = Category::create([
            'name'       => $data['name'],
            'slug'       => $this->uniqueSlug($data['name']),
            'sort_order' => (int) Category::max('sort_order') + 1,
            'is_active'  => 1,
        ]);

        return redirect()
            ->route('admin.menu', ['category' => $category->id])
            ->with('ok', 'Category added.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $category->id),
        ]);

        return redirect()
            ->route('admin.menu', ['category' => $category->id])
            ->with('ok', 'Category renamed.');
    }

    public function destroy(Category $category)
    {
        if ($category->items()->exists()) {
            return back()->with('error', 'This category still has menu items. Move or remove them first.');
        }

        $category->delete();

        return redirect()
            ->route('admin.menu')
            ->with('ok', 'Category deleted.');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i    = 2;

        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}