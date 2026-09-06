from datetime import datetime, timedelta
from flask import Blueprint, request, jsonify
import bcrypt, secrets
from db import query

auth_bp = Blueprint("auth", __name__, url_prefix="")


def _record_audit(user_id, action, details, ip_address=None):
    try:
        query(
            "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (%s, %s, %s, %s)",
            (user_id, action, details[:500], ip_address),
            commit=True,
        )
    except Exception:
        pass


@auth_bp.route("/register", methods=["POST"])
def register():
    data = request.get_json(silent=True)
    if not data:
        return jsonify({"success": False, "message": "Request body must be JSON"}), 400

    name = (data.get("name") or "").strip()
    email = (data.get("email") or "").strip().lower()
    password = (data.get("password") or "").strip()

    if not name or not email or not password:
        return jsonify({"success": False, "message": "Name, email, and password are required"}), 400

    if len(password) < 6:
        return jsonify({"success": False, "message": "Password must be at least 6 characters"}), 400

    if query("SELECT id FROM users WHERE email = %s LIMIT 1", (email,), fetchone=True):
        return jsonify({"success": False, "message": "An account with this email already exists"}), 409

    hashed = bcrypt.hashpw(password.encode("utf-8"), bcrypt.gensalt()).decode("utf-8")
    user_id = query(
        "INSERT INTO users (name, email, password, role, failed_login_count) VALUES (%s, %s, %s, 'user', 0)",
        (name, email, hashed),
        commit=True,
    )

    user = query("SELECT id, name, email, role FROM users WHERE id = %s LIMIT 1", (user_id,), fetchone=True)
    _record_audit(user_id, "register", "New account created", request.remote_addr)
    return jsonify({
        "success": True,
        "message": "Account created successfully",
        "user": {
            "id": user["id"],
            "name": user["name"],
            "email": user["email"],
            "role": user["role"],
        },
    }), 201


@auth_bp.route("/login", methods=["POST"])
def login():
    data = request.get_json(silent=True)
    if not data:
        return jsonify({"success": False, "message": "Request body must be JSON"}), 400

    email = (data.get("email") or "").strip().lower()
    password = (data.get("password") or "").strip()
    requested_role = (data.get("role") or "user").strip().lower()
    ip_address = request.remote_addr

    if not email or not password:
        return jsonify({"success": False, "message": "Email and password are required"}), 400

    user = query(
        "SELECT id, name, email, password, role, failed_login_count, locked_until FROM users WHERE email = %s LIMIT 1",
        (email,),
        fetchone=True,
    )

    if not user:
        _record_audit(None, "login_failed", f"Unknown email: {email}", ip_address)
        return jsonify({"success": False, "message": "Invalid email or password"}), 401

    if user.get("locked_until"):
        try:
            locked_until = datetime.strptime(str(user["locked_until"]), "%Y-%m-%d %H:%M:%S")
            if locked_until > datetime.now():
                _record_audit(user["id"], "login_blocked", "Account temporarily locked", ip_address)
                return jsonify({"success": False, "message": "This account has been temporarily locked after repeated failed attempts. Please try again later."}), 423
        except Exception:
            pass

    stored_hash = user["password"]
    try:
        password_ok = bcrypt.checkpw(password.encode("utf-8"), stored_hash.encode("utf-8"))
    except Exception:
        password_ok = (password == stored_hash)

    if not password_ok:
        failed_count = int(user.get("failed_login_count") or 0) + 1
        lock_until = None
        if failed_count >= 5:
            lock_until = (datetime.now() + timedelta(minutes=15)).strftime("%Y-%m-%d %H:%M:%S")
            status = 'blocked'
            message = "This account has been temporarily locked after repeated failed attempts. Please try again later."
        else:
            status = 'failed'
            message = "Invalid email or password"

        query(
            "UPDATE users SET failed_login_count = %s, locked_until = %s WHERE id = %s",
            (failed_count, lock_until, user["id"]),
            commit=True,
        )
        query(
            "INSERT INTO login_attempts (user_id, email, ip_address, user_agent, status) VALUES (%s, %s, %s, %s, %s)",
            (user["id"], email, ip_address, request.user_agent.string[:255], status),
            commit=True,
        )
        _record_audit(user["id"], "login_failed", f"Failed login count={failed_count}", ip_address)
        return jsonify({"success": False, "message": message}), 423 if lock_until else 401

    query(
        "UPDATE users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = %s",
        (user["id"],),
        commit=True,
    )
    query(
        "INSERT INTO login_attempts (user_id, email, ip_address, user_agent, status) VALUES (%s, %s, %s, %s, %s)",
        (user["id"], email, ip_address, request.user_agent.string[:255], 'success'),
        commit=True,
    )

    actual_role = (user["role"] or "user").lower()
    if requested_role not in ("user", "admin"):
        requested_role = "user"

    if requested_role != actual_role:
        _record_audit(user["id"], "role_mismatch", f"Requested role {requested_role}, actual role {actual_role}", ip_address)
        return jsonify({"success": False, "message": f"This account is not authorized for the {requested_role} console."}), 403

    _record_audit(user["id"], "login_success", "User login succeeded", ip_address)
    return jsonify({
        "success": True,
        "message": "Login successful",
        "user": {
            "id": user["id"],
            "name": user["name"],
            "email": user["email"],
            "role": actual_role,
        },
    }), 200


