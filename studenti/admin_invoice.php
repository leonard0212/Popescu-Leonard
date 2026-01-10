<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$intervention_id = isset($_GET['intervention_id']) ? (int)$_GET['intervention_id'] : 0;
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

// Check if invoice already exists
$chk = $conn->prepare("SELECT * FROM invoices WHERE intervention_id = ?");
$chk->bind_param("i", $intervention_id);
$chk->execute();
$existing_invoice = $chk->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoice'])) {
    if ($existing_invoice) {
        $msg = "Factura există deja.";
    } else {
        $amount = (float)$_POST['amount'];
        $inv_number = 'INV-' . date('Ymd') . '-' . $intervention_id;
        $details = "Manopera service + Piese"; // Simplified

        $ins = $conn->prepare("INSERT INTO invoices (admin_id, client_id, intervention_id, invoice_number, amount, invoice_date, details) VALUES (?, ?, ?, ?, ?, CURDATE(), ?)");
        $ins->bind_param("iiisds", $admin_id, $intervention['client_id'], $intervention_id, $inv_number, $amount, $details);

        if ($ins->execute()) {
             // Refresh to show generated state
             header("Location: admin_invoice.php?intervention_id=" . $intervention_id);
             exit();
        } else {
            $msg = "Eroare la generare: " . $conn->error;
        }
    }
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
    <style>
        body { background: #eee; padding: 20px; font-family: 'Onest', sans-serif; }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #333; }
        .invoice-meta { text-align: right; }
        .invoice-body { margin-bottom: 40px; }
        .table-inv { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table-inv th, .table-inv td { border: 1px solid #eee; padding: 10px; text-align: left; }
        .table-inv th { background: #f9f9f9; }
        .total-row { font-weight: bold; font-size: 1.2em; text-align: right; }
        @media print {
            .no-print { display: none; }
            body { background: #fff; padding: 0; }
            .invoice-box { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width: 800px; margin: 0 auto 20px auto;">
    <a href="admin_interventions.php" class="btn btn-secondary">&laquo; Înapoi la Intervenții</a>
    <button onclick="window.print()" class="btn btn-primary">Printează / PDF</button>
</div>

<div class="invoice-box">
    <?php if($existing_invoice): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; text-align: center;" class="no-print">
            Această factură a fost generată pe <?php echo date('d.m.Y', strtotime($existing_invoice['invoice_date'])); ?>.
        </div>
    <?php endif; ?>

    <div class="invoice-header">
        <div>
            <?php if(!empty($admin_data['logo_path'])): ?>
                <img src="<?php echo htmlspecialchars($admin_data['logo_path']); ?>" style="max-height: 60px; margin-bottom: 10px;">
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

        <p style="margin-top: 20px;"><strong>Referință Vehicul:</strong> <?php echo htmlspecialchars($intervention['model'] . ' (' . $intervention['serial_number'] . ')'); ?></p>

        <table class="table-inv">
            <thead>
                <tr>
                    <th>Descriere</th>
                    <th style="width: 150px; text-align: right;">Valoare (RON)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Servicii Service Auto (Intervenție #<?php echo $intervention_id; ?>)<br>
                        <small>Diagnostic: <?php echo htmlspecialchars($intervention['problem_description']); ?></small>
                    </td>
                    <td style="text-align: right;">
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
        <div class="no-print" style="margin-top: 40px; border-top: 2px dashed #ccc; padding-top: 20px;">
            <h3>Generează Factură Finală</h3>
            <p>Odată generată, factura va primi un număr unic și va fi salvată în sistem.</p>
            <form method="POST">
                <label>Valoare Finală (poate fi ajustată):</label>
                <input type="number" name="amount" step="0.01" value="<?php echo $intervention['labor_cost'] ?: '0.00'; ?>" style="padding: 5px;">
                <button type="submit" name="generate_invoice" class="btn btn-primary">Emite Factura</button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>