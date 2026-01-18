<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';
require_admin();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$admin_id = (int)$_SESSION['admin_id'];

// invoice_id can come from GET (link) or POST (form submit)
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
if (empty($invoice_id) && isset($_POST['invoice_id'])) $invoice_id = (int)$_POST['invoice_id'];

$intervention_id = isset($_GET['intervention_id']) ? (int)$_GET['intervention_id'] : 0;
if (empty($intervention_id) && isset($_POST['intervention_id'])) $intervention_id = (int)$_POST['intervention_id'];

$msg = '';
$existing_invoice = null;

// Load invoice if provided (ensure it belongs to this admin)
if ($invoice_id) {
    $invq = $conn->prepare("SELECT * FROM invoices WHERE id = ? AND admin_id = ? LIMIT 1");
    $invq->bind_param('ii', $invoice_id, $admin_id);
    $invq->execute();
    $invres = $invq->get_result();
    if ($invres && $invres->num_rows) {
        $existing_invoice = $invres->fetch_assoc();
        $intervention_id = (int)$existing_invoice['intervention_id'];
        if (!empty($existing_invoice['is_published']) && $existing_invoice['is_published'] == 1) {
            header('Location: admin_invoice.php?invoice_id=' . $invoice_id);
            exit();
        }
    } else {
        die('Factura inexistentă sau acces interzis.');
    }
}

if (empty($intervention_id)) {
    die('ID intervenție lipsă.');
}

// Fetch intervention + client + equipment and verify admin ownership
$stmt = $conn->prepare("SELECT i.*, c.full_name, c.id AS client_id, c.email AS client_email, e.model, e.serial_number FROM interventions i JOIN clients c ON i.client_id = c.id JOIN equipment e ON i.equipment_id = e.id WHERE i.id = ? AND c.admin_id = ? LIMIT 1");
$stmt->bind_param('ii', $intervention_id, $admin_id);
$stmt->execute();
$inter_res = $stmt->get_result();
if (!$inter_res || $inter_res->num_rows === 0) {
    die('Intervenție inexistentă sau acces interzis.');
}
$inter = $inter_res->fetch_assoc();

// Default VAT from admin settings
$adm = $conn->prepare("SELECT vat_rate_default, service_name, cui_cif, address, fiscal_address, logo_path, email FROM admins WHERE id = ? LIMIT 1");
$adm->bind_param('i', $admin_id);
$adm->execute();
$admin_data = $adm->get_result()->fetch_assoc() ?: [];
$default_vat = isset($admin_data['vat_rate_default']) ? (float)$admin_data['vat_rate_default'] : 19.0;

// If no existing invoice, check for invoice by intervention (for this admin only)
if (empty($existing_invoice)) {
    $chk = $conn->prepare("SELECT * FROM invoices WHERE intervention_id = ? AND admin_id = ? LIMIT 1");
    $chk->bind_param('ii', $intervention_id, $admin_id);
    $chk->execute();
    $res_chk = $chk->get_result();
    if ($res_chk && $res_chk->num_rows > 0) {
        $existing_invoice = $res_chk->fetch_assoc();
        if (!empty($existing_invoice['is_published']) && $existing_invoice['is_published'] == 1) {
            header('Location: admin_invoice.php?invoice_id=' . $existing_invoice['id']);
            exit();
        }
    }
}

