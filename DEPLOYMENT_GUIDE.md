# Campus Lost & Found - Deployment Guide

## 📋 Project Overview
A comprehensive lost and found management system with:
- **Frontend**: PHP with Bootstrap 5 and responsive design
- **Backend**: Python Flask REST API
- **Database**: MySQL
- **Features**: User authentication, item reporting, admin dashboard, messaging system

---

## 🚀 Deployment Options

### **Option 1: Local Development (Windows/Linux/Mac)**

#### Prerequisites
- PHP 7.4+ 
- Python 3.8+
- MySQL 5.7+

#### Setup Steps

1. **Extract the zip file**
   ```bash
   unzip campus-lost-found.zip
   cd campus-lost-found
   ```

2. **Setup MySQL Database**
   ```bash
   mysql -u root -p < database/lost_found.sql
   ```
   Or import manually using phpMyAdmin

3. **Configure Backend**
   - Edit `backend/config.py`:
   ```python
   DB_HOST = "localhost"
   DB_USER = "root"
   DB_PASSWORD = "your_password"
   DB_NAME = "lost_found_db"
   
   # Email Configuration (Optional)
   SMTP_SERVER = "smtp.gmail.com"
   SMTP_PORT = 587
   SMTP_USER = "your_email@gmail.com"
   SMTP_PASSWORD = "your_app_password"
   ADMIN_EMAIL = "admin@yoursite.com"
   ```

4. **Install Python Dependencies**
   ```bash
   cd backend
   pip install -r requirements.txt
   python app.py
   ```
   Backend runs on: `http://localhost:5001`

5. **Start Frontend (PHP)**
   ```bash
   # Terminal 2
   cd frontend
   php -S localhost:8000
   ```
   Frontend runs on: `http://localhost:8000`

6. **Access the Application**
   - User Console: `http://localhost:8000/login.php`
   - Demo Credentials:
     - Admin: `admin@campus.edu` / `admin123`
     - User: `user@campus.edu` / `user123`

---

### **Option 2: Cloud Deployment (Recommended)**

#### For Shared Hosting (cPanel/Plesk)

1. **Upload Files**
   - Extract zip file locally
   - Upload `frontend/` folder to your public_html directory
   - Upload `backend/` to a non-public directory (outside web root)

2. **Database Setup**
   - Use cPanel's MySQL tool to create database
   - Import `database/lost_found.sql`
   - Update database credentials in `backend/config.py`

3. **Backend Deployment**
   - Contact hosting provider for Python support
   - Or use a Python hosting service (see below)

#### For Modern Cloud Platforms

**Heroku** (Easiest for Flask backend)
```bash
# Install Heroku CLI
# Create Procfile
echo "web: gunicorn app:app" > backend/Procfile

# Deploy
heroku login
heroku create your-app-name
git push heroku main
```

**Render.com** (Free tier available)
- Connect your GitHub repo
- Set environment variables
- Deploy with one click

**PythonAnywhere** (Python-specific)
- Upload files via web interface
- Configure MySQL connection
- Set up web app configuration

**AWS/Google Cloud/Azure**
- Create virtual machine (EC2/Compute Engine/VM)
- Follow "Local Development" setup steps
- Set up SSL certificate
- Configure domain DNS

---

### **Option 3: Docker Deployment (Professional)**

Create `Dockerfile` in project root:

```dockerfile
FROM python:3.9

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    php \
    php-mysql \
    mysql-client

# Copy project
COPY . .

# Install Python dependencies
RUN pip install -r backend/requirements.txt

# Install PHP dependencies
COPY frontend /var/www/html

EXPOSE 5001 8000

CMD ["sh", "-c", "python backend/app.py & php -S 0.0.0.0:8000 -t /var/www/html"]
```

Build and run:
```bash
docker build -t campus-lost-found .
docker run -p 5001:5001 -p 8000:8000 campus-lost-found
```

---

## 📊 Database Structure

The application uses 5 main tables:

