# 🚀 Mini CRM Lead Tracker

A modern, production-ready Lead Management CRM built with PHP 8+, MySQL, and Vanilla JavaScript. Designed with a sleek, responsive dark-mode UI inspired by modern SaaS applications, this CRM provides a complete suite of tools for managing leads, tracking activities, and collaborating across a sales team.

🌐 **Live Demo:** [lead-tracker.infinityfree.me](http://lead-tracker.infinityfree.me) *(Update URL if different)*

## ✨ Key Features
- **Role-Based Access Control (RBAC):** Distinct permissions and views for Admins, Managers, and Sales Reps.
- **Advanced Lead Management:** Full CRUD operations, status pipelines, and priority filtering.
- **Activity Timeline & Logging:** Automatically tracks who did what (lead assignments, status changes) to maintain a clear audit trail.
- **Follow-up Reminders & Notes:** Schedule follow-ups, leave internal notes, and track communication history.
- **Sleek Dark Mode UI:** Premium "Ocean Palette" design featuring smooth micro-animations, glassmorphism, and responsive layouts.
- **Interactive Dashboard:** High-level statistics, pipeline progress, and recent leads.
- **Live Notifications:** Alert system for newly assigned leads and upcoming tasks.

## 🔒 Enterprise-Grade Security
- **Strict CSRF Protection:** Custom session-backed CSRF tokens with secure `Cache-Control` enforcement.
- **Secure Authentication:** `password_hash()` (Bcrypt), session regeneration, and Brute-Force prevention.
- **SQL Injection Prevention:** 100% PDO Prepared Statements.
- **XSS & Clickjacking Defense:** Advanced Content-Security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`).

## 🛠️ Technologies Used
- **Frontend:** HTML5, Custom CSS3 (CSS Variables, Flexbox/Grid), Bootstrap 5, Vanilla JavaScript.
- **Backend:** PHP 8+, MySQL (InnoDB Relational Database).
- **Icons & Typography:** Bootstrap Icons, Inter (Google Fonts).

## 🚀 Live Demo Credentials
Visit the live demo link to test the application. You can use the one-click auto-fill buttons on the login page, or manually use the following credentials to test different RBAC permission levels:
- **Admin Role:** `admin@example.com` / `SecurePass2026!`
- **Manager Role:** `manager@example.com` / `manager123`
- **Sales Rep Role:** `saleperson1@example.com` / `sales@123`

## 📦 Local Installation
1. **Clone the repository** to your local server directory (e.g., `htdocs` for XAMPP).
2. **Database Setup**:
   - Open phpMyAdmin and create a new database (e.g., `crm_lead_tracker`).
   - Import the `full_database_install.sql` file to instantly set up all tables and relationships.
3. **Configuration**:
   - Ensure your local database credentials match those configured in `includes/db.php` or your environment config.
   - For production, use environment variables (`CRM_DB_HOST`, `CRM_DB_NAME`, `CRM_DB_USER`, `CRM_DB_PASSWORD`).
4. **Run**: Access `http://localhost/CRM-Lead-Tracker` in your browser.

## 📂 Folder Structure
```text
CRM-Lead-Tracker/
├── assets/             # CSS styling and Vanilla JS scripts
├── includes/           # Core PHP logic (auth, db config, csrf protection)
├── leads/              # Lead management views and logic (CRUD operations)
├── team/               # User management and RBAC logic
├── dashboard.php       # Main analytics and high-level view
├── login.php           # Secure authentication entry point
└── full_database_install.sql # Complete database schema for installation
```
