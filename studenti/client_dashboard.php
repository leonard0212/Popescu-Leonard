<?php
session_start();
require_once 'db_connect.php';

// 1. Authentication Check
if (!isset($_SESSION['client_id'])) {
    header("Location: index.php");
    exit();
}

$client_id = $_SESSION['client_id'];

// 2. Fetch Client Data (Name, Points)
$stmt = $conn->prepare("SELECT full_name, loyalty_points FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();

// 3. Fetch ITP Alert (Nearest ITP expiring)
$itp_query = "SELECT model, serial_number, itp_expiry_date
              FROM equipment
              WHERE client_id = ? AND itp_expiry_date IS NOT NULL
              ORDER BY itp_expiry_date ASC LIMIT 1"; // Fetch the first one expiring (even if passed)

$stmt_itp = $conn->prepare($itp_query);
$stmt_itp->bind_param("i", $client_id);
$stmt_itp->execute();
$itp_res = $stmt_itp->get_result();
$itp_data = $itp_res->fetch_assoc();

// Calculate remaining days for ITP
$itp_msg = "Nu ai vehicule cu ITP setat.";
$itp_car = "";
$itp_class = ""; // CSS class for alert
$days_left = 999;

if ($itp_data) {
    $expiry = new DateTime($itp_data['itp_expiry_date']);
    $now = new DateTime();
    $interval = $now->diff($expiry);
    $days_left = (int)$interval->format('%r%a'); // %r gives sign (negative if passed)

    $itp_car = $itp_data['model'] . " (" . $itp_data['serial_number'] . ")";

    if ($days_left < 0) {
        $itp_msg = "EXPIRAT de " . abs($days_left) . " zile!";
        $itp_class = "alert-danger"; // Red
    } elseif ($days_left == 0) {
        $itp_msg = "Expiră AZI!";
        $itp_class = "alert-danger";
    } elseif ($days_left <= 30) {
        $itp_msg = "Expiră în $days_left zile";
        $itp_class = "alert"; // Yellow/Orange
    } else {
        $itp_msg = "Valabil încă $days_left zile";
        $itp_class = "success"; // Green
    }
}

// 4. Fetch Next Appointment (Revision/Intervention)
$prog_query = "SELECT i.scheduled_date, e.model
               FROM interventions i
               JOIN equipment e ON i.equipment_id = e.id
               WHERE i.client_id = ? AND i.scheduled_date > NOW()
               ORDER BY i.scheduled_date ASC LIMIT 1";

$stmt_prog = $conn->prepare($prog_query);
$stmt_prog->bind_param("i", $client_id);
$stmt_prog->execute();
$prog_res = $stmt_prog->get_result();
$prog_data = $prog_res->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portalul Meu - Service Auto</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/client.css">
    <link rel="stylesheet" href="style/pages.css">
</head>
<body id="top">

    <div class="client-portal">
        <?php include 'client_sidebar.php'; ?>

        <main class="client-content">
            <header class="client-header">
                <button id="sidebar-toggle" class="sidebar-toggle">&#9776;</button>
                <h1>Bun venit, <?php echo htmlspecialchars($client['full_name']); ?>!</h1>
                <p>Aici găsești toate informațiile despre vehiculele și programările tale.</p>
            </header>

            <section class="info-card-grid animate-on-scroll">

                <div class="info-card <?php echo $itp_class; ?> animate-on-scroll">
                    <h3>Status ITP</h3>
                    <div class="data"><?php echo $itp_msg; ?></div>

                    <?php if($itp_car): ?>
                        <p>Vehicul: <?php echo htmlspecialchars($itp_car); ?></p>
                    <?php endif; ?>

                    <?php if($days_left <= 30): ?>
                        <a href="client_booking.php?service=itp" class="btn btn-primary btn-mt-10">Programează ITP</a>
                    <?php endif; ?>
                </div>

                <div class="info-card animate-on-scroll">
                    <h3>Următoarea Vizită</h3>
                    <?php if ($prog_data): ?>
                        <?php
                            $date_prog = new DateTime($prog_data['scheduled_date']);
                        ?>
                        <div class="data"><?php echo $date_prog->format('d.m.Y'); ?></div>
                        <p>Ora: <?php echo $date_prog->format('H:i'); ?></p>
                        <p>Vehicul: <?php echo htmlspecialchars($prog_data['model']); ?></p>
                    <?php else: ?>
                        <div class="data">-</div>
                        <p>Nu ai programări viitoare.</p>
                        <a href="client_booking.php" class="btn btn-secondary btn-mt-5 btn-small">Fă o programare</a>
                    <?php endif; ?>
                </div>

                <div class="info-card animate-on-scroll">
                    <h3>Puncte Loialitate</h3>
                    <div class="data"><?php echo $client['loyalty_points']; ?> Puncte</div>
                    <p>
                        <?php
                            if($client['loyalty_points'] >= 100) {
                                echo "Felicitări! Poți beneficia de o reducere.";
                            } else {
                                echo "Mai ai nevoie de " . (100 - $client['loyalty_points']) . " puncte pentru o reducere.";
                            }
                        ?>
                    </p>
                </div>

            </section>
        </main>
        <?php include 'footer.php'; ?>
    </div>
    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>

    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>
