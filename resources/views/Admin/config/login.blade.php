<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin login | China Town</title>

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
}
* { box-sizing: border-box; }
html, body { margin: 0; }
body {
  font-family: 'DM Sans', system-ui, sans-serif;
  background: var(--bg);
  color: var(--text);
  font-size: 15px;
  line-height: 1.5;
}
:focus-visible { outline: 3px solid var(--gold); outline-offset: 2px; }

/* Login */
.login-body {
  min-height: 100vh;
  display: grid;
  place-items: center;
  background: var(--maroon);
  padding: 20px;
}
.login-card {
  background: var(--surface);
  width: 100%;
  max-width: 400px;
  border-radius: 14px;
  padding: 32px;
}

.brand { padding: 0 0 20px; }
.brand-small {
  display: block;
  font-family: 'Playfair Display', serif;
  font-size: 15px;
  color: var(--muted);
}
.brand-big {
  display: block;
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  font-size: 26px;
  color: var(--red);
  line-height: 1.15;
}

.field { margin-bottom: 16px; }
.field label { display: block; font-weight: 500; margin-bottom: 6px; }
.field input {
  width: 100%;
  font: inherit;
  padding: 11px 12px;
  border: 1px solid var(--line);
  border-radius: 8px;
}
.field input:focus {
  border-color: var(--red);
  outline: 0;
  box-shadow: 0 0 0 3px rgba(200, 0, 10, .15);
}

.alert {
  background: #fbdcdc;
  color: #8a1010;
  padding: 10px 12px;
  border-radius: 8px;
  margin-bottom: 16px;
}

.btn {
  font: inherit;
  font-weight: 700;
  width: 100%;
  padding: 12px;
  border-radius: 8px;
  border: 0;
  cursor: pointer;
  background: var(--red);
  color: #fff;
}
.btn:hover { background: var(--red-dark); }
</style>
</head>
<body class="login-body">

  <form class="login-card" method="post" action="{{ route('admin.login.post') }}" autocomplete="on">
    @csrf

    <div class="brand">
      <span class="brand-small">MyBatticaloa</span>
      <span class="brand-big">China Town</span>
    </div>

    {{-- Error alert --}}
    @if ($errors->any())
      <div class="alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus
             value="{{ old('email') }}">
    </div>

    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>

    <button class="btn" type="submit">Log in</button>
  </form>

</body>
</html>