

<?php
// Liste des paiements aux professeurs
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Filtres de recherche
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : '';
$start_date = isset($_GET['start_date']) ? clean($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? clean($_GET['end_date']) : '';

// Requête de base
$query = "SELECT tp.*, t.first_name, t.last_name, t.employee_id 
          FROM teacher_payments tp
          JOIN teachers t ON tp.teacher_id = t.id
          WHERE 1=1";

// Ajouter les filtres
if (!empty($search)) {
    $query .= " AND (t.first_name LIKE :search OR t.last_name LIKE :search OR t.employee_id LIKE :search)";
}
if (!empty($status)) {
    $query .= " AND tp.status = :status";
}
if (!empty($start_date)) {
    $query .= " AND tp.payment_date >= :start_date";
}
if (!empty($end_date)) {
    $query .= " AND tp.payment_date <= :end_date";
}

// Ajouter l'ordre
$query .= " ORDER BY tp.payment_date DESC";

// Exécuter la requête
$db = new Database();
$db->query($query);

if (!empty($search)) {
    $db->bind(':search', "%$search%");
}
if (!empty($status)) {
    $db->bind(':status', $status);
}
if (!empty($start_date)) {
    $db->bind(':start_date', $start_date);
}
if (!empty($end_date)) {
    $db->bind(':end_date', $end_date);
}

$payments = $db->resultSet();

// Calculer le total des paiements affichés
$total_amount = 0;
foreach ($payments as $payment) {
    if ($payment['status'] == 'completed') {
        $total_amount += $payment['amount'];
    }
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">دفعات الأساتذة</h5>
                <a href="<?php echo APP_URL; ?>/modules/payments/add_teacher_payment.php" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> إضافة دفعة جديدة
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
                            <select name="status" class="form-select">
                                <option value="">-- الحالة --</option>
                                <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>قيد الانتظار</option>
                                <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>مكتمل</option>
                                <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>ملغى</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="start_date" class="form-control" placeholder="من تاريخ" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="end_date" class="form-control" placeholder="إلى تاريخ" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-search"></i> بحث
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Résumé des paiements -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-end">إجمالي المدفوعات:</h5>
                                <h3 class="text-end text-success"><?php echo formatAmount($total_amount); ?> د.م</h3>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-end">عدد المعاملات:</h5>
                                <h3 class="text-end"><?php echo count($payments); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des paiements -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-end">رقم المعاملة</th>
                                <th class="text-end">الأستاذ</th>
                                <th class="text-end">المبلغ</th>
                                <th class="text-end">تاريخ الدفع</th>
                                <th class="text-end">طريقة الدفع</th>
                                <th class="text-end">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($payments) > 0): ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="text-end"><?php echo $payment['id']; ?></td>
                                        <td class="text-end">
                                            <a href="<?php echo APP_URL; ?>/modules/teachers/view.php?id=<?php echo $payment['teacher_id']; ?>">
                                                <?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?>
                                            </a>
                                            <small class="d-block text-muted"><?php echo $payment['employee_id']; ?></small>
                                        </td>
                                        <td class="text-end"><?php echo formatAmount($payment['amount']); ?> د.م</td>
                                        <td class="text-end"><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                        <td class="text-end">
                                            <?php 
                                            $payment_method = '';
                                            switch($payment['payment_method']) {
                                                case 'cash':
                                                    $payment_method = 'نقدًا';
                                                    break;
                                                case 'bank_transfer':
                                                    $payment_method = 'تحويل بنكي';
                                                    break;
                                                case 'check':
                                                    $payment_method = 'شيك';
                                                    break;
                                                case 'other':
                                                    $payment_method = 'أخرى';
                                                    break;
                                            }
                                            echo $payment_method;
                                            ?>
                                        </td>
                                        <td class="text-end">
                                            <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch($payment['status']) {
                                                case 'pending':
                                                    $status_class = 'warning';
                                                    $status_text = 'قيد الانتظار';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'success';
                                                    $status_text = 'مكتمل';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'danger';
                                                    $status_text = 'ملغى';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo APP_URL; ?>/modules/payments/view_teacher_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-info btn-sm text-white" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($payment['status'] == 'pending'): ?>
                                                <a href="<?php echo APP_URL; ?>/modules/payments/edit_teacher_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $payment['id']; ?>" title="حذف">
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
                هل أنت متأكد من رغبتك في حذف هذه الدفعة؟ هذا الإجراء لا يمكن التراجع عنه.
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
                const paymentId = this.getAttribute('data-id');
                confirmDeleteBtn.href = `<?php echo APP_URL; ?>/modules/payments/delete_teacher_payment.php?id=${paymentId}`;
            });
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>

