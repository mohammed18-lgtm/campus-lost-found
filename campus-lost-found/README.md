# 🎓 Campus Lost & Found Portal

A complete full-stack web application for managing lost and found items on a campus.

**Stack:** PHP 8 (frontend) · Python Flask (REST API) · MySQL (database) · Bootstrap 5

---

## Project Structure

```
campus-lost-found/
├── frontend/               # PHP frontend (run via XAMPP/Apache)
│   ├── login.php           # Glassmorphism login page
│   ├── dashboard.php       # Stats + recent activity dashboard
│   ├── report_item.php     # Report a lost or found item (with image upload)
│   ├── browse_items.php    # Browse, filter, search & claim items
│   ├── logout.php          # Session destroy + redirect
│   ├── config.php          # API base URL, session setup, cURL helpers
│   ├── css/style.css       # Custom dark-theme stylesheet
│   ├── js/main.js          # Frontend JavaScript (fetch, toasts, spinner)
│   └── uploads/            # Directory for uploaded images (PHP side — optional)
│
├── backend/                # Python Flask REST API
│   ├── app.py              # Flask application factory + entry point
│   ├── config.py           # Database & upload config (reads .env)
│   ├── db.py               # MySQL connection + parameterised query helper
│   ├── routes/
│   │   ├── auth.py         # POST /login
│   │   └── items.py        # All item endpoints
│   ├── uploads/            # Uploaded images served by Flask
│   ├── requirements.txt    # Python dependencies
│   └── .env.example        # Environment variable template
│
└── database/
    └── lost_found.sql      # Schema + sample data (12 items, 3 users)
```

---

## Quick-Start — Local Setup

### Prerequisites

| Tool | Version |
|------|---------|
| XAMPP (PHP + Apache) | PHP ≥ 8.1 |
| Python | ≥ 3.10 |
| MySQL | 8.x (included in XAMPP) |

---

### Step 1 — Database

1. Start **XAMPP** and ensure **MySQL** is running.
2. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
3. Click **Import** → choose `database/lost_found.sql` → click **Go**.
4. The database `lost_found_db` will be created with tables and sample data.

---

### Step 2 — Flask Backend

```bash
# Navigate to the backend folder
cd campus-lost-found/backend

# Copy and edit the environment file
cp .env.example .env
# Edit .env — set DB_PASSWORD to your MySQL root password

# Create a virtual environment
python -m venv venv

# Activate it
# Windows:
venv\Scripts\activate
# macOS / Linux:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Run the API server
python app.py
```

The Flask API will start at **http://localhost:5001**

> **Verify it's running:** open http://localhost:5001/health in your browser.
> You should see: `{"message":"Campus Lost & Found API running","status":"ok"}`

---

### Step 3 — PHP Frontend

1. Copy the `frontend/` folder into your XAMPP web root:
   - **Windows:** `C:\xampp\htdocs\lost-and-found\`
   - **macOS:**   `/Applications/XAMPP/htdocs/lost-and-found/`
2. Start **Apache** from the XAMPP Control Panel.
3. Open your browser: **http://localhost/lost-and-found/login.php**

---

## Demo Login Credentials

| Email | Password | Name |
|-------|----------|------|
| admin@campus.edu | admin123 | Admin User |
| john@campus.edu  | admin123 | John Student |
| sarah@campus.edu | admin123 | Sarah Johnson |

> Passwords in the sample SQL are bcrypt-hashed from `admin123`.

---

## REST API Reference

All endpoints run at `http://localhost:5001`.

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Verify email + password, returns user info |

**Request body:**
```json
{ "email": "admin@campus.edu", "password": "admin123" }
```

---

### Items

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/items` | List items with search, filter & pagination |
| GET  | `/item/<id>` | Get single item by ID |
| POST | `/item` | Create new item (multipart/form-data) |
| PUT  | `/item/<id>` | Update an existing item |
| DELETE | `/item/<id>` | Delete item + its image file |
| POST | `/claim/<id>` | Mark an item as Claimed |
| GET  | `/items/recent` | Recently reported items (dashboard) |
| GET  | `/stats` | Count of Lost / Found / Claimed |
| GET  | `/categories` | Distinct category list |

**Query params for GET /items:**

| Param | Example | Description |
|-------|---------|-------------|
| search | `backpack` | Search item name & description |
| category | `Electronics` | Filter by category |
| status | `Lost` | Filter: Lost / Found / Claimed |
| page | `1` | Page number |
| limit | `9` | Items per page (max 50) |

**Uploaded images** are served at `/uploads/<filename>`.

---

## Features

- ✅ Glassmorphism login with password toggle + show/hide
- ✅ Session-based authentication (PHP session, bcrypt passwords)
- ✅ Dashboard with live stats cards and recent activity table
- ✅ Report form — multipart image upload with drag-and-drop preview
- ✅ Browse page — responsive Bootstrap 5 cards with real images
- ✅ Search by item name, filter by category and status
- ✅ Pagination (9 items per page)
- ✅ One-click claim with confirmation popup and instant status update
- ✅ Loading spinner overlay during all API requests
- ✅ Toast notifications (success / error / warning)
- ✅ Mobile responsive (all breakpoints)
- ✅ SQL injection protection via prepared statements
- ✅ File type + size validation (max 5 MB, image types only)
- ✅ XSS protection via `htmlspecialchars()` on all PHP output

---

## Configuration

### `frontend/config.php`
```php
define('API_BASE_URL', 'http://localhost:5001');
```
Change this if your Flask server runs on a different host or port.

### `backend/.env`
```
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=lost_found_db
SECRET_KEY=change-this
```

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| "API connection failed" | Make sure Flask is running: `python app.py` |
| Login always fails | Check MySQL is running and `.env` has correct password |
| Images not showing | Confirm `backend/uploads/` directory exists and is writable |
| Blank page on PHP | Check Apache is running in XAMPP and the folder is in `htdocs/` |
| `ModuleNotFoundError` | Activate venv and run `pip install -r requirements.txt` |
| `Access-Control` error | Flask-CORS is installed; reload Flask after `pip install` |

---

## Security Notes

- Passwords are stored as bcrypt hashes (cost factor 12).
- All MySQL queries use parameterised statements (no string concatenation).
- Image uploads validate MIME type extension and enforce a 5 MB size limit.
- PHP output is escaped with `htmlspecialchars()` everywhere.
- PHP sessions use `HttpOnly` and `SameSite=Lax` cookie flags.

---

## License

MIT — free to use and modify for educational purposes.
