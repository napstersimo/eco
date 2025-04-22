



<!-- Sidebar -->
<div class="col-md-3">
    <div class="list-group">
        <a href="<?php echo APP_URL; ?>/index.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> لوحة التحكم
        </a>
        
        <div class="list-group-item list-group-item-primary">إدارة المتعلمين</div>
        <a href="<?php echo APP_URL; ?>/modules/students/list.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/students/list.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> قائمة المتعلمين
        </a>
        <a href="<?php echo APP_URL; ?>/modules/students/add.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/students/add.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-plus"></i> إضافة متعلم جديد
        </a>
        
        <div class="list-group-item list-group-item-primary">إدارة الأساتذة</div>
        <a href="<?php echo APP_URL; ?>/modules/teachers/list.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/teachers/list.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> قائمة الأساتذة
        </a>
        <a href="<?php echo APP_URL; ?>/modules/teachers/add.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/teachers/add.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-plus"></i> إضافة أستاذ جديد
        </a>
        
        <div class="list-group-item list-group-item-primary">إدارة المواد</div>
        <a href="<?php echo APP_URL; ?>/modules/courses/list.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/courses/list.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> قائمة المواد
        </a>
        <a href="<?php echo APP_URL; ?>/modules/courses/add.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/courses/add.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-plus"></i> إضافة مادة جديدة
        </a>

        

<div class="list-group-item list-group-item-primary">إدارة الفصول</div>
<a href="<?php echo APP_URL; ?>/modules/classes/list.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/classes/list.php') !== false) ? 'active' : ''; ?>">
    <i class="fas fa-chalkboard"></i> قائمة الفصول
</a>
<a href="<?php echo APP_URL; ?>/modules/classes/add.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/classes/add.php') !== false) ? 'active' : ''; ?>">
    <i class="fas fa-plus"></i> إضافة فصل جديد
</a>

<div class="list-group-item list-group-item-primary">إدارة التسجيلات</div>
<a href="<?php echo APP_URL; ?>/modules/enrollments/list.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/enrollments/list.php') !== false) ? 'active' : ''; ?>">
    <i class="fas fa-user-graduate"></i> قائمة التسجيلات
</a>
<a href="<?php echo APP_URL; ?>/modules/enrollments/add.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/enrollments/add.php') !== false) ? 'active' : ''; ?>">
    <i class="fas fa-user-plus"></i> تسجيل متعلم جديد
</a>
        
        <div class="list-group-item list-group-item-primary">إدارة المالية</div>
        <a href="<?php echo APP_URL; ?>/modules/payments/student_payments.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/payments/student_payments.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-money-bill"></i> دفعات المتعلمين
        </a>
        
<a href="<?php echo APP_URL; ?>/modules/reports/teacher_salaries.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/reports/teacher_salaries.php') !== false) ? 'active' : ''; ?>">
    <i class="fas fa-money-check-alt"></i> رواتب الأساتذة
</a>
        <a href="<?php echo APP_URL; ?>/modules/payments/teacher_payments.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/payments/teacher_payments.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-money-bill"></i> دفعات الأساتذة
        </a>
        <a href="<?php echo APP_URL; ?>/modules/payments/expenses.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/payments/expenses.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice-dollar"></i> المصاريف
        </a>
        
        <div class="list-group-item list-group-item-primary">التقارير</div>
        <a href="<?php echo APP_URL; ?>/modules/reports/financial.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/reports/financial.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> التقارير المالية
        </a>
        <a href="<?php echo APP_URL; ?>/modules/reports/students.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/reports/students.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> تقارير المتعلمين
        </a>
        
        <?php if(hasRole('admin')): ?>
        <div class="list-group-item list-group-item-primary">النظام</div>
        <a href="<?php echo APP_URL; ?>/modules/settings/index.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/settings/index.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> الإعدادات
        </a>
        <a href="<?php echo APP_URL; ?>/modules/users/list.php" class="list-group-item list-group-item-action <?php echo (strpos($_SERVER['PHP_SELF'], '/modules/users/list.php') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i> إدارة المستخدمين
        </a>
        <?php endif; ?>
    </div>
</div>