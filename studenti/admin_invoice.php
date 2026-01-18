<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$intervention_id = isset($_GET['intervention_id']) ? (int)$_GET['intervention_id'] : 0;
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// If called with invoice_id, resolve associated intervention and verify admin ownership
if ($invoice_id && empty($intervention_id)) {
    $qi = $conn->prepare("SELECT intervention_id FROM invoices WHERE id = ? AND admin_id = ? LIMIT 1");
    $qi->bind_param('ii', $invoice_id, $admin_id);
    $qi->execute();
    $rqi = $qi->get_result();
    if ($rqi && $rqi->num_rows) {
        $rowi = $rqi->fetch_assoc();
        $intervention_id = (int)$rowi['intervention_id'];
    } else {
        die('Factura inexistentă sau acces interzis.');
    }
}
$msg = '';

// Check if intervention exists and belongs to admin's client
$stmt = $conn->prepare("
    SELECT i.*, c.full_name, c.email, c.address, e.model, e.serial_number
    FROM interventions i
    JOIN clients c ON i.client_id = c.id
    JOIN equipment e ON i.equipment_id = e.id
    WHERE i.id = ? AND c.admin_id = ?
");
$stmt->bind_param("ii", $intervention_id, $admin_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Intervenție inexistentă sau acces interzis.");
}

$intervention = $res->fetch_assoc();

// Check if invoice already exists for this admin
$chk = $conn->prepare("SELECT * FROM invoices WHERE intervention_id = ? AND admin_id = ? LIMIT 1");
$chk->bind_param("ii", $intervention_id, $admin_id);
$chk->execute();
$existing_invoice = $chk->get_result()->fetch_assoc();

// Redirect generation to confirmation page so admin can set parts/labor/TVA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoice'])) {
    header('Location: admin_invoice_confirm.php?intervention_id=' . $intervention_id);
    exit();
}

// Get admin details for header
$adm_stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$adm_stmt->bind_param("i", $admin_id);
$adm_stmt->execute();
$admin_data = $adm_stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Factură Intervenție #<?php echo $intervention_id; ?></title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body>

<div class="no-print no-print-wrapper">
    <a href="admin_interventions.php" class="btn btn-secondary">&laquo; Înapoi la Intervenții</a>
    <button onclick="window.print()" class="btn btn-primary">Printează / PDF</button>
</div>

<div class="invoice-box">
    <?php if($existing_invoice): ?>
        <div class="form-alert alert-success no-print">
            Această factură a fost generată pe <?php echo date('d.m.Y', strtotime($existing_invoice['invoice_date'])); ?>.
        </div>
    <?php endif; ?>

    <div class="invoice-header">
        <div>
            <?php if(!empty($admin_data['logo_path'])): ?>
                <img src="<?php echo htmlspecialchars($admin_data['logo_path']); ?>" class="invoice-logo">
                <br>
            <?php else: ?>
                <div class="invoice-title"><?php echo htmlspecialchars($admin_data['service_name']); ?></div>
            <?php endif; ?>
            <p>
                CUI: <?php echo htmlspecialchars($admin_data['cui_cif']); ?><br>
                Email: <?php echo htmlspecialchars($admin_data['email']); ?>
            </p>
        </div>
        <div class="invoice-meta">
            <h2>FACTURĂ</h2>
            <p>
                Număr: <strong><?php echo $existing_invoice ? $existing_invoice['invoice_number'] : 'DRAFT'; ?></strong><br>
                Data: <?php echo $existing_invoice ? date('d.m.Y', strtotime($existing_invoice['invoice_date'])) : date('d.m.Y'); ?>
            </p>
        </div>
    </div>

    <div class="invoice-body">
        <p><strong>Cumpărător:</strong></p>
        <p>
            <?php echo htmlspecialchars($intervention['full_name']); ?><br>
            <?php echo htmlspecialchars($intervention['address']); ?><br>
            Email: <?php echo htmlspecialchars($intervention['email']); ?>
        </p>

        <p class="invoice-ref"><strong>Referință Vehicul:</strong> <?php echo htmlspecialchars($intervention['model'] . ' (' . $intervention['serial_number'] . ')'); ?></p>

        <table class="table-inv">
            <thead>
                <tr>
                    <th>Descriere</th>
                    <th class="w-150 text-right">Valoare (RON)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Servicii Service Auto (Intervenție #<?php echo $intervention_id; ?>)<br>
                        <small>Diagnostic: <?php echo htmlspecialchars($intervention['problem_description']); ?></small>
                    </td>
                    <td class="text-right">
                        <?php
                            // Pre-fill amount with labor_cost if not generated
                            $display_amount = $existing_invoice ? $existing_invoice['amount'] : $intervention['labor_cost'];
                            echo number_format($display_amount, 2);
                        ?>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="total-row">TOTAL DE PLATĂ:</td>
                    <td class="total-row">
                        <?php echo number_format($display_amount, 2); ?> RON
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if(!$existing_invoice): ?>
        <div class="no-print no-print-sep">
            <h3>Generează Factură Finală</h3>
            <p>Următorul pas îți permite să introduci cost piese, manoperă și TVA înainte de emitere.</p>
            <a href="admin_invoice_confirm.php?intervention_id=<?php echo $intervention_id; ?>" class="btn btn-primary">Completează și Emite Factura</a>
        </div>
        <?php else: ?>
        <?php if(isset($existing_invoice['is_published']) && $existing_invoice['is_published'] == 0): ?>
            <div class="no-print no-print-compact">
                <a href="admin_invoice_confirm.php?intervention_id=<?php echo $intervention_id; ?>" class="btn btn-secondary">Editează Factura (Draft)</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>