# 🚀 QUICK START GUIDE - Campus Lost & Found

## Download & Extract
✅ Download: `campus-lost-found-COMPLETE.zip` (24.43 MB)
✅ Extract to your desired location

---

## ⚡ 5-Minute Local Setup

### Step 1: Setup Database
```bash
# Import SQL file
mysql -u root -p < database/lost_found.sql
```

### Step 2: Configure Backend
Edit `backend/config.py`:
```python
DB_HOST = "localhost"
DB_USER = "root"
DB_PASSWORD = "your_mysql_password"
DB_NAME = "lost_found_db"
```

### Step 3: Install & Run Backend
```bash
cd backend
pip install -r requirements.txt
python app.py
# Runs on http://localhost:5001
```

### Step 4: Start Frontend (New Terminal)
```bash
cd frontend
php -S localhost:8000
# Opens on http://localhost:8000
```

### Step 5: Login
- URL: `http://localhost:8000/login.php`
- **Admin**: `admin@campus.edu` / `admin123`
- **User**: `user@campus.edu` / `user123`

---

## 📦 What's Inside

```
campus-lost-found/
├── backend/                    # Python Flask API
│   ├── app.py                 # Main application
│   ├── config.py              # Configuration (EDIT THIS!)
│   ├── db.py                  # Database manager
│   ├── requirements.txt        # Python dependencies
│   ├── routes/
│   │   ├── auth.py           # Login/Register API
│   │   └── items.py          # Items & Messages API
│   └── uploads/              # Uploaded images
│
├── frontend/                   # PHP Frontend
│   ├── login.php             # Authentication page
│   ├── dashboard.php         # Admin console
│   ├── browse_items.php      # Browse lost/found items
│   ├── report_item.php       # Report new item
│   ├── config.php            # PHP configuration
│   ├── js/
│   │   └── main.js          # Frontend logic
│   ├── css/
│   │   └── style.css        # Styling (dark theme)
│   └── uploads/             # User uploaded files
│
├── database/
│   └── lost_found.sql       # Database schema
│
├── README.md                # Project overview
├── DEPLOYMENT_GUIDE.md      # Full deployment instructions
└── QUICK_START.md          # This file!
```

---

## 🌐 Deployment Options

### **Option A: Shared Hosting (Easy)**
1. Upload `frontend/` to public_html via FTP
2. Upload `backend/` outside public_html
3. Create database via cPanel
4. Update database credentials

**Hosts**: HostGator, Bluehost, SiteGround, etc.

### **Option B: Cloud (Modern)**
1. **Render.com** - Free Python hosting
   - Connect GitHub repo
   - Deploy in 2 clicks
   
2. **PythonAnywhere** - Python-specific
   - Upload files via web interface
   - Easy MySQL setup

3. **Heroku** - Excellent for Flask
   ```bash
   heroku create your-app-name
   git push heroku main
   ```

### **Option C: VPS/Dedicated (Full Control)**
1. Rent VPS ($5-20/month)
2. Install Python, PHP, MySQL
3. Follow "Local Setup" steps
4. Use SSL certificate (Let's Encrypt - free!)

**VPS Providers**: DigitalOcean, Linode, Vultr, AWS

### **Option D: Docker (Professional)**
```bash
docker build -t campus-lost-found .
docker run -p 5001:5001 -p 8000:8000 campus-lost-found
```

---

## 🔑 Key Features

✅ **User Authentication** - Secure login with password hashing
✅ **Report Items** - Users can report lost/found items  
✅ **Admin Dashboard** - View all reports and stats
✅ **Admin Messaging** - Respond to user messages with email
✅ **Responsive Design** - Works on mobile & desktop
✅ **Dark Theme** - Modern glassmorphism UI
✅ **Image Upload** - Attach photos to reports
✅ **Search & Filter** - Find items by category/status

---

## 🔐 Important Security Notes

Before going live:

1. **Change Admin Password**
   - Login → Edit profile (or database)
   - Use strong password (12+ chars, mixed case, numbers)

2. **Setup HTTPS**
   - Use free SSL from Let's Encrypt
   - Update all http:// to https://

3. **Database Backup**
   - Backup daily to external storage
   - Command: `mysqldump -u root -p lost_found_db > backup.sql`

4. **Configure Email**
   - Update SMTP settings in `backend/config.py`
   - Test with: `python test_email.py`

5. **Update API URL**
   - In `frontend/js/main.js` (line 1):
   - Change: `const API_BASE_URL = "http://localhost:5001"`
   - To: `const API_BASE_URL = "https://your-api-domain.com"`

---

## ❌ Troubleshooting

**Backend won't start?**
```bash
# Check Python version (need 3.8+)
python --version

# Check if port 5001 is free
netstat -an | grep 5001

# Try running with verbose output
python app.py --debug
```

**Database error?**
```bash
# Verify MySQL running
mysql -u root -p -e "SHOW DATABASES;"

# Check if database exists
mysql -u root -p -e "USE lost_found_db; SHOW TABLES;"
```

**Text not visible?**
```bash
# Clear browser cache (Ctrl+Shift+Delete)
# Hard refresh page (Ctrl+Shift+R)
```

**CORS errors?**
- Backend is on port 5001, frontend on 8000
- This is normal and handled in code
- For production, update CORS in `backend/app.py`

---

## 📊 Test Data

Database includes sample items:
- **Users**: admin@campus.edu, user@campus.edu
- **Items**: 15 test items (Lost & Found)
- **Messages**: Sample messages in admin dashboard

---

## 📞 Need Help?

1. **Check DEPLOYMENT_GUIDE.md** - Full documentation
2. **Check browser console** - F12 → Console tab for errors
3. **Check Python logs** - Look at backend terminal output
4. **Check MySQL** - `mysql -u root -p lost_found_db -e "SELECT * FROM items;"`

---

## ✨ Next Steps

1. ✅ Extract zip file
2. ✅ Follow "5-Minute Local Setup" above
3. ✅ Test all features locally
4. ✅ Read DEPLOYMENT_GUIDE.md for production setup
5. ✅ Choose hosting platform
6. ✅ Deploy!

---

**Your Campus Lost & Found application is ready! 🎉**

Questions? Check DEPLOYMENT_GUIDE.md for detailed instructions.
