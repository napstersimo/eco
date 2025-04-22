<?php
// Include database connection file
include 'includes/db_connect.php';
// Include authentication check
include 'includes/auth_check.php';
// Check if user has appropriate permissions
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'accountant')) {
    $_SESSION['error'] = "Vous n'avez pas l'autorisation d'accéder à cette page.";
    header("Location: dashboard.php");
    exit();
}

// Get teacher ID from URL if provided
$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;

// Initialize variables for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$payment_status = isset($_GET['status']) ? $_GET['status'] : '';

// Base query for payments
$query = "SELECT p.*, t.name as teacher_name, t.email as teacher_email 
          FROM payments p 
          INNER JOIN teachers t ON p.teacher_id = t.id 
          WHERE p.payment_type = 'teacher'";

// Add filters if provided
if ($teacher_id > 0) {
    $query .= " AND p.teacher_id = $teacher_id";
}
if (!empty($start_date)) {
    $query .= " AND p.payment_date >= '$start_date'";
}
if (!empty($end_date)) {
    $query .= " AND p.payment_date <= '$end_date'";
}
if (!empty($payment_status)) {
    $query .= " AND p.status = '$payment_status'";
}

// Add order by clause
$query .= " ORDER BY p.payment_date DESC";

// Execute query
$result = mysqli_query($conn, $query);

// Get all teachers for the dropdown filter
$teachers_query = "SELECT id, name FROM teachers ORDER BY name ASC";
$teachers_result = mysqli_query($conn, $teachers_query);

// Include header
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Paiements des Enseignants</h1>
        </div>
        
        <div class="section-body">
            <!-- Filter Form -->
            <div class="card">
                <div class="card-header">
                    <h4>Filtres</h4>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="row">
                        <div class="form-group col-md-3">
                            <label>Enseignant</label>
                            <select name="teacher_id" class="form-control">
                                <option value="">Tous les enseignants</option>
                                <?php while ($teacher = mysqli_fetch_assoc($teachers_result)) : ?>
                                    <option value="<?php echo $teacher['id']; ?>" <?php echo ($teacher_id == $teacher['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Date de début</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Date de fin</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Statut</label>
                            <select name="status" class="form-control">
                                <option value="">Tous</option>
                                <option value="payé" <?php echo ($payment_status == 'payé') ? 'selected' : ''; ?>>Payé</option>
                                <option value="en attente" <?php echo ($payment_status == 'en attente') ? 'selected' : ''; ?>>En attente</option>
                                <option value="annulé" <?php echo ($payment_status == 'annulé') ? 'selected' : ''; ?>>Annulé</option>
                            </select>
                        </div>
                        <div class="form-group col-md-1">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary form-control">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Payments Table -->
            <div class="card">
                <div class="card-header">
                    <h4>Liste des Paiements</h4>
                    <div class="card-header-action">
                        <a href="add_payment.php?type=teacher" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Ajouter un Paiement
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="payment-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Enseignant</th>
                                    <th>Montant</th>
                                    <th>Date de paiement</th>
                                    <th>Méthode</th>
                                    <th>Référence</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0) : ?>
                                    <?php while ($payment = mysqli_fetch_assoc($result)) : ?>
                                        <tr>
                                            <td><?php echo $payment['id']; ?></td>
                                            <td>
                                                <a href="teacher_profile.php?id=<?php echo $payment['teacher_id']; ?>">
                                                    <?php echo htmlspecialchars($payment['teacher_name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo number_format($payment['amount'], 2); ?> €</td>
                                            <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo ($payment['status'] == 'payé') ? 'badge-success' : 
                                                        (($payment['status'] == 'en attente') ? 'badge-warning' : 'badge-danger'); 
                                                ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="payment_details.php?id=<?php echo $payment['id']; ?>" class="btn btn-info btn-sm" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-primary btn-sm" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-danger btn-sm btn-delete" data-id="<?php echo $payment['id']; ?>" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php if ($payment['status'] == 'en attente') : ?>
                                                    <a href="mark_payment.php?id=<?php echo $payment['id']; ?>&status=payé" class="btn btn-success btn-sm" title="Marquer comme payé">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Aucun paiement trouvé</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Summary Card -->
            <?php
            // Get summary statistics
            $summary_query = "SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'payé' THEN amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = 'en attente' THEN amount ELSE 0 END) as pending_amount
                FROM payments 
                WHERE payment_type = 'teacher'";
                
            if ($teacher_id > 0) {
                $summary_query .= " AND teacher_id = $teacher_id";
            }
            if (!empty($start_date)) {
                $summary_query .= " AND payment_date >= '$start_date'";
            }
            if (!empty($end_date)) {
                $summary_query .= " AND payment_date <= '$end_date'";
            }
            
            $summary_result = mysqli_query($conn, $summary_query);
            $summary = mysqli_fetch_assoc($summary_result);
            ?>
            
            <div class="row">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-primary">
                            <i class="fas fa-money-bill-alt"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Total des Paiements</h4>
                            </div>
                            <div class="card-body">
                                <?php echo $summary['total_payments']; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-success">
                            <i class="fas fa-euro-sign"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Montant Total</h4>
                            </div>
                            <div class="card-body">
                                <?php echo number_format($summary['total_amount'], 2); ?> €
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-info">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Montant Payé</h4>
                            </div>
                            <div class="card-body">
                                <?php echo number_format($summary['paid_amount'], 2); ?> €
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card card-statistic-1">
                        <div class="card-icon bg-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>Montant En Attente</h4>
                            </div>
                            <div class="card-body">
                                <?php echo number_format($summary['pending_amount'], 2); ?> €
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Êtes-vous sûr de vouloir supprimer ce paiement ? Cette action est irréversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Supprimer</a>
            </div>
        </div>
    </div>
</div>

<!-- Page Specific JS -->
<script>
    $(document).ready(function() {
        // Initialize datatable
        $('#payment-table').dataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/French.json"
            }
        });
        
        // Handle delete button click
        $('.btn-delete').click(function() {
            var paymentId = $(this).data('id');
            $('#confirmDelete').attr('href', 'delete_payment.php?id=' + paymentId);
            $('#deleteModal').modal('show');
        });
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>