<?php
// Formulaire d'ajout d'étudiant
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $first_name = clean($_POST['first_name']);
    $last_name = clean($_POST['last_name']);
    $date_of_birth = !empty($_POST['date_of_birth']) ? clean($_POST['date_of_birth']) : null;
    $gender = clean($_POST['gender']);
    $address = clean($_POST['address']);
    $phone = clean($_POST['phone']);
    $email = clean($_POST['email']);
    $parent_name = clean($_POST['parent_name']);
    $parent_phone = clean($_POST['parent_phone']);
    $enrollment_date = !empty($_POST['enrollment_date']) ? clean($_POST['enrollment_date']) : date('Y-m-d');
    $status = clean($_POST['status']);
    
    // Validation
    if (empty($first_name)) {
        $errors[] = 'الإسم الأول مطلوب';
    }
    
    if (empty($last_name)) {
        $errors[] = 'الإسم الأخير مطلوب';
    }
    
    if (empty($phone)) {
        $errors[] = 'رقم الهاتف مطلوب';
    }
    
    // Vérifier si l'e-mail existe déjà
    if (!empty($email)) {
        $db = new Database();
        $db->query("SELECT id FROM students WHERE email = :email");
        $db->bind(':email', $email);
        $student = $db->single();
        
        if ($student) {
            $errors[] = 'البريد الإلكتروني مستخدم بالفعل';
        }
    }
    
    // Si aucune erreur, ajouter l'étudiant
    if (empty($errors)) {
        $db = new Database();
        
        // Générer un numéro d'inscription
        $registration_number = generateRegistrationNumber();
        
        // Ajouter l'étudiant dans la base de données
        $db->query("INSERT INTO students (registration_number, first_name, last_name, date_of_birth, gender, address, phone, email, parent_name, parent_phone, enrollment_date, status) 
                   VALUES (:registration_number, :first_name, :last_name, :date_of_birth, :gender, :address, :phone, :email, :parent_name, :parent_phone, :enrollment_date, :status)");
        
        $db->bind(':registration_number', $registration_number);
        $db->bind(':first_name', $first_name);
        $db->bind(':last_name', $last_name);
        $db->bind(':date_of_birth', $date_of_birth);
        $db->bind(':gender', $gender);
        $db->bind(':address', $address);
        $db->bind(':phone', $phone);
        $db->bind(':email', $email);
        $db->bind(':parent_name', $parent_name);
        $db->bind(':parent_phone', $parent_phone);
        $db->bind(':enrollment_date', $enrollment_date);
        $db->bind(':status', $status);
        
        if($db->execute()) {
            $student_id = $db->lastInsertId();
            $success = true;
            
            // Rediriger vers la page de détails de l'étudiant
            redirect(APP_URL . '/modules/students/view.php?id=' . $student_id . '&success=add');
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة المتعلم';
        }
    }
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 text-end">إضافة متعلم جديد</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success text-end">
                        تمت إضافة المتعلم بنجاح!
                    </div>
                <?php endif; ?>
                
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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label text-end d-block">الإسم الأول *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label text-end d-block">الإسم الأخير *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="date_of_birth" class="form-label text-end d-block">تاريخ الميلاد</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="gender" class="form-label text-end d-block">الجنس</label>
                            <select id="gender" name="gender" class="form-select">
                                <option value="">-- اختر --</option>
                                <option value="male">ذكر</option>
                                <option value="female">أنثى</option>
                                <option value="other">آخر</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label text-end d-block">الحالة *</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active" selected>نشط</option>
                                <option value="inactive">غير نشط</option>
                                <option value="graduated">تخرج</option>
                                <option value="suspended">معلق</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="address" class="form-label text-end d-block">العنوان</label>
                            <textarea id="address" name="address" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label text-end d-block">رقم الهاتف *</label>
                            <input type="tel" id="phone" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label text-end d-block">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="parent_name" class="form-label text-end d-block">اسم ولي الأمر</label>
                            <input type="text" id="parent_name" name="parent_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="parent_phone" class="form-label text-end d-block">هاتف ولي الأمر</label>
                            <input type="tel" id="parent_phone" name="parent_phone" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="enrollment_date" class="form-label text-end d-block">تاريخ التسجيل</label>
                            <input type="date" id="enrollment_date" name="enrollment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?php echo APP_URL; ?>/modules/students/list.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <?php include_once '../../includes/sidebar.php'; ?>
</div>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>