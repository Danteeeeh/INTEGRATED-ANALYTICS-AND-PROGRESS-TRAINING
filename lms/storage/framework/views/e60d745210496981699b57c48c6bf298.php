<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Forbidden</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: grid; place-items: center; margin: 0; }
        .box { text-align: center; padding: 2rem; }
        h1 { font-size: 3rem; margin: 0 0 .5rem; color: #93c5fd; }
        a { color: #60a5fa; }
    </style>
</head>
<body>
    <div class="box">
        <h1>403</h1>
        <p>You are not authorized to access this resource.</p>
        <p><a href="<?php echo e(url('/')); ?>">Return home</a></p>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\lms\resources\views/errors/403.blade.php ENDPATH**/ ?>