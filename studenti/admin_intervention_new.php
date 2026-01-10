<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// 1. Procesare formular
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $client_id = $_POST['client_id'];
    $equipment_id = $_POST['equipment_id'];
    $problem = $_POST['problem'];
    $diagnostic = $_POST['diagnostic'];
    $parts = $_POST['parts'];
    $cost = $_POST['cost_manopera'];
    $status = $_POST['status'];
    $date = $_POST['scheduled_date']; // Format: YYYY-MM-DDTHH:MM

    // Validare de bază
    if(empty($client_id) || empty($equipment_id) || empty($date)) {
        $message = "Te rog selectează clientul, vehiculul și data programării.";
    } else {
        // Verificăm dacă clientul aparține adminului curent
        $admin_id = $_SESSION['admin_id'];
        $check_client = $conn->prepare("SELECT id FROM clients WHERE id = ? AND admin_id = ? LIMIT 1");
        $check_client->bind_param("ii", $client_id, $admin_id);
        $check_client->execute();
        $check_res = $check_client->get_result();
        if (!($check_res && $check_res->num_rows > 0)) {
            $message = "Client invalid sau nu aparține adminului curent.";
        } else {
            // Verificăm și dacă echipamentul aparține clientului
            $check_eq = $conn->prepare("SELECT id FROM equipment WHERE id = ? AND client_id = ? LIMIT 1");
            $check_eq->bind_param("ii", $equipment_id, $client_id);
            $check_eq->execute();
            $check_eq_res = $check_eq->get_result();
            if (!($check_eq_res && $check_eq_res->num_rows > 0)) {
                $message = "Echipament invalid pentru clientul selectat.";
            } else {
                $stmt = $conn->prepare("INSERT INTO interventions (client_id, equipment_id, problem_description, diagnostic_notes, parts_used, labor_cost, status, scheduled_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssdss", $client_id, $equipment_id, $problem, $diagnostic, $parts, $cost, $status, $date);

                if ($stmt->execute()) {
                    header("Location: admin_interventions.php");
                    exit();
                } else {
                    $message = "Eroare: " . $stmt->error;
                }
            }
        }
    }
}

// 2. Preluare date pentru Dropdown-uri
// Luăm doar clienții adminului curent
$admin_id = $_SESSION['admin_id'];
$clients_stmt = $conn->prepare("SELECT id, full_name FROM clients WHERE admin_id = ? ORDER BY full_name ASC");
$clients_stmt->bind_param("i", $admin_id);
$clients_stmt->execute();
$clients = $clients_stmt->get_result();

// Luăm toate echipamentele aferente clienților adminului curent, aducem și client_id ca să le putem filtra în JS
$equipment_stmt = $conn->prepare("SELECT e.id, e.client_id, e.model, e.serial_number FROM equipment e JOIN clients c ON e.client_id = c.id WHERE c.admin_id = ? ORDER BY e.model ASC");
$equipment_stmt->bind_param("i", $admin_id);
$equipment_stmt->execute();
$equipment = $equipment_stmt->get_result();

// Construim un array PHP pentru a-l folosi în JS
$equipment_list = [];
while($row = $equipment->fetch_assoc()) {
    $equipment_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaugă Intervenție - ServiceHub</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <button id="sidebar-toggle" class="sidebar-toggle" style="display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-right: 1rem;">&#9776;</button>
                <h1>Fișă Service Nouă</h1>
                <a href="admin_interventions.php" class="btn btn-secondary">Înapoi la Listă</a>
            </header>

            <section class="admin-form animate-on-scroll">
                
                <?php if($message): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label>1. Selectează Clientul *</label>
                        <select id="clientSelect" name="client_id" required onchange="filterEquipment()">
                            <option value="">-- Alege Client --</option>
                            <?php while($c = $clients->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>2. Selectează Echipamentul *</label>
                        <select id="equipmentSelect" name="equipment_id" required>
                            <option value="">-- Selectează întâi clientul --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Data și Ora Programării *</label>
                        <input type="datetime-local" name="scheduled_date" required>
                    </div>

                     <div class="form-group">
                        <label>3. Problemă Reclamată *</label>
                        <textarea name="problem" rows="3" required></textarea>
                    </div>
                     <div class="form-group">
                        <label>4. Diagnostic (Opțional)</label>
                        <textarea name="diagnostic" rows="3"></textarea>
                    </div>
                     <div class="form-group">
                        <label>5. Piese Necesare (Opțional)</label>
                        <textarea name="parts" rows="2" placeholder="Ex: Filtru ulei, Plăcuțe frână..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>6. Cost Manoperă (RON)</label>
                        <input type="number" step="0.01" name="cost_manopera">
                    </div>
                    <div class="form-group">
                        <label>7. Status Inițial</label>
                        <select name="status">
                            <option value="programata">Programată</option>
                            <option value="in_desfasurare">În Desfășurare (Mașina a intrat în service)</option>
                            <option value="finalizata">Finalizată</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Salvează Fișa</button>
                </form>
            </section>
        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
    <div id="equipment-data" data-json='<?php echo htmlspecialchars(json_encode($equipment_list), ENT_QUOTES); ?>'></div>
    <script src="js/intervention.js"></script>
</body>
</html>