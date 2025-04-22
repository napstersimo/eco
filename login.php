<?php
// Page de connexion
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard.php');
}

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($username)) {
        $errors[] = 'اسم المستخدم مطلوب';
    }
    
    if (empty($password)) {
        $errors[] = 'كلمة المرور مطلوبة';
    }
    
    // Si aucune erreur, vérifier les identifiants
    if (empty($errors)) {
        $db = new Database();
        
        // Vérifier si l'utilisateur existe
        $db->query("SELECT * FROM users WHERE username = :username");
        $db->bind(':username', $username);
        $user = $db->single();
        
        if ($user && password_verify($password, $user['password'])) {
            // Identifiants corrects, créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Rediriger vers la page d'accueil
            redirect(APP_URL . '/dashboard.php');
        } else {
            $errors[] = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}

// Numéro WhatsApp - Remplacez par votre numéro
$whatsapp_number = "+212600000000"; // Remplacez par votre numéro réel
$whatsapp_message = "Bonjour, j'ai besoin d'aide avec le système de gestion d'école.";
$whatsapp_link = "https://wa.me/" . $whatsapp_number . "?text=" . urlencode($whatsapp_message);
?>

<!DOCTYPE html>
<html lang="fr" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?php echo APP_NAME; ?></title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
        }
        .login-logo {
            font-size: 24px;
            color: #fff;
            margin-bottom: 0;
        }
        .whatsapp-help {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #25D366;
            color: white;
            border-radius: 50px;
            padding: 10px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        .whatsapp-help:hover {
            background-color: #128C7E;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        .whatsapp-help i {
            font-size: 1.5rem;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card login-card mx-auto">
            <div class="card-header bg-primary login-header">
                <h4 class="login-logo">مركز الدعم واللغات</h4>
                <p class="text-white mb-0">2025-2026</p>
            </div>
            <div class="card-body p-4">
                <h5 class="card-title text-center mb-4">تسجيل الدخول</h5>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger text-end">
                        <ul class="mb-0 pe-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label text-end d-block">اسم المستخدم</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label text-end d-block">كلمة المرور</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </div>
    
    <!-- Bouton d'aide WhatsApp -->
    <a href="<?php echo $whatsapp_link; ?>" class="whatsapp-help" target="_blank">
        <i class="fab fa-whatsapp"></i>
        <span>بحاجة إلى مساعدة؟</span>
    </a>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html><?php
// Page de connexion
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard.php');
}

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($username)) {
        $errors[] = 'اسم المستخدم مطلوب';
    }
    
    if (empty($password)) {
        $errors[] = 'كلمة المرور مطلوبة';
    }
    
    // Si aucune erreur, vérifier les identifiants
    if (empty($errors)) {
        $db = new Database();
        
        // Vérifier si l'utilisateur existe
        $db->query("SELECT * FROM users WHERE username = :username");
        $db->bind(':username', $username);
        $user = $db->single();
        
        if ($user && password_verify($password, $user['password'])) {
            // Identifiants corrects, créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Rediriger vers la page d'accueil
            redirect(APP_URL . '/dashboard.php');
        } else {
            $errors[] = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}

// Numéro WhatsApp - Remplacez par votre numéro
$whatsapp_number = "+212606688220"; // Remplacez par votre numéro réel
$whatsapp_message = "Bonjour, j'ai besoin d'aide avec le système de gestion d'école.";
$whatsapp_link = "https://wa.me/" . $whatsapp_number . "?text=" . urlencode($whatsapp_message);
?>
