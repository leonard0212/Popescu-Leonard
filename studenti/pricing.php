<?php
session_start();
// Pagina de Prețuri - Publică
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prețuri - ServiceFlow</title>
    <link rel="stylesheet" href="style/main.css">
    <style>
        /* CSS Specific pentru Pagina de Prețuri */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
            align-items: center; /* Aliniere verticală */
        }
        
        .pricing-plan {
            background: white;
            border-radius: 10px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            position: relative;
            border: 1px solid #eee;
        }

        .pricing-plan:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .pricing-plan.featured {
            border: 2px solid #007bff;
            transform: scale(1.05);
            z-index: 1;
            box-shadow: 0 10px 25px rgba(0,123,255,0.15);
        }
        
        .pricing-plan.featured:hover {
            transform: scale(1.05) translateY(-10px);
        }

        .pricing-plan h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }

        .price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #007bff;
            margin-bottom: 20px;
        }
        
        .price span {
            font-size: 1rem;
            color: #888;
            font-weight: normal;
        }

        .pricing-plan ul {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
            text-align: left;
        }

        .pricing-plan ul li {
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
            color: #666;
        }

        .pricing-plan ul li::before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
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
                    <li><a href="pricing.php" class="active">Prețuri</a></li>
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
            <div style="text-align: center; max-width: 700px; margin: 0 auto;">
                <h1 class="section-title">Un plan pentru fiecare service</h1>
                <p style="font-size: 1.1rem; color: #666;">Alege planul care se potrivește cel mai bine nevoilor tale actuale. Poți face upgrade oricând.</p>
            </div>
            
            <div class="pricing-grid animate-on-scroll">
                <div class="pricing-plan animate-on-scroll">
                    <h3>Basic</h3>
                    <div class="price">49 RON<span>/lună</span></div>
                    <p style="color: #888; margin-bottom: 20px;">Pentru service-urile la început de drum.</p>
                    <ul>
                        <li>Până la 100 de clienți</li>
                        <li>Gestiune Echipamente</li>
                        <li>Calendar Programări</li>
                        <li>Istoric Service</li>
                        <li>Suport Email</li>
                    </ul>
                    <a href="signup.php" class="btn btn-secondary" style="width: 100%; display: block;">Alege Basic</a>
                </div>

                <div class="pricing-plan featured animate-on-scroll">
                    <div style="background: #007bff; color: white; padding: 5px 15px; border-radius: 20px; position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 0.8rem; font-weight: bold;">POPULAR</div>
                    <h3>Pro</h3>
                    <div class="price">99 RON<span>/lună</span></div>
                    <p style="color: #888; margin-bottom: 20px;">Ideal pentru service-uri în creștere.</p>
                    <ul>
                        <li>Clienți nelimitați</li>
                        <li><strong>Totul din Basic, plus:</strong></li>
                        <li>Programări Online (Portal Client)</li>
                        <li>Notificări Automate (ITP, Revizii)</li>
                        <li>Sistem Loialitate</li>
                        <li>Suport Prioritar</li>
                    </ul>
                    <a href="signup.php" class="btn btn-primary" style="width: 100%; display: block;">Alege Pro</a>
                </div>

                <div class="pricing-plan animate-on-scroll">
                    <h3>Enterprise</h3>
                    <div class="price">Custom</div>
                    <p style="color: #888; margin-bottom: 20px;">Pentru rețele și nevoi complexe.</p>
                    <ul>
                        <li><strong>Totul din Pro, plus:</strong></li>
                        <li>Gestiune multi-locație</li>
                        <li>API de integrare</li>
                        <li>Training personalizat</li>
                        <li>Manager de cont dedicat</li>
                        <li>SLA Garantat</li>
                    </ul>
                    <a href="#" class="btn btn-secondary" style="width: 100%; display: block;">Contactează-ne</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 ServiceFlow. Toate drepturile rezervate.</p>
        </div>
    </footer>

    <a href="#top" class="back-to-top" aria-label="Mergi sus">&uarr;</a>
    <script src="js/animations.js"></script>
</body>
</html>