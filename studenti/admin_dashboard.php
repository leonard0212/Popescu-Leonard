<?php
session_start();
require_once 'db_connect.php';

// 1. Verificare securitate
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// 1.b Procesare acțiuni administrare programări (Acceptare / Respingere)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
    $allowed = ['confirmed', 'rejected'];

    if ($appointment_id > 0 && in_array($new_status, $allowed, true)) {
        // Verificăm că această programare aparține unui client al adminului curent
        $check_app = $conn->prepare("SELECT a.id FROM appointments a JOIN clients c ON a.client_id = c.id WHERE a.id = ? AND c.admin_id = ? LIMIT 1");
        if ($check_app) {
            $check_app->bind_param('ii', $appointment_id, $admin_id);
            $check_app->execute();
            $check_res = $check_app->get_result();

            if ($check_res && $check_res->num_rows > 0) {
                // Actualizăm statusul cererii
                $u = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
                if ($u) {
                    $u->bind_param("si", $new_status, $appointment_id);
                    $u->execute();
                }

                // Dacă a fost confirmată, creăm (dacă nu există) o intervenție pentru calendar
                if ($new_status === 'confirmed') {
                    $q = $conn->prepare("SELECT client_id, equipment_id, appointment_date, description FROM appointments WHERE id = ? LIMIT 1");
                    if ($q) {
                        $q->bind_param("i", $appointment_id);
                        $q->execute();
                        $res = $q->get_result();
                        if ($res && $row = $res->fetch_assoc()) {
                            $c_id = (int)$row['client_id'];
                            $e_id = (int)$row['equipment_id'];
                            $appt_date = $row['appointment_date'];
                            $desc = $row['description'];

                            // Verificăm că clientul încă aparține adminului (defensiv)
                            $chk_owner = $conn->prepare("SELECT id FROM clients WHERE id = ? AND admin_id = ? LIMIT 1");
                            if ($chk_owner) {
                                $chk_owner->bind_param('ii', $c_id, $admin_id);
                                $chk_owner->execute();
                                $chk_owner_res = $chk_owner->get_result();
                                if (!($chk_owner_res && $chk_owner_res->num_rows > 0)) {
                                    // Ownership invalid — nu inserăm
                                    $res = null;
                                }
                            }

                            if ($res) {
                                // Verificăm dacă există deja o intervenție pentru aceleași detalii
                                $chk = $conn->prepare("SELECT id FROM interventions WHERE client_id = ? AND equipment_id = ? AND scheduled_date = ? LIMIT 1");
                                if ($chk) {
                                    $chk->bind_param("iis", $c_id, $e_id, $appt_date);
                                    $chk->execute();
                                    $chk_res = $chk->get_result();
                                    if (!($chk_res && $chk_res->num_rows > 0)) {
                                        // Inserăm intervenția cu status 'programata'
                                        $ins = $conn->prepare("INSERT INTO interventions (client_id, equipment_id, problem_description, diagnostic_notes, parts_used, labor_cost, status, scheduled_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                        if ($ins) {
                                            $empty = '';
                                            $zero = 0.0;
                                            $status_init = 'programata';
                                            $ins->bind_param("iisssdss", $c_id, $e_id, $desc, $empty, $empty, $zero, $status_init, $appt_date);
                                            $ins->execute();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Inițializăm contoarele
$programari_azi = 0;
$vehicule_service = 0;
$clienti_noi = 0;
$itp_expira = 0;

// 2. Interogări Statistici

// A. Programări Astăzi (status 'programata' sau 'in_desfasurare' cu data de azi)
$admin_id = $_SESSION['admin_id'];
$sql1 = "SELECT COUNT(*) as total FROM interventions i 
         JOIN clients c ON i.client_id = c.id 
         WHERE DATE(i.scheduled_date) = CURDATE() AND i.status != 'anulata' AND c.admin_id = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param('i', $admin_id);
$stmt1->execute();
$res1 = $stmt1->get_result();
if($res1) $programari_azi = $res1->fetch_assoc()['total'];

// B. Vehicule în Service (status 'in_desfasurare' indiferent de dată) - doar pentru admin
$sql2 = "SELECT COUNT(*) as total FROM interventions i JOIN clients c ON i.client_id = c.id WHERE i.status = 'in_desfasurare' AND c.admin_id = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('i', $admin_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
if($res2) $vehicule_service = $res2->fetch_assoc()['total'];

// C. Clienți Noi (Luna Curentă) - doar ai adminului
$sql3 = "SELECT COUNT(*) as total FROM clients WHERE admin_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$stmt3 = $conn->prepare($sql3);
$stmt3->bind_param('i', $admin_id);
$stmt3->execute();
$res3 = $stmt3->get_result();
if($res3) $clienti_noi = $res3->fetch_assoc()['total'];

// D. ITP-uri ce Expiră (în următoarele 30 zile) - doar pentru clientii adminului
$sql4 = "SELECT COUNT(*) as total FROM equipment e JOIN clients c ON e.client_id = c.id WHERE e.itp_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND c.admin_id = ?";
$stmt4 = $conn->prepare($sql4);
$stmt4->bind_param('i', $admin_id);
$stmt4->execute();
$res4 = $stmt4->get_result();
if($res4) $itp_expira = $res4->fetch_assoc()['total'];

// Numele service-ului din sesiune (sau default)
$service_name = isset($_SESSION['service_name']) ? $_SESSION['service_name'] : 'Admin Service';

// Preluăm cererile noi (status = 'pending') pentru afișare în dashboard
// Preluăm doar cererile noi ale clienților adminului curent
$pending_stmt = $conn->prepare("SELECT a.id, a.client_id, a.equipment_id, a.appointment_date, a.service_type, a.description, c.full_name, e.model
                                FROM appointments a
                                JOIN clients c ON a.client_id = c.id
                                JOIN equipment e ON a.equipment_id = e.id
                                WHERE a.status = 'pending' AND c.admin_id = ?
                                ORDER BY a.appointment_date ASC");
if ($pending_stmt) {
    $pending_stmt->bind_param('i', $admin_id);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
} else {
    $pending_result = null;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <h1>Dashboard</h1>
                <nav class="user-info">
                    <span>Bun venit, <?php echo htmlspecialchars($service_name); ?>!</span>
                    <a href="#" class="btn btn-secondary">Setări</a>
                </nav>
            </header>

            <section class="dashboard-widgets animate-on-scroll">
                <div class="widget animate-on-scroll">
                    <h3>Programări Astăzi</h3>
                    <div class="value"><?php echo $programari_azi; ?></div>
                </div>
                <div class="widget animate-on-scroll">
                    <h3>Vehicule în Service</h3>
                    <div class="value"><?php echo $vehicule_service; ?></div>
                </div>
                <div class="widget animate-on-scroll">
                    <h3>Clienți Noi (Luna)</h3>
                    <div class="value"><?php echo $clienti_noi; ?></div>
                </div>
                <div class="widget animate-on-scroll">
                    <h3>ITP-uri ce Expiră (30 zile)</h3>
                    <div class="value" style="color: #ffc107;"><?php echo $itp_expira; ?></div>
                </div>
            </section>
            
            <section class="admin-form animate-on-scroll">
                <h2>Cereri de Programare Noi</h2>
                <?php if ($pending_result && $pending_result->num_rows > 0): ?>
                    <table class="data-table" style="margin-top:10px;">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Client</th>
                                <th>Vehicul</th>
                                <th>Serviciu</th>
                                <th>Descriere</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($r = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($r['appointment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['model']); ?></td>
                                    <td><?php echo htmlspecialchars($r['service_type']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($r['description'], 0, 80)); ?></td>
                                    <td class="actions">
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="update_appointment" value="1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="new_status" value="confirmed">
                                            <button type="submit" class="btn btn-primary">✓ Acceptă</button>
                                        </form>
                                        <form method="POST" style="display:inline; margin-left:8px;">
                                            <input type="hidden" name="update_appointment" value="1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="new_status" value="rejected">
                                            <button type="submit" class="btn btn-secondary">X Respinge</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nu există cereri noi în așteptare.</p>
                <?php endif; ?>
            </section>
                <h2>Statistici Rapide</h2>
                <div class="calendar-placeholder" style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f9f9f9; border: 2px dashed #ddd;">
                    <p style="color: #888;">[Graficul de încasări va fi disponibil în versiunea PRO]</p>
                </div>
            </section>

        </main>
    </div>
    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>

    <div id="cookie-consent-banner" class="cookie-banner" role="dialog" aria-labelledby="cookie-consent-title" aria-describedby="cookie-consent-text" aria-hidden="true">
        <p id="cookie-consent-text" class="cookie-banner__text">
            Acest site folosește cookie-uri pentru a vă oferi o experiență mai bună. Navigând în continuare, sunteți de acord cu <a href="privacy.php">politica noastră de confidențialitate</a>.
        </p>
        <button id="cookie-consent-accept" class="btn btn-primary">Am înțeles</button>
    </div>

    <script src="js/cookie-consent.js"></script>
    <script src="js/animations.js"></script>
</body>
</html>