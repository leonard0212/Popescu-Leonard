<?php
session_start();
require_once 'db_connect.php';

// Security check
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT 
            i.id, 
            i.client_id,
            i.scheduled_date, 
            i.status, 
            i.problem_description,
            c.full_name, 
            e.model, 
            e.serial_number 
        FROM interventions i
        JOIN clients c ON i.client_id = c.id
        JOIN equipment e ON i.equipment_id = e.id
        WHERE c.admin_id = ?
        ORDER BY i.scheduled_date DESC";

$stmt = $conn->prepare($sql);
$admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
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
            <div class="table-container animate-on-scroll">
                <div class="table-header">
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
                                        <a href="admin_client_detail.php?id=<?php echo $row['client_id']; ?>" style="color: #333; text-decoration: none;">
                                            <?php echo htmlspecialchars($row['full_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['model']); ?> 
                                        <div style="font-size: 0.85em; color: #666;"><?php echo htmlspecialchars($row['serial_number']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($row['problem_description'], 0, 50)) . (strlen($row['problem_description']) > 50 ? '...' : ''); ?></td>
                                    
                                    <?php 
                                        $color = '#333';
                                        $status_text = ucfirst(str_replace('_', ' ', $row['status']));
                                        
                                        if($row['status'] == 'finalizata') {
                                            $color = '#28a745';
                                            $status_text = "Finalizată";
                                        }
                                        if($row['status'] == 'in_desfasurare') {
                                            $color = '#ffc107'; // Yellow/Orange
                                            $status_text = "În Desfășurare";
                                        }
                                        if($row['status'] == 'programata') {
                                            $color = '#007bff'; // Blue
                                            $status_text = "Programată";
                                        }
                                    ?>
                                    <td>
                                        <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                            <?php echo $status_text; ?>
                                        </span>
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
</body>
</html>