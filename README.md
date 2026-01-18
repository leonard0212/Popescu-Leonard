<!-- ServiceFlow README -->
# ServiceFlow — Management pentru Service Auto

![PHP](https://img.shields.io/badge/PHP-%3E%3D%207.4-blue?style=flat-square&logo=php) ![MySQL](https://img.shields.io/badge/MySQL-%3E%3D%205.7-orange?style=flat-square&logo=mysql) ![Docker](https://img.shields.io/badge/Docker-Enabled-brightgreen?style=flat-square&logo=docker)

Pagina live: https://serviceflow.dev

Descriere scurtă
----------------
ServiceFlow este o aplicație web scrisă în PHP pentru gestionarea activităților unui service auto: clienți, vehicule/echipamente, programări, intervenții (work orders), facturare și campanii de marketing.

Proiectul principal se găsește în folderul `studenti/` și poate fi rulat local sau în Docker.

Funcționalități (detaliat)
--------------------------
- Autentificare: admini și clienți; suport pentru același email asociat mai multor service-uri — clientul alege service-ul la login.
- Management clienți: creare, editare, vizualizare istoric intervenții, puncte loialitate.
- Gestionare echipamente/vehicule: legare la client, serial numbers, termene ITP/garanție.
- Programări (calendar): creare programări, vizualizare calendar, notificări automate.
- Intervenții: înregistrare problemă, diagnostic, piese folosite, manoperă, status (programată, în desfășurare, finalizată, anulată).
- Facturare: generare facturi, defalcare `parts_amount` și `labor_amount`, aplicare TVA, emitere (publish) / draft, export tipărire.
- Marketing / Email: campanii SMTP configurabile per admin, targetare audiență, istoric campanii.
- Automatizări: reguli pentru memento-uri (ITP, reamintiri service) și acțiuni automate.
- UI responsive: stiluri centralizate în `studenti/style/common.css`; sidebar mobil cu toggle (hamburger).

Structura proiect
-----------------

```
Popescu-Leonard/
└── studenti/
    ├── index.php
    ├── login.php
    ├── select_service.php      # Alegere service (caz multiplu cont same-email)
    ├── signup.php
    ├── admin_dashboard.php
    ├── admin_marketing.php
    ├── admin_clients.php
    ├── admin_equipment.php
    ├── admin_interventions.php
    ├── admin_invoice.php
    ├── admin_invoice_confirm.php
    ├── issued_invoices.php
    ├── admin_sidebar.php
    ├── db_connect.php
    ├── js/
    │   ├── admin.js
    │   └── invoice_confirm.js
    ├── style/
    │   ├── common.css
    │   ├── admin.css
    │   ├── main.css
    │   └── client.css
    └── queries/
        └── service_flow_db.sql
```

Cerinte
--------
- PHP >= 7.4
- MySQL / MariaDB >= 5.7
- Apache (sau alt webserver) cu PHP
- Optional: Docker & Docker Compose

Instalare rapidă (fără Docker)
--------------------------------
1. Clone repo:

```bash
git clone <repo>
cd Popescu-Leonard
```

2. Creare și import DB:

```bash
mysql -u root -p -e "CREATE DATABASE service_flow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p service_flow_db < queries/service_flow_db.sql
```

3. Configurează `studenti/db_connect.php` cu credențiale.

4. Setează `studenti/` ca DocumentRoot și accesează site-ul.

Instalare cu Docker (sumar)
--------------------------
1. Modifică `docker-compose.yml` pentru parole și variabile de mediu.
2. Rulează `docker-compose up -d`.
3. Importă schema în containerul MySQL dacă este necesar.

Schema și migrații
-------------------
- Schema principală se află în `queries/service_flow_db.sql`. Migrațiile adiționale în `migrations/`.
- Tabele principale: `admins`, `clients`, `equipment`, `interventions`, `invoices`, `marketing_campaigns`, `notifications`.

Gestionarea clienților cu același email
-------------------------------------
Situația: un client poate exista în baza de date pentru mai multe service-uri (diferite `admin_id`) folosind același email.

Comportament implementat:
- La login, aplicația validează parola pentru fiecare intrare `clients` cu acel email. Dacă sunt mai multe potriviri valide, utilizatorul este redirecționat către `select_service.php` pentru a alege service-ul la care vrea să se logheze.

Recomandări:
- Workaround imediat pentru marketing: deduplicați destinatarii la trimitere (`SELECT DISTINCT email ...`).
- Pe termen lung, curățați duplicatele (script + revizuire manuală) și aplicați `UNIQUE (admin_id, email)`.

Remarcă despre trimiterea campaniilor
------------------------------------
- ATENȚIE: dacă nu curățați duplicatele, aceeași adresă poate primi mai multe emailuri. Folosiți `DISTINCT` la extragerea listei de emailuri înainte de trimitere.

Modificări UI/CSS importante
---------------------------
- `studenti/style/common.css` conține variabile (tokens) și reguli comune.
- `admin.css` conține ajustări de layout și fixuri responsive (tabele, sidebar mobile).
- `js/admin.js` controlează hamburger toggle pe mobil.

Scripturi utile
---------------
1. Raport duplicates (per admin):

```sql
SELECT admin_id, email, COUNT(*) AS cnt
FROM clients
WHERE email IS NOT NULL AND email <> ''
GROUP BY admin_id, email
HAVING cnt > 1;
```

2. Dupa verificare manuală, reasociați FK-urile (equipment, interventions, invoices) către `keep_id` și ștergeți duplicatele într-o tranzacție.

Dezvoltare și bune practici
---------------------------
- Folosiți branch-uri pentru funcționalități noi și PR pentru revizuire.
- Testați pe mobile și desktop înainte de a merge în producție.
- Faceți backup la baza de date înainte de operațiuni DDL/DELETE.

Contact și contribuții
----------------------
Pentru întrebări și contribuții: leonard@llogo.ro

Licență
-------
Proiect privat — toate drepturile rezervate © Leonard Popescu

---

**Last updated:** January 18, 2026
# ServiceFlow (Service Management)

![PHP](https://img.shields.io/badge/PHP-%3E%3D%207.4-blue?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-%3E%3D%205.7-orange?style=flat-square&logo=mysql)
![Docker](https://img.shields.io/badge/Docker-Enabled-brightgreen?style=flat-square&logo=docker)

## Overview

ServiceFlow is a lightweight PHP application to manage automotive service operations: clients, equipment, appointments, interventions and invoicing. The project runs in the `studenti/` folder and is designed to be deployable with or without Docker.

Live site: https://serviceflow.dev

---

## Key Notes (current project state)
- Centralized CSS tokens and shared rules in `studenti/style/common.css`.
- Admin styles in `studenti/style/admin.css` with responsive fixes for mobile tables and sidebar.
- Login flow updated to allow a single email to be associated with multiple `service` accounts; users now choose which service to log in to when multiple matches exist (`select_service.php`).
- `issued_invoices.php` and invoice flow fixes: manual parts/labor amounts preserved and server-side guards prevent saving zero-total invoices.

---

## Project Structure (important parts)
 
```
studenti/
├── index.php
├── login.php
├── select_service.php      # New: choose service when same email exists across services
├── signup.php
├── admin_dashboard.php
├── admin_marketing.php
├── admin_invoice_confirm.php
├── issued_invoices.php
├── admin_sidebar.php
├── db_connect.php
├── js/
│   ├── admin.js            # Sidebar toggle + mobile behaviors
│   └── invoice_confirm.js  # Invoice client-side logic
├── style/
│   ├── common.css          # Central tokens + shared components
│   ├── admin.css
│   ├── main.css
│   └── client.css
└── queries/
    └── service_flow_db.sql
```

---

## Requirements
- PHP >= 7.4
- MySQL / MariaDB >= 5.7
- Apache (or other webserver)
- Optional: Docker & Docker Compose

---

## Quick Setup (local, minimal)

1. Clone repository and open `studenti/` as your web root.

2. Create database and import schema (example):

```bash
mysql -u root -p -e "CREATE DATABASE service_flow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p service_flow_db < queries/service_flow_db.sql
```

3. Update `studenti/db_connect.php` with DB credentials.

4. Ensure webserver points to the `studenti/` folder and that `style/` and `js/` are accessible.

5. Visit: https://serviceflow.dev (or local URL configured)

---

## Important Operational Notes

- Multiple client rows can exist using the same email for different services (different `admin_id`). The login flow now validates password against each matching client row and presents a service selection if more than one matches.
- Recommended long-term: enforce uniqueness on `(admin_id, email)` and merge duplicates per business rules. Backup DB before running migrations.
- Marketing sends should deduplicate by `email` at send-time (use `SELECT DISTINCT email ...`) to avoid duplicate mails while duplicates exist in `clients`.

---

## Developer Tips

- Frontend: central tokens are in `studenti/style/common.css` — add new variables there.
- Sidebar toggle: `studenti/js/admin.js` controls the mobile slide-in; ensure `admin.js` is included on pages needing the hamburger.
- Invoice fixes: `studenti/js/invoice_confirm.js` preserves manual parts/labor; server-side checks in `studenti/admin_invoice_confirm.php` prevent saving invoices with total ≤ 0.

---

## Database & Migration

- The canonical schema is in `queries/service_flow_db.sql` and `migrations/complete_migration.sql`.
- To resolve duplicate clients by email per service, recommended steps:
  1. Run a report to list duplicates: `SELECT admin_id, email, COUNT(*) FROM clients GROUP BY admin_id, email HAVING COUNT(*)>1;`
  2. Manually merge records or write a migration script.
  3. After cleanup, add a unique index: `ALTER TABLE clients ADD UNIQUE (admin_id, email);`

---

## TODO / Next Improvements
- Full CSS lint and replace remaining hardcoded colors with variables.
- Automatic migration script to detect/merge duplicate clients.
- UX: during client creation, suggest linking to an existing client for the same email.
- Optional: add visual regression tests for mobile/desktop snapshots.

---

## Contact

Project maintainer: Leonard Popescu

Website: https://serviceflow.dev
Email: leonard@llogo.ro

---

**Last Updated:** January 18, 2026

```
