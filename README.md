# Lead Management CRM

A complete, production-quality Lead Management CRM built using PHP, MySQL, Bootstrap 5, HTML5, CSS3, and Vanilla JavaScript. Designed to look like a modern SaaS dashboard.

## Features
- **Dashboard**: High-level statistics, pipeline progress, and recent leads.
- **Lead Management**: Full CRUD (Create, Read, Update, Delete) operations for leads.
- **Search & Filter**: Search leads by name, company, email, phone, and filter by status or priority.
- **Authentication**: Secure login system using hashed passwords and PHP sessions.
- **Profile**: Update admin information and change password.
- **Modern UI**: Clean layout, custom CSS properties, Bootstrap 5 components, and Bootstrap Icons.
- **Responsive**: Fully responsive design for mobile, tablet, and desktop views.

## Technologies Used
- **Frontend**: HTML5, CSS3, Bootstrap 5, Vanilla JavaScript (No jQuery).
- **Backend**: PHP 8+, MySQL (PDO with Prepared Statements).
- **Icons**: Bootstrap Icons.
- **Fonts**: Inter (Google Fonts).

## Folder Structure
```
lead-management-crm/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── includes/
│   ├── auth.php
│   ├── config.php
│   ├── db.php
│   ├── footer.php
│   ├── header.php
│   ├── navbar.php
│   └── sidebar.php
├── leads/
│   ├── add.php
│   ├── edit.php
│   ├── list.php
│   └── view.php
├── dashboard.php
├── full_database_install.sql
├── index.php
├── login.php
├── logout.php
├── profile.php
└── README.md
```

## Installation Steps
1. **Clone or Download** the repository to your local server (e.g., inside `C:\xampp\htdocs\CRM-Lead-Tracker`).
2. **Database Setup**:
   - Open phpMyAdmin (e.g., `http://localhost/phpmyadmin`).
   - For a **new installation**, import `full_database_install.sql`. It creates the complete current schema.
   - The repository contains one complete installer; separate legacy migration files are not required.
3. **Database Configuration**:
   - Copy `includes/local_config.example.php` to `includes/local_config.php` and enter your local database credentials.
   - On a deployed server, prefer `CRM_DB_HOST`, `CRM_DB_NAME`, `CRM_DB_USER`, and `CRM_DB_PASSWORD` environment variables.
   - Set `CRM_APP_ENV=production` in production. Never commit `includes/local_config.php` or `.env` files.
4. **Access the CRM**:
   - Open your browser and navigate to `http://localhost/CRM-Lead-Tracker`.

## Reviewer Demo Credentials
- These credentials are for the restricted project reviewer account.
- **Email**: reviewer@example.com
- **Password**: SecurePass2026!
- The reviewer account has sales-representative access only.

## Security Implemented
- **Password Hashing**: Uses PHP's `password_hash()` and `password_verify()`.
- **SQL Injection Prevention**: Uses PDO prepared statements for all database queries.
- **Session Protection**: Validates session presence on all protected pages.
- **XSS Protection**: Uses `htmlspecialchars()` when outputting user data.
