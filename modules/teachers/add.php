

<?php
// Formulaire d'ajout de professeur
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
    $specialization = clean($_POST['specialization']);
    $qualification = clean($_POST['qualification']);
    $joining_date = !empty($_POST['joining_date']) ? clean($_POST['joining_date']) : date('Y-m-d');
    $commission_percentage = clean($_POST['commission_percentage']);
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
    
    if (empty($specialization)) {
        $errors[] = 'التخصص مطلوب';
    }
    
    // Vérifier si l'e-mail existe déjà
    if (!empty($email)) {
        $db = new Database();
        $db->query("SELECT id FROM teachers WHERE email = :email");
        $db->bind(':email', $email);
        $teacher = $db->single();
        
        if ($teacher) {
            $errors[] = 'البريد الإلكتروني مستخدم بالفعل';
        }
    }
    
    // Vérifier la commission
    if (!is_numeric($commission_percentage) || $commission_percentage < 0 || $commission_percentage > 100) {
        $errors[] = 'نسبة العمولة يجب أن تكون بين 0 و 100';
    }
    
    // Si aucune erreur, ajouter le professeur
    if (empty($errors)) {
        $db = new Database();
        
        // Générer un ID d'employé
        $employee_id = generateEmployeeId();
        
        // Ajouter le professeur dans la base de données
        $db->query("INSERT INTO teachers (employee_id, first_name, last_name, date_of_birth, gender, address, phone, email, specialization, qualification, joining_date, commission_percentage, status) 
                   VALUES (:employee_id, :first_name, :last_name, :date_of_birth, :gender, :address, :phone, :email, :specialization, :qualification, :joining_date, :commission_percentage, :status)");
        
        $db->bind(':employee_id', $employee_id);
        $db->bind(':first_name', $first_name);
        $db->bind(':last_name', $last_name);
        $db->bind(':date_of_birth', $date_of_birth);
        $db->bind(':gender', $gender);
        $db->bind(':address', $address);
        $db->bind(':phone', $phone);
        $db->bind(':email', $email);
        $db->bind(':specialization', $specialization);
        $db->bind(':qualification', $qualification);
        $db->bind(':joining_date', $joining_date);
        $db->bind(':commission_percentage', $commission_percentage);
        $db->bind(':status', $status);
        
        if($db->execute()) {
            $teacher_id = $db->lastInsertId();
            $success = true;
            
            // Rediriger vers la page de détails du professeur
            redirect(APP_URL . '/modules/teachers/view.php?id=' . $teacher_id . '&success=add');
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة الأستاذ';
        }
    }
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0 text-end">إضافة أستاذ جديد</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success text-end">
                        تمت إضافة الأستاذ بنجاح!
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
                                <option value="on_leave">في إجازة</option>
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
                            <label for="specialization" class="form-label text-end d-block">التخصص *</label>
                            <input type="text" id="specialization" name="specialization" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="qualification" class="form-label text-end d-block">المؤهل العلمي</label>
                            <input type="text" id="qualification" name="qualification" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="joining_date" class="form-label text-end d-block">تاريخ التعيين</label>
                            <input type="date" id="joining_date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="commission_percentage" class="form-label text-end d-block">نسبة العمولة (%)</label>
                            <input type="number" id="commission_percentage" name="commission_percentage" class="form-control" min="0" max="100" step="0.01" value="0">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?php echo APP_URL; ?>/modules/teachers/list.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                        <button type="submit" class="btn btn-success">
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