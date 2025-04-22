

<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

// Redirection si l'utilisateur n'est pas connecté
if(!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    redirect(APP_URL . '/login.php');
}
?>
<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="bg-primary text-white py-3">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h1 class="text-end">مركز الدعم واللغات</h1>
                    <p class="text-end">2025-2026</p>
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <?php if(isLoggedIn()): ?>
                        <div class="dropdown ms-auto">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="userMenu">
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/modules/profile/index.php">Mon profil</a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/modules/profile/change_password.php">Changer le mot de passe</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Déconnexion</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-outline-light ms-auto">
                            <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Navbar -->
    <?php if(isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/index.php">
                            <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/students/list.php">
                            <i class="fas fa-user-graduate"></i> المتعلمين
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/teachers/list.php">
                            <i class="fas fa-chalkboard-teacher"></i> الأساتذة
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/courses/list.php">
                            <i class="fas fa-book"></i> المواد
                        </a>
                        
                    </li>
                    <li class="nav-item">
    <a class="nav-link" href="<?php echo APP_URL; ?>/modules/classes/list.php">
        <i class="fas fa-chalkboard"></i> الفصول
    </a>
</li>
<li class="nav-item">
    <a class="nav-link" href="<?php echo APP_URL; ?>/modules/enrollments/list.php">
        <i class="fas fa-user-graduate"></i> التسجيلات
    </a>
</li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/payments/student_payments.php">
                            <i class="fas fa-money-bill-wave"></i> الإشتراكات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/payments/teacher_payments.php">
                            <i class="fas fa-hand-holding-usd"></i> دفع الأساتذة
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/reports/financial.php">
                            <i class="fas fa-chart-bar"></i> التقارير
                        </a>
                    </li>
                    <?php if(hasRole('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/modules/settings/index.php">
                            <i class="fas fa-cog"></i> الإعدادات
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="py-4">
        <div class="container">

