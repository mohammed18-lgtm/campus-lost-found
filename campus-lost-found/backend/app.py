import os
from flask import Flask
from flask_cors import CORS
from config import Config

# ── create upload folder ──────────────────────────────────────────────────────
os.makedirs(Config.UPLOAD_FOLDER, exist_ok=True)

# ── factory ───────────────────────────────────────────────────────────────────
def create_app():
    app = Flask(__name__)
    app.config.from_object(Config)
    app.config["MAX_CONTENT_LENGTH"] = Config.MAX_CONTENT_LENGTH

    CORS(app, resources={r"/*": {"origins": "*"}}, supports_credentials=True)

    # ── blueprints ────────────────────────────────────────────────────────────
    from routes.auth  import auth_bp
    from routes.items import items_bp

    app.register_blueprint(auth_bp)
    app.register_blueprint(items_bp)

    # ── health check ──────────────────────────────────────────────────────────
    @app.route("/health", methods=["GET"])
    def health():
        return {"status": "ok", "message": "Campus Lost & Found API running"}, 200

    # ── generic error handlers ────────────────────────────────────────────────
    @app.errorhandler(404)
    def not_found(e):
        return {"error": "Not found"}, 404

    @app.errorhandler(413)
    def too_large(e):
        return {"error": "File too large. Max 5 MB allowed."}, 413

    @app.errorhandler(500)
    def server_error(e):
        return {"error": "Internal server error", "detail": str(e)}, 500

    return app


app = create_app()

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=True)
