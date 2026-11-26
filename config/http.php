<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $action, array $parameters = []): string
{
    return 'index.php?' . http_build_query(['action' => $action] + $parameters);
}

function assetUrl(string $path): string
{
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function redirect(string $action, array $parameters = []): void
{
    header('Location: ' . url($action, $parameters), true, 303);
    exit;
}

function requestText(array $input, string $key): string
{
    return is_string($input[$key] ?? null) ? $input[$key] : '';
}

function searchQuery(): string
{
    $query = trim(requestText($_GET, 'q'));
    return preg_match('/^.{0,150}$/usD', $query) === 1 ? $query : '';
}

function startSession(): void
{
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $secure = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
    session_name('REPITSESSID');
    session_set_cookie_params([
        'path' => rtrim($path, '/') . '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] >= 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('Ta session a expiré. Reconnecte-toi.');
    }
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_activity'] = time();
    }
}

function endSession(): void
{
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    unset($cookie['lifetime']);
    setcookie(session_name(), '', ['expires' => time() - 3600] + $cookie);
    session_destroy();
}

function csrfToken(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function requireCsrf(): void
{
    $expected = $_SESSION['csrf_token'] ?? null;
    if (!is_string($expected) || !hash_equals($expected, requestText($_POST, 'csrf_token'))) {
        errorPage(403, 'Formulaire expiré ou invalide. Recharge la page et réessaie.');
    }
}

function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function render(string $view, string $title, array $data = [], string $layout = 'front'): void
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    $icon = static function (string $name): void {
        require __DIR__ . '/../Views/layouts/icon.php';
    };

    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/../Views/' . $view . '.php';
    $content = ob_get_clean();
    require __DIR__ . '/../Views/layouts/' . $layout . '.php';
}

function errorPage(int $status, string $description): void
{
    http_response_code($status);
    $titles = [
        403 => 'Accès refusé',
        404 => 'Page introuvable',
        405 => 'Action non autorisée',
        409 => 'Action impossible',
        503 => 'Service indisponible',
    ];
    render('front/error', $titles[$status] ?? 'Une erreur est survenue', ['description' => $description]);
    exit;
}
