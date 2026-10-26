<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Category;
use App\Models\Admin\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        return $this->page($request);
    }

    public function edit(Request $request, MenuItem $item)
    {
        return $this->page($request, $item);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        if ($request->hasFile('image')) {
            $data['image_url'] = $this->saveImage($request->file('image'));
        }

        $item = MenuItem::create($data);

        $this->audit('create', $item->id, null, $item->toArray());

        return redirect()
            ->route('admin.menu', ['category' => $item->category_id])
            ->with('ok', 'Menu item added.');
    }

    public function update(Request $request, MenuItem $item)
    {
        $data = $this->validated($request);
        $old  = $item->toArray();

        if ($data['name'] !== $item->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $item->id);
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($item->image_url);
            $data['image_url'] = $this->saveImage($request->file('image'));
        }

        $item->update($data);

        $this->audit('update', $item->id, $old, $item->fresh()->toArray());

        return redirect()
            ->route('admin.menu', ['category' => $item->category_id])
            ->with('ok', 'Menu item updated.');
    }

    public function toggle(MenuItem $item)
    {
        $item->update(['is_available' => ! $item->is_available]);

        $this->audit('toggle', $item->id, null, ['is_available' => $item->is_available]);

        return back()->with('ok', $item->is_available ? 'Marked as available.' : 'Marked as sold out.');
    }

    public function destroy(MenuItem $item)
    {
        // soft delete, old orders still keep working
        $item->update(['is_active' => 0]);

        $this->audit('delete', $item->id, null, null);

        return redirect()
            ->route('admin.menu', ['category' => $item->category_id])
            ->with('ok', 'Menu item removed.');
    }

    /*   helpers   */

    private function page(Request $request, ?MenuItem $editItem = null)
    {
        $categories = Category::withCount(['items' => fn ($q) => $q->where('is_active', 1)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $currentId = $request->integer('category') ?: ($editItem?->category_id);
        $search    = trim((string) $request->query('q', ''));

        $items = MenuItem::with('category')
            ->where('is_active', 1)
            ->when($currentId, fn ($q) => $q->where('category_id', $currentId))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $editCat = $request->integer('edit_cat')
            ? Category::find($request->integer('edit_cat'))
            : null;

        return view('admin.menu', [
            'categories' => $categories,
            'items'      => $items,
            'currentId'  => $currentId,
            'search'     => $search,
            'editItem'   => $editItem,
            'editCat'    => $editCat,
        ]);
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'category_id'   => ['required', 'exists:categories,id'],
            'name'          => ['required', 'string', 'max:150'],
            'name_chinese'  => ['nullable', 'string', 'max:150'],
            'description'   => ['nullable', 'string', 'max:2000'],
            'base_price'    => ['required', 'numeric', 'min:0', 'max:99999999'],
            'spice_level'   => ['required', 'integer', 'between:0,3'],
            'prep_time_min' => ['nullable', 'integer', 'min:1', 'max:240'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
            'image'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        unset($v['image']);

        $v['prep_time_min'] = $v['prep_time_min'] ?? 15;
        $v['sort_order']    = $v['sort_order'] ?? 0;
        $v['is_veg']        = $request->boolean('is_veg');
        $v['is_featured']   = $request->boolean('is_featured');
        $v['is_popular']    = $request->boolean('is_popular');
        $v['is_available']  = $request->boolean('is_available');

        return $v;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i    = 2;

        while (
            MenuItem::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function saveImage(UploadedFile $file): string
    {
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('uploads/menu'), $name);

        return 'uploads/menu/' . $name;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && is_file(public_path($path))) {
            @unlink(public_path($path));
        }
    }

    private function audit(string $action, int $entityId, ?array $old, ?array $new): void
    {
        DB::table('audit_logs')->insert([
            'admin_id'   => Auth::guard('admin')->id(),
            'action'     => $action,
            'entity'     => 'menu_item',
            'entity_id'  => $entityId,
            'old_data'   => $old ? json_encode($old) : null,
            'new_data'   => $new ? json_encode($new) : null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}