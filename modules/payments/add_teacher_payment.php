<?php
// Ajouter un paiement professeur
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Récupérer l'ID du professeur s'il est fourni dans l'URL
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $teacher_id = clean($_POST['teacher_id']);
    $class_id = !empty($_POST['class_id']) ? clean($_POST['class_id']) : null;
    $amount = clean($_POST['amount']);
    $payment_date = clean($_POST['payment_date']);
    $payment_method = clean($_POST['payment_method']);
    $reference_number = clean($_POST['reference_number']);
    $description = clean($_POST['description']);
    $status = clean($_POST['status']);
    
    // Validation
    if (empty($teacher_id)) {
        $errors[] = 'يجب اختيار أستاذ';
    }
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'المبلغ يجب أن يكون رقمًا موجبًا';
    }
    
    if (empty($payment_date)) {
        $errors[] = 'تاريخ الدفع مطلوب';
    }
    
    if (empty($payment_method)) {
        $errors[] = 'طريقة الدفع مطلوبة';
    }
    
    // Si aucune erreur, ajouter le paiement
    if (empty($errors)) {
        $db = new Database();
        
        // Ajouter le paiement dans la base de données
        $db->query("INSERT INTO teacher_payments (teacher_id, class_id, amount, payment_date, payment_method, reference_number, description, status) 
                   VALUES (:teacher_id, :class_id, :amount, :payment_date, :payment_method, :reference_number, :description, :status)");
        
        $db->bind(':teacher_id', $teacher_id);
        $db->bind(':class_id', $class_id);
        $db->bind(':amount', $amount);
        $db->bind(':payment_date', $payment_date);
        $db->bind(':payment_method', $payment_method);
        $db->bind(':reference_number', $reference_number);
        $db->bind(':description', $description);
        $db->bind(':status', $status);
        
        if($db->execute()) {
            $payment_id = $db->lastInsertId();
            $success = true;
            
            // Rediriger vers la liste des paiements
            redirect(APP_URL . '/modules/payments/teacher_payments.php?success=add');
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة الدفعة';
        }
    }
}

// Récupérer la liste des professeurs
$db = new Database();
$db->query("SELECT id, first_name, last_name, employee_id FROM teachers WHERE status = 'active' ORDER BY first_name, last_name");
$teachers = $db->resultSet();

// Récupérer les classes du professeur s'il est sélectionné
$classes = [];
if ($teacher_id) {
    $db->query("SELECT cl.id, c.course_name, cl.class_name
               FROM classes cl
               JOIN courses c ON cl.course_id = c.id
               WHERE cl.teacher_id = :teacher_id AND cl.status IN ('scheduled', 'ongoing')");
    $db->bind(':teacher_id', $teacher_id);
    $classes = $db->resultSet();
    
    // Récupérer le pourcentage de commission du professeur
    $db->query("SELECT commission_percentage FROM teachers WHERE id = :teacher_id");
    $db->bind(':teacher_id', $teacher_id);
    $teacher = $db->single();
    $commission_percentage = $teacher['commission_percentage'];
}

// Inclure l'en-tête
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0 text-end">إضافة دفعة جديدة للأستاذ</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success text-end">
                        تمت إضافة الدفعة بنجاح!
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
                            <label for="teacher_id" class="form-label text-end d-block">الأستاذ *</label>
                            <select id="teacher_id" name="teacher_id" class="form-select" required>
                                <option value="">-- اختر أستاذ --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" <?php echo ($teacher_id == $teacher['id']) ? 'selected' : ''; ?>>
                                        <?php echo $teacher['first_name'] . ' ' . $teacher['last_name'] . ' (' . $teacher['employee_id'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="class_id" class="form-label text-end d-block">الدورة (اختياري)</label>
                            <select id="class_id" name="class_id" class="form-select">
                                <option value="">-- اختر دورة --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo $class['course_name'] . ' - ' . $class['class_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label text-end d-block">المبلغ *</label>
                            <input type="number" id="amount" name="amount" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_date" class="form-label text-end d-block">تاريخ الدفع *</label>
                            <input type="date" id="payment_date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label text-end d-block">طريقة الدفع *</label>
                            <select id="payment_method" name="payment_method" class="form-select" required>
                                <option value="">-- اختر طريقة الدفع --</option>
                                <option value="cash">نقدًا</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                                <option value="check">شيك</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="reference_number" class="form-label text-end d-block">رقم المرجع</label>
                            <input type="text" id="reference_number" name="reference_number" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status" class="form-label text-end d-block">الحالة *</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="completed" selected>مكتمل</option>
                                <option value="pending">قيد الانتظار</option>
                                <option value="cancelled">ملغى</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="description" class="form-label text-end d-block">ملاحظات</label>
                            <textarea id="description" name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?php echo APP_URL; ?>/modules/payments/teacher_payments.php" class="btn btn-secondary ms-2">
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

<script>
    // Script pour charger les classes et calculer le montant quand le professeur change
    document.addEventListener('DOMContentLoaded', function() {
        const teacherSelect = document.getElementById('teacher_id');
        const classSelect = document.getElementById('class_id');
        const amountInput = document.getElementById('amount');
        
        // Charger les classes quand le professeur change
        teacherSelect.addEventListener('change', function() {
            const teacherId = this.value;
            
            if (teacherId) {
                // Vider le select des classes
                classSelect.innerHTML = '<option value="">-- اختر دورة --</option>';
                amountInput.value = '';
                
                // Faire une requête AJAX pour obtenir les classes
                fetch(`<?php echo APP_URL; ?>/modules/api/get_teacher_classes.php?teacher_id=${teacherId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.classes && data.classes.length > 0) {
                            data.classes.forEach(cls => {
                                const option = document.createElement('option');
                                option.value = cls.id;
                                option.textContent = `${cls.course_name} - ${cls.class_name}`;
                                classSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                // Vider le select des classes
                classSelect.innerHTML = '<option value="">-- اختر دورة --</option>';
                amountInput.value = '';
            }
        });
        
        // Calculer le montant quand la classe change
        classSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const teacherId = teacherSelect.value;
            const classId = this.value;
            
            if (teacherId && classId) {
                // Récupérer automatiquement le montant suggéré
                fetch(`<?php echo APP_URL; ?>/modules/api/calculate_teacher_payment.php?teacher_id=${teacherId}&class_id=${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            amountInput.value = data.payment.toFixed(2);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                amountInput.value = '';
            }
        });
    });
</script>

<?php
// Inclure le pied de page
include_once '../../includes/footer.php';
?>