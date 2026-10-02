<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify login — {{ config('app.name') }}</title>
    <style>
        :root{--navy:#0b1220;--blue:#173aa8;--blue2:#4d8ff0;--ink:#13213b;--muted:#71809b;--line:#dce4f0;--soft:#eef6ff}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#edf3fa;color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.card{width:min(460px,100%);padding:34px;background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:0 24px 70px rgba(9,25,58,.16)}.eyebrow{color:var(--blue2);font-size:.74rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.icon{display:grid;place-items:center;width:48px;height:48px;margin:18px 0;color:#fff;background:var(--blue);border-radius:12px;font-size:1.2rem}.card h1{margin:0 0 8px;font-size:1.8rem}.muted{color:var(--muted);line-height:1.6}.masked-email{font-weight:700;color:var(--blue)}.message{padding:11px 13px;margin:16px 0;border-radius:9px;font-size:.86rem}.message.error{color:#9b1c1c;background:#fff0f0;border:1px solid #ffd0d0}.message.success{color:#146c43;background:#edfff5;border:1px solid #bcebd0}.code-input{width:100%;height:58px;margin:12px 0 18px;border:1px solid var(--line);border-radius:10px;text-align:center;letter-spacing:.45em;font-size:1.7rem;font-weight:800;color:var(--ink)}.code-input:focus{outline:0;border-color:var(--blue2);box-shadow:0 0 0 4px rgba(77,143,240,.14)}.submit{width:100%;height:48px;border:0;border-radius:10px;color:#fff;background:var(--blue);font-weight:800;cursor:pointer}.actions{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:18px}.link-button{padding:0;border:0;background:transparent;color:var(--blue);font-weight:700;cursor:pointer}.back{color:var(--muted);text-decoration:none;font-size:.86rem}.cooldown{color:var(--muted);font-size:.8rem}
    </style>
</head>
<body>
    <main class="card">
        <div class="eyebrow">{{ config('app.name') }} security</div>
        <div class="icon" aria-hidden="true">&#128274;</div>
        <h1>Verify your login</h1>
        <p class="muted">We sent a six-digit verification code to <span class="masked-email">{{ $maskedEmail }}</span>.</p>
        @if ($errors->any())<div class="message error" role="alert">{{ $errors->first() }}</div>@endif
        @if (session('status'))<div class="message success" role="status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('login.verify') }}">
            @csrf
            <label for="code">Verification code</label>
            <input class="code-input" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" minlength="6" required autofocus aria-describedby="code-help">
            <p id="code-help" class="muted" style="font-size:.8rem">The code expires in {{ $expiresInMinutes }} minutes.</p>
            <button class="submit" type="submit">Verify and sign in</button>
        </form>
        <div class="actions">
            <form method="POST" action="{{ route('login.resend') }}">
                @csrf
                <button class="link-button" id="resendBtn" type="submit" @if($resendWait > 0) disabled @endif>Resend code</button>
            <span class="cooldown" id="resendCooldown">@if($resendWait > 0) Try again in {{ $resendWait }}s @endif</span>
            </form>
            <a class="back" href="{{ route('login') }}">Back to login</a>
        </div>
    </main>
<script>
    (function () {
        var btn = document.getElementById('resendBtn');
        var cd = document.getElementById('resendCooldown');
        if (!btn || !cd) return;
        var wait = {{ $resendWait }};
        function tick() {
            if (wait <= 0) {
                btn.disabled = false;
                cd.textContent = '';
                return;
            }
            btn.disabled = true;
            cd.textContent = 'Try again in ' + wait + 's';
            wait -= 1;
            setTimeout(tick, 1000);
        }
        tick();
    })();
</script>
</body>
</html>
