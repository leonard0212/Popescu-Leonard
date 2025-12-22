<?php
session_start();
require_once 'db_connect.php';

// --- SIMULARE LOGIN (Dacă nu ai login încă) ---
// $_SESSION['client_id'] = 1; 
// ----------------------------------------------

// 1. Verificare Autentificare
if (!isset($_SESSION['client_id'])) {
    header("Location: index.php");
    exit();
}

$client_id = $_SESSION['client_id'];

// 2. Logică Filtrare (Opțional, dacă venim din pagina Echipamente)
$vehicle_filter_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;

// Construim interogarea SQL
// Interogăm tabelul `appointments` și luăm detalii echipament
$sql = "SELECT a.*, e.model, e.serial_number 
    FROM appointments a
    JOIN equipment e ON a.equipment_id = e.id
    WHERE a.client_id = ?";

// Dacă avem filtru, adăugăm condiția
if ($vehicle_filter_id) {
    $sql .= " AND a.equipment_id = ?";
}

$sql .= " ORDER BY a.appointment_date DESC";

$stmt = $conn->prepare($sql);

if ($vehicle_filter_id) {
    $stmt->bind_param("ii", $client_id, $vehicle_filter_id);
} else {
    $stmt->bind_param("i", $client_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Istoric Service - Portal Client</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/client.css">
    <link rel="stylesheet" href="style/pages.css">
</head>
<body id="top">
    
    <div class="client-portal">
        <?php include 'client_sidebar.php'; ?>

        <main class="client-content">
            <header class="client-header animate-on-scroll">
                <h1>Istoric Service</h1>
                <p>Vezi toate intervențiile efectuate asupra vehiculelor tale.</p>
                <?php if($vehicle_filter_id): ?>
                    <div class="mb-20">
                        <span class="filter-badge">
                            Filtru activ: Arătăm doar istoricul vehiculului selectat
                            <a href="client_history.php" class="filter-clear">&times; Șterge filtrul</a>
                        </span>
                    </div>
                <?php endif; ?>
            </header>

            <div class="animate-on-scroll">
                <?php if ($result->num_rows > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Vehicul</th>
                                <th>Problemă / Serviciu</th>
                                <th>Status</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Data">
                                        <?php echo date('d.m.Y H:i', strtotime($row['appointment_date'])); ?>
                                    </td>
                                    <td data-label="Vehicul">
                                        <?php echo htmlspecialchars($row['model']); ?>
                                        <br><small style="color:#888"><?php echo htmlspecialchars($row['serial_number']); ?></small>
                                    </td>
                                    <td data-label="Descriere">
                                        <?php echo htmlspecialchars(substr($row['description'], 0, 60)) . (strlen($row['description']) > 60 ? '...' : ''); ?>
                                    </td>
                                    <td data-label="Status">
                                        <?php
                                            // Mapăm status la badge-uri specifice
                                            if ($row['status'] === 'pending') {
                                                echo '<span class="badge badge-warning">În Așteptare</span>';
                                            } elseif ($row['status'] === 'confirmed') {
                                                echo '<span class="badge badge-success">Confirmată</span>';
                                            } elseif ($row['status'] === 'rejected') {
                                                echo '<span class="badge badge-danger">Respinsă</span>';
                                            } else {
                                                echo '<span class="badge">' . htmlspecialchars($row['status']) . '</span>';
                                            }
                                        ?>
                                    </td>
                                    <td data-label="">
                                        <a href="client_history_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-secondary">Vezi Detalii</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px; background: white; border-radius: 8px;">
                        <p>Nu există nicio intervenție înregistrată.</p>
                        <a href="client_booking.php" class="btn btn-primary">Fă o programare nouă</a>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>