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
// Vom aduce atât înregistrările din `appointments`, cât și din `interventions`
// și le vom combina în PHP pentru a le sorta după dată.

$items = [];

// 1) Appointments
$sqlA = "SELECT a.id, a.appointment_date AS date, 'appointment' AS type, a.description AS description, a.status AS status, e.model, e.serial_number, a.equipment_id FROM appointments a JOIN equipment e ON a.equipment_id = e.id WHERE a.client_id = ?";
if ($vehicle_filter_id) $sqlA .= " AND a.equipment_id = ?";
$sqlA .= " ORDER BY a.appointment_date DESC";
$stmtA = $conn->prepare($sqlA);
if ($vehicle_filter_id) {
    $stmtA->bind_param("ii", $client_id, $vehicle_filter_id);
} else {
    $stmtA->bind_param("i", $client_id);
}
$stmtA->execute();
$resA = $stmtA->get_result();
while($r = $resA->fetch_assoc()) {
    $items[] = $r;
}

// 2) Interventions (created by admin or via appointments)
$sqlI = "SELECT i.id, i.scheduled_date AS date, 'intervention' AS type, i.problem_description AS description, i.status AS status, e.model, e.serial_number, i.equipment_id FROM interventions i JOIN equipment e ON i.equipment_id = e.id WHERE i.client_id = ?";
if ($vehicle_filter_id) $sqlI .= " AND i.equipment_id = ?";
$sqlI .= " ORDER BY i.scheduled_date DESC";
$stmtI = $conn->prepare($sqlI);
if ($vehicle_filter_id) {
    $stmtI->bind_param("ii", $client_id, $vehicle_filter_id);
} else {
    $stmtI->bind_param("i", $client_id);
}
$stmtI->execute();
$resI = $stmtI->get_result();
while($r = $resI->fetch_assoc()) {
    $items[] = $r;
}

// Sortăm toate item-urile după dată descrescător
usort($items, function($a, $b) {
    $da = strtotime($a['date']);
    $db = strtotime($b['date']);
    return $db <=> $da;
});

$result = null; // legacy variable used later by template - we will iterate $items instead
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
                <button id="sidebar-toggle" class="sidebar-toggle">&#9776;</button>
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
                <?php if (count($items) > 0): ?>
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
                            <?php foreach($items as $row): ?>
                                <tr>
                                    <td data-label="Data">
                                        <?php echo date('d.m.Y H:i', strtotime($row['date'])); ?>
                                    </td>
                                    <td data-label="Vehicul">
                                        <?php echo htmlspecialchars($row['model']); ?>
                                        <br><small class="muted-text"><?php echo htmlspecialchars($row['serial_number']); ?></small>
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
                                                // Map common intervention statuses
                                                $map = [
                                                    'programata' => 'Programată',
                                                    'in_desfasurare' => 'În Desfășurare',
                                                    'finalizata' => 'Finalizată',
                                                    'anulata' => 'Anulată'
                                                ];
                                                $label = isset($map[$row['status']]) ? $map[$row['status']] : $row['status'];
                                                echo '<span class="badge">' . htmlspecialchars($label) . '</span>';
                                            }
                                        ?>
                                    </td>
                                    <td data-label="">
                                        <a href="client_history_detail.php?id=<?php echo $row['id']; ?>&type=<?php echo urlencode($row['type']); ?>" class="btn btn-sm btn-secondary">Vezi Detalii</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Nu există nicio intervenție înregistrată.</p>
                        <a href="client_booking.php" class="btn btn-primary">Fă o programare nouă</a>
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