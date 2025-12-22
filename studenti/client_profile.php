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
$msg = '';
$msg_type = '';

// 2. Procesare Actualizare Date
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_info'])) {
    $nume = trim($_POST['nume']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $adresa = trim($_POST['adresa']);

    if(empty($nume) || empty($email)) {
        $msg = "Numele și Email-ul sunt obligatorii.";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE clients SET full_name=?, email=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("ssssi", $nume, $email, $telefon, $adresa, $client_id);
        
        if($stmt->execute()) {
            $msg = "Datele au fost actualizate cu succes!";
            $msg_type = "success";
        } else {
            $msg = "Eroare: " . $conn->error;
            $msg_type = "error";
        }
    }
}

// 3. Procesare Schimbare Parolă
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_pass'])) {
    $pass_new = $_POST['pass_new'];
    $pass_confirm = $_POST['pass_confirm'];

    if (strlen($pass_new) < 6) {
        $msg = "Parola nouă trebuie să aibă minim 6 caractere.";
        $msg_type = "error";
    } elseif ($pass_new !== $pass_confirm) {
        $msg = "Parolele nu coincid.";
        $msg_type = "error";
    } else {
        // Notă: Într-un sistem real, ar trebui să verificăm și parola veche aici.
        // Fiindcă adminul a creat contul fără parolă inițial, permitem setarea directă acum.
        $hash = password_hash($pass_new, PASSWORD_DEFAULT);
        
        // Verificăm dacă coloana password_hash există (pentru siguranță)
        $check_col = $conn->query("SHOW COLUMNS FROM clients LIKE 'password_hash'");
        if($check_col->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE clients SET password_hash=? WHERE id=?");
            $stmt->bind_param("si", $hash, $client_id);
            if($stmt->execute()) {
                $msg = "Parola a fost schimbată!";
                $msg_type = "success";
            }
        } else {
            $msg = "Eroare: Baza de date nu suportă parole pentru clienți încă (lipsește coloana password_hash).";
            $msg_type = "error";
        }
    }
}

// 4. Preluare Date Actuale Client
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilul Meu - Portal Client</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/client.css">
</head>
<body id="top">
    
    <div class="client-portal">
        <?php include 'client_sidebar.php'; ?>

        <main class="client-content">
            <header class="client-header">
                <h1>Profilul Meu</h1>
                <p>Actualizează-ți datele de contact și parola.</p>
            </header>

            <?php if($msg): ?>
                <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; color: white; background-color: <?php echo ($msg_type == 'success') ? '#28a745' : '#dc3545'; ?>;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form class="booking-form" action="" method="POST">
                <input type="hidden" name="update_info" value="1">
                
                <div class="form-group">
                    <label for="nume">Nume Complet</label>
                    <input type="text" id="nume" name="nume" value="<?php echo htmlspecialchars($client['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="telefon">Telefon</label>
                    <input type="text" id="telefon" name="telefon" value="<?php echo htmlspecialchars($client['phone']); ?>">
                </div>

                <div class="form-group">
                    <label for="adresa">Adresă</label>
                    <input type="text" id="adresa" name="adresa" value="<?php echo htmlspecialchars($client['address']); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Salvează Datele de Contact</button>
            </form>

            <form class="booking-form" style="margin-top: 2rem;" action="" method="POST">
                <input type="hidden" name="update_pass" value="1">
                <h2 style="margin-bottom: 1.5rem;">Schimbare Parolă</h2>
                
                <div class="form-group">
                    <label for="pass_new">Parola Nouă</label>
                    <input type="password" id="pass_new" name="pass_new" required>
                </div>
                 <div class="form-group">
                    <label for="pass_confirm">Confirmă Parola Nouă</label>
                    <input type="password" id="pass_confirm" name="pass_confirm" required>
                </div>
                <button type="submit" class="btn btn-secondary">Schimbă Parola</button>
            </form>
        </main>
    </div>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>