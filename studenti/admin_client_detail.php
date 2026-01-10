<?php
session_start();
require_once 'db_connect.php';

// Verificăm autentificarea și existența ID-ului
if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header("Location: admin_clients.php");
    exit();
}

$client_id = (int)$_GET['id'];
$admin_id = $_SESSION['admin_id'];

// 1. Logică Update Client (dacă s-a trimis formularul)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_client'])) {
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    
    // Simplă validare
    if(!empty($phone) && !empty($email)) {
        $stmt = $conn->prepare("UPDATE clients SET phone=?, email=? WHERE id=? AND admin_id = ?");
        $stmt->bind_param("ssii", $phone, $email, $client_id, $admin_id);
        
        if($stmt->execute()) {
            $success_msg = "Datele au fost actualizate cu succes!";
        } else {
            $error_msg = "Eroare la actualizare.";
        }
    }
}

// 2. Fetch Client Info (only if belongs to current admin)
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ? AND admin_id = ?");
$stmt->bind_param("ii", $client_id, $admin_id);
$stmt->execute();
$client_result = $stmt->get_result();

if ($client_result->num_rows === 0) {
    die("Clientul nu a fost găsit.");
}

$client = $client_result->fetch_assoc();

// 3. Fetch Echipamente Client (only if client belongs to admin) - client check already performed
$equip_stmt = $conn->prepare("SELECT * FROM equipment WHERE client_id = ? ORDER BY created_at DESC");
$equip_stmt->bind_param('i', $client_id);
$equip_stmt->execute();
$equipments = $equip_stmt->get_result();

// 4. Fetch Istoric Intervenții
// 4. Fetch Istoric Intervenții (client scoped already)
$hist_stmt = $conn->prepare("SELECT i.*, e.model 
                             FROM interventions i 
                             JOIN equipment e ON i.equipment_id = e.id 
                             WHERE i.client_id = ? 
                             ORDER BY i.scheduled_date DESC");
$hist_stmt->bind_param('i', $client_id);
$hist_stmt->execute();
$history = $hist_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalii Client - ServiceHub</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <button id="sidebar-toggle" class="sidebar-toggle" style="display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-right: 1rem;">&#9776;</button>
                <h1>Client: <?php echo htmlspecialchars($client['full_name']); ?></h1>
                <a href="admin_clients.php" class="btn btn-secondary">Înapoi la Listă</a>
            </header>

            <?php if(isset($success_msg)): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <section class="admin-form animate-on-scroll" style="margin-bottom: 2rem;">
                <h2>Date de Contact</h2>
                <form method="POST">
                    <input type="hidden" name="update_client" value="1">
                    <div class="form-group">
                        <label>Telefon</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Puncte Loialitate</label>
                        <input type="text" value="<?php echo $client['loyalty_points']; ?>" disabled style="background-color: #f0f0f0;">
                    </div>
                    <button type="submit" class="btn btn-primary">Actualizează Date</button>
                </form>
            </section>

            <section class="admin-form animate-on-scroll" style="margin-bottom: 2rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h2>Echipamente / Vehicule</h2>
                    <a href="admin_equipment_new.php" class="btn btn-sm btn-secondary" style="font-size: 0.9rem;">+ Adaugă Vehicul</a>
                </div>
                
                <table class="data-table">
                    <thead><tr><th>Model</th><th>Serie / VIN</th><th>ITP Expiră</th></tr></thead>
                    <tbody>
                        <?php if ($equipments->num_rows > 0): ?>
                            <?php while($eq = $equipments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($eq['model']); ?></td>
                                    <td><?php echo htmlspecialchars($eq['serial_number']); ?></td>
                                    <td>
                                        <?php 
                                            if ($eq['itp_expiry_date']) {
                                                echo date('d.m.Y', strtotime($eq['itp_expiry_date']));
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3">Acest client nu are echipamente înregistrate.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="admin-form animate-on-scroll">
                <h2>Istoric Intervenții</h2>
                <table class="data-table">
                    <thead><tr><th>Data</th><th>Vehicul</th><th>Problemă</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if ($history->num_rows > 0): ?>
                            <?php while($h = $history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y', strtotime($h['scheduled_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($h['model']); ?></td>
                                    <td><?php echo htmlspecialchars($h['problem_description']); ?></td>
                                    <td>
                                        <?php 
                                            $status = $h['status'];
                                            $color = '#000';
                                            if($status == 'programata') $color = '#007bff';
                                            if($status == 'in_desfasurare') $color = '#ffc107';
                                            if($status == 'finalizata') $color = '#28a745';
                                            
                                            // Transformăm "in_desfasurare" în "În Desfășurare"
                                            $status_text = ucfirst(str_replace('_', ' ', $status));
                                            if($status == 'in_desfasurare') $status_text = 'În Desfășurare';
                                            if($status == 'programata') $status_text = 'Programată';
                                            if($status == 'finalizata') $status_text = 'Finalizată';
                                        ?>
                                        <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4">Nu există intervenții în istoric.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>