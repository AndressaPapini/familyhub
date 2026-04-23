<?php
/* ============================================================
   FAMILIA MANAGER — CONFIGURAÇÃO GERAL
   ============================================================ */

define('DB_HOST',     'localhost');
define('DB_NAME',     'familia_manager');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

define('APP_NAME',    'FamíliaApp');
define('APP_URL',     'http://localhost/familia');
define('APP_VERSION', '1.0.0');

// Fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Conexão PDO ───────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Erro de conexão com o banco de dados.']));
        }
    }
    return $pdo;
}

// ── Autenticação ──────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/index.html');
        exit;
    }
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

// ── Helpers ───────────────────────────────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function formatMoney(float $val): string {
    return 'R$ ' . number_format($val, 2, ',', '.');
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'agora mesmo';
    if ($diff < 3600)   return floor($diff/60) . ' min atrás';
    if ($diff < 86400)  return floor($diff/3600) . 'h atrás';
    if ($diff < 604800) return floor($diff/86400) . 'd atrás';
    return date('d/m/Y', strtotime($datetime));
}
