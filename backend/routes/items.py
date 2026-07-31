import os
import uuid
from datetime import date as date_type
from flask import Blueprint, request, jsonify, send_from_directory
from werkzeug.utils import secure_filename
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
    return out


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
            SUM(status = 'Claimed') AS claimed
        FROM items
        """,
        fetchone=True,
    )
    return jsonify({
        "total":   int(rows["total"]   or 0),
        "lost":    int(rows["lost"]    or 0),
        "found":   int(rows["found"]   or 0),
        "claimed": int(rows["claimed"] or 0),
    }), 200


# ─── recent items (dashboard) ────────────────────────────────────────────────

@items_bp.route("/items/recent", methods=["GET"])
def recent_items():
    limit = min(int(request.args.get("limit", 5)), 20)
    rows  = query(
        "SELECT * FROM items ORDER BY created_at DESC LIMIT %s",
        (limit,),
    )
    return jsonify([serialize_item(r) for r in rows]), 200


# ─── GET /items  (browse with search / filter / pagination) ──────────────────

@items_bp.route("/items", methods=["GET"])
def get_items():
    search   = request.args.get("search",   "").strip()
    category = request.args.get("category", "").strip()
    status   = request.args.get("status",   "").strip()
    page     = max(int(request.args.get("page",  1)), 1)
    per_page = min(int(request.args.get("limit", 9)), 50)

    conditions = []
    params     = []

    if search:
        conditions.append("(item_name LIKE %s OR description LIKE %s)")
        params += [f"%{search}%", f"%{search}%"]
    if category:
        conditions.append("category = %s")
        params.append(category)
    if status in ("Lost", "Found", "Claimed"):
        conditions.append("status = %s")
        params.append(status)

    where = ("WHERE " + " AND ".join(conditions)) if conditions else ""

    # total count
    total_row = query(
        f"SELECT COUNT(*) AS cnt FROM items {where}",
        tuple(params),
        fetchone=True,
    )
    total = int(total_row["cnt"] or 0)

    # paged data
    offset = (page - 1) * per_page
    rows   = query(
        f"SELECT * FROM items {where} ORDER BY created_at DESC LIMIT %s OFFSET %s",
        tuple(params) + (per_page, offset),
    )

    return jsonify({
        "items":      [serialize_item(r) for r in rows],
        "total":      total,
        "page":       page,
        "per_page":   per_page,
        "total_pages": max(1, -(-total // per_page)),   # ceiling division
    }), 200


# ─── GET /item/<id> ───────────────────────────────────────────────────────────

@items_bp.route("/item/<int:item_id>", methods=["GET"])
def get_item(item_id):
    row = query("SELECT * FROM items WHERE id = %s LIMIT 1", (item_id,), fetchone=True)
    if not row:
        return jsonify({"error": "Item not found"}), 404
    return jsonify(serialize_item(row)), 200


# ─── POST /item  (create, multipart/form-data) ───────────────────────────────

@items_bp.route("/item", methods=["POST"])
def create_item():
    # Accept both JSON and multipart
    if request.content_type and "application/json" in request.content_type:
        data = request.get_json(silent=True) or {}
        image_filename = None
    else:
        data  = request.form.to_dict()
        image_filename = _handle_upload()

    # Validate required fields
    required = ["item_name", "category", "status", "location", "date", "contact_number"]
    missing  = [f for f in required if not (data.get(f) or "").strip()]
    if missing:
        return jsonify({"error": f"Missing required fields: {', '.join(missing)}"}), 400

    status = data["status"]
    if status not in ("Lost", "Found", "Claimed"):
        return jsonify({"error": "status must be Lost, Found, or Claimed"}), 400

    new_id = query(
        """
        INSERT INTO items
            (item_name, category, status, description, location, date, contact_number, image)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
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
        ),
        commit=True,
    )
    row = query("SELECT * FROM items WHERE id = %s", (new_id,), fetchone=True)
    return jsonify({"success": True, "message": "Item reported successfully", "item": serialize_item(row)}), 201


