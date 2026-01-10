<?php
session_start();
require_once 'db_connect.php';

// Verificare securitate
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preluare date
    $nume = trim($_POST['nume']);
    $telefon = trim($_POST['telefon']);
    $email = trim($_POST['email']);
    $adresa = trim($_POST['adresa']);
    $cui = trim($_POST['cui']);
    $password_raw = trim($_POST['password']); // Parola necriptată
    $admin_id = $_SESSION['admin_id'];

    // Validare simplă
    if(empty($nume) || empty($telefon) || empty($password_raw)) {
        $message = "Numele, Telefonul și Parola sunt obligatorii.";
    } else {
        // Criptăm parola
        $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

        // Inserare în DB (Acum includem și password_hash)
        $stmt = $conn->prepare("INSERT INTO clients (admin_id, full_name, phone, email, address, cui_cif, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $admin_id, $nume, $telefon, $email, $adresa, $cui, $password_hash);

        if ($stmt->execute()) {
            header("Location: admin_clients.php"); // Redirect înapoi la listă
            exit();
        } else {
            $message = "Eroare la salvare: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaugă Client - ServiceHub</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body id="top">
    <div class="admin-wrapper">
        <?php include 'admin_sidebar.php'; ?>

        <main class="admin-content">
            <header class="admin-header animate-on-scroll">
                <button id="sidebar-toggle" class="sidebar-toggle" style="display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-right: 1rem;">&#9776;</button>
                <h1>Adaugă Client Nou</h1>
                <a href="admin_clients.php" class="btn btn-secondary">Înapoi la Listă</a>
            </header>

            <section class="admin-form animate-on-scroll">
                <?php if($message): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Nume Complet *</label>
                        <input type="text" name="nume" required>
                    </div>
                    <div class="form-group">
                        <label>Telefon *</label>
                        <input type="text" name="telefon" required>
                    </div>
                    <div class="form-group">
                        <label>Email (Folosit pentru autentificare)</label>
                        <input type="email" name="email">
                    </div>
                    
                    <div class="form-group" style="background: #eef2ff; padding: 10px; border-radius: 5px; border: 1px dashed #007bff;">
                        <label style="color: #007bff; font-weight: bold;">Setează o Parolă Inițială *</label>
                        <input type="text" name="password" value="123456" required>
                        <small>Clientul va folosi această parolă pentru a se conecta prima dată.</small>
                    </div>

                     <div class="form-group">
                        <label>Adresă</label>
                        <input type="text" name="adresa">
                    </div>
                    <div class="form-group">
                        <label>CUI / CIF (pentru Firme)</label>
                        <input type="text" name="cui">
                    </div>
                    <button type="submit" class="btn btn-primary">Salvează Client</button>
                </form>
            </section>
        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>