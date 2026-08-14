# Water Billing System - Implementation Plan

## Executive Summary
This document outlines the detailed architectural blueprint and execution plan for the **Water Billing System** based on the features specified in [`Feature.md`](file:///C:/xampp/htdocs/Technopreneurship/Feature.md). The application connects to the MariaDB/MySQL database (`ramos_db`) and provides a streamlined, secure, responsive web application for managing water bills, consumer records, user accounts, and financial reports.

---

## 1. System Architecture & Tech Stack

- **Backend Logic**: PHP 8.x (using PDO for secure, prepared SQL statements)
- **Database Engine**: MySQL / MariaDB (`ramos_db`)
- **Frontend Presentation**: HTML5, Semantic Structure
- **Styling**: Vanilla CSS (Custom Design System, Fluid Responsive Grid, Modern Aesthetics)
- **Client Scripting**: Vanilla JavaScript (Interactive Search/Filters, Confirmations, Dynamic Data Exports)
- **Data Exporting**: CSV / Excel (SheetJS / PHP Header Stream Export)

---

## 2. Database Schema (`ramos_db`)

### 2.1. `bills` Table (Core Data Model)
| Column Name | Data Type | Constraints / Attributes | Description |
| :--- | :--- | :--- | :--- |
| `bill_id` | `INT` | Primary Key, Auto Increment | Unique billing identifier |
| `consumer_name` | `VARCHAR(150)` | NOT NULL | Full name of the water consumer |
| `meter_number` | `VARCHAR(50)` | NOT NULL | Unique physical meter number |
| `billing_month` | `VARCHAR(30)` | NOT NULL | Month & Year of billing (e.g., "August 2026") |
| `consumption` | `DECIMAL(10,2)` | NOT NULL | Water usage volume (in cubic meters / m³) |
| `amount_due` | `DECIMAL(10,2)` | NOT NULL | Computed bill amount payable |
| `due_date` | `DATE` | NOT NULL | Payment deadline |
| `status` | `ENUM('paid', 'unpaid')` | DEFAULT 'unpaid' | Current payment status |
| `remarks` | `TEXT` | NULLABLE | Notes or status remarks for reporting |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Record creation timestamp |
| `updated_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Last modification timestamp |

### 2.2. `users` Table (User Management Module)
| Column Name | Data Type | Constraints / Attributes | Description |
| :--- | :--- | :--- | :--- |
| `user_id` | `INT` | Primary Key, Auto Increment | Unique user identifier |
| `username` | `VARCHAR(50)` | Unique, NOT NULL | Account login username |
| `full_name` | `VARCHAR(100)` | NOT NULL | Full display name |
| `email` | `VARCHAR(100)` | Unique, NOT NULL | User email address |
| `password` | `VARCHAR(255)` | NOT NULL | Hashed password (`password_hash`) |
| `role` | `ENUM('admin', 'staff')` | DEFAULT 'staff' | Access control level |
| `status` | `ENUM('active', 'inactive')`| DEFAULT 'active' | Account state |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | User registration timestamp |

---

## 3. Module Breakdown & Functionality

### Phase 1: Core Setup & Authentication
1. **Database Initialization Script (`schema.sql`)**:
   - Automated creation of `bills` and `users` tables, foreign keys, and indexes.
   - Seed data for default admin account and sample bill records.
2. **Database Connection (`config/db.php`)**:
   - PDO connection wrapper with error handling for `ramos_db`.
3. **User Authentication**:
   - **Login Module** (`index.php` / `login.php`): Secure login with password hashing verification (`password_verify`), CSRF protection, and session management.
   - **User Registration** (`register.php`): Self-registration form for system access with input validation.
   - **Logout** (`logout.php`): Clean session destruction and redirection to login page.

### Phase 2: Core Bill Records CRUD & Reports
1. **Dashboard** (`dashboard.php`):
   - Key Metrics Widgets: Total Bills, Total Unpaid Bills, Total Collected Revenue, Pending Balance.
   - Quick Action Shortcuts: Add New Bill, Export Data, View Unpaid Accounts.
   - Visual Breakdown: Collection summary & bill status overview.
2. **View Records** (`bills/index.php`):
   - Tabular list of all billing records.
   - Real-time search by Consumer Name or Meter Number.
   - Instant filters by Payment Status (`paid`, `unpaid`) and Billing Month.
   - Pagination control for smooth navigation across large datasets.
3. **Add Record Module** (`bills/add.php`):
   - Form fields: Consumer Name, Meter Number, Billing Month, Consumption (m³), Amount Due, Due Date, Status, Remarks.
   - Server-side and client-side validation.
4. **Edit Record Module** (`bills/edit.php`):
   - Pre-filled modal or page to update consumer details, consumption, payment status, and due dates.
5. **Delete Record Module** (`bills/delete.php`):
   - Safe deletion handler with confirmation dialog to prevent accidental data loss.
6. **Excel Export** (`bills/export.php`):
   - Export active bill records or filtered results into `.xlsx` / `.csv` format for offline accounting.
7. **Reports Module** (`reports.php`):
   - Summarized financial reports categorized by Status (`Paid` vs `Unpaid`) and Remarks.
   - Filter reports by date range or specific billing cycle.

### Phase 3: User Management & Account Profile
1. **User Management (Admin)** (`users/index.php`, `users/add.php`, `users/edit.php`, `users/delete.php`):
   - View list of all system users.
   - Create, edit, activate/deactivate, or remove user accounts.
2. **User Profile Management** (`profile.php`):
   - Update full name and email address.
3. **Change Password** (`profile.php` / `change-password.php`):
   - Change account password with current password verification and password strength validation.

---

## 4. UI/UX & Design System

- **Layout Grid**: Sidebar navigation + Top Bar + Fluid Main Content Area.
- **Color Palette**: Clean modern theme (Navy/Slate primary, Aqua/Blue secondary, Emerald green for Paid status, Crimson for Unpaid/Overdue, Dark neutral background elements).
- **Responsive Behavior**: Adaptive mobile navigation drawer, responsive data tables with horizontal scroll wrappers.
- **Micro-interactions**: Hover transitions on action buttons, status pills, dynamic search filtering.

---

## 5. Directory Structure

```
Technopreneurship/
├── Feature.md
├── PLAN.md
├── schema.sql
├── config/
│   └── db.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── index.php
├── login.php
├── register.php
├── dashboard.php
├── bills/
│   ├── index.php
│   ├── add.php
│   ├── edit.php
│   ├── delete.php
│   └── export.php
├── users/
│   ├── index.php
│   ├── add.php
│   ├── edit.php
│   └── delete.php
├── profile.php
├── reports.php
└── logout.php
```

---

## 6. Next Steps & Execution Order

1. **Step 1**: Review database structure with target database `ramos_db`.
2. **Step 2**: Generate `schema.sql` SQL script to establish `bills` and `users` tables.
3. **Step 3**: Develop `config/db.php` and base styling system (`assets/css/style.css`).
4. **Step 4**: Build Authentication flow (Login, Register, Logout).
5. **Step 5**: Build CRUD functionality for Water Bills (`bills/index.php`, `add.php`, `edit.php`, `delete.php`, `export.php`).
6. **Step 6**: Build Dashboard, User Management, and Reports.
7. **Step 7**: Test local design, responsiveness, search/filter functionality, and data exports.
