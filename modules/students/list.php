/*** 1. Fichier modules/students/list.php ***/

<?php
// Liste des étudiants
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres de recherche
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : '';

// Requête de base
$query = "SELECT * FROM students WHERE 1=1";

// Ajouter les filtres
if (!empty($search)) {
    $query .= " AND (first_name LIKE :search OR last_name LIKE :search OR registration_number LIKE :search OR email LIKE :search)";
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

$students = $db->resultSet();

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">قائمة المتعلمين</h5>
                <a href="<?php echo APP_URL; ?>/modules/students/add.php" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> إضافة متعلم جديد
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
                                <option value="graduated" <?php echo ($status == 'graduated') ? 'selected' : ''; ?>>تخرج</option>
                                <option value="suspended" <?php echo ($status == 'suspended') ? 'selected' : ''; ?>>معلق</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> بحث
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Tableau des étudiants -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رقم التسجيل</th>
                                <th class="text-end">الإسم الكامل</th>
                                <th class="text-end">الهاتف</th>
                                <th class="text-end">البريد الإلكتروني</th>
                                <th class="text-end">تاريخ التسجيل</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td class="text-end"><?php echo $student['registration_number']; ?></td>
                                        <td class="text-end"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                        <td class="text-end"><?php echo $student['phone']; ?></td>
                                        <td class="text-end"><?php echo $student['email']; ?></td>
                                        <td class="text-end"><?php echo date('d/m/Y', strtotime($student['enrollment_date'])); ?></td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($student['status']) {
                                                case 'active':
                                                    $status_class = 'success';
                                                    $status_text = 'نشط';
                                                    break;
                                                case 'inactive':
                                                    $status_class = 'secondary';
                                                    $status_text = 'غير نشط';
                                                    break;
                                                case 'graduated':
                                                    $status_class = 'primary';
                                                    $status_text = 'تخرج';
                                                    break;
                                                case 'suspended':
                                                    $status_class = 'danger';
                                                    $status_text = 'معلق';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $student['id']; ?>" class="btn btn-info btn-sm" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/students/edit.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/payments/add_payment.php?student_id=<?php echo $student['id']; ?>" class="btn btn-success btn-sm" title="إضافة دفعة">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/enrollments/add.php?student_id=<?php echo $student['id']; ?>" class="btn btn-warning btn-sm" title="تسجيل في دورة">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $student['id']; ?>" title="حذف">
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
</div>

<script>
    // Script pour la suppression
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const studentId = this.getAttribute('data-id');
                confirmDeleteBtn.href = `<?php echo APP_URL; ?>/modules/students/delete.php?id=${studentId}`;
            });
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>>
    
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
                هل أنت متأكد من رغبتك في حذف هذا المتعلم؟ هذا الإجراء لا يمكن التراجع عنه.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">حذف</a>
            </div>
        </div>
    </div