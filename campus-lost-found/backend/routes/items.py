import os
import uuid
import smtplib
import secrets
from datetime import date as date_type, datetime
from email.message import EmailMessage
from flask import Blueprint, request, jsonify, send_from_directory
from db import query
from config import Config

items_bp = Blueprint("items", __name__, url_prefix="")

# ─── helpers ──────────────────────────────────────────────────────────────────

def allowed_file(filename: str) -> bool:
    return (
        "." in filename
        and filename.rsplit(".", 1)[1].lower() in Config.ALLOWED_EXTENSIONS
    )


def serialize_item(row: dict) -> dict:
    """Convert MySQL row to JSON-serialisable dict."""
    if row is None:
        return None
    out = dict(row)
    for key, val in out.items():
        if isinstance(val, date_type):
            out[key] = val.isoformat()
        elif isinstance(val, datetime):
            out[key] = val.isoformat()
    return out


def _default_owner_message(item_name: str = "your item") -> str:
    return f"We found your item: {item_name}. Please come and collect it from the office."


def _send_owner_email(recipient_email: str, subject: str, message: str) -> bool:
    if not recipient_email:
        return False

    try:
        msg = EmailMessage()
        msg["Subject"] = subject or "Campus Lost & Found"
        msg["From"] = Config.EMAIL_FROM
        msg["To"] = recipient_email
        msg.set_content(message)

        with smtplib.SMTP(Config.EMAIL_HOST, Config.EMAIL_PORT, timeout=15) as server:
            if Config.EMAIL_USE_TLS:
                server.starttls()
            if Config.EMAIL_USER and Config.EMAIL_PASSWORD:
                server.login(Config.EMAIL_USER, Config.EMAIL_PASSWORD)
            server.send_message(msg)
        return True
    except Exception:
        return False


def _create_notification(user_id, title, message, notification_type="info", related_item_id=None, sent_by="System"):
    try:
        return query(
            "INSERT INTO notifications (user_id, title, message, sent_by, notification_type, related_item_id) VALUES (%s, %s, %s, %s, %s, %s)",
            (int(user_id), title, message, sent_by, notification_type, related_item_id),
            commit=True,
        )
    except Exception:
        return None


def _normalize_text(value):
    if value is None:
        return ""
    return " ".join(str(value).lower().replace("-", " ").split())


def _calculate_match_score(item_a, item_b):
    if not item_a or not item_b:
        return 0

    score = 0
    total = 0
    checks = [
        ("item_name", 35, _normalize_text(item_a.get("item_name")), _normalize_text(item_b.get("item_name"))),
        ("category", 20, _normalize_text(item_a.get("category")), _normalize_text(item_b.get("category"))),
        ("location", 15, _normalize_text(item_a.get("location")), _normalize_text(item_b.get("location"))),
        ("color", 10, _normalize_text(item_a.get("color") or item_a.get("description")), _normalize_text(item_b.get("color") or item_b.get("description"))),
        ("date", 10, str(item_a.get("date") or ""), str(item_b.get("date") or "")),
        ("description", 10, _normalize_text(item_a.get("description")), _normalize_text(item_b.get("description"))),
    ]
    for _, weight, left, right in checks:
        total += weight
        if not left or not right:
            continue
        if left == right:
            score += weight
        elif left in right or right in left:
            score += max(0, weight - 5)
        elif (left.split() and set(left.split()) & set(right.split())):
            score += weight // 2
        elif "date" in left and left and right:
            try:
                if abs((datetime.strptime(str(item_a.get("date")), "%Y-%m-%d") - datetime.strptime(str(item_b.get("date")), "%Y-%m-%d")).days) <= 7:
                    score += weight
            except Exception:
                pass
    if total:
        return min(100, max(0, round((score / total) * 100)))
    return 0


def _generate_claim_code():
    return "CLF-" + secrets.token_urlsafe(6).upper().replace("-", "")[:10]


# ─── serve uploaded images ────────────────────────────────────────────────────

@items_bp.route("/uploads/<path:filename>", methods=["GET"])
def uploaded_file(filename):
    return send_from_directory(Config.UPLOAD_FOLDER, filename)


# ─── stats (dashboard) ────────────────────────────────────────────────────────

