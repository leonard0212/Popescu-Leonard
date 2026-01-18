<?php
session_start();
require_once 'db_connect.php';

// 1. Client Authentication Check
if (!isset($_SESSION['client_id'])) {
    // If not logged in, redirect to home or login
    header("Location: index.php");
    exit();
}

$client_id = $_SESSION['client_id'];
$message = '';
$msg_type = '';

// 2. Process Form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $equipment_id = isset($_POST['vehicle']) ? (int)$_POST['vehicle'] : 0; // ID of selected car
    $service_type = isset($_POST['service']) ? trim($_POST['service']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';

    // Short description for registration
    $description = "Solicitare Online: " . ($service_type ? ucfirst($service_type) : 'Serviciu');

    // Simple validation
    if($equipment_id <= 0 || empty($date)) {
        $message = "Te rog selectează un vehicul și o dată validă.";
        $msg_type = "error";
    } else {
        // Insert into `appointments` with 'pending' status
        $stmt = $conn->prepare("INSERT INTO appointments (client_id, equipment_id, appointment_date, service_type, description, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        if ($stmt) {
            $stmt->bind_param("iisss", $client_id, $equipment_id, $date, $service_type, $description);
            if($stmt->execute()) {
                $message = "Cererea a fost trimisă cu succes și așteaptă confirmarea service-ului.";
                $msg_type = "success";
            } else {
                $message = "Eroare la trimitere: " . $conn->error;
                $msg_type = "error";
            }
        } else {
            $message = "Eroare internă: nu am putut pregăti interogarea.";
            $msg_type = "error";
        }
    }
}

// 3. Fetch Client Vehicles
$stmt_veh = $conn->prepare("SELECT id, model, serial_number FROM equipment WHERE client_id = ?");
$stmt_veh->bind_param("i", $client_id);
$stmt_veh->execute();
$vehicles_result = $stmt_veh->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programare Online - Portal Client</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/client.css">
</head>
<body id="top">

    <div class="client-portal">
        <?php include 'client_sidebar.php'; ?>

        <main class="client-content">
            <header class="client-header">
                <button id="sidebar-toggle" class="sidebar-toggle">&#9776;</button>
                <h1>Programare Online</h1>
                <p>Alege serviciul, vehiculul și data dorită.</p>
            </header>

            <?php if($message): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; color: white; background-color: <?php echo ($msg_type == 'success') ? '#28a745' : '#dc3545'; ?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form class="booking-form" action="" method="POST">
                <div class="form-group">
                    <label for="vehicle">Pasul 1: Alege Vehiculul</label>
                    <select id="vehicle" name="vehicle" required style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem;">
                        <?php if($vehicles_result->num_rows > 0): ?>
                            <?php while($car = $vehicles_result->fetch_assoc()): ?>
                                <option value="<?php echo $car['id']; ?>">
                                    <?php echo htmlspecialchars($car['model']) . " (" . htmlspecialchars($car['serial_number']) . ")"; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="">Nu ai vehicule înregistrate. Contactează service-ul.</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="service">Pasul 2: Alege Serviciul Dorit</label>
                    <select id="service" name="service" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem;">
                        <option value="itp">Efectuare ITP</option>
                        <option value="revizie">Revizie Periodică</option>
                        <option value="mecanica">Problemă Mecanică (Necesită diagnostic)</option>
                        <option value="electrica">Problemă Electrică</option>
                        <option value="vulcanizare">Vulcanizare / Schimb Anvelope</option>
                        <option value="alta">Altă problemă</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Pasul 3: Alege Data și Ora</label>
                    <input type="datetime-local" id="date" name="date" required
                           min="<?php echo date('Y-m-d\TH:i'); ?>"
                           class="form-group" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem;">
                </div>

                <button type="submit" class="btn btn-primary">Trimite Solicitarea de Programare</button>
            </form>
        </main>
        <?php include 'footer.php'; ?>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>
