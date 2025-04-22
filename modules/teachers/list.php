

<?php
// Liste des professeurs
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres de recherche
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : '';

// Requête de base
$query = "SELECT * FROM teachers WHERE 1=1";

// Ajouter les filtres
if (!empty($search)) {
    $query .= " AND (first_name LIKE :search OR last_name LIKE :search OR employee_id LIKE :search OR email LIKE :search)";
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

$teachers = $db->resultSet();

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">قائمة الأساتذة</h5>
                <a href="<?php echo APP_URL; ?>/modules/teachers/add.php" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> إضافة أستاذ جديد
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
                                <option value="on_leave" <?php echo ($status == 'on_leave') ? 'selected' : ''; ?>>في إجازة</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-search"></i> بحث
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Tableau des professeurs -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رقم الموظف</th>
                                <th class="text-end">الإسم الكامل</th>
                                <th class="text-end">الهاتف</th>
                                <th class="text-end">البريد الإلكتروني</th>
                                <th class="text-end">التخصص</th>
                                <th class="text-end">نسبة العمولة</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($teachers) > 0): ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td class="text-end"><?php echo $teacher['employee_id']; ?></td>
                                        <td class="text-end"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></td>
                                        <td class="text-end"><?php echo $teacher['phone']; ?></td>
                                        <td class="text-end"><?php echo $teacher['email']; ?></td>
                                        <td class="text-end"><?php echo $teacher['specialization']; ?></td>
                                        <td class="text-end"><?php echo $teacher['commission_percentage']; ?>%</td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($teacher['status']) {
                                                case 'active':
                                                    $status_class = 'success';
                                                    $status_text = 'نشط';
                                                    break;
                                                case 'inactive':
                                                    $status_class = 'secondary';
                                                    $status_text = 'غير نشط';
                                                    break;
                                                case 'on_leave':
                                                    $status_class = 'warning';
                                                    $status_text = 'في إجازة';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/teachers/view.php?id=<?php echo $teacher['id']; ?>" class="btn btn-info btn-sm" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/teachers/edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/payments/teacher_payment.php?teacher_id=<?php echo $teacher['id']; ?>" class="btn btn-success btn-sm" title="إضافة دفعة">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $teacher['id']; ?>" title="حذف">
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
                هل أنت متأكد من رغبتك في حذف هذا الأستاذ؟ هذا الإجراء لا يمكن التراجع عنه.
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
                const teacherId = this.getAttribute('data-id');
                confirmDeleteBtn.href = `<?php echo APP_URL; ?>/modules/teachers/delete.php?id=${teacherId}`;
            });
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>

/*** 1. Fichier modules/teachers/list.php ***/

<?php
// Liste des professeurs
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres de recherche
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : '';

// Requête de base
$query = "SELECT * FROM teachers WHERE 1=1";

// Ajouter les filtres
if (!empty($search)) {
    $query .= " AND (first_name LIKE :search OR last_name LIKE :search OR employee_id LIKE :search OR email LIKE :search)";
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

$teachers = $db->resultSet();

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">قائمة الأساتذة</h5>
                <a href="<?php echo APP_URL; ?>/modules/teachers/add.php" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> إضافة أستاذ جديد
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
                                <option value="on_leave" <?php echo ($status == 'on_leave') ? 'selected' : ''; ?>>في إجازة</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-search"></i> بحث
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Tableau des professeurs -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رقم الموظف</th>
                                <th class="text-end">الإسم الكامل</th>
                                <th class="text-end">الهاتف</th>
                                <th class="text-end">البريد الإلكتروني</th>
                                <th class="text-end">التخصص</th>
                                <th class="text-end">نسبة العمولة</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($teachers) > 0): ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td class="text-end"><?php echo $teacher['employee_id']; ?></td>
                                        <td class="text-end"><?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?></td>
                                        <td class="text-end"><?php echo $teacher['phone']; ?></td>
                                        <td class="text-end"><?php echo $teacher['email']; ?></td>
                                        <td class="text-end"><?php echo $teacher['specialization']; ?></td>
                                        <td class="text-end"><?php echo $teacher['commission_percentage']; ?>%</td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($teacher['status']) {
                                                case 'active':
                                                    $status_class = 'success';
                                                    $status_text = 'نشط';
                                                    break;
                                                case 'inactive':
                                                    $status_class = 'secondary';
                                                    $status_text = 'غير نشط';
                                                    break;
                                                case 'on_leave':
                                                    $status_class = 'warning';
                                                    $status_text = 'في إجازة';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/teachers/view.php?id=<?php echo $teacher['id']; ?>" class="btn btn-info btn-sm" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/teachers/edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>/modules/payments/teacher_payment.php?teacher_id=<?php echo $teacher['id']; ?>" class="btn btn-success btn-sm" title="إضافة دفعة">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $teacher['id']; ?>" title="حذف">
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
                هل أنت متأكد من رغبتك في حذف هذا الأستاذ؟ هذا الإجراء لا يمكن التراجع عنه.
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
                const teacherId = this.getAttribute('data-id');
                confirmDeleteBtn.href = `<?php echo APP_URL; ?>/modules/teachers/delete.php?id=${teacherId}`;
            });
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>