1. **users** - User accounts (admin/regular users)
2. **items** - Lost/Found items reports
3. **messages** - User messages to admin
4. **message_responses** - Admin responses to user messages
5. **notifications** - System notifications

All tables are auto-created by running the backend (ensure `auto_create_schema` is enabled).

---

## 🔐 Security Checklist

Before deploying to production:

- [ ] Change default admin password
- [ ] Configure HTTPS/SSL certificate
- [ ] Set strong database password
- [ ] Configure CORS properly for your domain
- [ ] Set up email sending (SMTP configuration)
- [ ] Enable CSRF protection
- [ ] Set secure session cookies (PHP.ini)
- [ ] Implement rate limiting on API endpoints
- [ ] Set up regular database backups
- [ ] Configure firewall rules
- [ ] Hide error messages in production
- [ ] Use environment variables for secrets

---

## 📧 Email Configuration

For email notifications to work:

1. **Gmail Setup**
   - Enable 2-Factor Authentication
   - Create "App Password": https://myaccount.google.com/apppasswords
   - Update `config.py` with app password

2. **Custom SMTP**
   - Update SMTP_SERVER, SMTP_PORT in `config.py`
   - Use your email provider's credentials

3. **Test Email**
   ```python
   python -c "from backend.routes.items import _send_owner_email; _send_owner_email('test@example.com', 'Test Subject', 'Test Body')"
   ```

---

## 🛠️ Environment Variables

Create `.env` file in project root:

```
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=lost_found_db
FLASK_ENV=production
FLASK_DEBUG=False
SMTP_SERVER=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SECRET_KEY=your_secret_key_here
```

Update `backend/config.py` to read from `.env`:
```python
from dotenv import load_dotenv
import os

load_dotenv()
DB_HOST = os.getenv('DB_HOST')
DB_USER = os.getenv('DB_USER')
# ... etc
```

---

## 📱 Frontend Deployment Notes

The frontend is pure PHP and can be deployed to:
- Traditional web hosting (cPanel, Plesk)
- GitHub Pages (static only - won't work with PHP backend)
- Netlify, Vercel (frontend only - need separate API host)
- Any server with PHP support

**Important**: Update API base URL in `frontend/js/main.js`:
```javascript
// Line 1-5 in main.js
const API_BASE_URL = "http://localhost:5001"; // Change this!
// For production:
const API_BASE_URL = "https://your-api-domain.com";
```

---

## 🧪 Testing After Deployment

1. **Test User Registration**
   - Create new account via "Create Account"
   - Verify email and password requirements

2. **Test Report Submission**
   - Report lost/found item
   - Verify item appears in database

3. **Test Admin Features**
   - Login as admin
   - View all reports
   - Send message responses

4. **Test Email Sending**
   - Admin sends response to user
   - Check user email inbox

5. **Test Mobile Responsiveness**
   - Open site on mobile device
   - Verify all text is visible and readable
   - Test form submissions

---

## 🐛 Troubleshooting

### Backend won't start
```bash
# Check Python version
python --version  # Should be 3.8+

# Check if port 5001 is in use
netstat -an | grep 5001

# Try different port in config.py
```

### Database connection error
```bash
# Verify MySQL is running
# Check credentials in config.py
# Ensure database exists
mysql -u root -p -e "SHOW DATABASES;"
```

### Text not visible on pages
- Clear browser cache (Ctrl+Shift+Delete)
- Check if CSS file is loading (F12 → Network tab)
- Verify PHP is serving files correctly

### CORS errors in browser console
- Update CORS settings in `backend/app.py`
- Change allowed origins to your domain

### Email not sending
- Verify SMTP credentials
- Check email service allows "less secure apps"
- Enable debug logging in `config.py`

---

## 📞 Support

For issues or questions:
1. Check this guide first
2. Review browser console for errors (F12)
3. Check backend logs
4. Check database connection

---

## 📄 License

This project is provided as-is for educational purposes.

---

## ✅ Project Complete!

Your Campus Lost & Found application is ready for deployment. Choose your hosting platform above and follow the setup instructions.

**Happy deploying! 🚀**