// Handle POST (save or publish)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parts_raw = isset($_POST['parts_amount']) ? str_replace(',', '.', trim($_POST['parts_amount'])) : '0';
    $labor_raw = isset($_POST['labor_amount']) ? str_replace(',', '.', trim($_POST['labor_amount'])) : '0';
    $vat_raw = isset($_POST['vat_rate']) ? str_replace(',', '.', trim($_POST['vat_rate'])) : (string)$default_vat;
    $parts = (float)$parts_raw;
    $labor = (float)$labor_raw;
    $vat = (float)$vat_raw;
    $action = isset($_POST['action_type']) ? $_POST['action_type'] : 'publish';
    $items_json = isset($_POST['items_json']) ? trim($_POST['items_json']) : '';
    $details = isset($_POST['details']) ? trim($_POST['details']) : '';

    if (!empty($items_json)) {
        $decoded = json_decode($items_json, true);
        if (is_array($decoded)) {
            $lines = [];
            foreach ($decoded as $it) {
                $d = isset($it['desc']) ? trim($it['desc']) : '';
                $a = isset($it['amount']) ? number_format((float)$it['amount'], 2, '.', '') : '0.00';
                $tag = !empty($it['labor']) ? '[Manoperă]' : '[Piese]';
                $lines[] = $tag . ' ' . $d . ' - ' . $a . ' RON';
            }
            if (count($lines)) $details = implode("\n", $lines);
        }
    }

    $subtotal = $parts + $labor;
    $total = round($subtotal * (1 + $vat/100), 2);
    // Debug logging for troubleshooting value propagation
    error_log('Invoice DEBUG - POST: ' . json_encode($_POST) . ' | computed parts=' . $parts . ' labor=' . $labor . ' vat=' . $vat . ' total=' . $total);

    if ($total <= 0) {
        $msg = 'ATENȚIE: Total calculat = 0. Verificați valorile trimise.';
    }

    $is_pub = ($action === 'publish') ? 1 : 0;

    // Only perform DB write if total > 0
    if ($total > 0) {
        if ($existing_invoice) {
            // keep existing invoice_number when editing
            $inv_number = $existing_invoice['invoice_number'];
            // ensure update only affects this admin's invoice
            $up = $conn->prepare("UPDATE invoices SET invoice_number = ?, amount = ?, details = ?, parts_amount = ?, labor_amount = ?, vat_rate = ?, is_published = ? WHERE id = ? AND admin_id = ?");
            $up->bind_param('sdsdddiii', $inv_number, $total, $details, $parts, $labor, $vat, $is_pub, $existing_invoice['id'], $admin_id);
            if ($up->execute()) {
                $invoice_id = $existing_invoice['id'];
                if ($is_pub) {
                    // award points once
                    $chk_aw = $conn->prepare("SELECT points_awarded FROM interventions WHERE id = ? LIMIT 1");
                    $chk_aw->bind_param('i', $intervention_id);
                    $chk_aw->execute();
                    $pa = $chk_aw->get_result()->fetch_assoc();
                    if (empty($pa['points_awarded']) || $pa['points_awarded'] == 0) {
                        $points = floor($total / 10);
                        if ($points > 0) {
                            $upd = $conn->prepare("UPDATE clients SET loyalty_points = IFNULL(loyalty_points,0) + ? WHERE id = ?");
                            $upd->bind_param('ii', $points, $inter['client_id']);
                            $upd->execute();
                        }
                        $mark = $conn->prepare("UPDATE interventions SET points_awarded = 1 WHERE id = ?");
                        $mark->bind_param('i', $intervention_id);
                        $mark->execute();
                    }
                }
                header('Location: admin_invoice.php?invoice_id=' . (int)$invoice_id);
                exit();
            } else {
                $msg = 'Eroare actualizare: ' . $conn->error;
            }
        } else {
            // Create new invoice: allocate per-admin sequential number inside a transaction
            try {
                $conn->begin_transaction();

                // Lock and read sequence row
                $seq_select = $conn->prepare("SELECT last_seq FROM invoice_sequences WHERE admin_id = ? FOR UPDATE");
                $seq_select->bind_param('i', $admin_id);
                $seq_select->execute();
                $seq_res = $seq_select->get_result();
                if ($seq_res && $seq_res->num_rows) {
                    $rowseq = $seq_res->fetch_assoc();
                    $new_seq = (int)$rowseq['last_seq'] + 1;
                    $seq_up = $conn->prepare("UPDATE invoice_sequences SET last_seq = ? WHERE admin_id = ?");
                    $seq_up->bind_param('ii', $new_seq, $admin_id);
                    $seq_up->execute();
                } else {
                    $new_seq = 1;
                    $seq_ins = $conn->prepare("INSERT INTO invoice_sequences (admin_id, last_seq) VALUES (?, ?) ");
                    $seq_ins->bind_param('ii', $admin_id, $new_seq);
                    $seq_ins->execute();
                }

                // Build invoice number: INV-<admin_id>-<seq padded to 4>
                $inv_number = 'INV-' . $admin_id . '-' . str_pad((string)$new_seq, 4, '0', STR_PAD_LEFT);

                $ins = $conn->prepare("INSERT INTO invoices (admin_id, client_id, intervention_id, invoice_number, amount, invoice_date, details, parts_amount, labor_amount, vat_rate, is_published) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?)");
                if ($ins) {
                    $ins->bind_param('iiisdsdddi', $admin_id, $inter['client_id'], $intervention_id, $inv_number, $total, $details, $parts, $labor, $vat, $is_pub);
                    if ($ins->execute()) {
                        $new_id = $ins->insert_id;
                        // commit transaction (sequence + invoice)
                        $conn->commit();
                        if ($is_pub) {
                            $points = floor($total / 10);
                            if ($points > 0) {
                                $upd = $conn->prepare("UPDATE clients SET loyalty_points = IFNULL(loyalty_points,0) + ? WHERE id = ?");
                                $upd->bind_param('ii', $points, $inter['client_id']);
                                $upd->execute();
                            }
                            $mark = $conn->prepare("UPDATE interventions SET points_awarded = 1 WHERE id = ?");
                            $mark->bind_param('i', $intervention_id);
                            $mark->execute();
                        }
                        header('Location: admin_invoice.php?invoice_id=' . (int)$new_id);
                        exit();
                    } else {
                        $conn->rollback();
                        $msg = 'Eroare insert: ' . $conn->error;
                    }
                } else {
                    $conn->rollback();
                    $msg = 'Eroare internă pregătire interogare.';
                }
            } catch (Exception $e) {
                $conn->rollback();
                $msg = 'Eroare tranzacție: ' . $e->getMessage();
            }
        }
    } else {
        // Do not save zero-total invoices; inform via $msg and log for debugging
        error_log('Invoice NOT SAVED - total <= 0 | POST: ' . json_encode($_POST));
    }
}

