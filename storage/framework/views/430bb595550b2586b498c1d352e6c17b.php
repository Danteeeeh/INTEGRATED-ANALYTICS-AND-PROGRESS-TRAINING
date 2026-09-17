<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Sign in — <?php echo e(config('app.name')); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root{--navy:#0b1220;--blue:#173aa8;--blue2:#4d8ff0;--cyan:#62c9f5;--ink:#13213b;--muted:#71809b;--line:#dce4f0;--soft:#eef6ff}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--ink);background:#edf3fa;display:flex;align-items:center;justify-content:center;padding:28px}.auth-frame{width:min(1080px,100%);min-height:640px;display:grid;grid-template-columns:1fr 1fr;background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 28px 90px rgba(9,25,58,.2);margin:auto}
        .brand-panel{position:relative;overflow:hidden;padding:54px 52px;color:#fff;background:linear-gradient(145deg,#0b1837 0%,#173aa8 62%,#2877d8 100%);display:flex;flex-direction:column;justify-content:space-between}.brand-content{flex:1;display:flex;flex-direction:column;justify-content:center}
        .brand-panel:before,.brand-panel:after{content:"";position:absolute;border:1px solid rgba(255,255,255,.18);border-radius:50%}.brand-panel:before{width:420px;height:420px;right:-210px;top:-120px}.brand-panel:after{width:320px;height:320px;left:-190px;bottom:-160px}
        .brand-content,.brand-footer{position:relative;z-index:1}.brand-mark{width:86px;height:96px;object-fit:contain;margin-bottom:34px}.eyebrow{color:#9edbff;font-size:.75rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase}.brand-panel h1{max-width:430px;margin:14px 0 18px;font-size:clamp(2rem,4vw,3.25rem);line-height:1.03;letter-spacing:-.045em}.brand-panel p{max-width:430px;color:#d6e5ff;line-height:1.7;font-size:1rem}.brand-footer{display:flex;gap:10px;flex-wrap:wrap}.brand-pill{padding:9px 13px;border:1px solid rgba(255,255,255,.22);border-radius:999px;color:#e4f4ff;background:rgba(255,255,255,.08);font-size:.78rem}
        .form-panel{padding:52px clamp(28px,5vw,72px);display:flex;align-items:center}.form-wrap{width:100%;max-width:390px;margin:auto}.form-kicker{color:var(--blue2);font-size:.76rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.form-wrap h2{margin:10px 0 8px;font-size:2rem;letter-spacing:-.035em}.form-subtitle{margin:0 0 28px;color:var(--muted);line-height:1.6}.message{padding:12px 14px;border-radius:10px;margin-bottom:18px;font-size:.88rem}.message.error{color:#9b1c1c;background:#fff0f0;border:1px solid #ffd0d0}.message.success{color:#146c43;background:#edfff5;border:1px solid #bcebd0}.field{margin-bottom:18px}.field label{display:block;margin-bottom:7px;font-size:.82rem;font-weight:750}.input-wrap{position:relative}.input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#8a98ae}.field input{width:100%;height:50px;padding:0 44px;border:1px solid var(--line);border-radius:11px;background:#fbfdff;color:var(--ink);font-size:.95rem;transition:border-color .2s,box-shadow .2s}.field input:focus{outline:0;border-color:var(--blue2);box-shadow:0 0 0 4px rgba(77,143,240,.14)}.password-toggle{position:absolute;right:9px;top:7px;width:36px;height:36px;border:0;border-radius:8px;color:#72819a;background:transparent}.password-toggle:hover{color:var(--blue);background:#eef4ff}.form-meta{display:flex;justify-content:flex-end;margin:-4px 0 22px}.form-meta a{color:var(--blue);font-size:.82rem;text-decoration:none}.form-meta a:hover{text-decoration:underline}.form-help{color:var(--muted);font-size:.75rem}.form-help i{color:var(--blue2);margin-right:4px}.submit-btn{width:100%;height:50px;border:0;border-radius:11px;color:#fff;background:linear-gradient(135deg,var(--blue),#092179);font-size:.98rem;font-weight:800;box-shadow:0 12px 24px rgba(23,58,168,.22);transition:transform .2s,filter .2s}.submit-btn:hover{filter:brightness(1.1);transform:translateY(-1px)}.submit-btn:disabled{opacity:.7;cursor:wait}.instructions{margin-top:26px;padding:16px 17px;border-radius:12px;background:var(--soft);border:1px solid #d8eafd}.instructions strong{display:block;margin-bottom:8px;color:var(--blue);font-size:.78rem;letter-spacing:.06em;text-transform:uppercase}.instructions ul{margin:0;padding-left:18px;color:#58708f;font-size:.78rem;line-height:1.65}
        @media(max-width:760px){body{padding:0;align-items:stretch}.auth-frame{min-height:100vh;border-radius:0;grid-template-columns:1fr}.brand-panel{min-height:270px;padding:32px 28px}.brand-mark{width:62px;height:70px;margin-bottom:18px}.brand-panel h1{font-size:2rem;margin:8px 0}.brand-panel p{display:none}.brand-footer{display:none}.form-panel{padding:34px 24px;align-items:flex-start}.form-wrap{max-width:500px}}
        /* Global 75% density scale (kasing-liit ng buong app) */
        html{zoom:.75}
        @supports not (zoom:1){html{font-size:75%}body{padding:21px}.auth-frame{min-height:480px}.brand-panel{padding:40px 39px}.brand-mark{width:64px;height:72px;margin-bottom:26px}.form-panel{padding:39px clamp(21px,3.75vw,54px)}.field input{height:38px;border-radius:8px;font-size:.95rem}.submit-btn{height:38px;border-radius:8px}}
    </style>
</head>
<body>
    <main class="auth-frame">
        <section class="brand-panel" aria-label="Bestlink College branding">
            <div class="brand-content">
                <img class="brand-mark" src="<?php echo e(asset('images/BCP_LOGO.png')); ?>" alt="Bestlink College of the Philippines logo">
                <div class="eyebrow">Bestlink College of the Philippines</div>
                <h1>Learn with purpose. Grow with confidence.</h1>
                <p>Access your courses, assignments, progress, and academic community in one secure learning space.</p>
            </div>
            <div class="brand-footer"><span class="brand-pill"><i class="fa-solid fa-shield-halved"></i> Secure access</span><span class="brand-pill"><i class="fa-solid fa-graduation-cap"></i> Student success</span></div>
        </section>
        <section class="form-panel">
            <div class="form-wrap">
                <div class="form-kicker">Learning Management System</div>
                <h2>Welcome back</h2>
                <p class="form-subtitle">Sign in to continue your learning journey.</p>
                <?php if($errors->any()): ?><div class="message error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?php echo e($errors->first()); ?></div><?php endif; ?>
                <?php if(session('status')): ?><div class="message success" role="status"><i class="fa-solid fa-circle-check"></i> <?php echo e(session('status')); ?></div><?php endif; ?>
                <form method="POST" action="<?php echo e(route('login')); ?>" id="loginForm">
                    <?php echo csrf_field(); ?>
                    <div class="field"><label for="email">Email address</label><div class="input-wrap"><i class="fa-regular fa-envelope" aria-hidden="true"></i><input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" placeholder="you@example.com" autocomplete="email" required autofocus></div></div>
                    <div class="field"><label for="password">Password</label><div class="input-wrap"><i class="fa-solid fa-lock" aria-hidden="true"></i><input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required><button type="button" class="password-toggle" id="togglePassword" aria-label="Show password"><i class="fa-regular fa-eye"></i></button></div></div>
                    <div class="form-meta"><span class="form-help" title="Contact your school support team to reset your password"><i class="fa-solid fa-circle-info"></i> Contact support to reset password</span></div>
                    <button class="submit-btn" type="submit" id="loginBtn">Sign in <i class="fa-solid fa-arrow-right"></i></button>
                </form>
                <aside class="instructions"><strong>Need help signing in?</strong><ul><li>Use your official school email address.</li><li>Keep your password private and secure.</li><li>Contact the school support team if you need assistance.</li></ul></aside>
            </div>
        </section>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded',function(){const input=document.getElementById('password'),toggle=document.getElementById('togglePassword'),form=document.getElementById('loginForm'),button=document.getElementById('loginBtn');if(toggle&&input)toggle.addEventListener('click',function(){const show=input.type==='password';input.type=show?'text':'password';this.setAttribute('aria-label',show?'Hide password':'Show password');this.innerHTML=show?'<i class="fa-regular fa-eye-slash"></i>':'<i class="fa-regular fa-eye"></i>'});if(form)form.addEventListener('submit',function(){button.disabled=true;button.innerHTML='Signing in <i class="fa-solid fa-spinner fa-spin"></i>'})});
    </script>
</body>
</html><?php /**PATH C:\xampp\htdocs\lms\resources\views\auth\login.blade.php ENDPATH**/ ?>