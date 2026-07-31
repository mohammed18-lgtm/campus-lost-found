<?php
// ─── API base URL ─────────────────────────────────────────────────────────────
// Change this to your Flask server address if different.
define('API_BASE_URL', 'http://localhost:5001');

// ─── Session start ────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name('lf_session');
    session_set_cookie_params([
        'lifetime' => 86400,       // 1 day
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ─── Auth guard ───────────────────────────────────────────────────────────────
function require_login(): void {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

// ─── cURL helper ─────────────────────────────────────────────────────────────
/**
 * Send a JSON request to the Flask API.
 *
 * @param string $endpoint   e.g. '/login' or '/items?page=1'
 * @param string $method     GET | POST | PUT | DELETE
 * @param array  $body       Associative array; sent as JSON
 * @return array             ['status' => int, 'body' => mixed]
 */
function api_request(string $endpoint, string $method = 'GET', array $body = []): array {
    $url = API_BASE_URL . $endpoint;
    $ch  = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    ]);

    if (!empty($body) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['status' => 0, 'body' => ['error' => 'API connection failed: ' . $err]];
    }

    return ['status' => $status, 'body' => json_decode($response, true)];
}

/**
 * POST multipart/form-data (for file uploads).
 */
function api_upload(string $endpoint, array $fields, array $file_field = []): array {
    $url  = API_BASE_URL . $endpoint;
    $post = $fields;

    if (!empty($file_field)) {
        $post[$file_field['name']] = new CURLFile(
            $file_field['tmp_name'],
            $file_field['type'],
            $file_field['file_name']
        );
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post,
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['status' => 0, 'body' => ['error' => 'Upload failed: ' . $err]];
    }

    return ['status' => $status, 'body' => json_decode($response, true)];
}
?>