@items_bp.route("/stats", methods=["GET"])
def stats():
    rows = query(
        """
        SELECT
            COUNT(*) AS total,
            SUM(status = 'Lost')    AS lost,
            SUM(status = 'Found')   AS found,
            SUM(status = 'Claimed') AS claimed,
            SUM(status = 'Returned') AS returned
        FROM items
        WHERE deleted_at IS NULL
        """,
        fetchone=True,
    )
    pending = query("SELECT COUNT(*) AS cnt FROM item_claims WHERE status = 'pending'", fetchone=True)
    unclaimed = query("SELECT COUNT(*) AS cnt FROM items WHERE deleted_at IS NULL AND status IN ('Lost', 'Found')", fetchone=True)
    return jsonify({
        "total": int(rows["total"] or 0),
        "lost": int(rows["lost"] or 0),
        "found": int(rows["found"] or 0),
        "claimed": int(rows["claimed"] or 0),
        "returned": int(rows["returned"] or 0),
        "pending": int(pending["cnt"] or 0),
        "unclaimed": int(unclaimed["cnt"] or 0),
    }), 200


@items_bp.route("/admin/analytics", methods=["GET"])
def admin_analytics():
    return stats()


@items_bp.route("/security-dashboard", methods=["GET"])
def security_dashboard():
    failed = query("SELECT COUNT(*) AS cnt FROM login_attempts WHERE status = 'failed'", fetchone=True)
    blocked = query("SELECT COUNT(*) AS cnt FROM login_attempts WHERE status = 'blocked'", fetchone=True)
    recent_attempts = query("SELECT * FROM login_attempts ORDER BY created_at DESC LIMIT 10")
    audit_logs = query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10")
    return jsonify({
        "failed_attempts": int(failed["cnt"] or 0),
        "blocked_attempts": int(blocked["cnt"] or 0),
        "recent_attempts": recent_attempts,
        "audit_logs": audit_logs,
    }), 200


# ─── recent items (dashboard) ────────────────────────────────────────────────

@items_bp.route("/items/recent", methods=["GET"])
def recent_items():
    limit = min(int(request.args.get("limit", 5)), 20)
    rows = query(
        "SELECT * FROM items WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT %s",
        (limit,),
    )
    return jsonify([serialize_item(r) for r in rows]), 200


# ─── GET /items  (browse with search / filter / pagination) ──────────────────