# ─── PUT /item/<id> ───────────────────────────────────────────────────────────

@items_bp.route("/item/<int:item_id>", methods=["PUT"])
def update_item(item_id):
    existing = query("SELECT * FROM items WHERE id = %s LIMIT 1", (item_id,), fetchone=True)
    if not existing:
        return jsonify({"error": "Item not found"}), 404

    if request.content_type and "application/json" in request.content_type:
        data = request.get_json(silent=True) or {}
        new_image = existing["image"]
    else:
        data      = request.form.to_dict()
        uploaded  = _handle_upload()
        new_image = uploaded if uploaded else existing["image"]

    status = data.get("status", existing["status"])
    if status not in ("Lost", "Found", "Claimed"):
        return jsonify({"error": "status must be Lost, Found, or Claimed"}), 400

    query(
        """
        UPDATE items
        SET item_name=%s, category=%s, status=%s, description=%s,
            location=%s, date=%s, contact_number=%s, image=%s
        WHERE id=%s
        """,
        (
            data.get("item_name",       existing["item_name"]),
            data.get("category",        existing["category"]),
            status,
            data.get("description",     existing["description"] or ""),
            data.get("location",        existing["location"]),
            data.get("date",            existing["date"].isoformat() if hasattr(existing["date"], "isoformat") else existing["date"]),
            data.get("contact_number",  existing["contact_number"]),
            new_image,
            item_id,
        ),
        commit=True,
    )
    row = query("SELECT * FROM items WHERE id = %s", (item_id,), fetchone=True)
    return jsonify({"success": True, "message": "Item updated", "item": serialize_item(row)}), 200


# ─── DELETE /item/<id> ────────────────────────────────────────────────────────

@items_bp.route("/item/<int:item_id>", methods=["DELETE"])
def delete_item(item_id):
    row = query("SELECT image FROM items WHERE id = %s LIMIT 1", (item_id,), fetchone=True)
    if not row:
        return jsonify({"error": "Item not found"}), 404

    # Remove image file if present
    if row["image"]:
        path = os.path.join(Config.UPLOAD_FOLDER, row["image"])
        if os.path.isfile(path):
            os.remove(path)

    query("DELETE FROM items WHERE id = %s", (item_id,), commit=True)
    return jsonify({"success": True, "message": "Item deleted"}), 200


# ─── POST /claim/<id> ─────────────────────────────────────────────────────────

@items_bp.route("/claim/<int:item_id>", methods=["POST"])
def claim_item(item_id):
    row = query("SELECT id, status FROM items WHERE id = %s LIMIT 1", (item_id,), fetchone=True)
    if not row:
        return jsonify({"error": "Item not found"}), 404
    if row["status"] == "Claimed":
        return jsonify({"error": "Item is already claimed"}), 409

    query("UPDATE items SET status = 'Claimed' WHERE id = %s", (item_id,), commit=True)
    updated = query("SELECT * FROM items WHERE id = %s", (item_id,), fetchone=True)
    return jsonify({"success": True, "message": "Item marked as claimed", "item": serialize_item(updated)}), 200


# ─── GET /categories ──────────────────────────────────────────────────────────

@items_bp.route("/categories", methods=["GET"])
def categories():
    rows = query("SELECT DISTINCT category FROM items ORDER BY category")
    return jsonify([r["category"] for r in rows]), 200


# ─── internal helper ─────────────────────────────────────────────────────────

def _handle_upload() -> str | None:
    """Save uploaded file and return stored filename, or None."""
    file = request.files.get("image")
    if not file or file.filename == "":
        return None
    if not allowed_file(file.filename):
        raise ValueError("Invalid file type. Allowed: jpg, jpeg, png, gif, webp")
    ext      = file.filename.rsplit(".", 1)[1].lower()
    filename = f"{uuid.uuid4().hex}.{ext}"
    file.save(os.path.join(Config.UPLOAD_FOLDER, filename))
    return filename
