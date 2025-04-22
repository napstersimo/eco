<?php
// Fichier: modules/enrollments/list.php
// Liste des inscriptions (étudiants inscrits à un cours)
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres de recherche
$class_id = isset($_GET['class_id']) ? (int)clean($_GET['class_id']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : '';
$search = isset($_GET['search']) ? clean($_GET['search']) : '';

// Vérifier si un succès est signalé
$success_message = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'add') {
        $success_message = 'تم تسجيل المتعلم بنجاح!';
    } elseif ($_GET['success'] == 'update') {
        $success_message = 'تم تحديث التسجيل بنجاح!';
    } elseif ($_GET['success'] == 'delete') {
        $success_message = 'تم حذف التسجيل بنجاح!';
    }
}

// Récupérer la liste des classes pour le filtre
$db = new Database();
$db->query("SELECT cl.id, cl.class_name, c.course_name 
            FROM classes cl
            JOIN courses c ON cl.course_id = c.id
            ORDER BY c.course_name, cl.class_name");
$classes = $db->resultSet();

// Construire la requête pour les inscriptions
$query = "SELECT e.*, 
          s.first_name, s.last_name, s.registration_number, s.phone, s.email,
          cl.class_name, c.course_name, c.fee,
          (SELECT SUM(amount) FROM student_payments sp WHERE sp.enrollment_id = e.id AND sp.status = 'completed') as paid_amount
          FROM enrollments e
          JOIN students s ON e.student_id = s.id
          JOIN classes cl ON e.class_id = cl.id
          JOIN courses c ON cl.course_id = c.id
          WHERE 1=1";

// Ajouter les filtres
if (!empty($class_id)) {
    $query .= " AND e.class_id = :class_id";
}
if (!empty($status)) {
    $query .= " AND e.status = :status";
}
if (!empty($search)) {
    $query .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.registration_number LIKE :search)";
}

// Ajouter l'ordre
$query .= " ORDER BY s.first_name, s.last_name";

// Exécuter la requête
$db->query($query);

if (!empty($class_id)) {
    $db->bind(':class_id', $class_id);
}
if (!empty($status)) {
    $db->bind(':status', $status);
}
if (!empty($search)) {
    $db->bind(':search', "%$search%");
}

$enrollments = $db->resultSet();

