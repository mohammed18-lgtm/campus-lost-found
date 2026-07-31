import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    # Database
    DB_HOST     = os.getenv("DB_HOST",     "localhost")
    DB_PORT     = int(os.getenv("DB_PORT", "3306"))
    DB_USER     = os.getenv("DB_USER",     "root")
    DB_PASSWORD = os.getenv("DB_PASSWORD", "")
    DB_NAME     = os.getenv("DB_NAME",     "lost_found_db")

    # Upload settings
    UPLOAD_FOLDER     = os.path.join(os.path.dirname(__file__), "uploads")
    ALLOWED_EXTENSIONS = {"jpg", "jpeg", "png", "gif", "webp"}
    MAX_CONTENT_LENGTH = 5 * 1024 * 1024   # 5 MB

    # Security
    SECRET_KEY = os.getenv("SECRET_KEY", "campus-lf-secret-key-change-in-prod")
