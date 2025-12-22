<?php
session_start();
// Aceasta este pagina ta originală, transformată în PHP pentru a gestiona logarea.
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiceFlow - Digitalizează-ți Service-ul</title>
    <link rel="stylesheet" href="style/main.css">
</head>
<body id="top">
    
    <header class="navbar">
        <div class="container">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="ServiceFlow Logo" class="logo">
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Acasă</a></li>
                    <li><a href="features.php">Funcționalități</a></li>
                    <li><a href="pricing.php">Prețuri</a></li>
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

    <main>
        <section class="hero animate-on-scroll">
            <div class="container">
                <h1>Digitalizează-ți service-ul. Fidelizează-ți clienții.</h1>
                <p>Platforma completă pentru managementul clienților, programări online și notificări automate.</p>
                
                <?php if(isset($_SESSION['admin_id'])): ?>
                    <a href="admin_dashboard.php" class="btn btn-primary">Mergi la Dashboard</a>
                <?php elseif(isset($_SESSION['client_id'])): ?>
                    <a href="client_dashboard.php" class="btn btn-primary">Mergi la Portal</a>
                <?php else: ?>
                    <a href="signup.php" class="btn btn-primary">Începe Gratuit Acum</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="section animate-on-scroll">
            <div class="container">
                <h2 class="section-title animate-on-scroll">De ce ServiceFlow?</h2>
                <div class="features-grid animate-on-scroll">
                    <div class="feature-card animate-on-scroll">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📅</div>
                        
                        <h3>Programări Online</h3>
                        <p>Permite clienților să se programeze singuri, 24/7, reducând munca la recepție.</p>
                    </div>
                    <div class="feature-card animate-on-scroll">
                        <div style="font-size: 3rem; margin-bottom: 10px;">🔔</div>
                        
                        <h3>Notificări Automate</h3>
                        <p>Trimite automat remindere pentru ITP, revizii sau expirarea garanției. Nu mai uita niciun client.</p>
                    </div>
                    <div class="feature-card animate-on-scroll">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📂</div>
                        
                        <h3>Istoric Digital</h3>
                        <p>Păstrează un istoric complet al intervențiilor pentru fiecare vehicul, accesibil oricând.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 ServiceFlow. Toate drepturile rezervate.</p>
            <div class="footer-links">
                <a href="#">Termeni și Condiții</a>
                <a href="privacy.php">Politica de Confidențialitate</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </footer>
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