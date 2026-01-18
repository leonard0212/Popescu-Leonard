<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';
require_admin();

// Handle actions: publish / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && !empty($_POST['invoice_id'])) {
    $act = $_POST['action'];
    $iid = (int)$_POST['invoice_id'];
    // Only allow acting on invoices that belong to this admin
    if ($act === 'publish') {
        $u = $conn->prepare("UPDATE invoices SET is_published = 1, invoice_date = CURDATE() WHERE id = ? AND admin_id = ? LIMIT 1");
        $u->bind_param('ii', $iid, $_SESSION['admin_id']);
        $u->execute();
    } elseif ($act === 'unpublish') {
        $u = $conn->prepare("UPDATE invoices SET is_published = 0 WHERE id = ? AND admin_id = ? LIMIT 1");
        $u->bind_param('ii', $iid, $_SESSION['admin_id']);
        $u->execute();
    } elseif ($act === 'delete') {
        $d = $conn->prepare("DELETE FROM invoices WHERE id = ? AND admin_id = ? LIMIT 1");
        $d->bind_param('ii', $iid, $_SESSION['admin_id']);
        $d->execute();
    }
    // Redirect to avoid re-submission
    $redir = 'issued_invoices.php';
    if (!empty($_GET['status'])) $redir .= '?status=' . urlencode($_GET['status']);
    header('Location: ' . $redir);
    exit();
}

// Determine filter: issued / draft / all, but always restrict to current admin
$admin_id = (int)$_SESSION['admin_id'];
$status = isset($_GET['status']) ? $_GET['status'] : 'issued';
$where = 'WHERE i.admin_id = ?';
if ($status === 'draft') {
    $where .= ' AND i.is_published = 0';
} elseif ($status === 'issued') {
    $where .= ' AND i.is_published = 1';
} // 'all' -> keep only admin's invoices

$sql = "SELECT i.*, c.full_name AS client_name, a.email AS admin_email, interv.problem_description AS intervention_desc
        FROM invoices i
        LEFT JOIN clients c ON c.id = i.client_id
        LEFT JOIN admins a ON a.id = i.admin_id
        LEFT JOIN interventions interv ON interv.id = i.intervention_id
        $where
        ORDER BY i.invoice_date DESC, i.id DESC";

$stmt = $conn->prepare($sql);
$res = false;
if ($stmt) {
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Issued Invoices</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
<div class="admin-wrapper">
    <?php include 'admin_sidebar.php'; ?>
    <main class="admin-content">
            <header class="admin-header">
                <button id="sidebar-toggle" class="sidebar-toggle">&#9776;</button>
                <h1>Facturi</h1>
            </header>

            <div class="table-container">
                <div class="table-header">
                    <h2>Facturi</h2>
                    <form method="GET" class="form-inline">
                        <label for="status">Filtrează:</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="issued" <?php if($status==='issued') echo 'selected'; ?>>Emise</option>
                            <option value="draft" <?php if($status==='draft') echo 'selected'; ?>>Draft</option>
                            <option value="all" <?php if($status==='all') echo 'selected'; ?>>Toate</option>
                        </select>
                    </form>
                </div>

                <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Număr</th>
                <th>Data</th>
                <th>Client</th>
                <th>Suma (RON)</th>
                <th>Admin</th>
                <th>Intervenție</th>
                <th>Status</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                <td><?php echo htmlspecialchars($row['invoice_date']); ?></td>
                <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                <td><?php echo number_format((float)$row['amount'], 2, '.', ''); ?></td>
                <td><?php echo htmlspecialchars($row['admin_email']); ?></td>
                <td><?php echo htmlspecialchars($row['intervention_desc']); ?></td>
                <td>
                    <?php if (!empty($row['is_published']) && $row['is_published']==1): ?>
                        <span class="badge badge-success">Emis</span>
                    <?php else: ?>
                        <span class="badge">Draft</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a href="admin_invoice.php?invoice_id=<?php echo $row['id']; ?>">Vizualizează</a>
                    |
                    <a href="admin_invoice_confirm.php?invoice_id=<?php echo $row['id']; ?>">Editează</a>
                    |
                    <form method="POST" class="table-inline-form">
                        <input type="hidden" name="invoice_id" value="<?php echo (int)$row['id']; ?>">
                        <?php if (empty($row['is_published']) || $row['is_published']==0): ?>
                            <button type="submit" name="action" value="publish" class="btn btn-publish">Emite</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="unpublish" class="btn btn-unpublish">Revenire</button>
                        <?php endif; ?>
                    </form>
                    |
                    <form method="POST" class="table-inline-form" data-confirm="Ștergeți această factură?">
                        <input type="hidden" name="invoice_id" value="<?php echo (int)$row['id']; ?>">
                        <button type="submit" name="action" value="delete" class="btn btn-link btn-link-danger">Șterge</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
                </div> <!-- .table-container -->
    <script src="js/confirmations.js"></script>
    <script src="js/admin.js"></script>
    </main>
</div>
</body>
</html>
