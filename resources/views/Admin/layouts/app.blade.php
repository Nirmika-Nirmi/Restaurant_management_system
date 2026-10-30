<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Admin') | Golden Dragon Admin</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

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
}
* { box-sizing: border-box; }
html, body { margin: 0; }
body { font-family: 'DM Sans', system-ui, sans-serif; background: var(--bg); color: var(--text); font-size: 15px; line-height: 1.5; }
h1, h2, h3 { font-family: 'Playfair Display', Georgia, serif; margin: 0; }
a { color: var(--red); }
:focus-visible { outline: 3px solid var(--gold); outline-offset: 2px; }

/*   Shell   */
.shell { display: flex; min-height: 100vh; }

.sidebar { width: 250px; background: var(--maroon); color: #f3e9e9; padding: 24px 16px; position: sticky; top: 0; height: 100vh; overflow-y: auto; flex-shrink: 0; }
.brand { padding: 4px 12px 24px; border-bottom: 1px solid rgba(255,255,255,.12); margin-bottom: 8px; }
.brand-small { display: block; font-family: 'Playfair Display', serif; font-size: 15px; opacity: .85; }
.brand-big { display: block; font-family: 'Playfair Display', serif; font-weight: 700; font-size: 26px; color: var(--gold); line-height: 1.15; }
.nav-group { margin: 20px 12px 6px; font-size: 12px; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .5px; }
.sidebar nav a { display: block; padding: 9px 12px; border-radius: 8px; color: #f3e9e9; text-decoration: none; }
.sidebar nav a:hover { background: rgba(255,255,255,.08); }
.sidebar nav a.active { background: var(--red); color: #fff; font-weight: 700; }

.main { flex: 1; min-width: 0; }
.topbar { display: flex; align-items: center; gap: 16px; padding: 16px 28px; background: var(--surface); border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 5; }
.topbar h1 { font-size: 24px; }
.topbar-right { margin-left: auto; display: flex; align-items: center; gap: 14px; }
.admin-name { color: var(--muted); }
.menu-btn { display: none; font: inherit; padding: 8px 12px; border: 1px solid var(--line); background: var(--surface); border-radius: 8px; cursor: pointer; }
.content { padding: 28px; max-width: 1200px; }

/*   Shared bits   */
.btn { font: inherit; font-weight: 700; padding: 10px 18px; border-radius: 8px; border: 0; cursor: pointer; background: var(--red); color: #fff; text-decoration: none; display: inline-block; }
.btn:hover { background: var(--red-dark); }
.btn-sm { padding: 8px 14px; font-size: 14px; }
.btn-ghost { background: transparent; color: var(--text); border: 1px solid var(--line); }
.btn-ghost:hover { background: var(--bg); }

.panel { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
.flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-weight: 500; }
.flash.ok { background: #d9f1e4; color: var(--ok); }
.flash.error { background: #fbdcdc; color: #8a1010; }

/*   Responsive   */
@media (max-width: 900px) {
  .sidebar { position: fixed; left: -260px; z-index: 20; transition: left .2s; }
  .sidebar.open { left: 0; }
  .menu-btn { display: inline-block; }
  .content { padding: 18px; }
  .admin-name { display: none; }
}
@media (prefers-reduced-motion: reduce) { .sidebar { transition: none; } }
</style>

@stack('styles')
</head>
<body>
<div class="shell">

  {{-- Common sidebar for every admin page --}}
  @include('admin.partials.sidebar')

  <div class="main">
    <header class="topbar">
      <button class="menu-btn" type="button" id="menuBtn" aria-label="Open menu">Menu</button>
      <h1>@yield('title', 'Dashboard')</h1>

      <div class="topbar-right">
        <span class="admin-name">{{ auth('admin')->user()->full_name }}</span>
        <form method="post" action="{{ route('admin.logout') }}" style="margin:0">
          @csrf
          <button type="submit" class="btn btn-ghost btn-sm">Log out</button>
        </form>
      </div>
    </header>

    <main class="content">
      @if (session('ok'))
        <div class="flash ok">{{ session('ok') }}</div>
      @endif
      @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
      @endif

      @yield('content')
    </main>
  </div>

</div>

<script>
  document.getElementById('menuBtn').addEventListener('click', function () {
    document.getElementById('sidebar').classList.toggle('open');
  });
</script>

@stack('scripts')
</body>
</html>