// Values for form initial population
$parts_value = htmlspecialchars(number_format((float)($existing_invoice['parts_amount'] ?? $inter['parts_used'] ?? 0), 2, '.', ''));
$labor_value = htmlspecialchars(number_format((float)($existing_invoice['labor_amount'] ?? $inter['labor_cost'] ?? 0), 2, '.', ''));
$vat_value = htmlspecialchars($existing_invoice['vat_rate'] ?? $default_vat);
$details_text = htmlspecialchars($existing_invoice['details'] ?? $inter['problem_description'] ?? '');

// Prepare small payload for external JS
$js_init = json_encode([
    'existingParts' => floatval($existing_invoice['parts_amount'] ?? 0),
    'existingLabor' => floatval($existing_invoice['labor_amount'] ?? 0),
    'existingDetails' => $existing_invoice['details'] ?? ''
]);
?>
<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Confirmare Factură</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>
        <main class="admin-content">
            <header class="admin-header"><h1>Confirmare Factură</h1></header>

            <?php if ($msg): ?>
                <div style="background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;"> <?php echo $msg; ?> </div>
            <?php endif; ?>

            <section class="admin-form">
                <h2>Intervenție #<?php echo (int)$intervention_id; ?></h2>
                <p><strong>Client:</strong> <?php echo htmlspecialchars($inter['full_name']); ?></p>
                <p><strong>Echipament:</strong> <?php echo htmlspecialchars($inter['model']); ?> <small><?php echo htmlspecialchars($inter['serial_number']); ?></small></p>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($existing_invoice['id'] ?? $invoice_id ?? ''); ?>">
                    <input type="hidden" name="intervention_id" value="<?php echo (int)$intervention_id; ?>">

                    <div class="form-group">
                        <label>Articole factură (descriere + sumă). Bifați "Manoperă" dacă este manoperă.</label>
                        <div id="items-container"></div>
                        <button type="button" id="add-item" class="btn btn-secondary" style="margin-top:8px;">Adaugă articol</button>
                    </div>

                    <div class="form-group">
                        <label>Piese (RON)</label>
                        <input type="number" step="0.01" id="parts_amount" name="parts_amount" value="<?php echo $parts_value; ?>">
                    </div>
                    <div class="form-group">
                        <label>Manoperă (RON)</label>
                        <input type="number" step="0.01" id="labor_amount" name="labor_amount" value="<?php echo $labor_value; ?>">
                    </div>
                    <div class="form-group">
                        <label>TVA (%)</label>
                        <input type="number" step="0.01" id="vat_rate" name="vat_rate" value="<?php echo $vat_value; ?>">
                    </div>
                    <div class="form-group">
                        <label>Detalii (opțional)</label>
                        <textarea id="details" name="details" rows="4"><?php echo $details_text; ?></textarea>
                    </div>
                    <input type="hidden" id="items_json" name="items_json" value="">

                    <div style="margin-top:15px; display:flex; gap:8px; align-items:center;">
                        <button type="submit" name="action_type" value="save" class="btn btn-secondary">Salvează Draft</button>
                        <button type="submit" name="action_type" value="publish" class="btn btn-primary">Confirmă și Emite Factura</button>
                        <a href="admin_interventions.php" class="btn btn-secondary" style="margin-left:10px;">Anulează</a>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script>window.__INVOICE_INIT = <?php echo $js_init; ?>;</script>
    <script src="js/invoice_confirm.js"></script>
</body>
</html>
