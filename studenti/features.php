<?php
session_start();
// Aceasta este o pagină publică, deci nu verificăm dacă e logat pentru a restricționa accesul,
// ci doar pentru a adapta meniul de navigare.
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcționalități - ServiceHub</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/pages.css">
</head>
<body id="top">

    <header class="navbar">
        <div class="container">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="ServiceHub Logo" class="logo">
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Acasă</a></li>
                    <li><a href="features.php" class="active">Funcționalități</a></li>
                </ul>
            </nav>
            <div>
                <?php if(isset($_SESSION['admin_id'])): ?>
                    <a href="admin_dashboard.php" class="btn btn-primary">Panou Admin</a>
                <?php elseif(isset($_SESSION['client_id'])): ?>
                    <a href="client_dashboard.php" class="btn btn-primary">Portal Client</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary">Autentificare</a>
                    <a href="signup.php" class="btn btn-primary">Creează Cont</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="section animate-on-scroll">
        <div class="container">
                <div class="hero-text">
                <h1 class="section-title">Tot ce ai nevoie pentru un service modern</h1>
                <p class="hero-subtitle">ServiceHub înlocuiește agendele și fișierele Excel cu o soluție complet digitalizată.</p>
            </div>

            <div class="features-grid animate-on-scroll">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-illustration feat-ill-1"><i class="fas fa-users"></i></div>
                    <h3>Gestiune Clienți și Echipamente</h3>
                    <p>O bază de date centralizată cu toți clienții și vehiculele lor. Acces rapid la istoricul complet.</p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-illustration feat-ill-2"><i class="fas fa-calendar-alt"></i></div>
                    <h3>Calendar Inteligent</h3>
                    <p>Un calendar vizual al programărilor. Evită suprapunerile și optimizează timpul mecanicilor.</p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-illustration feat-ill-3"><i class="fas fa-bullhorn"></i></div>
                    <h3>Marketing Automatizat</h3>
                    <p>Trimite automat notificări pentru ITP, revizii sau oferte speciale prin Email sau SMS.</p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-illustration feat-ill-4"><i class="fas fa-clipboard-list"></i></div>
                    <h3>Istoric Service Detaliat</h3>
                    <p>Fiecare intervenție este înregistrată digital. Diagnostic, piese, manoperă și costuri.</p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-illustration feat-ill-5"><i class="fas fa-laptop"></i></div>
                    <h3>Portal Dedicat Clientului</h3>
                    <p>Clienții tăi au propriul cont unde își văd istoricul, garanțiile și pot face programări online.</p>
                </div>

                <div class="feature-card animate-on-scroll">
                    <div class="feature-illustration feat-ill-6"><i class="fas fa-star"></i></div>
                    <h3>Sistem de Loialitate</h3>
                    <p>Fidelizează clienții acordând puncte pentru fiecare vizită și oferind beneficii automate.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section cta-section">
            <div class="container animate-on-scroll">
            <h2 class="hero-cta-title">Gata să digitalizezi service-ul tău?</h2>
            <p class="cta-subtitle">Alătură-te comunității ServiceHub astăzi!</p>
            <a href="signup.php" class="btn btn-cta">Începe Acum</a>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 ServiceHub. Toate drepturile rezervate.</p>
        </div>
    </footer>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>