@items_bp.route("/items", methods=["GET"])
def get_items():
    search = request.args.get("search", "").strip()
    category = request.args.get("category", "").strip()
    status = request.args.get("status", "").strip()
    location = request.args.get("location", "").strip()
    page = max(int(request.args.get("page", 1)), 1)
    per_page = min(int(request.args.get("limit", 9)), 50)

    conditions = ["deleted_at IS NULL"]
    params = []

    if search:
        conditions.append("(item_name LIKE %s OR description LIKE %s)")
        params += [f"%{search}%", f"%{search}%"]
    if category:
        conditions.append("category = %s")
        params.append(category)
    if status in ("Lost", "Found", "Claimed", "Returned"):
        conditions.append("status = %s")
        params.append(status)
    if location:
        conditions.append("location LIKE %s")
        params.append(f"%{location}%")

    where = "WHERE " + " AND ".join(conditions)
    total_row = query(f"SELECT COUNT(*) AS cnt FROM items {where}", tuple(params), fetchone=True)
    total = int(total_row["cnt"] or 0)
    offset = (page - 1) * per_page
    rows = query(f"SELECT * FROM items {where} ORDER BY created_at DESC LIMIT %s OFFSET %s", tuple(params) + (per_page, offset))

    return jsonify({
        "items": [serialize_item(r) for r in rows],
        "total": total,
        "page": page,
        "per_page": per_page,
        "total_pages": max(1, -(-total // per_page)),
    }), 200


@items_bp.route("/locations", methods=["GET"])
def locations():
    rows = query("SELECT DISTINCT location FROM items WHERE deleted_at IS NULL ORDER BY location")
    return jsonify([r["location"] for r in rows if r.get("location")]), 200


# ─── GET /item/<id> ───────────────────────────────────────────────────────────

@items_bp.route("/item/<int:item_id>", methods=["GET"])
def get_item(item_id):
    row = query("SELECT * FROM items WHERE id = %s AND deleted_at IS NULL LIMIT 1", (item_id,), fetchone=True)
    if not row:
        return jsonify({"error": "Item not found"}), 404
    return jsonify(serialize_item(row)), 200


# ─── POST /item  (create, multipart/form-data) ───────────────────────────────

@items_bp.route("/item", methods=["POST"])
def create_item():
    if request.content_type and "application/json" in request.content_type:
        data = request.get_json(silent=True) or {}
        image_filename = None
    else:
        data = request.form.to_dict()
        image_filename = _handle_upload()

    reporter_name = (data.get("reporter_name") or "").strip()
    reporter_email = (data.get("reporter_email") or "").strip().lower()
    color = (data.get("color") or "").strip()

    required = ["item_name", "category", "status", "location", "date", "contact_number"]
    missing = [f for f in required if not (data.get(f) or "").strip()]
    if missing:
        return jsonify({"error": f"Missing required fields: {', '.join(missing)}"}), 400

    if not reporter_name or not reporter_email:
        return jsonify({"error": "Reporter name and email are required so the admin can follow up"}), 400

    status = data["status"]
    if status not in ("Lost", "Found", "Claimed"):
        return jsonify({"error": "status must be Lost, Found, or Claimed"}), 400

    new_id = query(
        """
        INSERT INTO items
            (item_name, category, status, description, location, date, contact_number, image, reporter_name, reporter_email, color)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            data["item_name"].strip(),
            data["category"].strip(),
            status,
            (data.get("description") or "").strip(),
            data["location"].strip(),
            data["date"],
            data["contact_number"].strip(),
            image_filename,
            reporter_name,
            reporter_email,
            color or None,
        ),
        commit=True,
    )
    item = query("SELECT * FROM items WHERE id = %s", (new_id,), fetchone=True)
    _create_notification(
        user_id=data.get("reported_by_user_id") or 0,
        title="New report submitted",
        message=f"A new {status.lower()} item report was submitted for {data['item_name'].strip()}.",
        notification_type="report",
        related_item_id=new_id,
        sent_by="System",
    )
    return jsonify({"success": True, "message": "Item reported successfully", "item": serialize_item(item)}), 201


# ─── PUT /item/<id> ───────────────────────────────────────────────────────────

@items_bp.route("/item/<int:item_id>", methods=["PUT"])
def update_item(item_id):
    existing = query("SELECT * FROM items WHERE id = %s AND deleted_at IS NULL LIMIT 1", (item_id,), fetchone=True)
    if not existing:
        return jsonify({"error": "Item not found"}), 404

    if request.content_type and "application/json" in request.content_type:
        data = request.get_json(silent=True) or {}
        new_image = existing["image"]
    else:
        data = request.form.to_dict()
        uploaded = _handle_upload()
        new_image = uploaded if uploaded else existing["image"]

    status = data.get("status", existing["status"])
    if status not in ("Lost", "Found", "Claimed", "Returned"):
        return jsonify({"error": "status must be Lost, Found, Claimed, or Returned"}), 400

    query(
        """
        UPDATE items
        SET item_name=%s, category=%s, status=%s, description=%s,
            location=%s, date=%s, contact_number=%s, image=%s, color=%s
        WHERE id=%s
        """,
        (
            data.get("item_name", existing["item_name"]),
            data.get("category", existing["category"]),
            status,
            data.get("description", existing["description"] or ""),
            data.get("location", existing["location"]),
            data.get("date", existing["date"].isoformat() if hasattr(existing["date"], "isoformat") else existing["date"]),
            data.get("contact_number", existing["contact_number"]),
            new_image,
            data.get("color", existing.get("color") or None),
            item_id,
        ),
        commit=True,
    )
    row = query("SELECT * FROM items WHERE id = %s", (item_id,), fetchone=True)
    return jsonify({"success": True, "message": "Item updated", "item": serialize_item(row)}), 200


# ─── DELETE /item/<id> ────────────────────────────────────────────────────────

@items_bp.route("/item/<int:item_id>", methods=["DELETE"])
def delete_item(item_id):
    row = query("SELECT image FROM items WHERE id = %s AND deleted_at IS NULL LIMIT 1", (item_id,), fetchone=True)
    if not row:
        return jsonify({"error": "Item not found"}), 404

    if row["image"]:
        path = os.path.join(Config.UPLOAD_FOLDER, row["image"])
        if os.path.isfile(path):
            os.remove(path)

    query("UPDATE items SET deleted_at = NOW() WHERE id = %s", (item_id,), commit=True)
    return jsonify({"success": True, "message": "Item archived and can be restored by the admin"}), 200


@items_bp.route("/item/<int:item_id>/restore", methods=["POST"])
def restore_item(item_id):
    row = query("SELECT * FROM items WHERE id = %s LIMIT 1", (item_id,), fetchone=True)
    if not row:
        return jsonify({"error": "Item not found"}), 404
    query("UPDATE items SET deleted_at = NULL WHERE id = %s", (item_id,), commit=True)
    return jsonify({"success": True, "message": "Item restored"}), 200


# ─── POST /claim/<id> ─────────────────────────────────────────────────────────

@items_bp.route("/claim/<int:item_id>", methods=["POST"])
def claim_item(item_id):
    data = request.get_json(silent=True) or {}
    row = query("SELECT * FROM items WHERE id = %s AND deleted_at IS NULL LIMIT 1", (item_id,), fetchone=True)
    if not row:
        return jsonify({"error": "Item not found"}), 404
    if row["status"] in ("Claimed", "Returned"):
        return jsonify({"error": "Item is already claimed or returned"}), 409

    claimant_name = (data.get("claimant_name") or "").strip()
    claimant_email = (data.get("claimant_email") or "").strip().lower()
    claimant_phone = (data.get("claimant_phone") or "").strip()
    identifying_details = (data.get("identifying_details") or "").strip()

    if not claimant_name or not claimant_email or not identifying_details:
        return jsonify({"error": "claimant_name, claimant_email, and identifying_details are required"}), 400

    claim_id = query(
        """
        INSERT INTO item_claims (item_id, user_id, claimant_name, claimant_email, claimant_phone, identifying_details, status)
        VALUES (%s, %s, %s, %s, %s, %s, 'pending')
        """,
        (item_id, data.get("user_id"), claimant_name, claimant_email, claimant_phone or None, identifying_details),
        commit=True,
    )
    _create_notification(
        user_id=row.get("reported_by_user_id") or row["id"],
        title="Claim request submitted",
        message=f"A claim has been submitted for {row['item_name']} and is awaiting review.",
        notification_type="claim",
        related_item_id=item_id,
        sent_by="System",
    )
    return jsonify({"success": True, "message": "Claim request submitted for review", "claim_id": claim_id}), 201


@items_bp.route("/claims", methods=["GET"])
def get_claims():
    rows = query(
        "SELECT c.*, i.item_name, i.location FROM item_claims c LEFT JOIN items i ON i.id = c.item_id ORDER BY c.created_at DESC"
    )
    return jsonify(rows), 200


@items_bp.route("/claims/<int:claim_id>/approve", methods=["POST"])
def approve_claim(claim_id):
    claim = query("SELECT * FROM item_claims WHERE id = %s LIMIT 1", (claim_id,), fetchone=True)
    if not claim:
        return jsonify({"error": "Claim not found"}), 404

    item = query("SELECT * FROM items WHERE id = %s LIMIT 1", (claim["item_id"],), fetchone=True)
    if not item:
        return jsonify({"error": "Item not found"}), 404

    claim_code = _generate_claim_code()
    query("UPDATE item_claims SET status = 'approved', admin_id = %s, approved_at = NOW(), claim_code = %s WHERE id = %s", (claim.get("admin_id") or 1, claim_code, claim_id), commit=True)
    query("UPDATE items SET status = 'Claimed', claim_code = %s, verification_details = %s WHERE id = %s", (claim_code, claim["identifying_details"], claim["item_id"]), commit=True)
    _create_notification(
        user_id=item.get("reported_by_user_id") or item["id"],
        title="Claim approved",
        message=f"The claim for {item['item_name']} has been approved. Claim code: {claim_code}",
        notification_type="claim",
        related_item_id=item["id"],
        sent_by="Admin",
    )
    return jsonify({"success": True, "message": "Claim approved", "claim_code": claim_code}), 200


@items_bp.route("/claims/<int:claim_id>/reject", methods=["POST"])
def reject_claim(claim_id):
    query("UPDATE item_claims SET status = 'rejected' WHERE id = %s", (claim_id,), commit=True)
    return jsonify({"success": True, "message": "Claim rejected"}), 200


@items_bp.route("/claim/<int:item_id>/return", methods=["POST"])
def return_item(item_id):
    data = request.get_json(silent=True) or {}
    claim_code = (data.get("claim_code") or "").strip()
    item = query("SELECT * FROM items WHERE id = %s AND deleted_at IS NULL LIMIT 1", (item_id,), fetchone=True)
    if not item:
        return jsonify({"error": "Item not found"}), 404
    if item["claim_code"] and claim_code and item["claim_code"].lower() == claim_code.lower():
        query("UPDATE items SET status = 'Returned', returned_at = NOW() WHERE id = %s", (item_id,), commit=True)
        query("UPDATE item_claims SET status = 'returned' WHERE item_id = %s ORDER BY created_at DESC LIMIT 1", (item_id,), commit=True)
        return jsonify({"success": True, "message": "Item marked as returned"}), 200
    return jsonify({"error": "Invalid or missing claim code"}), 400


# ─── GET /categories ──────────────────────────────────────────────────────────

@items_bp.route("/messages", methods=["GET", "POST"])
def messages():
    if request.method == "GET":
        rows = query("""
            SELECT m.*, u.email as user_email 
            FROM messages m
            LEFT JOIN users u ON m.user_id = u.id
            ORDER BY m.created_at DESC
        """)
        return jsonify(rows), 200

    data = request.get_json(silent=True) or {}
    if not data:
        return jsonify({"error": "Request body must be JSON"}), 400

    user_id = data.get("user_id")
    user_name = (data.get("user_name") or "").strip()
    item_name = (data.get("item_name") or "").strip()
    subject = (data.get("subject") or "Found item report").strip()
    message = (data.get("message") or "").strip()

    if not user_id or not user_name or not message:
        return jsonify({"error": "user_id, user_name, and message are required"}), 400

    msg_id = query(
        "INSERT INTO messages (user_id, user_name, item_name, subject, message, from_user_id, from_role, to_role) VALUES (%s, %s, %s, %s, %s, %s, 'user', 'admin')",
        (user_id, user_name, item_name or None, subject or "Found item report", message, user_id),
        commit=True,
    )
    _create_notification(
        user_id=1,
        title="New message",
        message=f"{user_name} sent a new message about {item_name or 'a campus item'}.",
        notification_type="message",
        sent_by="System",
    )
    row = query("SELECT * FROM messages WHERE id = %s LIMIT 1", (msg_id,), fetchone=True)
    return jsonify({"success": True, "message": "Message sent to admin", "data": row}), 201


@items_bp.route("/notifications", methods=["GET", "POST"])
def notifications():
    if request.method == "GET":
        user_id = request.args.get("user_id")
        if not user_id:
            rows = query("SELECT * FROM notifications ORDER BY created_at DESC")
        else:
            rows = query("SELECT * FROM notifications WHERE user_id = %s ORDER BY created_at DESC", (int(user_id),))
        return jsonify(rows), 200

    data = request.get_json(silent=True) or {}
    user_id = data.get("user_id")
    title = (data.get("title") or "Lost item update").strip()
    message = (data.get("message") or "").strip()
    recipient_email = (data.get("recipient_email") or "").strip().lower()
    item_id = data.get("item_id")

    if not user_id:
        return jsonify({"error": "user_id is required"}), 400

    if not recipient_email:
        user = query("SELECT email FROM users WHERE id = %s LIMIT 1", (int(user_id),), fetchone=True)
        if user:
            recipient_email = (user.get("email") or "").strip().lower()

    if not recipient_email and item_id:
        item = query("SELECT reporter_email FROM items WHERE id = %s LIMIT 1", (int(item_id),), fetchone=True)
        if item:
            recipient_email = (item.get("reporter_email") or "").strip().lower()

    default_message = _default_owner_message(item_name=(data.get("item_name") or "your item").strip() or "your item")
    if not message:
        message = default_message

    email_sent = _send_owner_email(recipient_email, title or "Campus Lost & Found", message) if recipient_email else False

    notif_id = query(
        "INSERT INTO notifications (user_id, title, message, sent_by, notification_type, related_item_id) VALUES (%s, %s, %s, %s, %s, %s)",
        (user_id, title or "Lost item update", message, data.get("sent_by", "Admin"), data.get("notification_type", "info"), item_id),
        commit=True,
    )
    row = query("SELECT * FROM notifications WHERE id = %s LIMIT 1", (notif_id,), fetchone=True)
    return jsonify({
        "success": True,
        "message": "Notification sent",
        "email_sent": email_sent,
        "recipient_email": recipient_email,
        "data": row,
    }), 201


@items_bp.route("/item/<int:item_id>/matches", methods=["GET"])
def smart_matches(item_id):
    current = query("SELECT * FROM items WHERE id = %s AND deleted_at IS NULL LIMIT 1", (item_id,), fetchone=True)
    if not current:
        return jsonify({"error": "Item not found"}), 404

    candidates = query("SELECT * FROM items WHERE id != %s AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 50", (item_id,))
    matches = []
    for candidate in candidates:
        score = _calculate_match_score(current, candidate)
        if score >= 25:
            matches.append({
                "item": serialize_item(candidate),
                "score": score,
                "match_type": "possible match",
            })
    matches.sort(key=lambda x: x["score"], reverse=True)
    return jsonify({"item_id": item_id, "matches": matches[:5]}), 200


# ─── admin message responses ──────────────────────────────────────────────────

@items_bp.route("/message-response", methods=["POST"])
def send_message_response():
    data = request.get_json(silent=True) or {}
    message_id = data.get("message_id")
    user_id = data.get("user_id")
    user_email = (data.get("user_email") or "").strip().lower()
    admin_id = data.get("admin_id")
    admin_name = (data.get("admin_name") or "Admin").strip()
    response_text = (data.get("response_text") or "").strip()

    if not message_id or not user_id or not user_email or not admin_id or not response_text:
        return jsonify({"error": "message_id, user_id, user_email, admin_id, and response_text are required"}), 400

    msg = query("SELECT * FROM messages WHERE id = %s LIMIT 1", (int(message_id),), fetchone=True)
    if not msg:
        return jsonify({"error": "Message not found"}), 404

    query("UPDATE messages SET status = 'reviewed' WHERE id = %s", (int(message_id),), commit=True)
    email_subject = f"Re: {msg.get('subject', 'Your Campus Lost & Found Message')}"
    email_body = f"""
Hello {msg.get('user_name', 'User')},

Thank you for contacting us. Here is our response to your message:

---
Your message: {msg.get('message', 'N/A')}

Our response:
{response_text}
---

If you have any further questions, please feel free to contact us.

Best regards,
Campus Lost & Found Team
"""
    email_sent = _send_owner_email(user_email, email_subject, email_body)
    response_id = query(
        """INSERT INTO message_responses 
           (message_id, user_id, user_email, admin_id, admin_name, response_text, email_sent) 
           VALUES (%s, %s, %s, %s, %s, %s, %s)""",
        (int(message_id), int(user_id), user_email, int(admin_id), admin_name, response_text, int(email_sent)),
        commit=True,
    )

    row = query("SELECT * FROM message_responses WHERE id = %s LIMIT 1", (response_id,), fetchone=True)
    return jsonify({
        "success": True,
        "message": "Response sent successfully",
        "email_sent": email_sent,
        "recipient_email": user_email,
        "data": row,
    }), 201


@items_bp.route("/message-responses/<int:message_id>", methods=["GET"])
def get_message_responses(message_id):
    rows = query("SELECT * FROM message_responses WHERE message_id = %s ORDER BY created_at DESC", (message_id,))
    return jsonify(rows), 200


@items_bp.route("/categories", methods=["GET"])
def categories():
    rows = query("SELECT DISTINCT category FROM items WHERE deleted_at IS NULL ORDER BY category")
    return jsonify([r["category"] for r in rows]), 200


# ─── internal helper ─────────────────────────────────────────────────────────

def _handle_upload() -> str | None:
    file = request.files.get("image")
    if not file or file.filename == "":
        return None
    if not allowed_file(file.filename):
        raise ValueError("Invalid file type. Allowed: jpg, jpeg, png, gif, webp")
    ext = file.filename.rsplit(".", 1)[1].lower()
    filename = f"{uuid.uuid4().hex}.{ext}"
    file.save(os.path.join(Config.UPLOAD_FOLDER, filename))
    return filename
