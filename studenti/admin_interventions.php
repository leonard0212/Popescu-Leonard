<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';
require_admin();

// Security check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;

// Handle status update requests from admin (do this before fetching list)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_intervention_status'])) {
    $inter_id = isset($_POST['intervention_id']) ? (int)$_POST['intervention_id'] : 0;
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';

    // Verify intervention belongs to one of admin's clients
    $v = $conn->prepare("SELECT i.id, i.client_id, i.labor_cost FROM interventions i JOIN clients c ON i.client_id = c.id WHERE i.id = ? AND c.admin_id = ? LIMIT 1");
    if ($v) {
        $v->bind_param('ii', $inter_id, $admin_id);
        $v->execute();
        $vr = $v->get_result();
        if ($vr && $vr->num_rows > 0) {
            $ir = $vr->fetch_assoc();
            // Update status
            $u = $conn->prepare("UPDATE interventions SET status = ? WHERE id = ?");
            if ($u) {
                $u->bind_param('si', $new_status, $inter_id);
                $u->execute();
            }

            // If status changed to finalizata: 1) award loyalty points if not awarded, 2) redirect to invoice confirmation page
            if ($new_status === 'finalizata') {
                // Award points if not already awarded
                if (empty($ir['points_awarded']) || $ir['points_awarded'] == 0) {
                    $labor = isset($ir['labor_cost']) ? (float)$ir['labor_cost'] : 0.0;
                    $points = floor($labor / 10);
                    if ($points > 0) {
                        $upd = $conn->prepare("UPDATE clients SET loyalty_points = IFNULL(loyalty_points,0) + ? WHERE id = ?");
                        if ($upd) {
                            $upd->bind_param('ii', $points, $ir['client_id']);
                            $upd->execute();
                        }
                    }
                    // Mark intervention as points awarded
                    $mark = $conn->prepare("UPDATE interventions SET points_awarded = 1 WHERE id = ?");
                    if ($mark) { $mark->bind_param('i', $inter_id); $mark->execute(); }
                }

                // Redirect to confirmation where admin can set parts, labor, VAT
                header('Location: admin_invoice_confirm.php?intervention_id=' . $inter_id);
                exit();
            }
        }
    }

    // Refresh page to show updates
    header('Location: admin_interventions.php');
    exit();
}

// Build query with optional filter from dashboard
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
if ($filter === 'today') {
    $sql = "SELECT i.id, i.client_id, i.scheduled_date, i.status, i.problem_description, c.full_name, e.model, e.serial_number FROM interventions i JOIN clients c ON i.client_id = c.id JOIN equipment e ON i.equipment_id = e.id WHERE c.admin_id = ? AND DATE(i.scheduled_date) = CURDATE() ORDER BY i.scheduled_date DESC";
} elseif ($filter === 'in_desfasurare') {
    $sql = "SELECT i.id, i.client_id, i.scheduled_date, i.status, i.problem_description, c.full_name, e.model, e.serial_number FROM interventions i JOIN clients c ON i.client_id = c.id JOIN equipment e ON i.equipment_id = e.id WHERE c.admin_id = ? AND i.status = 'in_desfasurare' ORDER BY i.scheduled_date DESC";
} else {
    $sql = "SELECT i.id, i.client_id, i.scheduled_date, i.status, i.problem_description, c.full_name, e.model, e.serial_number FROM interventions i JOIN clients c ON i.client_id = c.id JOIN equipment e ON i.equipment_id = e.id WHERE c.admin_id = ? ORDER BY i.scheduled_date DESC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intervenții - ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <button id="sidebar-toggle" class="sidebar-toggle">&#9776;</button>
                <h1>Istoric Service</h1>
                <a href="admin_intervention_new.php" class="btn btn-primary">Adaugă Intervenție</a>
            </header>

            <div class="table-container animate-on-scroll">
                <div class="table-header hidden"> <!-- Hidden header, used header above -->
                    <h1>Istoric Service</h1>
                    <a href="admin_intervention_new.php" class="btn btn-primary">Adaugă Intervenție</a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Client</th>
                            <th>Echipament</th>
                            <th>Problemă</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($row['scheduled_date'])); ?></td>
                                    <td>
                                        <a href="admin_client_detail.php?id=<?php echo $row['client_id']; ?>" class="client-link">
                                            <?php echo htmlspecialchars($row['full_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['model']); ?> 
                                        <div class="muted-text"><?php echo htmlspecialchars($row['serial_number']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($row['problem_description'], 0, 50)) . (strlen($row['problem_description']) > 50 ? '...' : ''); ?></td>
                                    
                                    <?php 
                                        $status_text = ucfirst(str_replace('_', ' ', $row['status']));
                                        $status_class = 'status-' . $row['status'];
                                        if($row['status'] == 'finalizata') $status_text = 'Finalizată';
                                        if($row['status'] == 'in_desfasurare') $status_text = 'În Desfășurare';
                                        if($row['status'] == 'programata') $status_text = 'Programată';
                                    ?>
                                    <td>
                                        <span class="<?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                        <!-- Inline status update form -->
                                        <div style="margin-top:8px;">
                                            <form method="POST" class="form-row">
                                                <input type="hidden" name="update_intervention_status" value="1">
                                                <input type="hidden" name="intervention_id" value="<?php echo $row['id']; ?>">
                                                <select name="new_status" class="form-select">
                                                    <option value="programata" <?php echo $row['status'] == 'programata' ? 'selected' : ''; ?>>Programată</option>
                                                    <option value="in_desfasurare" <?php echo $row['status'] == 'in_desfasurare' ? 'selected' : ''; ?>>În Desfășurare</option>
                                                    <option value="finalizata" <?php echo $row['status'] == 'finalizata' ? 'selected' : ''; ?>>Finalizată</option>
                                                    <option value="anulata" <?php echo $row['status'] == 'anulata' ? 'selected' : ''; ?>>Anulată</option>
                                                </select>
                                                <button type="submit" class="btn btn-small">Actualizează</button>
                                            </form>
                                        </div>
                                        <?php if($row['status'] == 'finalizata'): ?>
                                            <div style="margin-top: 5px;">
                                                <a href="admin_invoice.php?intervention_id=<?php echo $row['id']; ?>" class="btn-sm btn-purple">Vezi Factură</a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px;">Nu există intervenții înregistrate.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>