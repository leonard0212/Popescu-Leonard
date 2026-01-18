<?php
session_start();
// Pagina Politica de Confidențialitate
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politica de Confidențialitate - ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <style>
        /* Stiluri specifice pentru pagini de text (Privacy, Terms) */
        .text-content {
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
            color: #444;
        }
        .text-content h1 {
            margin-bottom: 10px;
            color: #333;
        }
        .text-content h2 {
            margin-top: 30px;
            margin-bottom: 15px;
            color: #007bff;
            font-size: 1.5rem;
        }
        .text-content p {
            margin-bottom: 15px;
        }
        .text-content ul {
            margin-bottom: 15px;
            padding-left: 20px;
        }
        .last-updated {
            color: #888;
            font-style: italic;
            margin-bottom: 40px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
    </style>
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
            <div class="text-content">
                <h1>Politica de Confidențialitate</h1>
                <p class="last-updated">Ultima actualizare: <?php echo date("d.m.Y"); ?></p>

                <h2>1. Introducere</h2>
                <p>Confidențialitatea datelor dumneavoastră este importantă pentru noi. Această politică explică modul în care ServiceFlow colectează, utilizează și protejează informațiile personale ale utilizatorilor.</p>

                <h2>2. Datele pe care le colectăm</h2>
                <p>Putem colecta următoarele tipuri de informații:</p>
                <ul>
                    <li>Informații de identificare (Nume, Email, Telefon) furnizate la crearea contului.</li>
                    <li>Informații despre vehicule (Număr înmatriculare, Serie șasiu) necesare pentru gestiunea service-ului.</li>
                    <li>Date tehnice despre dispozitivul utilizat pentru a accesa platforma.</li>
                </ul>

                <h2>3. Cum folosim datele</h2>
                <p>Utilizăm datele colectate pentru:</p>
                <ul>
                    <li>A furniza și menține serviciile noastre.</li>
                    <li>A vă notifica despre modificări ale serviciului sau programări.</li>
                    <li>A oferi asistență clienților.</li>
                    <li>A monitoriza utilizarea serviciului pentru a detecta probleme tehnice.</li>
                </ul>

                <h2>4. Securitatea Datelor</h2>
                <p>Securitatea datelor dumneavoastră este importantă pentru noi, dar rețineți că nicio metodă de transmitere prin Internet sau metodă de stocare electronică nu este 100% sigură. Ne străduim să folosim mijloace acceptabile comercial pentru a vă proteja datele personale.</p>

                <h2>5. Contact</h2>
                <p>Dacă aveți întrebări despre această Politică de Confidențialitate, ne puteți contacta la adresa de email: suport@serviceflow.ro</p>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>