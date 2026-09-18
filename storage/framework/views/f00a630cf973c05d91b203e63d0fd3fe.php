<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Register — <?php echo e(config('app.name')); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/sms-template.css', 'resources/js/app.js']); ?>
</head>
<body class="auth-page">
<div class="card register-card">
  <!-- Left panel -->
  <div class="left">
    <div class="left-top">
      <?php if(file_exists(public_path('images/logo.png'))): ?>
        <img src="<?php echo e(asset('images/logo.png')); ?>" alt="Logo" class="left-logo"/>
      <?php else: ?>
        <div style="width: 52px; height: 52px; background: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #1a3a8c; font-weight: bold; font-size: 1.2rem;">LMS</div>
      <?php endif; ?>
      <p class="left-school"><?php echo e(config('app.name')); ?></p>
    </div>
    <div class="left-body">
      <h1>Learning Management System</h1>
      <p class="subtitle">Academic Portal</p>
      <p>
        <span>Registration</span> is the process of creating your academic profile to access courses, view grades, and participate in the learning management system.
        Complete the form to begin your educational journey with us.
      </p>
    </div>
  </div>

  <!-- Right panel -->
  <div class="right">
    <?php if(file_exists(public_path('images/logo.png'))): ?>
      <img class="bcp-logo" src="<?php echo e(asset('images/logo.png')); ?>" alt="<?php echo e(config('app.name')); ?> Logo" />
    <?php else: ?>
      <div class="bcp-logo" style="width: 82px; height: 92px; background: #1a3a8c; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 1.5rem;">LMS</div>
    <?php endif; ?>

    <h2>Register Account</h2>

    <?php if($errors->any()): ?>
      <div class="auth-error">
          <?php echo e($errors->first()); ?>

      </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('register')); ?>" style="width:100%" id="registerForm">
      <?php echo csrf_field(); ?>
      <div class="form-row">
        <div class="form-group">
          <label>
            <i class="fa-solid fa-user"></i>
            First Name
          </label>
          <input type="text" id="first_name" name="first_name" required autofocus value="<?php echo e(old('first_name')); ?>" autocomplete="given-name"/>
        </div>

        <div class="form-group">
          <label>
            <i class="fa-solid fa-user"></i>
            Last Name
          </label>
          <input type="text" id="last_name" name="last_name" required value="<?php echo e(old('last_name')); ?>" autocomplete="family-name"/>
        </div>
      </div>

      <div class="form-group">
        <label>
          <i class="fa-solid fa-envelope"></i>
          Email
        </label>
        <input type="email" id="email" name="email" required value="<?php echo e(old('email')); ?>" autocomplete="email"/>
      </div>

      <div class="form-group">
        <label>
          <i class="fa-solid fa-lock"></i>
          Password
        </label>
        <input type="password" id="password" name="password" required autocomplete="new-password"/>
      </div>

      <div class="form-group">
        <label>
          <i class="fa-solid fa-lock"></i>
          Confirm Password
        </label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"/>
      </div>

      <button type="submit" class="btn-done" id="registerBtn">
        Create Account
        <i class="fa-solid fa-check"></i>
      </button>
    </form>

    <p class="register-link">
      Already have an account? <a href="<?php echo e(route('login')); ?>">Sign in here</a>
    </p>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');

    // Add loading state to button
    registerForm.addEventListener('submit', function(e) {
        if (registerBtn) {
            registerBtn.innerHTML = 'Creating account…';
            registerBtn.disabled = true;
        }
    });
});
</script>
</body>
</html><?php /**PATH C:\xampp\htdocs\lms\resources\views\auth\register.blade.php ENDPATH**/ ?>