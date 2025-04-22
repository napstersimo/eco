<?php
// Formulaire d'édition d'étudiant
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect(APP_URL . '/modules/students/list.php');
}

$student_id = (int)$_GET['id'];

// Récupérer les informations de l'étudiant
$db = new Database();
$db->query("SELECT * FROM students WHERE id = :id");
$db->bind(':id', $student_id);
$student = $db->single();

// Vérifier si l'étudiant existe
if (!$student) {
    redirect(APP_URL . '/modules/students/list.php');
}

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
    $enrollment_date = !empty($_POST['enrollment_date']) ? clean($_POST['enrollment_date']) : null;
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
    
    // Vérifier si l'e-mail existe déjà (ignorer l'étudiant actuel)
    if (!empty($email)) {
        $db = new Database();
        $db->query("SELECT id FROM students WHERE email = :email AND id != :id");
        $db->bind(':email', $email);
        $db->bind(':id', $student_id);
        $existingStudent = $db->single();
        
        if ($existingStudent) {
            $errors[] = 'البريد الإلكتروني مستخدم بالفعل';
        }
    }
    
    // Si aucune erreur, mettre à jour l'étudiant
    if (empty($errors)) {
        $db = new Database();
        
        // Mettre à jour l'étudiant dans la base de données
        $db->query("UPDATE students 
                   SET first_name = :first_name, 
                       last_name = :last_name, 
                       date_of_birth = :date_of_birth, 
                       gender = :gender, 
                       address = :address, 
                       phone = :phone, 
                       email = :email, 
                       parent_name = :parent_name, 
                       parent_phone = :parent_phone, 
                       enrollment_date = :enrollment_date, 
                       status = :status 
                   WHERE id = :id");
        
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
        $db->bind(':id', $student_id);
        
        if($db->execute()) {
            $success = true;
        } else {
            $errors[] = 'حدث خطأ أثناء تحديث بيانات المتعلم';
        }
    }
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $student_id; ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-eye"></i> عرض الملف الشخصي
                    </a>
                </div>
                <h5 class="mb-0">تعديل بيانات المتعلم</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success text-end">
                        تم تحديث بيانات المتعلم بنجاح!
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
                            <label for="registration_number" class="form-label text-end d-block">رقم التسجيل</label>
                            <input type="text" id="registration_number" class="form-control" value="<?php echo $student['registration_number']; ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label text-end d-block">الإسم الأول *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo $student['first_name']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label text-end d-block">الإسم الأخير *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo $student['last_name']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="date_of_birth" class="form-label text-end d-block">تاريخ الميلاد</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?php echo $student['date_of_birth']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="gender" class="form-label text-end d-block">الجنس</label>
                            <select id="gender" name="gender" class="form-select">
                                <option value="">-- اختر --</option>
                                <option value="male" <?php echo ($student['gender'] == 'male') ? 'selected' : ''; ?>>ذكر</option>
                                <option value="female" <?php echo ($student['gender'] == 'female') ? 'selected' : ''; ?>>أنثى</option>
                                <option value="other" <?php echo ($student['gender'] == 'other') ? 'selected' : ''; ?>>آخر</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label text-end d-block">الحالة *</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active" <?php echo ($student['status'] == 'active') ? 'selected' : ''; ?>>نشط</option>
                                <option value="inactive" <?php echo ($student['status'] == 'inactive') ? 'selected' : ''; ?>>غير نشط</option>
                                <option value="graduated" <?php echo ($student['status'] == 'graduated') ? 'selected' : ''; ?>>تخرج</option>
                                <option value="suspended" <?php echo ($student['status'] == 'suspended') ? 'selected' : ''; ?>>معلق</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="address" class="form-label text-end d-block">العنوان</label>
                            <textarea id="address" name="address" class="form-control" rows="2"><?php echo $student['address']; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label text-end d-block">رقم الهاتف *</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $student['phone']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label text-end d-block">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo $student['email']; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="parent_name" class="form-label text-end d-block">اسم ولي الأمر</label>
                            <input type="text" id="parent_name" name="parent_name" class="form-control" value="<?php echo $student['parent_name']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="parent_phone" class="form-label text-end d-block">هاتف ولي الأمر</label>
                            <input type="tel" id="parent_phone" name="parent_phone" class="form-control" value="<?php echo $student['parent_phone']; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="enrollment_date" class="form-label text-end d-block">تاريخ التسجيل</label>
                            <input type="date" id="enrollment_date" name="enrollment_date" class="form-control" value="<?php echo $student['enrollment_date']; ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?php echo APP_URL; ?>/modules/students/list.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ التغييرات
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