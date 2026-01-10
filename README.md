# Popescu-Leonard - ServiceHub

![PHP](https://img.shields.io/badge/PHP-%3E%3D%207.4-blue?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-%3E%3D%205.7-orange?style=flat-square&logo=mysql)
![Docker](https://img.shields.io/badge/Docker-Enabled-brightgreen?style=flat-square&logo=docker)
![License](https://img.shields.io/badge/License-Private-red?style=flat-square)

## 📋 Description

**ServiceHub** is a comprehensive web application for digitizing and managing automotive service operations. It enables businesses to handle client management, online appointment scheduling, automated notifications, and maintain complete service history records.

> **Digitize your service. Retain your clients.**

🌐 **Live Demo:** [servicehub.llogo.ro](https://servicehub.llogo.ro)

---

## ✨ Core Features

### 📅 Online Appointments
* **24/7 Self-Service:** Clients can book appointments anytime.
* **Calendar Management:** Automated sync and conflict resolution.
* **Notifications:** Instant alerts for new bookings.

### 🔔 Automated Retentions
* **Smart Reminders:** Automatic alerts for ITP (inspections), oil changes, and warranty expirations.
* **Client Engagement:** Proactive communication to reduce "no-shows".

### 📁 Digital Service History
* **Cloud Records:** Full intervention history for every vehicle.
* **Maintenance Tracking:** Detailed logs of parts and labor.
* **Transparency:** Professional documentation accessible to clients.

### 🎯 Business Tools
* **Admin Dashboard:** Real-time operational analytics.
* **Resource Management:** Track equipment and workshop availability.
* **Billing:** Integrated invoice and payment tracking.
* **GDPR Ready:** Built-in cookie consent and data privacy modules.

---

## 🏗️ Project Structure

```text
Popescu-Leonard/
├── studenti/                          # Main Application Source
│   ├── index.php                      # Public Landing Page
│   ├── login.php                      # Client Authentication
│   ├── signup.php                     # User Registration
│   ├── admin_dashboard.php            # Administrative Control Panel
│   ├── admin_calendar.php             # Appointment Scheduling
│   ├── admin_clients.php              # CRM Module
│   ├── admin_equipment.php            # Inventory Management
│   ├── admin_interventions.php        # Service Ticket Tracking
│   ├── admin_invoice.php              # Billing & Invoicing
│   ├── admin_marketing.php            # Campaigns & Emailing
│   ├── admin_automations.php          # Logic & Rules Engine
│   ├── db_connect.php                 # Database Connection
│   ├── cron_process.php               # Background Worker Scripts
│   ├── assets/                        # Static Resources (JS, CSS, Images)
│   ├── service_flow_db.sql            # Local Schema Backup
│   └── README_DOCKER.md               # Docker Documentation
├── queries/                           # SQL Scripts & Maintenance
│   ├── Query.sql                      # Utility Queries
│   └── service_flow_db.sql            # Master Database Schema
├── apache/                            # Web Server Configuration
│   └── Dockerfile                     # PHP-Apache Environment
├── docker-compose.yml                 # Container Orchestration
└── README.md                          # Repository Documentation

---

## 🚀 Installation & Setup

### ✅ Requirements

- **PHP** >= 7.4
- **MySQL/MariaDB** >= 5.7
- **Apache** with mod_rewrite enabled
- **Docker & Docker Compose** (optional but recommended)
- **Git**

### 📦 Local Installation (Without Docker)

#### 1. Clone Repository
```bash
git clone https://github.com/leonard0212/Popescu-Leonard.git
cd Popescu-Leonard
```

#### 2. Database Setup
```bash
# Create new MySQL database
mysql -u root -p -e "CREATE DATABASE service_flow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import database schema
mysql -u root -p service_flow_db < queries/service_flow_db.sql

```

#### 3. Configure Database Connection
Edit `studenti/db_connect.php`:
```php
$host = "localhost";
$user = "root";
$password = "YOUR_PASSWORD";
$dbname = "service_flow_db";
$port = 3306;
```

#### 4. Configure Apache VirtualHost
Create `/etc/apache2/sites-available/servicehub.conf`:
```apache
<VirtualHost *:80>
    ServerName servicehub.local
    ServerAlias www.servicehub.local
    DocumentRoot /var/www/Popescu-Leonard/studenti
    
    <Directory /var/www/Popescu-Leonard/studenti>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/servicehub-error.log
    CustomLog ${APACHE_LOG_DIR}/servicehub-access.log combined
</VirtualHost>
```

Enable site and restart Apache:
```bash
sudo a2ensite servicehub.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### 5. Access Application
- **Client:** `http://servicehub.local`
- **Admin:** `http://servicehub.local/admin_dashboard.php`

---

### 🐳 Docker Installation (Recommended)

#### 1. Clone Repository
```bash
git clone https://github.com/leonard0212/Popescu-Leonard.git
cd Popescu-Leonard
```

#### 2. Configure Docker
Edit `docker-compose.yml` credentials:
```yaml
environment:
  MYSQL_ROOT_PASSWORD: secure_password
  MYSQL_DATABASE: service_flow_db
  MYSQL_USER: servicehub
  MYSQL_PASSWORD: app_password
```

#### 3. Start Containers
```bash
docker-compose up -d
```

#### 4. Initialize Database
```bash
docker exec servicehub-mysql mysql -u root -psecure_password service_flow_db < queries/service_flow_db.sql
```

#### 5. Access Application
- **Client:** `http://localhost:8080`
- **Admin:** `http://localhost:8080/admin_dashboard.php`



---

## 🛠️ Technology Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | PHP 7.4+ (plain, no framework) |
| **Database** | MySQL 5.7+ / MariaDB |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript |
| **Server** | Apache 2.4+ |
| **Containerization** | Docker & Docker Compose |
| **Version Control** | Git / GitHub |

---

## 📊 Database Structure

Main database tables:
- `users` - Client and admin accounts
- `clients` - Client profile information
- `vehicles` - Vehicle records
- `appointments` - Scheduled service appointments
- `services` - Service types and categories
- `interventions` - Service work records
- `equipment` - Resources and tools
- `invoices` - Billing and payments
- `automations` - Scheduled tasks and reminders

---

## 🔒 Security Implementation

✅ **Currently Implemented:**
- Password hashing with bcrypt
- Prepared statements (PDO/MySQLi)
- Input validation and sanitization
- CSRF token protection
- Session security
- GDPR cookie consent banner
- SQL injection prevention

⚠️ **Production Recommendations:**
- Enable HTTPS/SSL certificates
- Configure firewall rules
- Implement WAF (ModSecurity)
- Regular database backups
- Access log monitoring
- Update PHP/MySQL regularly
- Restrict admin panel IP access
- Disable PHP directory listing

---

## 📝 Usage Guide

### For Clients
1. **Sign Up** - Create new account at `/signup.php`
2. **Login** - Access dashboard at `/login.php`
3. **Book Appointment** - Schedule service online
4. **View History** - Check service records
5. **Manage Profile** - Update personal information

### For Admin
1. **Dashboard** - Overview and statistics
2. **Client Management** - Add, edit, remove clients
3. **Calendar** - Manage appointment schedules
4. **Equipment** - Track resources and inventory
5. **Invoicing** - Generate and track payments
6. **Marketing** - Create promotional campaigns
7. **Automations** - Configure reminder notifications
8. **Reports** - View analytics and metrics

---

## 🚦 Development Workflow

### Local Development
```bash
# Clone and setup
git clone <repo>
cd Popescu-Leonard
php -S localhost:8000 -t studenti/

# Access at http://localhost:8000
```

### Git Workflow
```bash
# Create feature branch
git checkout -b feature/new-feature

# Commit changes
git add .
git commit -m "Add new feature description"

# Push to GitHub
git push origin feature/new-feature
```



## 📄 License & Ownership

**All Rights Reserved © 2025 - Leonard Popescu**

This project is private property. Unauthorized use, modification, or distribution is prohibited.

---

## 📞 Contact & Support

- **Primary Email:** leonard@llogo.ro
- **GitHub:** [@leonard0212](https://github.com/leonard0212)
- **Website:** [servicehub.llogo.ro](https://servicehub.llogo.ro)
- **Support:** Issues and feature requests via GitHub


---

## 🎯 Project Goals

✓ Provide complete service management solution
✓ Reduce administrative overhead
✓ Improve client retention through automation
✓ Maintain comprehensive service history
✓ Enable 24/7 appointment booking
✓ Scale to enterprise needs

---

## ✅ Recent Updates (Jan 2026)

- Database schema improvements
- Admin interface refinements
- Automation system enhancements
- Security updates
- Cookie consent implementation
- Responsive design optimization

---

**Version:** 1.0.0  
**Last Updated:** January 10, 2026  
**Status:** Production Ready  
**Maintainer:** Leonard Popescu
```
