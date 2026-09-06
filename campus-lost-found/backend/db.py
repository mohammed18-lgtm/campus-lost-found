import mysql.connector
import bcrypt
from mysql.connector import Error
from config import Config


def ensure_schema():
    """Make sure the required tables and fields exist for user/admin workflows."""
    conn = None
    try:
        conn = get_connection()
        cur = conn.cursor(dictionary=True)

        cur.execute("""
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                failed_login_count INT NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                last_login_at DATETIME NULL,
                reset_token VARCHAR(255) NULL,
                reset_token_expires_at DATETIME NULL,
                deleted_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        for column_name in ('role', 'failed_login_count', 'locked_until', 'last_login_at', 'reset_token', 'reset_token_expires_at', 'deleted_at'):
            cur.execute(f"SHOW COLUMNS FROM users LIKE '{column_name}'")
            if cur.fetchone() is None:
                if column_name == 'role':
                    cur.execute("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER email")
                elif column_name == 'failed_login_count':
                    cur.execute("ALTER TABLE users ADD COLUMN failed_login_count INT NOT NULL DEFAULT 0 AFTER created_at")
                elif column_name == 'locked_until':
                    cur.execute("ALTER TABLE users ADD COLUMN locked_until DATETIME NULL AFTER failed_login_count")
                elif column_name == 'last_login_at':
                    cur.execute("ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL AFTER locked_until")
                elif column_name == 'reset_token':
                    cur.execute("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL AFTER last_login_at")
                elif column_name == 'reset_token_expires_at':
                    cur.execute("ALTER TABLE users ADD COLUMN reset_token_expires_at DATETIME NULL AFTER reset_token")
                elif column_name == 'deleted_at':
                    cur.execute("ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL AFTER reset_token_expires_at")

        cur.execute("UPDATE users SET role = 'admin' WHERE email = %s", ('admin@campus.edu',))
        cur.execute("UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''")

        cur.execute("""
            CREATE TABLE IF NOT EXISTS items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_name VARCHAR(150) NOT NULL,
                category VARCHAR(100) NOT NULL,
                status ENUM('Lost', 'Found', 'Claimed', 'Returned') NOT NULL DEFAULT 'Lost',
                description TEXT,
                location VARCHAR(200) NOT NULL,
                date DATE NOT NULL,
                contact_number VARCHAR(20) NOT NULL,
                image VARCHAR(255) DEFAULT NULL,
                reporter_name VARCHAR(100) DEFAULT NULL,
                reporter_email VARCHAR(150) DEFAULT NULL,
                reported_by_user_id INT NULL,
                color VARCHAR(60) DEFAULT NULL,
                claim_code VARCHAR(80) DEFAULT NULL,
                verification_details TEXT DEFAULT NULL,
                returned_at DATETIME NULL,
                deleted_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_reported_by_user (reported_by_user_id),
                UNIQUE KEY uq_claim_code (claim_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        cur.execute("SHOW COLUMNS FROM items LIKE 'status'")
        status_row = cur.fetchone()
        if status_row and 'Returned' not in (status_row.get('Type') or ''):
            cur.execute("ALTER TABLE items MODIFY COLUMN status ENUM('Lost', 'Found', 'Claimed', 'Returned') NOT NULL DEFAULT 'Lost'")

        for column_name in ('reported_by_user_id', 'reporter_name', 'reporter_email', 'color', 'claim_code', 'verification_details', 'returned_at', 'deleted_at'):
            cur.execute(f"SHOW COLUMNS FROM items LIKE '{column_name}'")
            if cur.fetchone() is None:
                if column_name == 'reported_by_user_id':
                    cur.execute("ALTER TABLE items ADD COLUMN reported_by_user_id INT NULL AFTER image")
                elif column_name == 'reporter_name':
                    cur.execute("ALTER TABLE items ADD COLUMN reporter_name VARCHAR(100) NULL AFTER image")
                elif column_name == 'reporter_email':
                    cur.execute("ALTER TABLE items ADD COLUMN reporter_email VARCHAR(150) NULL AFTER reporter_name")
                elif column_name == 'color':
                    cur.execute("ALTER TABLE items ADD COLUMN color VARCHAR(60) NULL AFTER reporter_email")
                elif column_name == 'claim_code':
                    cur.execute("ALTER TABLE items ADD COLUMN claim_code VARCHAR(80) NULL AFTER color")
                elif column_name == 'verification_details':
                    cur.execute("ALTER TABLE items ADD COLUMN verification_details TEXT NULL AFTER claim_code")
                elif column_name == 'returned_at':
                    cur.execute("ALTER TABLE items ADD COLUMN returned_at DATETIME NULL AFTER verification_details")
                elif column_name == 'deleted_at':
                    cur.execute("ALTER TABLE items ADD COLUMN deleted_at DATETIME NULL AFTER returned_at")

        cur.execute("""
            CREATE TABLE IF NOT EXISTS item_claims (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                user_id INT NULL,
                claimant_name VARCHAR(120) NOT NULL,
                claimant_email VARCHAR(150) NOT NULL,
                claimant_phone VARCHAR(30) DEFAULT NULL,
                identifying_details TEXT NOT NULL,
                status ENUM('pending', 'approved', 'rejected', 'returned') NOT NULL DEFAULT 'pending',
                claim_code VARCHAR(80) DEFAULT NULL,
                admin_id INT NULL,
                approved_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_item_claims_item (item_id),
                INDEX idx_item_claims_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        cur.execute("""
            CREATE TABLE IF NOT EXISTS conversations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                admin_id INT NULL,
                subject VARCHAR(150) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        cur.execute("""
            CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                user_name VARCHAR(100) NOT NULL,
                item_name VARCHAR(150) DEFAULT NULL,
                subject VARCHAR(150) NOT NULL,
                message TEXT NOT NULL,
                status ENUM('new', 'reviewed') NOT NULL DEFAULT 'new',
                conversation_id INT NULL,
                from_user_id INT NULL,
                to_user_id INT NULL,
                from_role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
                to_role ENUM('user', 'admin') NOT NULL DEFAULT 'admin',
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_messages (user_id),
                INDEX idx_conversation_messages (conversation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        for column_name in ('conversation_id', 'from_user_id', 'to_user_id', 'from_role', 'to_role', 'is_read', 'updated_at'):
            cur.execute(f"SHOW COLUMNS FROM messages LIKE '{column_name}'")
            if cur.fetchone() is None:
                if column_name == 'conversation_id':
                    cur.execute("ALTER TABLE messages ADD COLUMN conversation_id INT NULL AFTER status")
                elif column_name == 'from_user_id':
                    cur.execute("ALTER TABLE messages ADD COLUMN from_user_id INT NULL AFTER conversation_id")
                elif column_name == 'to_user_id':
                    cur.execute("ALTER TABLE messages ADD COLUMN to_user_id INT NULL AFTER from_user_id")
                elif column_name == 'from_role':
                    cur.execute("ALTER TABLE messages ADD COLUMN from_role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER to_user_id")
                elif column_name == 'to_role':
                    cur.execute("ALTER TABLE messages ADD COLUMN to_role ENUM('user', 'admin') NOT NULL DEFAULT 'admin' AFTER from_role")
                elif column_name == 'is_read':
                    cur.execute("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER to_role")
                elif column_name == 'updated_at':
                    cur.execute("ALTER TABLE messages ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at")

        cur.execute("""
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(150) NOT NULL,
                message TEXT NOT NULL,
                sent_by VARCHAR(100) NOT NULL DEFAULT 'Admin',
                notification_type VARCHAR(50) NOT NULL DEFAULT 'info',
                related_item_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                INDEX idx_notifications_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        for column_name in ('notification_type', 'related_item_id'):
            cur.execute(f"SHOW COLUMNS FROM notifications LIKE '{column_name}'")
            if cur.fetchone() is None:
                if column_name == 'notification_type':
                    cur.execute("ALTER TABLE notifications ADD COLUMN notification_type VARCHAR(50) NOT NULL DEFAULT 'info' AFTER sent_by")
                elif column_name == 'related_item_id':
                    cur.execute("ALTER TABLE notifications ADD COLUMN related_item_id INT NULL AFTER notification_type")

        cur.execute("""
            CREATE TABLE IF NOT EXISTS message_responses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message_id INT NOT NULL,
                user_id INT NOT NULL,
                user_email VARCHAR(150) NOT NULL,
                admin_id INT NOT NULL,
                admin_name VARCHAR(100) NOT NULL,
                response_text TEXT NOT NULL,
                email_sent TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_message_responses (message_id),
                INDEX idx_response_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        cur.execute("""
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_reset_tokens_user (user_id),
                UNIQUE KEY uq_password_reset_token (token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        cur.execute("""
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                email VARCHAR(150) NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                status ENUM('failed', 'success', 'blocked') NOT NULL DEFAULT 'failed',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_login_attempts_email (email),
                INDEX idx_login_attempts_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        cur.execute("""
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(120) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_audit_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        conn.commit()

        cur.execute("SELECT id, password, role FROM users WHERE email = %s LIMIT 1", ('admin@campus.edu',))
        admin_row = cur.fetchone()
        if admin_row is None:
            cur.execute(
                "INSERT INTO users (name, email, password, role) VALUES (%s, %s, %s, %s)",
                ('Admin User', 'admin@campus.edu', bcrypt.hashpw(b'admin123', bcrypt.gensalt()).decode('utf-8'), 'admin'),
            )
            conn.commit()
        else:
            stored_password = (admin_row.get('password') or '')
            if stored_password == 'admin123' or not stored_password.startswith('$2'):
                cur.execute(
                    "UPDATE users SET password = %s, role = 'admin' WHERE email = %s",
                    (bcrypt.hashpw(b'admin123', bcrypt.gensalt()).decode('utf-8'), 'admin@campus.edu'),
                )
                conn.commit()

    except Error as e:
        if conn:
            conn.rollback()
        raise e
    finally:
        if conn and conn.is_connected():
            conn.close()


def get_connection():
    """Return a fresh MySQL connection."""
    return mysql.connector.connect(
        host=Config.DB_HOST,
        port=Config.DB_PORT,
        user=Config.DB_USER,
        password=Config.DB_PASSWORD,
        database=Config.DB_NAME,
        charset="utf8mb4",
        use_unicode=True,
    )


def query(sql: str, params: tuple = (), fetchone: bool = False, commit: bool = False):
    """
    Execute a parameterised query.
    - For SELECT  : returns list of dicts (or one dict if fetchone=True).
    - For INSERT  : returns lastrowid.
    - For UPDATE/DELETE : returns rowcount.
    Set commit=True for INSERT / UPDATE / DELETE.
    """
    conn = None
    try:
        conn = get_connection()
        cur = conn.cursor(dictionary=True)
        cur.execute(sql, params)

        if commit:
            conn.commit()
            if sql.strip().upper().startswith("INSERT"):
                return cur.lastrowid
            return cur.rowcount

        if fetchone:
            return cur.fetchone()
        return cur.fetchall()

    except Error as e:
        if conn and commit:
            conn.rollback()
        raise e
    finally:
        if conn and conn.is_connected():
            conn.close()