@auth_bp.route("/forgot-password", methods=["POST"])
def forgot_password():
    data = request.get_json(silent=True) or {}
    email = (data.get("email") or "").strip().lower()
    if not email:
        return jsonify({"success": False, "message": "Email is required"}), 400

    user = query("SELECT id, email, name FROM users WHERE email = %s LIMIT 1", (email,), fetchone=True)
    if not user:
        return jsonify({"success": True, "message": "If an account exists, we sent a password reset link."}), 200

    token = secrets.token_urlsafe(32)
    expires = (datetime.now() + timedelta(hours=1)).strftime("%Y-%m-%d %H:%M:%S")
    query("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (%s, %s, %s)", (user["id"], token, expires), commit=True)
    query("UPDATE users SET reset_token = %s, reset_token_expires_at = %s WHERE id = %s", (token, expires, user["id"]), commit=True)
    _record_audit(user["id"], "password_reset_requested", "Password reset link generated", request.remote_addr)
    return jsonify({
        "success": True,
        "message": "If an account exists, we sent a password reset link.",
        "token": token,
    }), 200


@auth_bp.route("/reset-password", methods=["POST"])
def reset_password():
    data = request.get_json(silent=True) or {}
    token = (data.get("token") or "").strip()
    password = (data.get("password") or "").strip()
    if not token or not password:
        return jsonify({"success": False, "message": "Token and password are required"}), 400
    if len(password) < 6:
        return jsonify({"success": False, "message": "Password must be at least 6 characters"}), 400

    reset_row = query(
        "SELECT * FROM password_reset_tokens WHERE token = %s AND used_at IS NULL ORDER BY created_at DESC LIMIT 1",
        (token,),
        fetchone=True,
    )
    if not reset_row:
        return jsonify({"success": False, "message": "This reset link is invalid or already used."}), 400

    expires_at = reset_row.get("expires_at")
    if expires_at and datetime.strptime(str(expires_at), "%Y-%m-%d %H:%M:%S") < datetime.now():
        return jsonify({"success": False, "message": "This reset link has expired."}), 410

    hashed = bcrypt.hashpw(password.encode("utf-8"), bcrypt.gensalt()).decode("utf-8")
    query("UPDATE users SET password = %s, reset_token = NULL, reset_token_expires_at = NULL, failed_login_count = 0, locked_until = NULL WHERE id = %s", (hashed, reset_row["user_id"]), commit=True)
    query("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = %s", (reset_row["id"],), commit=True)
    _record_audit(reset_row["user_id"], "password_reset_success", "Password successfully changed", request.remote_addr)
    return jsonify({"success": True, "message": "Password updated successfully"}), 200
