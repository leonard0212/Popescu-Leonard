<?php
// 1. Includem conexiunea la baza de date
require_once 'db_connect.php';

$message = "";
$error = "";

// 2. Verificăm dacă formularul a fost trimis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preluăm și curățăm datele din formular
    $nume_service = trim($_POST['service-name']);
    $cui = trim($_POST['cui']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    // Verificări simple
    if (empty($nume_service) || empty($email) || empty($pass)) {
        $error = "Te rog să completezi toate câmpurile obligatorii.";
    } else {
        // 3. Verificăm dacă email-ul există deja
        $check = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Acest email este deja înregistrat.";
        } else {
            // 4. Criptăm parola (OBLIGATORIU pentru securitate)
            $password_hash = password_hash($pass, PASSWORD_DEFAULT);

            // 5. Inserăm în baza de date
            // Presupunem planul 'basic' implicit la înregistrare
            $sql = "INSERT INTO admins (service_name, cui_cif, email, password_hash, subscription_plan) VALUES (?, ?, ?, ?, 'basic')";
            
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("ssss", $nume_service, $cui, $email, $password_hash);

                if ($stmt->execute()) {
                    // Succes! Redirecționăm către login
                    header("Location: login.php?success=created");
                    exit();
                } else {
                    $error = "Eroare la salvarea în baza de date: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Eroare la pregătirea interogării: " . $conn->error;
            }
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creează Cont Nou - ServiceHub</title>
    <link rel="stylesheet" href="style/main.css">
</head>
<body id="top">
    <div class="form-container animate-on-scroll">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="ServiceHub Logo" style="height: 60px;">
            </a>
        </div>
        <h1>Creează-ți contul de service</h1>
        
        <?php if($error): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="service-name">Numele Service-ului *</label>
                <input type="text" id="service-name" name="service-name" required value="<?php echo isset($_POST['service-name']) ? htmlspecialchars($_POST['service-name']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="cui">CUI / CIF</label>
                <input type="text" id="cui" name="cui" value="<?php echo isset($_POST['cui']) ? htmlspecialchars($_POST['cui']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="email">Adresa ta de Email (Admin) *</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="password">Parolă *</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Înregistrează-te</button>
            <p>Ai deja cont? <a href="login.php">Autentifică-te</a></p>
        </form>
    </div>
    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>