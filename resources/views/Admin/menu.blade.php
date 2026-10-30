@extends('admin.layouts.app')

@section('title', 'Menu items')

@section('content')

<style>
:root {
  --red: #c8000a;
  --red-dark: #8f0007;
  --maroon: #2b0b0d;
  --gold: #f6c453;
  --bg: #f4f4f6;
  --surface: #ffffff;
  --text: #1d1b1c;
  --muted: #6d6869;
  --line: #e4e2e3;
  --ok: #1f7a4d;
  --warn: #a86400;
  --info: #1d5fa8;
}

/*   Flash   */
.flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-weight: 500; }
.flash.ok { background: #d9f1e4; color: var(--ok); }
.flash.error { background: #fbdcdc; color: #8a1010; }

/*   Layout   */
.menu-layout { display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start; }

.panel { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 20px; }
.panel-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px; }
.panel-head h2 { font-family: 'Playfair Display', Georgia, serif; font-size: 20px; margin: 0; }
.panel-head a { font-size: 14px; color: var(--red); }
.form-panel { margin-bottom: 20px; border-color: var(--red); }

/*   Categories   */
.cat-list { list-style: none; margin: 0 0 18px; padding: 0; }
.cat-list li { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 8px; padding: 9px 10px; border-radius: 8px; }
.cat-list li > a:first-child { flex: 1; text-decoration: none; color: var(--text); }
.cat-list li.current { background: #fbe9ea; }
.cat-list li.current > a:first-child { font-weight: 700; color: var(--red); }
.cat-list .count { font-size: 13px; color: var(--muted); }
.cat-actions { flex-basis: 100%; display: flex; gap: 14px; font-size: 13px; }
.cat-actions a { color: var(--red); }
.cat-actions form, .row-actions form { margin: 0; display: inline; }
.cat-form label { display: block; font-weight: 500; margin-bottom: 6px; }
.inline { display: flex; gap: 8px; }
.inline input { flex: 1; min-width: 0; }
.cancel-link { display: inline-block; margin-top: 8px; font-size: 14px; color: var(--red); }

/*   Form fields   */
.field { margin-bottom: 16px; }
.field label { display: block; font-weight: 500; margin-bottom: 6px; }

input[type=text], input[type=number], input[type=search], select, textarea {
  width: 100%;
  font: inherit;
  padding: 10px 12px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  color: var(--text);
}
textarea { resize: vertical; }
input:focus, select:focus, textarea:focus {
  border-color: var(--red);
  outline: 0;
  box-shadow: 0 0 0 3px rgba(200, 0, 10, .15);
}
input[type=file] { width: 100%; border: 0; padding: 6px 0; font: inherit; }
input[type=checkbox] { width: auto; accent-color: var(--red); }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
.form-grid .span-2 { grid-column: span 2; }
.radio-row { display: flex; flex-wrap: wrap; gap: 8px 18px; padding-top: 8px; }
.radio-row label, .checks label { display: inline-flex; align-items: center; gap: 6px; font-weight: 400; margin: 0; }
.search-row { display: flex; gap: 8px; margin-bottom: 14px; max-width: 420px; }

/*   Buttons   */
.btn { font: inherit; font-weight: 700; padding: 10px 18px; border-radius: 8px; border: 0; cursor: pointer; background: var(--red); color: #fff; text-decoration: none; display: inline-block; }
.btn:hover { background: var(--red-dark); }
.btn-sm { padding: 8px 14px; font-size: 14px; }
.btn-ghost { background: transparent; color: var(--text); border: 1px solid var(--line); }
.btn-ghost:hover { background: var(--bg); }
.link-btn { background: none; border: 0; padding: 0; font: inherit; font-size: inherit; color: var(--red); cursor: pointer; text-decoration: underline; }
.link-btn.danger { color: #a31212; }

/*   Table   */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { text-align: left; padding: 11px 10px; border-bottom: 1px solid var(--line); white-space: nowrap; vertical-align: middle; }
th { font-size: 13px; color: var(--muted); font-weight: 500; }
tr:last-child td { border-bottom: 0; }
.empty { color: var(--muted); padding: 18px 0; }
.muted { color: var(--muted); font-size: 13px; }

.thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; display: block; background: var(--bg); }
.thumb.big { width: 120px; height: 120px; margin-bottom: 8px; }
.thumb.none { border: 1px dashed var(--line); }

.toggle { font: inherit; font-size: 13px; font-weight: 700; padding: 3px 12px; border-radius: 99px; border: 1px solid var(--line); background: #eee; color: var(--muted); cursor: pointer; }
.toggle.on { background: #d9f1e4; color: var(--ok); border-color: #b7e0c8; }
.row-actions { display: flex; gap: 14px; align-items: center; }
.row-actions a { color: var(--red); }

/*   Badges   */
.badge { display: inline-block; padding: 2px 9px; margin: 2px 4px 0 0; border-radius: 99px; font-size: 12px; font-weight: 500; background: #eee; color: #444; }
.badge.pending { background: #fff1d6; color: var(--warn); }
.badge.confirmed { background: #dcebfa; color: var(--info); }
.badge.completed { background: #d9f1e4; color: var(--ok); }

/*   Responsive   */
@media (max-width: 900px) {
  .menu-layout { grid-template-columns: 1fr; }
  .form-grid { grid-template-columns: 1fr; }
  .form-grid .span-2 { grid-column: auto; }
}
</style>

@php
  $isEdit = (bool) $editItem;
  $spice  = [0 => 'None', 1 => 'Mild', 2 => 'Medium', 3 => 'Hot'];
  $money  = fn ($v) => 'LKR ' . number_format((float) $v, 2);
@endphp

@if ($errors->any())
  <div class="flash error">{{ $errors->first() }}</div>
@endif

<div class="menu-layout">

 
  <aside>
    <div class="panel">
      <div class="panel-head"><h2>Categories</h2></div>

      <ul class="cat-list">
        <li class="{{ ! $currentId ? 'current' : '' }}">
          <a href="{{ route('admin.menu') }}">All items</a>
        </li>

        @foreach ($categories as $c)
          <li class="{{ $currentId === $c->id ? 'current' : '' }}">
            <a href="{{ route('admin.menu', ['category' => $c->id]) }}">{{ $c->name }}</a>
            <span class="count">{{ $c->items_count }}</span>

            <div class="cat-actions">
              <a href="{{ route('admin.menu', ['category' => $c->id, 'edit_cat' => $c->id]) }}">Rename</a>

              <form method="post" action="{{ route('admin.categories.destroy', $c) }}"
                    onsubmit="return confirm('Delete category {{ addslashes($c->name) }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="link-btn danger">Delete</button>
              </form>
            </div>
          </li>
        @endforeach
      </ul>

      @if ($editCat)
        <form method="post" action="{{ route('admin.categories.update', $editCat) }}" class="cat-form">
          @csrf
          @method('PUT')
          <div class="field">
            <label for="cat_name">Rename category</label>
            <div class="inline">
              <input type="text" id="cat_name" name="name" value="{{ old('name', $editCat->name) }}" required>
              <button class="btn btn-sm" type="submit">Save</button>
            </div>
          </div>
          <a class="cancel-link" href="{{ route('admin.menu', ['category' => $editCat->id]) }}">Cancel</a>
        </form>
      @else
        <form method="post" action="{{ route('admin.categories.store') }}" class="cat-form">
          @csrf
          <div class="field">
            <label for="new_cat">New category</label>
            <div class="inline">
              <input type="text" id="new_cat" name="name" placeholder="e.g. Seafood" required>
              <button class="btn btn-sm" type="submit">Add</button>
            </div>
          </div>
        </form>
      @endif
    </div>
  </aside>

  
  <section>

    
    <div class="panel form-panel">
      <div class="panel-head">
        <h2>{{ $isEdit ? 'Edit menu item' : 'Add menu item' }}</h2>
        @if ($isEdit)
          <a href="{{ route('admin.menu', ['category' => $editItem->category_id]) }}">Cancel edit</a>
        @endif
      </div>

      <form method="post" enctype="multipart/form-data"
            action="{{ $isEdit ? route('admin.menu.update', $editItem) : route('admin.menu.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="form-grid">
          <div class="field">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required maxlength="150"
                   value="{{ old('name', $editItem->name ?? '') }}">
          </div>

          <div class="field">
            <label for="name_chinese">Chinese name (optional)</label>
            <input type="text" id="name_chinese" name="name_chinese" maxlength="150"
                   value="{{ old('name_chinese', $editItem->name_chinese ?? '') }}">
          </div>

          <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
              <option value="">Select category</option>
              @foreach ($categories as $c)
                <option value="{{ $c->id }}"
                  {{ (int) old('category_id', $editItem->category_id ?? $currentId) === $c->id ? 'selected' : '' }}>
                  {{ $c->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="field">
            <label for="base_price">Price (LKR)</label>
            <input type="number" id="base_price" name="base_price" step="0.01" min="0" required
                   value="{{ old('base_price', $editItem->base_price ?? '') }}">
          </div>

          <div class="field">
            <label for="spice_level">Spice level</label>
            <select id="spice_level" name="spice_level">
              @foreach ($spice as $k => $label)
                <option value="{{ $k }}"
                  {{ (int) old('spice_level', $editItem->spice_level ?? 0) === $k ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="field">
            <label for="prep_time_min">Prep time (minutes)</label>
            <input type="number" id="prep_time_min" name="prep_time_min" min="1" max="240"
                   value="{{ old('prep_time_min', $editItem->prep_time_min ?? 15) }}">
          </div>

          <div class="field span-2">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" maxlength="2000">{{ old('description', $editItem->description ?? '') }}</textarea>
          </div>

          <div class="field span-2">
            <label for="image">Photo (jpg, png, webp, max 2 MB)</label>
            @if ($isEdit && $editItem->image_url)
              <img class="thumb big" src="{{ asset($editItem->image_url) }}" alt="{{ $editItem->name }}">
            @endif
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
          </div>

          <div class="field">
            <label for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" min="0"
                   value="{{ old('sort_order', $editItem->sort_order ?? 0) }}">
          </div>

          <div class="field checks">
            <label>Options</label>
            <div class="radio-row">
              <label><input type="checkbox" name="is_veg" value="1"
                {{ old('is_veg', $editItem->is_veg ?? false) ? 'checked' : '' }}> Vegetarian</label>
              <label><input type="checkbox" name="is_featured" value="1"
                {{ old('is_featured', $editItem->is_featured ?? false) ? 'checked' : '' }}> Featured</label>
              <label><input type="checkbox" name="is_popular" value="1"
                {{ old('is_popular', $editItem->is_popular ?? false) ? 'checked' : '' }}> Popular</label>
              <label><input type="checkbox" name="is_available" value="1"
                {{ old('is_available', $editItem->is_available ?? true) ? 'checked' : '' }}> Available</label>
            </div>
          </div>
        </div>

        <button class="btn" type="submit">{{ $isEdit ? 'Save changes' : 'Add item' }}</button>
      </form>
    </div>

   
    <div class="panel">
      <div class="panel-head">
        <h2>{{ $items->count() }} {{ \Illuminate\Support\Str::plural('item', $items->count()) }}</h2>
      </div>

      <form method="get" class="search-row">
        @if ($currentId)
          <input type="hidden" name="category" value="{{ $currentId }}">
        @endif
        <input type="search" name="q" value="{{ $search }}" placeholder="Search by name">
        <button class="btn btn-ghost btn-sm" type="submit">Search</button>
      </form>

      @if ($items->isNotEmpty())
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Photo</th><th>Name</th><th>Category</th><th>Price</th>
                <th>Spice</th><th>Status</th><th></th>
              </tr>
            </thead>
            <tbody>
              @foreach ($items as $i)
                <tr>
                  <td>
                    @if ($i->image_url)
                      <img class="thumb" src="{{ asset($i->image_url) }}" alt="{{ $i->name }}">
                    @else
                      <span class="thumb none"></span>
                    @endif
                  </td>
                  <td>
                    <strong>{{ $i->name }}</strong>
                    @if ($i->name_chinese)<div class="muted">{{ $i->name_chinese }}</div>@endif
                    @if ($i->is_veg)<span class="badge completed">Veg</span>@endif
                    @if ($i->is_featured)<span class="badge confirmed">Featured</span>@endif
                    @if ($i->is_popular)<span class="badge pending">Popular</span>@endif
                  </td>
                  <td>{{ $i->category->name ?? '-' }}</td>
                  <td>{{ $money($i->base_price) }}</td>
                  <td>{{ $spice[$i->spice_level] ?? '-' }}</td>
                  <td>
                    <form method="post" action="{{ route('admin.menu.toggle', $i) }}">
                      @csrf
                      <button type="submit" class="toggle {{ $i->is_available ? 'on' : '' }}">
                        {{ $i->is_available ? 'Available' : 'Sold out' }}
                      </button>
                    </form>
                  </td>
                  <td>
                    <div class="row-actions">
                      <a href="{{ route('admin.menu.edit', $i) }}">Edit</a>
                      <form method="post" action="{{ route('admin.menu.destroy', $i) }}"
                            onsubmit="return confirm('Remove {{ addslashes($i->name) }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="link-btn danger">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="empty">No menu items here yet. Add your first dish using the form above.</p>
      @endif
    </div>

  </section>
</div>
@endsection