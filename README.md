# 🎬 VideoStream

A full-stack video streaming web application built with **custom PHP MVC architecture** — no Laravel, no Symfony, no framework. Everything from routing to real-time WebSocket updates is hand-crafted from scratch.

---

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Live Features](#live-features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Architecture — How It Works](#architecture--how-it-works)
- [Database Schema](#database-schema)
- [All Modules](#all-modules)
- [Security Implementation](#security-implementation)
- [Real-Time WebSocket System](#real-time-websocket-system)
- [Email & OTP System](#email--otp-system)
- [Installation & Setup](#installation--setup)
- [Environment Configuration](#environment-configuration)
- [Git Setup](#git-setup)
- [Known Issues & TODO](#known-issues--todo)
- [Author](#author)

---

## Project Overview

VideoStream is a **Netflix-style video platform** with two separate panels:

| Panel | URL | Who |
|---|---|---|
| User Panel | `/` | Registered users — watch, wishlist, review |
| Admin Panel | `/admin` | Admins — manage videos, users, subscriptions, payments |

Built as a **college/portfolio project** to demonstrate full-stack PHP development, MVC design patterns, real-time features, and database design — without relying on any framework.

---

## Live Features

### User Side
- ✅ Register with email validation (format + MX/DNS record check)
- ✅ OTP email verification (6-digit, 10-minute expiry, max 5 attempts)
- ✅ Google reCAPTCHA v2 on login and registration
- ✅ bcrypt password hashing (`password_hash` / `password_verify`)
- ✅ Watch videos with progress tracking (resumes where you left off)
- ✅ Wishlist — add/remove videos
- ✅ Watch history with clear option
- ✅ Submit reviews and ratings (1–5 stars)
- ✅ Report inappropriate videos
- ✅ Subscription plans (Free / Basic / Premium) with access control
- ✅ Real-time homepage updates via WebSocket
- ✅ Update profile and change password
- ✅ Delete own account (full cascade)
- ✅ Notifications system
- ✅ Blog section powered by WordPress RSS feed

### Admin Side
- ✅ Separate admin login with session guard on every route
- ✅ Dashboard with live stats (total videos, users, revenue, views)
- ✅ Revenue chart (last 7 months) — updates live
- ✅ Subscriber breakdown chart (Free / Basic / Premium)
- ✅ Full video CRUD (upload thumbnail + video file, set status, access level, category)
- ✅ User management (ban/unban, view profile, delete)
- ✅ Category management
- ✅ Subscription and payment management
- ✅ Review moderation (approve / reject)
- ✅ Report resolution
- ✅ Admin messaging system
- ✅ Activity log for all admin/user actions
- ✅ Notifications with live badge count
- ✅ Global search (client-side, instant, no page reload)
- ✅ Real-time updates via WebSocket + AJAX polling fallback

---

## Tech Stack

### Backend
| Technology | Version | Usage |
|---|---|---|
| **PHP** | 8.1+ | Core language — controllers, models, routing |
| **MySQL** | 8.0+ | Primary database |
| **PDO** | Built-in | Database abstraction — all queries via prepared statements |
| **PHPMailer** | 6.x | SMTP email sending for OTP |
| **WebSocket Server** | Custom PHP | Real-time event broadcasting (TCP bridge on port 8081, WS on 8080) |
| **Apache** | 2.4+ | Web server with `mod_rewrite` |

### Frontend
| Technology | Version | Usage |
|---|---|---|
| **HTML5 / CSS3** | — | Markup and styling |
| **Vanilla JavaScript** | ES2020 | DOM manipulation, AJAX, WebSocket client |
| **Bootstrap** | 5.3.3 | UI components and grid |
| **Bootstrap Icons** | 1.11.3 | Icon set throughout the UI |
| **Chart.js** | 4.4.0 | Revenue bar chart and subscriber doughnut chart on dashboard |
| **Google reCAPTCHA** | v2 | Bot protection on login and registration forms |

### External Services
| Service | Purpose |
|---|---|
| **Google reCAPTCHA v2** | CAPTCHA verification (frontend widget + backend `siteverify` API) |
| **Gmail SMTP** | Sending OTP emails via PHPMailer |
| **WordPress** | Blog section — main app reads posts via RSS feed using `simplexml_load_file()` |
| **DNS / MX Records** | `checkdnsrr()` to validate email domains before sending OTP |

### Design Pattern
| Pattern | Where Used |
|---|---|
| **MVC (Model-View-Controller)** | Entire application structure |
| **Front Controller** | `public/index.php` — single entry point for all requests |
| **Singleton** | `Database::getInstance()` — one PDO connection per request |
| **Abstract Base Class** | `BaseController` and `BaseModel` — shared logic inherited by all controllers/models |
| **SPL Autoloader** | `spl_autoload_register()` — auto-loads classes without manual `require` |

---

## Project Structure

```
vs950/
├── .htaccess                          # URL rewriting — all traffic → public/index.php
│
├── config/
│   ├── app.php                        # App constants, SMTP config, reCAPTCHA keys, timezone
│   └── database.php                   # DB_HOST, DB_NAME, DB_USER, DB_PASS constants
│
├── public/                            # Web root — only this folder is publicly accessible
│   ├── index.php                      # Front controller — routing, autoloader, session start
│   ├── .htaccess                      # Secondary rewrite rules
│   ├── assets/
│   │   ├── css/
│   │   │   ├── auth.css               # Login/register page styles
│   │   │   ├── dashboard.css          # Admin panel styles
│   │   │   └── user.css               # User home styles
│   │   ├── js/
│   │   │   ├── app.js                 # Admin JS — WebSocket, live updates, Chart.js, search
│   │   │   ├── auth.js                # Registration form validation, password strength
│   │   │   └── user.js                # User home — video player, wishlist, review modal
│   │   └── images/
│   │       └── logo1.png
│   └── uploads/
│       ├── videos/                    # Uploaded video files
│       └── thumbnails/                # Uploaded thumbnail images
│
├── app/
│   ├── core/
│   │   ├── BaseController.php         # Abstract base — view(), redirect(), url()
│   │   └── WsPublisher.php            # WebSocket publisher — TCP push to port 8081
│   │
│   ├── controllers/
│   │   ├── AuthController.php         # Register, login, logout, OTP, CAPTCHA
│   │   ├── HomeController.php         # User home, wishlist, history, reviews, feed_json
│   │   ├── AdminController.php        # Admin base — requireAdmin() session guard
│   │   ├── DashboardController.php    # Admin dashboard + admin feed_json
│   │   ├── VideoController.php        # Admin video CRUD, file upload
│   │   ├── UserController.php         # Admin user management
│   │   ├── CategoryController.php     # Admin category CRUD
│   │   ├── PaymentController.php      # Admin payment records
│   │   ├── SubscriptionController.php # Admin subscription management
│   │   ├── ReviewController.php       # Admin review moderation
│   │   ├── ReportController.php       # Admin report resolution
│   │   ├── MessageController.php      # Admin messaging system
│   │   ├── NotificationController.php # Admin notifications
│   │   ├── ActivityLogController.php  # Admin activity log
│   │   └── SettingsController.php     # Admin settings
│   │
│   ├── models/
│   │   ├── Database.php               # PDO Singleton — one connection per request
│   │   ├── BaseModel.php              # Abstract base — query(), queryOne(), execute()
│   │   ├── AuthModel.php              # findUserByEmail, createUserWithPasswordHash
│   │   ├── VideoModel.php             # getPublished, getTrending, getByCategoryId, recordView
│   │   ├── UserModel.php              # getUserHomeData, getPlans, delete (cascade)
│   │   ├── DashboardModel.php         # getStats, getRevenueChart, getSubscriptionBreakdown
│   │   ├── CategoryModel.php          # getAllWithCounts, getAllForSelect
│   │   ├── NotificationModel.php      # create, unreadCount, userHighlights
│   │   ├── ActivityLogModel.php       # log(actor, action, module, details, ip)
│   │   ├── MessageModel.php           # Admin messaging CRUD
│   │   ├── ReviewModel.php            # getAll, getAvgRating, approve
│   │   ├── ReportModel.php            # getAll, resolve, dismiss
│   │   ├── PaymentModel.php           # Payment records
│   │   ├── SubscriptionModel.php      # Active subscription lookup
│   │   └── SettingsModel.php          # App settings key-value store
│   │
│   └── views/
│       ├── layouts/
│       │   ├── main.php               # User panel layout wrapper
│       │   ├── admin.php              # Admin panel layout — sidebar, topbar, JS includes
│       │   └── auth.php               # Login/register layout
│       ├── auth/
│       │   ├── user_login.php
│       │   ├── user_register.php
│       │   ├── user_verify_otp.php
│       │   └── admin_login.php
│       ├── user/
│       │   ├── home.php               # Main user page (~96KB — all sections in one file)
│       │   └── includes/
│       │       └── recent_blogs.php   # WordPress RSS feed reader
│       └── admin/
│           ├── dashboard.php          # Stats cards + Chart.js charts
│           ├── videos.php             # Video list + upload/edit modal
│           ├── users.php              # User management table
│           ├── categories.php
│           ├── payments.php
│           ├── subscriptions.php
│           ├── reviews.php
│           ├── reports.php
│           ├── messages.php
│           ├── activity.php
│           ├── settings.php
│           ├── user_detail.php
│           ├── _helpers.php           # Shared PHP formatting functions (used by feed_json too)
│           └── partials/
│               └── sidebar.php
│
├── blog/                              # WordPress installation (separate CMS)
│   └── wp-admin/
│   └── wp-content/
│   └── wp-includes/
│
└── storage/
    └── sessions/                      # Custom PHP session storage directory
```

---

## Architecture — How It Works

### Request Lifecycle

```
Browser request
    ↓
.htaccess  →  RewriteRule sends to public/index.php?url=...
    ↓
public/index.php  (Front Controller)
    ↓
Parse URL segments  →  $prettyRoutes map  →  module / page / action
    ↓
$routes dispatch table  →  resolve Controller class
    ↓
$authMethods / $userActions  →  resolve method name
    ↓
$controller->$method()
    ↓
Controller calls Model  →  Model runs PDO prepared statement  →  returns array
    ↓
Controller calls $this->view('template', $data, 'layout')
    ↓
views/layouts/[layout].php wraps views/[section]/[template].php
    ↓
HTML sent to browser
```

### URL Routing Examples

| URL | Module | Controller | Method |
|---|---|---|---|
| `/` | user | HomeController | index() |
| `/login` | auth | AuthController | userLogin() |
| `/login/login` | auth | AuthController | userAuthenticate() |
| `/register/save` | auth | AuthController | registerUser() |
| `/register/confirm` | auth | AuthController | verifyRegistrationOtp() |
| `/admin` | admin | DashboardController | index() |
| `/admin/videos` | admin | VideoController | index() |
| `/admin/videos/delete` | admin | VideoController | delete() |
| `/?action=wishlist_toggle` | user | HomeController | wishlist_toggle() |
| `/?action=feed_json` | user | HomeController | feed_json() |

---

## Database Schema

### Core Tables

| Table | Purpose |
|---|---|
| `users` | Registered users — name, email, password_hash, status, plan_id |
| `videos` | Video records — title, file_path, thumbnail, status, access_level, category_id, views |
| `categories` | Video categories with active/inactive status |
| `plans` | Subscription plans — Free, Basic, Premium with price |
| `subscriptions` | User subscriptions — user_id, plan_id, status, starts_at, expires_at |
| `payments` | Payment records — amount, status (success/pending/failed/refunded) |
| `wishlists` | user_id + video_id pairs |
| `watch_history` | user_id + video_id + progress_percent + watched_at (UNIQUE key on user+video) |
| `reviews` | User reviews — rating (1-5), comment, status (pending/approved/rejected) |
| `reports` | Video reports — report_code, reason, type, status |
| `admin_messages` | Admin internal messaging |
| `notifications` | System notifications for admin topbar |
| `activity_logs` | Full audit trail — actor, action, module, details, ip, logged_at |
| `settings` | Key-value app settings |

### Key Design Decisions

- `watch_history` has `UNIQUE KEY (user_id, video_id)` → enables `ON DUPLICATE KEY UPDATE` upsert
- `videos.access_level` ENUM `('free', 'basic', 'premium')` controls who can watch
- `subscriptions.status` = `'active'` checked together with `expires_at >= CURDATE()` for safety
- All passwords stored as `password_hash($password, PASSWORD_DEFAULT)` — bcrypt, never plaintext
- `v_dashboard_stats` is a MySQL VIEW used by `DashboardModel::getStats()`

---

## All Modules

### Auth Module — `AuthController`
Handles everything related to identity.
- `userLogin()` / `userAuthenticate()` — show form / process login
- `adminLogin()` / `adminAuthenticate()` — admin version
- `register()` / `registerUser()` — show form / process registration
- `showOtpVerification()` / `verifyRegistrationOtp()` — OTP step
- `logout()` — destroy session and cookie

### User Home Module — `HomeController`
The entire user-facing experience in one controller.
- `index()` — render homepage with videos, wishlist, history, subscription
- `wishlist_toggle()` — add/remove from wishlist (JSON response)
- `save_progress()` — upsert watch progress (JSON)
- `record_view()` — increment video view counter (JSON)
- `save_review()` — submit or update review (JSON)
- `save_report()` — submit video report (JSON)
- `update_profile()` — change name/password (JSON)
- `delete_account()` — delete user + session destroy
- `clear_history()` / `clear_notifications()` — housekeeping
- `subscription_request()` — user requests a plan
- `feed_json()` — JSON endpoint polled after every WebSocket event

### Admin Module
Each page = separate controller extending `AdminController`.
All protected by `requireAdmin()` which checks `$_SESSION['role'] === 'admin'`.

| Controller | Manages |
|---|---|
| DashboardController | Stats, charts, activity feed, admin feed_json |
| VideoController | Video CRUD, file upload |
| UserController | View, ban, unban, delete users |
| CategoryController | Category CRUD |
| PaymentController | View payment records |
| SubscriptionController | Manage subscriptions |
| ReviewController | Approve / reject reviews |
| ReportController | Resolve / dismiss reports |
| MessageController | Admin inbox + compose |
| NotificationController | Mark read, clear |
| ActivityLogController | View audit trail |
| SettingsController | App-wide settings |

---

## Security Implementation

| Feature | Implementation |
|---|---|
| Password storage | `password_hash($pass, PASSWORD_DEFAULT)` — bcrypt |
| Password verification | `password_verify($input, $hash)` — timing-safe |
| OTP storage | `password_hash($otp, PASSWORD_DEFAULT)` in `$_SESSION` — never plaintext |
| SQL injection prevention | PDO prepared statements everywhere — zero raw string interpolation in queries |
| CAPTCHA | Google reCAPTCHA v2 — frontend widget + backend `siteverify` API call |
| Email validation | `filter_var()` format check + `checkdnsrr()` MX/A DNS check |
| Session protection | Custom session path (`storage/sessions/`), `SESSION_NAME` constant |
| Admin route guard | `AdminController::requireAdmin()` — checks `$_SESSION['role']` |
| File upload security | `preg_replace('/[^a-z0-9]+/i', '-', $name)` + `uniqid()` prefix on filenames |
| OTP brute force | Max 5 attempts → session unset → redirect to register |
| OTP expiry | 10-minute TTL: `time() + 600` stored in session |
| XSS prevention | `htmlspecialchars()` on all view output |

---

## Real-Time WebSocket System

```
User/Admin action
    ↓
PHP Controller (e.g. save_review, upload video)
    ↓
WsPublisher::push('topic')          ← TCP socket to 127.0.0.1:8081
    ↓                                  (0.4s timeout, silent fail if server down)
WebSocket Bridge (port 8081)
    ↓
WebSocket Server (port 8080)
    ↓
Browser: socket.addEventListener('message', ...)
    ↓
frame.event === 'live' → scheduleReload(200ms)
    ↓
fetch('/admin/dashboard?action=feed_json')  or  fetch('/?action=feed_json')
    ↓
applyFeed(data) → update DOM without page reload
    ↓
updateDashboardLive()  →  VSCharts.revenue.update('none')  — Chart.js instant update
```

**Fallback:** If WebSocket server is not running — polls every 5 seconds. If connected — polls every 30 seconds.

**Topics pushed:**

| Topic | Triggered by |
|---|---|
| `'reviews'` | User submits/updates a review |
| `'wishlist'` | Wishlist add or remove |
| `'history'` | Progress saved or history cleared |
| `'reports'` | User submits a report |
| `'notifications'` | Notifications cleared |
| `'videos'` | Admin uploads, edits, or deletes a video |
| `'messages'` | Admin sends a message |

---

## Email & OTP System

```
User submits registration form
    ↓
AuthController validates all fields
    ↓
Generate OTP: random_int(100000, 999999)
    ↓
Hash OTP:    password_hash($otp, PASSWORD_DEFAULT)
    ↓
Store in:    $_SESSION['pending_registration'] = [
                 'otp_hash'  => $hash,
                 'expires_at'=> time() + 600,
                 'attempts'  => 0,
                 'email'     => $email,
                 'name'      => $name,
                 'password'  => $passwordHash
             ]
    ↓
PHPMailer → Gmail SMTP (TLS, port 587) → HTML email to user
    ↓
User enters 6-digit OTP
    ↓
verifyRegistrationOtp():
    - increment attempts
    - check attempts > 5 → lockout
    - check time() > expires_at → expired
    - password_verify($input, $hash) → match?
    ↓
YES → AuthModel::createUserWithPasswordHash() → set session → redirect home
```

---

## Installation & Setup

### Requirements
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Apache with `mod_rewrite` enabled
- Composer (for PHPMailer)
- A Gmail account with App Password enabled

### Step 1 — Clone the project

```bash
git clone https://github.com/YOUR_USERNAME/videostream.git
cd videostream
```

### Step 2 — Install dependencies

```bash
composer require phpmailer/phpmailer
```

### Step 3 — Create the database

```bash
mysql -u root -p
CREATE DATABASE videostream CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

Import the SQL schema:
```bash
mysql -u root -p videostream < database/videostream.sql
```

### Step 4 — Configure the app

Edit `config/app.php`:
```php
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_USER',       'youremail@gmail.com');
define('SMTP_PASS',       'your_gmail_app_password');  // NOT your login password
define('SMTP_FROM_EMAIL', 'youremail@gmail.com');
```

Edit the return array at the bottom of `config/app.php`:
```php
return [
    'recaptcha_site_key'   => 'YOUR_RECAPTCHA_SITE_KEY',
    'recaptcha_secret_key' => 'YOUR_RECAPTCHA_SECRET_KEY',
];
```

Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'videostream');
define('DB_USER', 'root');
define('DB_PASS', 'your_db_password');
```

### Step 5 — Create storage directory

```bash
mkdir -p storage/sessions
chmod 775 storage/sessions
```

### Step 6 — Apache Virtual Host

```apache
<VirtualHost *:80>
    ServerName videostream.local
    DocumentRoot /path/to/vs950/public

    <Directory /path/to/vs950/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable mod_rewrite:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Step 7 — Start WebSocket server

```bash
php ws-server.php &
```

### Step 8 — Visit the app

```
http://videostream.local          → User home (login required)
http://videostream.local/register → Register new account
http://videostream.local/admin    → Admin panel
```

---

## Environment Configuration

> ⚠️ **Never commit real credentials to Git.**

Create a `.env` file (not committed) and load it in `config/app.php`. Or at minimum, ensure `config/app.php` is in `.gitignore` and use `config/app.example.php` as a template.

**Sensitive values that must not be in Git:**
- `SMTP_PASS` — Gmail app password
- `recaptcha_secret_key` — reCAPTCHA backend secret
- `DB_PASS` — Database password

---

## Git Setup

### First time — initialize and push to GitHub

```bash
# Step 1: Go into your project folder
cd /path/to/vs950

# Step 2: Initialize git
git init

# Step 3: Create .gitignore FIRST (before adding files)
# (see .gitignore contents below)

# Step 4: Add all files
git add .

# Step 5: First commit
git commit -m "initial commit: VideoStream full-stack PHP MVC project"

# Step 6: Create repo on GitHub (go to github.com → New repository)
# Name it: videostream
# Do NOT initialize with README (you already have one)

# Step 7: Connect local to GitHub
git remote add origin https://github.com/YOUR_USERNAME/videostream.git

# Step 8: Push
git branch -M main
git push -u origin main
```

### .gitignore file — create this before your first commit

```gitignore
# Credentials — NEVER commit these
config/app.php
config/database.php

# Keep example files (rename app.php → app.example.php with fake values)
# config/app.example.php   ← commit this one

# Uploaded user files — too large for Git
public/uploads/videos/
public/uploads/thumbnails/

# WordPress — massive folder, not your code
blog/

# PHP sessions
storage/sessions/

# Composer vendor folder
vendor/

# OS files
.DS_Store
Thumbs.db

# Editor files
.vscode/
.idea/
*.swp
```

### Daily workflow after setup

```bash
# Check what changed
git status

# See exact changes
git diff

# Stage specific files
git add app/controllers/HomeController.php
git add app/views/user/home.php

# Or stage everything
git add .

# Commit with a meaningful message
git commit -m "feat: add subscription expiry check in feed_json"

# Push to GitHub
git push
```

### Good commit message examples

```bash
git commit -m "feat: add OTP attempt lockout after 5 wrong tries"
git commit -m "fix: session not persisting on new server — create storage/sessions dir"
git commit -m "refactor: move subscription query from HomeController to UserModel"
git commit -m "security: remove hardcoded SMTP credentials from config"
git commit -m "docs: update README with WebSocket setup instructions"
```

---

## Known Issues & TODO

### Security (should fix before production)
- [ ] `SMTP_PASS` and `recaptcha_secret_key` are hardcoded in `config/app.php` — move to `.env`
- [ ] `VideoController::save()` and `delete()` do not call `requireAdmin()` — add session check
- [ ] No CSRF tokens on any form — add token generation and validation
- [ ] Two-tab registration with different emails overwrites `$_SESSION['pending_registration']`

### Architecture improvements
- [ ] Move `sendOtpMail()`, `verifyCaptcha()`, `emailDomainExists()` out of `AuthController` into service classes
- [ ] Move subscription SQL queries from `HomeController` into `SubscriptionModel`
- [ ] Replace `$userActions` whitelist array with convention-based routing
- [ ] Add RSS cache (file or DB) — `simplexml_load_file()` blocks page render if WordPress is slow

### Features
- [ ] Video search for users (currently only admin has search)
- [ ] Password reset via email
- [ ] Admin role levels (super admin vs moderator)
- [ ] Pagination on video lists
- [ ] Video upload progress indicator

---

## Author

**Anjali Purohit**
- Email: purohitanjali098@gmail.com
- Project built: May 2026
- Purpose: Training-Project

---

## License

This project is built for educational purposes.