// Récupérer les détails de la classe si elle est spécifiée
$class = null;
if ($class_id) {
    $db->query("SELECT cl.*, c.course_name, c.fee, c.max_students,
               CONCAT(t.first_name, ' ', t.last_name) as teacher_name
               FROM classes cl
               JOIN courses c ON cl.course_id = c.id
               LEFT JOIN teachers t ON cl.teacher_id = t.id
               WHERE cl.id = :id");
    $db->bind(':id', $class_id);
    $class = $db->single();
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">قائمة التسجيلات</h5>
                <?php if ($class_id): ?>
                <a href="<?php echo APP_URL; ?>/modules/enrollments/add.php?class_id=<?php echo $class_id; ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> تسجيل متعلم جديد
                </a>
                <?php else: ?>
                <a href="<?php echo APP_URL; ?>/modules/enrollments/add.php" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> تسجيل متعلم جديد
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success m-3 text-end">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="card-body">
                <!-- Formulaire de recherche -->
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <select name="class_id" class="form-select">
                                <option value="">-- جميع الفصول --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($class_id == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo $c['course_name'] . ' - ' . $c['class_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">-- الحالة --</option>
                                <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>نشط</option>
                                <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>مكتمل</option>
                                <option value="dropped" <?php echo ($status == 'dropped') ? 'selected' : ''; ?>>انسحب</option>
                                <option value="failed" <?php echo ($status == 'failed') ? 'selected' : ''; ?>>راسب</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="البحث..." value="<?php echo $search; ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-search"></i> بحث
                            </button>
                        </div>
                    </div>
                </form>

                <?php if ($class): ?>
                <div class="alert alert-info text-end mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>معلومات الفصل:</h5>
                            <p><strong>المادة:</strong> <?php echo $class['course_name']; ?></p>
                            <p><strong>الفصل:</strong> <?php echo $class['class_name']; ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>الأستاذ:</strong> <?php echo $class['teacher_name']; ?></p>
                            <p><strong>الرسوم:</strong> <?php echo formatAmount($class['fee']); ?> د.م</p>
                            <p><strong>تاريخ البداية:</strong> <?php echo $class['start_date'] ? date('d/m/Y', strtotime($class['start_date'])) : 'غير محدد'; ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>الحالة:</strong> 
                                <?php 
                                switch($class['status']) {
                                    case 'scheduled': echo 'مجدول'; break;
                                    case 'ongoing': echo 'جاري'; break;
                                    case 'completed': echo 'مكتمل'; break;
                                    case 'cancelled': echo 'ملغي'; break;
                                    default: echo $class['status'];
                                }
                                ?>
                            </p>
                            <p><strong>عدد الطلاب:</strong> <?php echo count($enrollments); ?><?php echo $class['max_students'] ? '/' . $class['max_students'] : ''; ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tableau des inscriptions -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رقم التسجيل</th>
                                <th class="text-end">اسم المتعلم</th>
                                <th class="text-end">الهاتف</th>
                                <?php if (!$class_id): ?><th class="text-end">الفصل</th><?php endif; ?>
                                <th class="text-end">تاريخ التسجيل</th>
                                <th class="text-end">الرسوم</th>
                                <th class="text-end">المدفوع</th>
                                <th class="text-end">المتبقي</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($enrollments) > 0): ?>
                                <?php foreach ($enrollments as $enrollment): ?>
                                    <?php 
                                    $paid = $enrollment['paid_amount'] ? $enrollment['paid_amount'] : 0;
                                    $remaining = $enrollment['fee'] - $paid;
                                    ?>
                                    <tr>
                                        <td class="text-end"><?php echo $enrollment['registration_number']; ?></td>
                                        <td class="text-end">
                                            <a href="<?php echo APP_URL; ?>/modules/students/view.php?id=<?php echo $enrollment['student_id']; ?>">
                                                <?php echo $enrollment['first_name'] . ' ' . $enrollment['last_name']; ?>
                                            </a>
                                        </td>
                                        <td class="text-end"><?php echo $enrollment['phone']; ?></td>
                                        <?php if (!$class_id): ?>
                                        <td class="text-end">
                                            <a href="<?php echo APP_URL; ?>/modules/enrollments/list.php?class_id=<?php echo $enrollment['class_id']; ?>">
                                                <?php echo $enrollment['course_name'] . ' - ' . $enrollment['class_name']; ?>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                        <td class="text-end"><?php echo date('d/m/Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                        <td class="text-end"><?php echo formatAmount($enrollment['fee']); ?> د.م</td>
                                        <td class="text-end text-success"><?php echo formatAmount($paid); ?> د.م</td>
                                        <td class="text-end <?php echo $remaining > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo formatAmount($remaining); ?> د.م</td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($enrollment['status']) {
                                                case 'active':
                                                    $status_class = 'success';
                                                    $status_text = 'نشط';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'primary';
                                                    $status_text = 'مكتمل';
                                                    break;
                                                case 'dropped':
                                                    $status_class = 'warning';
                                                    $status_text = 'انسحب';
                                                    break;
                                                case 'failed':
                                                    $status_class = 'danger';
                                                    $status_text = 'راسب';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/enrollments/edit.php?id=<?php echo $enrollment['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($remaining > 0): ?>
                                            <a href="<?php echo APP_URL; ?>/modules/payments/add_payment.php?student_id=<?php echo $enrollment['student_id']; ?>&enrollment_id=<?php echo $enrollment['id']; ?>" class="btn btn-success btn-sm" title="إضافة دفعة">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $enrollment['id']; ?>" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo (!$class_id) ? 10 : 9; ?>" class="text-center">لا توجد سجلات</td>
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
                هل أنت متأكد من رغبتك في حذف هذا التسجيل؟ هذا الإجراء لا يمكن التراجع عنه.
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
                const enrollmentId = this.getAttribute('data-id');
                confirmDeleteBtn.href = `<?php echo APP_URL; ?>/modules/enrollments/delete.php?id=${enrollmentId}`;
            });
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>