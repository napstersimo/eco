

<?php
// Formulaire d'ajout de cours
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $course_name = clean($_POST['course_name']);
    $course_code = clean($_POST['course_code']);
    $description = clean($_POST['description']);
    $duration = clean($_POST['duration']);
    $fee = clean($_POST['fee']);
    $max_students = clean($_POST['max_students']);
    $status = clean($_POST['status']);
    
    // Validation
    if (empty($course_name)) {
        $errors[] = 'اسم المادة مطلوب';
    }
    
    if (empty($course_code)) {
        $errors[] = 'رمز المادة مطلوب';
    }
    
    // Vérifier si le code du cours existe déjà
    $db = new Database();
    $db->query("SELECT id FROM courses WHERE course_code = :course_code");
    $db->bind(':course_code', $course_code);
    $existing_course = $db->single();
    
    if ($existing_course) {
        $errors[] = 'رمز المادة مستخدم بالفعل';
    }
    
    if (!is_numeric($duration) || $duration <= 0) {
        $errors[] = 'المدة يجب أن تكون رقمًا موجبًا';
    }
    
    if (!is_numeric($fee) || $fee < 0) {
        $errors[] = 'الرسوم يجب أن تكون رقمًا موجبًا أو صفر';
    }
    
    if (!empty($max_students) && (!is_numeric($max_students) || $max_students <= 0)) {
        $errors[] = 'الحد الأقصى للطلاب يجب أن يكون رقمًا موجبًا';
    }
    
    // Si aucune erreur, ajouter le cours
    if (empty($errors)) {
        $db = new Database();
        
        // Ajouter le cours dans la base de données
        $db->query("INSERT INTO courses (course_code, course_name, description, duration, fee, max_students, status) 
                   VALUES (:course_code, :course_name, :description, :duration, :fee, :max_students, :status)");
        
        $db->bind(':course_code', $course_code);
        $db->bind(':course_name', $course_name);
        $db->bind(':description', $description);
        $db->bind(':duration', $duration);
        $db->bind(':fee', $fee);
        $db->bind(':max_students', $max_students);
        $db->bind(':status', $status);
        
        if($db->execute()) {
            $course_id = $db->lastInsertId();
            $success = true;
            
            // Rediriger vers la page de détails du cours
            redirect(APP_URL . '/modules/courses/view.php?id=' . $course_id . '&success=add');
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة المادة';
        }
    }
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0 text-end">إضافة مادة جديدة</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success text-end">
                        تمت إضافة المادة بنجاح!
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
                            <label for="course_name" class="form-label text-end d-block">اسم المادة *</label>
                            <input type="text" id="course_name" name="course_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="course_code" class="form-label text-end d-block">رمز المادة *</label>
                            <input type="text" id="course_code" name="course_code" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="description" class="form-label text-end d-block">وصف المادة</label>
                            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="duration" class="form-label text-end d-block">المدة (ساعات) *</label>
                            <input type="number" id="duration" name="duration" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label for="fee" class="form-label text-end d-block">الرسوم *</label>
                            <input type="number" id="fee" name="fee" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label for="max_students" class="form-label text-end d-block">الحد الأقصى للطلاب</label>
                            <input type="number" id="max_students" name="max_students" class="form-control" min="1">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label text-end d-block">الحالة *</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active" selected>نشط</option>
                                <option value="inactive">غير نشط</option>
                                <option value="upcoming">قادم</option>
                                <option value="completed">مكتمل</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?php echo APP_URL; ?>/modules/courses/list.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                        <button type="submit" class="btn btn-info text-white">
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