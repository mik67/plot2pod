# plot2pod – MVP Design
*2026-03-13*

## Overview

plot2pod is an English-language podcast platform where users submit topics, source links, or their own files, and the platform converts them into AI-generated debate-format podcasts (via NotebookLM). The MVP validates user interest before building full automation.

## Approach

Static-feeling frontend (HTML/CSS/JS via Frontend Design plugin) backed by a lightweight PHP/MariaDB layer for authentication and request tracking. Hosted on shared cPanel hosting. NotebookLM workflow remains manual — Miloš processes requests and uploads the resulting MP3s.

## Architecture

```
[User browser]
    │
    ▼
[Frontend – HTML/CSS/JS]
    │
    ▼
[PHP Backend – shared hosting]
    ├── auth.php        (registration, login, session)
    ├── request.php     (submit request, file upload)
    ├── dashboard.php   (user's request status)
    └── admin.php       (Miloš: manage requests, link MP3s)
    │
    ▼
[MariaDB]
    ├── users
    ├── podcasts
    └── requests
    │
    ▼
[Server filesystem]  ← MP3 files uploaded by Miloš via FTP/cPanel
```

**Email:** PHPMailer — notifies Miloš on new request, notifies user when status → `done`.

## Pages & User Flow

### Anonymous visitor
- `/` — Hero + podcast showcase grid with HTML5 player. Header: Login | Register.
- `/podcast/:id` — Podcast detail + player.

### Registered user (additionally)
- `/register` — name, email, password
- `/login` — email, password
- `/request` — submission form with 3 modes:
  - 📝 Topic only (text field)
  - 🔗 Source links (textarea for URLs)
  - 📁 Upload files (max 3 files, PDF/TXT/DOCX)
- `/dashboard` — list of own requests with status badges (Pending → Processing → Done). Done = link to finished podcast.

### Admin (Miloš)
- `/admin` — list of all requests (newest first), status management, assign MP3 to request → triggers user email notification.

## Database Schema

```sql
users
  id            INT AUTO_INCREMENT PRIMARY KEY
  name          VARCHAR(100)
  email         VARCHAR(150) UNIQUE
  password_hash VARCHAR(255)
  created_at    DATETIME DEFAULT NOW()
  verified      TINYINT DEFAULT 0

podcasts
  id          INT AUTO_INCREMENT PRIMARY KEY
  title       VARCHAR(200)
  description TEXT
  mp3_path    VARCHAR(500)
  duration    INT
  created_at  DATETIME DEFAULT NOW()
  published   TINYINT DEFAULT 1

requests
  id           INT AUTO_INCREMENT PRIMARY KEY
  user_id      INT REFERENCES users(id)
  type         ENUM('topic','links','files')
  content      TEXT
  file_paths   TEXT            -- JSON array of paths
  status       ENUM('pending','processing','done') DEFAULT 'pending'
  podcast_id   INT NULL REFERENCES podcasts(id)
  created_at   DATETIME DEFAULT NOW()
  notified_at  DATETIME NULL
```

## File Structure

```
plot2pod/
├── index.php
├── register.php
├── login.php
├── logout.php
├── request.php
├── dashboard.php
├── podcast.php
├── admin.php
├── config.php      (gitignored)
├── db.php
├── auth.php
├── css/style.css
├── js/app.js
└── uploads/        (gitignored)
```

## Security (MVP minimum)

- PDO prepared statements everywhere (SQL injection prevention)
- `htmlspecialchars()` on all user-generated output (XSS prevention)
- Upload validation: PDF/TXT/DOCX only, max 10 MB per file
- CSRF tokens on all forms
- Passwords: PHP `password_hash()` / `password_verify()` (bcrypt)
- Admin access: `is_admin` flag on users table

## Email Notifications

1. **Miloš** receives email on every new request submission
2. **User** receives email when Miloš changes request status to `done`

## Deliberately Out of Scope (MVP)

- Email verification on registration
- Password reset flow
- Payments / monetization
- Stories / literature section
- NotebookLM API automation
- Cloud storage (files stay on server filesystem)
- Multi-language support

## Initial Content

Miloš creates 3–5 example podcasts himself (via NotebookLM) before launch to populate the showcase.

## Success Criteria

- Site is live on shared hosting
- Anonymous visitors can browse and play podcasts
- Users can register, log in, and submit a request
- Miloš receives email notifications for new requests
- Users receive email notification when their podcast is ready
- Dashboard shows request status correctly
