<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <button id="sidebar-close" class="sidebar-close" style="display: none;">&times;</button>
    <div class="logo-container">
        <?php
            $logo_src = "assets/images/logo.png";
            if(isset($_SESSION['logo_path']) && !empty($_SESSION['logo_path'])) {
                $logo_src = $_SESSION['logo_path'];
            }
        ?>
        <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo Service" class="logo">
    </div>
    <nav>
        <ul class="sidebar-nav">
            <li><a href="admin_dashboard.php" <?php if($current_page === 'admin_dashboard.php') echo 'class="active"'; ?>>Dashboard</a></li>

            <li>
                <a href="admin_clients.php" <?php if(in_array($current_page, ['admin_clients.php','admin_client_detail.php','admin_client_new.php'])) echo 'class="active"'; ?>>Gestiune Clienți</a>
            </li>
            <li>
                <a href="admin_client_new.php" <?php if($current_page === 'admin_client_new.php') echo 'class="active"'; ?>>&nbsp;&nbsp;<i class="fas fa-level-up-alt fa-rotate-90"></i> Adaugă Client</a>
            </li>

            <li>
                <a href="admin_equipment.php" <?php if(in_array($current_page, ['admin_equipment.php','admin_equipment_new.php'])) echo 'class="active"'; ?>>Gestiune Echipamente</a>
            </li>
            <li>
                <a href="admin_equipment_new.php" <?php if($current_page === 'admin_equipment_new.php') echo 'class="active"'; ?>>&nbsp;&nbsp;<i class="fas fa-level-up-alt fa-rotate-90"></i> Adaugă Echipament</a>
            </li>

            <li><a href="admin_calendar.php" <?php if($current_page === 'admin_calendar.php') echo 'class="active"'; ?>>Calendar Programări</a></li>

            <li>
                <a href="admin_interventions.php" <?php if(in_array($current_page, ['admin_interventions.php','admin_intervention_new.php'])) echo 'class="active"'; ?>>Gestiune Intervenții</a>
            </li>
            <li>
                <a href="admin_intervention_new.php" <?php if($current_page === 'admin_intervention_new.php') echo 'class="active"'; ?>>&nbsp;&nbsp;<i class="fas fa-level-up-alt fa-rotate-90"></i> Adaugă Intervenție</a>
            </li>

            <li><a href="admin_marketing.php" <?php if($current_page === 'admin_marketing.php') echo 'class="active"'; ?>>Comunicare &amp; Marketing</a></li>
            <li><a href="admin_automations.php" <?php if($current_page === 'admin_automations.php') echo 'class="active"'; ?>>Automatizări Inteligente</a></li>
            <li><a href="admin_settings.php" <?php if($current_page === 'admin_settings.php') echo 'class="active"'; ?>>Setări Cont</a></li>
            <li><a href="logout.php">Deconectare</a></li>
        </ul>
    </nav>
</aside>
