<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 — Session Expired</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: grid; place-items: center; margin: 0; }
        .box { text-align: center; padding: 2rem; }
        h1 { font-size: 3rem; margin: 0 0 .5rem; color: #93c5fd; }
        a { color: #60a5fa; }
    </style>
</head>
<body>
    <div class="box">
        <h1>419</h1>
        <p>Your session expired. Please refresh and try again.</p>
        <p><a href="<?php echo e(url('/login')); ?>">Sign in</a></p>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\lms\resources\views\errors\419.blade.php ENDPATH**/ ?>