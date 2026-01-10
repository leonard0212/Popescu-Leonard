<?php
session_start();
require_once 'db_connect.php';

// Verificare autentificare
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Preluăm clienții pentru Dropdown (doar ai adminului curent)
$admin_id = $_SESSION['admin_id'];
$clients_stmt = $conn->prepare("SELECT id, full_name FROM clients WHERE admin_id = ? ORDER BY full_name ASC");
$clients_stmt->bind_param("i", $admin_id);
$clients_stmt->execute();
$clients = $clients_stmt->get_result();

// Procesare Formular
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $client_id = $_POST['client_id'];
    $model = trim($_POST['model']);
    $serial = trim($_POST['serie']);
    
    // Dacă câmpurile de dată sunt goale, trimitem NULL în baza de date
    $itp = !empty($_POST['itp']) ? $_POST['itp'] : NULL;
    $warranty = !empty($_POST['garantie']) ? $_POST['garantie'] : NULL;

    // Validare sumară
    if(empty($client_id) || empty($model) || empty($serial)) {
        $message = "Te rog completează clientul, modelul și seria.";
    } else {
        // Ensure the client belongs to this admin before inserting
        $check = $conn->prepare("SELECT id FROM clients WHERE id = ? AND admin_id = ? LIMIT 1");
        $check->bind_param("ii", $client_id, $admin_id);
        $check->execute();
        $check_res = $check->get_result();
        if ($check_res && $check_res->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO equipment (client_id, model, serial_number, warranty_expiry_date, itp_expiry_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $client_id, $model, $serial, $warranty, $itp);
            
            if ($stmt->execute()) {
                header("Location: admin_equipment.php");
                exit();
            } else {
                $message = "Eroare la salvare: " . $stmt->error;
            }
        } else {
            $message = "Client invalid sau nu aparține adminului curent.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaugă Echipament - ServiceHub</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <button id="sidebar-toggle" class="sidebar-toggle" style="display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-right: 1rem;">&#9776;</button>
                <h1>Adaugă Vehicul Nou</h1>
                <a href="admin_equipment.php" class="btn btn-secondary">Înapoi la Listă</a>
            </header>

            <section class="admin-form animate-on-scroll">
                
                <?php if($message): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label>Selectează Clientul *</label>
                        <select name="client_id" required>
                            <option value="">-- Alege Client --</option>
                            <?php while($c = $clients->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model Echipament / Vehicul *</label>
                        <input type="text" name="model" placeholder="ex: Dacia Logan" required>
                    </div>
                    <div class="form-group">
                        <label>Serie Șasiu / Nr. Înmatriculare *</label>
                        <input type="text" name="serie" placeholder="ex: B 123 ABC" required>
                    </div>
                     <div class="form-group">
                        <label>Dată Expirare ITP (Opțional)</label>
                        <input type="date" name="itp">
                    </div>
                    <div class="form-group">
                        <label>Dată Expirare Garanție (Opțional)</label>
                        <input type="date" name="garantie">
                    </div>
                    <button type="submit" class="btn btn-primary">Salvează Echipament</button>
                </form>
            </section>
        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>