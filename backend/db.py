import mysql.connector
from mysql.connector import Error
from config import Config

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
