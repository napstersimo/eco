<?php
// Fichier: modules/classes/list.php
// Liste des classes
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres de recherche
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$course_id = isset($_GET['course_id']) ? (int)clean($_GET['course_id']) : '';
$teacher_id = isset($_GET['teacher_id']) ? (int)clean($_GET['teacher_id']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : '';

// Requête de base
$query = "SELECT c.*, co.course_name, co.fee, 
          CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
          (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id AND e.status = 'active') as student_count
          FROM classes c
          JOIN courses co ON c.course_id = co.id
          LEFT JOIN teachers t ON c.teacher_id = t.id
          WHERE 1=1";

// Ajouter les filtres
if (!empty($search)) {
    $query .= " AND (c.class_name LIKE :search OR co.course_name LIKE :search OR t.first_name LIKE :search OR t.last_name LIKE :search)";
}
if (!empty($course_id)) {
    $query .= " AND c.course_id = :course_id";
}
if (!empty($teacher_id)) {
    $query .= " AND c.teacher_id = :teacher_id";
}
if (!empty($status)) {
    $query .= " AND c.status = :status";
}

// Ajouter l'ordre
$query .= " ORDER BY c.start_date DESC, co.course_name";

// Exécuter la requête
$db = new Database();
$db->query($query);

if (!empty($search)) {
    $db->bind(':search', "%$search%");
}
if (!empty($course_id)) {
    $db->bind(':course_id', $course_id);
}
if (!empty($teacher_id)) {
    $db->bind(':teacher_id', $teacher_id);
}
if (!empty($status)) {
    $db->bind(':status', $status);
}

$classes = $db->resultSet();

// Récupérer la liste des cours pour le filtre
$db->query("SELECT id, course_name FROM courses ORDER BY course_name");
$courses = $db->resultSet();

// Récupérer la liste des professeurs pour le filtre
$db->query("SELECT id, first_name, last_name FROM teachers WHERE status = 'active' ORDER BY first_name, last_name");
$teachers = $db->resultSet();

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">قائمة الفصول</h5>
                <a href="<?php echo APP_URL; ?>/modules/classes/add.php" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> إضافة فصل جديد
                </a>
            </div>
            <div class="card-body">
                <!-- Formulaire de recherche -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="البحث..." value="<?php echo $search; ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="course_id" class="form-select">
                                <option value="">-- جميع المواد --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>" <?php echo ($course_id == $course['id']) ? 'selected' : ''; ?>>
                                        <?php echo $course['course_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="teacher_id" class="form-select">
                                <option value="">-- جميع الأساتذة --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" <?php echo ($teacher_id == $teacher['id']) ? 'selected' : ''; ?>>
                                        <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">-- الحالة --</option>
                                <option value="scheduled" <?php echo ($status == 'scheduled') ? 'selected' : ''; ?>>مجدول</option>
                                <option value="ongoing" <?php echo ($status == 'ongoing') ? 'selected' : ''; ?>>جاري</option>
                                <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>مكتمل</option>
                                <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>ملغي</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-info w-100 text-white">
                                <i class="fas fa-search"></i> بحث
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Tableau des classes -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">المادة</th>
                                <th class="text-end">الفصل</th>
                                <th class="text-end">الأستاذ</th>
                                <th class="text-end">الفترة</th>
                                <th class="text-end">الطلاب</th>
                                <th class="text-end">الرسوم</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($classes) > 0): ?>
                                <?php foreach ($classes as $class): ?>
                                    <tr>
                                        <td class="text-end"><?php echo $class['course_name']; ?></td>
                                        <td class="text-end"><?php echo $class['class_name']; ?></td>
                                        <td class="text-end"><?php echo $class['teacher_name']; ?></td>
                                        <td class="text-end">
                                            <?php if ($class['start_date'] && $class['end_date']): ?>
                                                <?php echo date('d/m/Y', strtotime($class['start_date'])); ?> - <?php echo date('d/m/Y', strtotime($class['end_date'])); ?>
                                            <?php elseif ($class['start_date']): ?>
                                                من <?php echo date('d/m/Y', strtotime($class['start_date'])); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?php echo $class['student_count']; ?></td>
                                        <td class="text-end"><?php echo formatAmount($class['fee']); ?> د.م</td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($class['status']) {
                                                case 'scheduled':
                                                    $status_class = 'primary';
                                                    $status_text = 'مجدول';
                                                    break;
                                                case 'ongoing':
                                                    $status_class = 'success';
                                                    $status_text = 'جاري';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'info';
                                                    $status_text = 'مكتمل';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'danger';
                                                    $status_text = 'ملغي';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/classes/view.php?id=<?php echo $class['id']; ?>" class="btn btn-info btn-sm text-white" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/classes/edit.php?id=<?php echo $class['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/enrollments/list.php?class_id=<?php echo $class['id']; ?>" class="btn btn-success btn-sm" title="قائمة الطلاب">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/enrollments/add.php?class_id=<?php echo $class['id']; ?>" class="btn btn-warning btn-sm" title="إضافة طالب">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $class['id']; ?>" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">لا توجد سجلات</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <?php include_once '../../includes/sidebar.php'; ?>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-end">
                هل أنت متأكد من رغبتك في حذف هذا الفصل؟ سيتم حذف جميع التسجيلات المرتبطة به. هذا الإجراء لا يمكن التراجع عنه.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">حذف</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Script pour la suppression
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const classId = this.getAttribute('data-id');
                confirmDeleteBtn.href = `<?php echo APP_URL; ?>/modules/classes/delete.php?id=${classId}`;
            });
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>