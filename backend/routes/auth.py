from flask import Blueprint, request, jsonify
import bcrypt
from db import query

auth_bp = Blueprint("auth", __name__, url_prefix="")


@auth_bp.route("/login", methods=["POST"])
def login():
    """
    POST /login
    Body: { "email": "...", "password": "..." }
    Returns: { "success": true, "user": {...} }  or  { "success": false, "message": "..." }
    """
    data = request.get_json(silent=True)
    if not data:
        return jsonify({"success": False, "message": "Request body must be JSON"}), 400

    email    = (data.get("email")    or "").strip().lower()
    password = (data.get("password") or "").strip()

    if not email or not password:
        return jsonify({"success": False, "message": "Email and password are required"}), 400

    user = query(
        "SELECT id, name, email, password FROM users WHERE email = %s LIMIT 1",
        (email,),
        fetchone=True,
    )

    if not user:
        return jsonify({"success": False, "message": "Invalid email or password"}), 401

    # Verify password
    stored_hash = user["password"]
    # Support both bcrypt-hashed and plain-text (dev convenience only)
    try:
        password_ok = bcrypt.checkpw(password.encode("utf-8"), stored_hash.encode("utf-8"))
    except Exception:
        password_ok = (password == stored_hash)

    if not password_ok:
        return jsonify({"success": False, "message": "Invalid email or password"}), 401

    return jsonify({
        "success": True,
        "message": "Login successful",
        "user": {
            "id":    user["id"],
            "name":  user["name"],
            "email": user["email"],
        },
    }), 200
