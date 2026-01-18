<?php
session_start();
require_once 'db_connect.php';

// --- SIMULARE LOGIN (Șterge după ce ai login real) ---
// $_SESSION['client_id'] = 1; 
// -----------------------------------------------------

// 1. Verificare Autentificare
if (!isset($_SESSION['client_id'])) {
    header("Location: index.php");
    exit();
}

$client_id = $_SESSION['client_id'];

// 2. Preluare Echipamente
$sql = "SELECT * FROM equipment WHERE client_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Echipamentele Mele - Portal Client</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/client.css">
    <link rel="stylesheet" href="style/pages.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    
    <div class="admin-wrapper">
        <?php include 'client_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <button id="sidebar-toggle" class="sidebar-toggle">&#9776;</button>
                <h1>Echipamentele Mele</h1>
                <div class="user-info">
                    <p>Vezi detalii, garanții și istoricul de service pentru fiecare vehicul.</p>
                </div>
            </header>

            <div class="equipment-list animate-on-scroll">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="equipment-card animate-on-scroll">
                            <div class="details">
                                <h3><?php echo htmlspecialchars($row['model']); ?></h3>
                                <p><strong>Nr. Înmatriculare / Serie:</strong> <?php echo htmlspecialchars($row['serial_number']); ?></p>
                                
                                <p><strong>Garanție:</strong> 
                                    <?php 
                                        if($row['warranty_expiry_date']) {
                                            $w_date = new DateTime($row['warranty_expiry_date']);
                                            echo $w_date->format('d.m.Y');
                                            if($w_date < new DateTime()) echo " <span class='text-danger'>(Expirată)</span>";
                                        } else {
                                            echo "-";
                                        }
                                    ?>
                                </p>
                                
                                <p><strong>Următorul ITP:</strong> 
                                        <?php 
                                            if($row['itp_expiry_date']) {
                                                $itp_date = new DateTime($row['itp_expiry_date']);
                                                $now = new DateTime();
                                                $colorClass = ($itp_date < $now) ? 'text-danger' : 'text-success';
                                                echo "<span class='" . $colorClass . " font-bold'>" . $itp_date->format('d.m.Y') . "</span>";
                                            } else {
                                                echo "Nesetat";
                                            }
                                        ?>
                                </p>
                            </div>
                            <div class="actions">
                                <a href="client_history.php?vehicle_id=<?php echo $row['id']; ?>" class="btn btn-secondary">Vezi Istoric</a>
                                <a href="client_booking.php?vehicle_id=<?php echo $row['id']; ?>" class="btn btn-primary ml-5">Programează</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-placeholder">
                        <p>Nu ai niciun vehicul înregistrat.</p>
                        <p>Te rugăm să contactezi service-ul pentru a adăuga un vehicul.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>