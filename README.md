

```markdown
# Popescu-Leonard - ServiceHub

![GitHub](https://img.shields.io/badge/language-PHP-blue)
![GitHub](https://img.shields.io/badge/database-MySQL-orange)
![GitHub](https://img.shields.io/badge/docker-enabled-green)
![License](https://img.shields.io/badge/license-Private-red)

## 📋 Description

**ServiceHub** is a comprehensive web application for digitizing and managing automotive service operations. It enables businesses to handle client management, online appointment scheduling, automated notifications, and maintain complete service history records.

**Key Tagline:** *Digitize your service. Retain your clients.*

🌐 **Live Site:** [servicehub.llogo.ro](https://servicehub.llogo.ro)

---

## ✨ Core Features

### 📅 Online Appointments
- 24/7 self-service appointment booking for clients
- Automated calendar management
- Appointment reminders and notifications
- Flexible scheduling system

### 🔔 Automated Notifications
- Automatic reminders for ITP (vehicle inspection), reviews, warranty expiration
- Client retention through proactive communication
- No missed appointments or service deadlines

### 📁 Digital Service History
- Complete intervention history for each vehicle
- Accessible service records anytime, anywhere
- Detailed maintenance tracking
- Professional documentation for clients

### 🎯 Additional Features
- Secure client authentication and accounts
- Admin dashboard with operational analytics
- Equipment and resource management
- Invoice and payment tracking
- Marketing and promotional tools
- Automated cron jobs and processes
- GDPR compliance with cookie consent

---

## 🏗️ Project Structure

```
Popescu-Leonard/
├── studenti/                          # Main PHP application (production code)
│   ├── index.php                      # Homepage and public interface
│   ├── login.php                      # Client authentication
│   ├── signup.php                     # New account registration
│   ├── logout.php                     # Session termination
│   ├── privacy.php                    # Privacy policy page
│   │
│   ├── admin_dashboard.php            # Admin control panel
│   ├── admin_calendar.php             # Appointment scheduling system
│   ├── admin_clients.php              # Client management interface
│   ├── admin_equipment.php            # Resource management
│   ├── admin_interventions.php        # Service intervention tracking
│   ├── admin_invoice.php              # Billing and payment management
│   ├── admin_marketing.php            # Marketing campaigns
│   ├── admin_automations.php          # Automated processes and cron jobs
│   │
│   ├── db_connect.php                 # Database connection module
│   ├── features.php                   # System features page
│   ├── cron_process.php               # Automated background processes
│   ├── setup_automations_db.php       # Initial automation setup
│   │
│   ├── assets/
│   │   ├── images/                    # Logo and UI images
│   │   ├── js/                        # JavaScript functionality
│   │   │   ├── cookie-consent.js      # Cookie consent banner
│   │   │   └── admin_clients.js       # Client interface interactions
│   │   └── style/                     # CSS stylesheets
│   │       ├── main.css               # Primary styles
│   │       └── admin.css              # Admin panel styles
│   │
│   ├── footer.php                     # Shared footer component
│   ├── service_flow_db.sql            # Database schema
│   ├── README.md                      # Project documentation
│   └── README_DOCKER.md               # Docker setup guide
│
├── queries/                           # SQL database files
│   ├── Query.sql                      # General database queries
│   ├── Query_1.sql                    # Additional SQL scripts
│   └── service_flow_db.sql            # Complete database schema
│
├── apache/                            # Docker configuration
│   └── Dockerfile                     # Apache + PHP container setup
│
├── docker-compose.yml                 # Docker Compose orchestration
├── README.md                          # This file
└── .gitignore                         # Git exclusion rules

```

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
