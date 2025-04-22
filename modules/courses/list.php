

<?php
// Liste des cours
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres de recherche
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : '';

// Requête de base
$query = "SELECT * FROM courses WHERE 1=1";

// Ajouter les filtres
if (!empty($search)) {
    $query .= " AND (course_name LIKE :search OR course_code LIKE :search OR description LIKE :search)";
}
if (!empty($status)) {
    $query .= " AND status = :status";
}

// Ajouter l'ordre
$query .= " ORDER BY created_at DESC";

// Exécuter la requête
$db = new Database();
$db->query($query);

if (!empty($search)) {
    $db->bind(':search', "%$search%");
}
if (!empty($status)) {
    $db->bind(':status', $status);
}

$courses = $db->resultSet();

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">قائمة المواد</h5>
                <a href="<?php echo APP_URL; ?>/modules/courses/add.php" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> إضافة مادة جديدة
                </a>
            </div>
            <div class="card-body">
                <!-- Formulaire de recherche -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="البحث..." value="<?php echo $search; ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="status" class="form-select">
                                <option value="">-- الحالة --</option>
                                <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>نشط</option>
                                <option value="inactive" <?php echo ($status == 'inactive') ? 'selected' : ''; ?>>غير نشط</option>
                                <option value="upcoming" <?php echo ($status == 'upcoming') ? 'selected' : ''; ?>>قادم</option>
                                <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>مكتمل</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-info w-100 text-white">
                                <i class="fas fa-search"></i> بحث
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Tableau des cours -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رمز المادة</th>
                                <th class="text-end">اسم المادة</th>
                                <th class="text-end">المدة (ساعات)</th>
                                <th class="text-end">الرسوم</th>
                                <th class="text-end">الحد الأقصى للطلاب</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($courses) > 0): ?>
                                <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td class="text-end"><?php echo $course['course_code']; ?></td>
                                        <td class="text-end"><?php echo $course['course_name']; ?></td>
                                        <td class="text-end"><?php echo $course['duration']; ?></td>
                                        <td class="text-end"><?php echo formatAmount($course['fee']); ?> د.م</td>
                                        <td class="text-end"><?php echo $course['max_students']; ?></td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($course['status']) {
                                                case 'active':
                                                    $status_class = 'success';
                                                    $status_text = 'نشط';
                                                    break;
                                                case 'inactive':
                                                    $status_class = 'secondary';
                                                    $status_text = 'غير نشط';
                                                    break;
                                                case 'upcoming':
                                                    $status_class = 'info';
                                                    $status_text = 'قادم';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'primary';
                                                    $status_text = 'مكتمل';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/courses/view.php?id=<?php echo $course['id']; ?>" class="btn btn-info btn-sm text-white" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/courses/edit.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/classes/add.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success btn-sm" title="إضافة فصل">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $course['id']; ?>" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد سجلات</td>
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
                هل أنت متأكد من رغبتك في حذف هذه المادة؟ هذا الإجراء لا يمكن التراجع عنه.
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
                const courseId = this.getAttribute('data-id');
                confirmDeleteBtn.href = `<?php echo APP_URL; ?>/modules/courses/delete.php?id=${courseId}`;
            });
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>

