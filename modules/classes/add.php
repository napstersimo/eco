<?php
// Fichier: modules/classes/add.php
// Création d'une classe avec assignation d'un professeur
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Récupérer l'ID du cours s'il est fourni
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $course_id = clean($_POST['course_id']);
    $teacher_id = clean($_POST['teacher_id']);
    $class_name = clean($_POST['class_name']);
    $start_date = !empty($_POST['start_date']) ? clean($_POST['start_date']) : null;
    $end_date = !empty($_POST['end_date']) ? clean($_POST['end_date']) : null;
    $schedule = clean($_POST['schedule']);
    $room_number = clean($_POST['room_number']);
    $status = clean($_POST['status']);
    
    // Validation
    if (empty($course_id)) {
        $errors[] = 'يجب اختيار مادة';
    }
    
    if (empty($teacher_id)) {
        $errors[] = 'يجب اختيار أستاذ';
    }
    
    if (empty($class_name)) {
        $errors[] = 'اسم الفصل مطلوب';
    }
    
    // Si aucune erreur, ajouter la classe
    if (empty($errors)) {
        $db = new Database();
        
        // Ajouter la classe dans la base de données
        $db->query("INSERT INTO classes (course_id, teacher_id, class_name, start_date, end_date, schedule, room_number, status) 
                   VALUES (:course_id, :teacher_id, :class_name, :start_date, :end_date, :schedule, :room_number, :status)");
        
        $db->bind(':course_id', $course_id);
        $db->bind(':teacher_id', $teacher_id);
        $db->bind(':class_name', $class_name);
        $db->bind(':start_date', $start_date);
        $db->bind(':end_date', $end_date);
        $db->bind(':schedule', $schedule);
        $db->bind(':room_number', $room_number);
        $db->bind(':status', $status);
        
        if($db->execute()) {
            $class_id = $db->lastInsertId();
            $success = true;
            
            // Rediriger vers la liste des classes
            redirect(APP_URL . '/modules/classes/list.php?success=add');
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة الفصل';
        }
    }
}

// Récupérer la liste des cours
$db = new Database();
$db->query("SELECT id, course_name FROM courses WHERE status IN ('active', 'upcoming') ORDER BY course_name");
$courses = $db->resultSet();

// Récupérer la liste des professeurs
$db->query("SELECT id, first_name, last_name, employee_id FROM teachers WHERE status = 'active' ORDER BY first_name, last_name");
$teachers = $db->resultSet();

// Récupérer les détails du cours s'il est sélectionné
$course = null;
if ($course_id) {
    $db->query("SELECT * FROM courses WHERE id = :course_id");
    $db->bind(':course_id', $course_id);
    $course = $db->single();
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0 text-end">إضافة فصل جديد</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success text-end">
                        تمت إضافة الفصل بنجاح!
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
                            <label for="course_id" class="form-label text-end d-block">المادة *</label>
                            <select id="course_id" name="course_id" class="form-select" required>
                                <option value="">-- اختر مادة --</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($course_id == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo $c['course_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="teacher_id" class="form-label text-end d-block">الأستاذ *</label>
                            <select id="teacher_id" name="teacher_id" class="form-select" required>
                                <option value="">-- اختر أستاذ --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo $teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['employee_id'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="class_name" class="form-label text-end d-block">اسم الفصل *</label>
                            <input type="text" id="class_name" name="class_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="room_number" class="form-label text-end d-block">رقم القاعة</label>
                            <input type="text" id="room_number" name="room_number" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label text-end d-block">تاريخ البداية</label>
                            <input type="date" id="start_date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label text-end d-block">تاريخ النهاية</label>
                            <input type="date" id="end_date" name="end_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label text-end d-block">الحالة *</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="scheduled" selected>مجدول</option>
                                <option value="ongoing">جاري</option>
                                <option value="completed">مكتمل</option>
                                <option value="cancelled">ملغي</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="schedule" class="form-label text-end d-block">جدول الحصص</label>
                            <textarea id="schedule" name="schedule" class="form-control" rows="3" placeholder="مثال: الاثنين والأربعاء من 4 إلى 6 مساءً"></textarea>
                        </div>
                    </div>
                    
                    <?php if ($course): ?>
                    <div class="alert alert-info text-end">
                        <p><strong>معلومات المادة:</strong></p>
                        <p>السعر: <?php echo formatAmount($course['fee']); ?> د.م</p>
                        <p>المدة: <?php echo $course['duration']; ?> ساعة</p>
                        <?php if ($course['max_students']): ?>
                        <p>الحد الأقصى للطلاب: <?php echo $course['max_students']; ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?php echo APP_URL; ?>/modules/classes/list.php" class="btn btn-secondary ms-2">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const courseSelect = document.getElementById('course_id');
        
        courseSelect.addEventListener('change', function() {
            if (this.value) {
                window.location.href = `<?php echo APP_URL; ?>/modules/classes/add.php?course_id=${this.value}`;
            }
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>