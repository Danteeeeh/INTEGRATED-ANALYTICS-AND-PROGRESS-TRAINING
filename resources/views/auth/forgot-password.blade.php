<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot password — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root{--blue:#173aa8;--blue2:#4d8ff0;--ink:#13213b;--muted:#71809b;--line:#dce4f0}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#edf3fa;color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}
        .card{width:min(470px,100%);padding:34px;background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:0 24px 70px rgba(9,25,58,.16)}
        .eyebrow{color:var(--blue2);font-size:.74rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        .icon{display:grid;place-items:center;width:48px;height:48px;margin:18px 0;color:#fff;background:var(--blue);border-radius:12px;font-size:1.2rem}
        h1{margin:0 0 8px;font-size:1.8rem}.muted{color:var(--muted);line-height:1.6}
        .message{padding:11px 13px;margin:16px 0;border-radius:9px;font-size:.86rem}.message.error{color:#9b1c1c;background:#fff0f0;border:1px solid #ffd0d0}.message.success{color:#146c43;background:#edfff5;border:1px solid #bcebd0}
        label{display:block;margin:18px 0 7px;font-size:.83rem;font-weight:750}
        input{width:100%;height:50px;padding:0 14px;border:1px solid var(--line);border-radius:10px;background:#fbfdff;color:var(--ink);font-size:.95rem}
        input:focus{outline:0;border-color:var(--blue2);box-shadow:0 0 0 4px rgba(77,143,240,.14)}
        .submit{width:100%;height:48px;margin-top:20px;border:0;border-radius:10px;color:#fff;background:var(--blue);font-weight:800;cursor:pointer}
        .actions{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:20px;font-size:.86rem}.actions a{color:var(--blue);font-weight:700;text-decoration:none}.actions a:hover{text-decoration:underline}
    </style>
</head>
<body>
    <main class="card">
        <div class="eyebrow">{{ config('app.name') }} security</div>
        <div class="icon" aria-hidden="true"><i class="fa-solid fa-key"></i></div>
        <h1>Forgot your password?</h1>
        <p class="muted">Enter your official school email and we will send a link to set a new password. New AA-synced accounts can use this too.</p>

        @if ($errors->any())
            <div class="message error" role="alert">{{ $errors->first() }}</div>
        @endif
        @if (session('status'))
            <div class="message success" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required autofocus>
            <button class="submit" type="submit">Email password reset link</button>
        </form>

        <div class="actions">
            <a href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i> Back to sign in</a>
        </div>
    </main>
</body>
</html>
