<?php
session_start();
require_once 'db_connect.php';

// Verificare autentificare
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Ștergere echipament (verificăm apartenența clientului la admin)
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $admin_id = $_SESSION['admin_id'];
    $del_stmt = $conn->prepare("DELETE e FROM equipment e JOIN clients c ON e.client_id = c.id WHERE e.id = ? AND c.admin_id = ?");
    $del_stmt->bind_param('ii', $del_id, $admin_id);
    
    if($del_stmt->execute()) {
        header("Location: admin_equipment.php?msg=deleted");
        exit();
    }
}

// Listare echipamente + Nume Client (doar pentru clientii adminului)
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT e.*, c.full_name FROM equipment e 
        JOIN clients c ON e.client_id = c.id 
        WHERE c.admin_id = ?
        ORDER BY e.created_at DESC";
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
    <title>Echipamente - ServiceHub</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <button id="sidebar-toggle" class="sidebar-toggle" style="display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-right: 1rem;">&#9776;</button>
                <h1>Parc Auto / Echipamente</h1>
                <a href="admin_equipment_new.php" class="btn btn-primary">Adaugă Echipament</a>
            </header>

            <div class="table-container animate-on-scroll">
                <div class="table-header" style="display:none;"> <!-- Hidden header, used header above -->
                    <h1>Parc Auto / Echipamente</h1>
                    <a href="admin_equipment_new.php" class="btn btn-primary">Adaugă Echipament</a>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                        Vehiculul a fost șters cu succes.
                    </div>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Serie / Nr. Înmatriculare</th>
                            <th>Proprietar</th>
                            <th>ITP Expiră</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['model']); ?></td>
                                    <td><?php echo htmlspecialchars($row['serial_number']); ?></td>
                                    <td>
                                        <a href="admin_client_detail.php?id=<?php echo $row['client_id']; ?>" style="color: #007bff; text-decoration: none;">
                                            <?php echo htmlspecialchars($row['full_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php 
                                            if($row['itp_expiry_date']) {
                                                $itp_date = new DateTime($row['itp_expiry_date']);
                                                // Obținem data curentă
                                                $now = new DateTime();
                                                // Calculăm diferența în zile
                                                $interval = $now->diff($itp_date);
                                                $days = (int)$interval->format('%r%a'); // %r pune semnul minus dacă a trecut
                                                
                                                // Roșu dacă a expirat (zile negative) sau expiră în 7 zile
                                                $color = ($days < 7) ? '#dc3545' : '#28a745';
                                                $weight = ($days < 7) ? 'bold' : 'normal';
                                                
                                                echo "<span style='color:$color; font-weight:$weight'>" . $itp_date->format('d.m.Y') . "</span>";
                                            } else {
                                                echo "-";
                                            }
                                        ?>
                                    </td>
                                    <td class="actions">
                                        <a href="admin_equipment.php?delete_id=<?php echo $row['id']; ?>" 
                                           class="delete"
                                           onclick="return confirm('Sigur ștergi acest vehicul?')" 
                                           style="color:#dc3545;">
                                           Șterge
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:20px;">Nu există echipamente înregistrate.</td></tr>
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