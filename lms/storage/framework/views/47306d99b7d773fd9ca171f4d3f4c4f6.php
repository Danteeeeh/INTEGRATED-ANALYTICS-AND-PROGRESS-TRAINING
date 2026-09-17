<?php $__env->startSection('title', 'Edit Student'); ?>
<?php
    $activeNav = 'students';
    $pageTitle = 'Edit Student';
    $pageIcon = '<i class="fa-solid fa-user-graduate"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-user-graduate"></i>
            Edit Student
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="form-card enhanced-form">
        <div class="form-header">
            <h3><i class="fa-solid fa-user-edit"></i> Edit Student Information</h3>
            <p>Update the details for <?php echo e($student->full_name); ?> below.</p>
        </div>
        
        <form action="<?php echo e(route('admin.students.update', $student)); ?>" method="POST" id="studentForm" class="enhanced-form">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            
            <!-- Personal Information Section -->
            <div class="form-section">
                <div class="modal-section-title">
                    <i class="fa-solid fa-user"></i> Personal Information
                </div>
                
                <div class="form-grid">
                    <div class="form-field">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" value="<?php echo e($student->first_name); ?>" required placeholder="Enter first name" autocomplete="given-name">
                        <span class="field-error"><?php echo e($errors->first('first_name')); ?></span>
                    </div>
                    
                    <div class="form-field">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" value="<?php echo e($student->last_name); ?>" required placeholder="Enter last name" autocomplete="family-name">
                        <span class="field-error"><?php echo e($errors->first('last_name')); ?></span>
                    </div>
                    
                    <div class="form-field full">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" value="<?php echo e($student->email); ?>" required placeholder="student@example.com" autocomplete="email">
                        <span class="field-error"><?php echo e($errors->first('email')); ?></span>
                    </div>
                    
                    <div class="form-field full">
                        <label>Password (leave blank to keep current)</label>
                        <div class="password-input-group">
                            <input type="password" name="password" minlength="8" placeholder="Minimum 8 characters" id="password" autocomplete="new-password">
                            <span class="password-toggle" id="togglePassword">
                                <i class="fa-solid fa-eye"></i>
                            </span>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar"></div>
                            <span class="strength-text">Password strength</span>
                        </div>
                        <span class="field-error"><?php echo e($errors->first('password')); ?></span>
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="form-section">
                <div class="modal-section-title">
                    <i class="fa-solid fa-info-circle"></i> Additional Information
                </div>
                
                <div class="form-grid">
                    <div class="form-field">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo e($student->phone); ?>" placeholder="09XXXXXXXXX" autocomplete="tel">
                        <span class="field-error"><?php echo e($errors->first('phone')); ?></span>
                    </div>
                    
                    <div class="form-field">
                        <label>Student ID</label>
                        <input type="text" name="identifier" value="<?php echo e($student->identifier); ?>" placeholder="Student ID (optional)">
                        <span class="field-error"><?php echo e($errors->first('identifier')); ?></span>
                    </div>
                    
                    <div class="form-field full">
                        <label>Address</label>
                        <textarea name="address" rows="3" placeholder="Enter address (optional)"><?php echo e($student->address); ?></textarea>
                        <span class="field-error"><?php echo e($errors->first('address')); ?></span>
                    </div>
                </div>
            </div>

            <!-- Account Settings Section -->
            <div class="form-section">
                <div class="modal-section-title">
                    <i class="fa-solid fa-cog"></i> Account Settings
                </div>
                
                <div class="form-grid">
                    <div class="form-field">
                        <label>Role</label>
                        <select name="role_id">
                            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($role->id); ?>" <?php echo e($student->role_id === $role->id ? 'selected' : ''); ?>>
                                    <?php echo e(ucfirst($role->slug)); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('role_id')); ?></span>
                    </div>
                    
                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo e($student->status === 'active' ? 'selected' : ''); ?>>Active</option>
                            <option value="inactive" <?php echo e($student->status === 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                            <option value="suspended" <?php echo e($student->status === 'suspended' ? 'selected' : ''); ?>>Suspended</option>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('status')); ?></span>
                    </div>
                </div>
            </div>

            <div class="form-actions enhanced-actions">
                <a href="<?php echo e(route('admin.students.show', $student)); ?>" class="btn-modal-cancel">
                    <i class="fa-solid fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn-modal-submit" id="submitBtn">
                    <i class="fa-solid fa-save"></i> Update Student
                </button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .enhanced-form {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-header {
        margin-bottom: 24px;
    }
    
    .form-header h3 {
        font-size: 1.5rem;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .form-header p {
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .form-section {
        background: #f8fafc;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    
    .form-section:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .modal-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .modal-section-title i {
        color: #2563eb;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .form-field.full {
        grid-column: span 2;
    }
    
    .form-field label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
        font-size: 0.9rem;
    }
    
    .required {
        color: #ef4444;
    }
    
    .form-field input,
    .form-field select,
    .form-field textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: #fff;
    }
    
    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }
    
    .form-field textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .field-error {
        display: block;
        color: #ef4444;
        font-size: 0.85rem;
        margin-top: 6px;
        animation: shake 0.5s ease;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .password-input-group {
        position: relative;
    }
    
    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #64748b;
        transition: color 0.3s ease;
    }
    
    .password-toggle:hover {
        color: #2563eb;
    }
    
    .password-strength {
        margin-top: 8px;
    }
    
    .strength-bar {
        height: 4px;
        background: #e2e8f0;
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 4px;
    }
    
    .strength-bar::after {
        content: '';
        display: block;
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #ef4444, #f59e0b, #22c55e);
        transition: width 0.3s ease;
    }
    
    .strength-text {
        font-size: 0.8rem;
        color: #64748b;
    }
    
    .enhanced-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 24px;
    }
    
    .btn-modal-cancel,
    .btn-modal-submit {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    
    .btn-modal-cancel {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .btn-modal-cancel:hover {
        background: #e2e8f0;
        color: #475569;
    }
    
    .btn-modal-submit {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }
    
    .btn-modal-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .form-field.full {
            grid-column: span 1;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const passwordStrength = document.getElementById('passwordStrength');
    const form = document.getElementById('studentForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Password visibility toggle
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' 
                ? '<i class="fa-solid fa-eye"></i>' 
                : '<i class="fa-solid fa-eye-slash"></i>';
        });
    }
    
    // Password strength indicator
    if (passwordInput && passwordStrength) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = passwordStrength.querySelector('.strength-bar');
            const strengthText = passwordStrength.querySelector('.strength-text');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            const colors = ['#ef4444', '#f59e0b', '#22c55e', '#2563eb'];
            const texts = ['Weak', 'Fair', 'Good', 'Strong'];
            
            strengthBar.style.setProperty('--strength', (strength / 4) * 100 + '%');
            strengthBar.querySelector('::after').style.width = (strength / 4) * 100 + '%';
            strengthBar.querySelector('::after').style.background = colors[strength - 1] || colors[0];
            strengthText.textContent = texts[strength - 1] || 'Password strength';
        });
    }
    
    // Form submission with loading state
    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
        });
    }
    
    // Add focus animations to form fields
    const formFields = document.querySelectorAll('.form-field input, .form-field select, .form-field textarea');
    formFields.forEach(field => {
        field.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        field.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views/admin/students/edit.blade.php ENDPATH**/ ?>