<?php
session_start();

// If no candidates, redirect to login
if (empty($_SESSION['login_candidates']) || !is_array($_SESSION['login_candidates'])) {
    header('Location: login.php');
    exit();
}

// Handle selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['client_id'])) {
    $chosen = (int)$_POST['client_id'];
    $found = null;
    foreach ($_SESSION['login_candidates'] as $c) {
        if ($c['client_id'] === $chosen) { $found = $c; break; }
    }

    if ($found) {
        // Set final session (client logged in under chosen service)
        $_SESSION['client_id'] = $found['client_id'];
        $_SESSION['client_name'] = $found['client_name'];
        $_SESSION['admin_id'] = $found['admin_id'];
        $_SESSION['role'] = 'client';
        // Clean up
        unset($_SESSION['login_candidates']);
        header('Location: client_dashboard.php');
        exit();
    } else {
        $error = "Selecție invalidă.";
    }
}

$candidates = $_SESSION['login_candidates'];
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Alege Service - Autentificare</title>
    <link rel="stylesheet" href="style/main.css">
</head>
<body>
    <div class="form-container">
        <h1>Alege service</h1>
        <p>Am găsit mai multe conturi asociate aceleiași adrese de email. Alege service-ul la care dorești să te autentifici:</p>

        <?php if (!empty($error)): ?>
            <div class="form-alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php foreach ($candidates as $c): ?>
                <div class="form-group">
                    <label>
                        <input type="radio" name="client_id" value="<?php echo (int)$c['client_id']; ?>" required>
                        <?php echo htmlspecialchars($c['service_name'] . ' — ' . $c['client_name']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Conectează-te</button>
            </div>
        </form>

        <p><a href="logout.php">Anulează</a></p>
    </div>
</body>
</html>
