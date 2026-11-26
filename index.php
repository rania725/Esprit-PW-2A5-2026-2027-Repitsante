<?php
declare(strict_types=1);

require_once __DIR__ . '/config/http.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/Models/UserModel.php';
require_once __DIR__ . '/Models/MessagingModel.php';
require_once __DIR__ . '/Models/PasswordResetModel.php';
require_once __DIR__ . '/Controller/AuthController.php';
require_once __DIR__ . '/Controller/DashboardController.php';
require_once __DIR__ . '/Controller/MessagingController.php';
require_once __DIR__ . '/Controller/PasswordResetController.php';
require_once __DIR__ . '/config/mailer.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
header('Cache-Control: no-store');

startSession();

$action = requestText($_GET, 'action') ?: 'home';
$method = $_SERVER['REQUEST_METHOD'];
$routes = [
    'home' => ['GET'],
    'register' => ['GET', 'POST'],
    'login' => ['GET', 'POST'],
    'password-forgot' => ['GET', 'POST'],
    'password-code' => ['GET', 'POST'],
    'password-new' => ['GET', 'POST'],
    'dashboard' => ['GET', 'POST'],
    'profile-photo' => ['GET'],
    'messages' => ['GET'],
    'message-new' => ['GET', 'POST'],
    'conversation' => ['GET'],
    'message-send' => ['POST'],
    'logout' => ['POST'],
    'admin' => ['GET'],
    'users' => ['GET'],
    'user-block' => ['POST'],
    'user-unblock' => ['POST'],
    'user-create' => ['GET', 'POST'],
    'user-edit' => ['GET', 'POST'],
    'user-delete' => ['GET', 'POST'],
];

if (!isset($routes[$action])) {
    errorPage(404, 'Cette page n’existe pas.');
}
if (!in_array($method, $routes[$action], true)) {
    header('Allow: ' . implode(', ', $routes[$action]));
    errorPage(405, 'Ouvre cette action depuis son formulaire.');
}

try {
    $controller = new AuthController();

    switch ($action) {
        case 'home':
            $controller->home();
            break;
        case 'register':
            $controller->register($method);
            break;
        case 'login':
            $controller->login($method);
            break;
        case 'password-forgot':
            (new PasswordResetController())->requestCode($method);
            break;
        case 'password-code':
            (new PasswordResetController())->verifyCode($method);
            break;
        case 'password-new':
            (new PasswordResetController())->setNewPassword($method);
            break;
        case 'dashboard':
            $controller->dashboard($method);
            break;
        case 'profile-photo':
            $controller->profilePhoto();
            break;
        case 'messages':
            (new MessagingController($controller))->inbox();
            break;
        case 'message-new':
            (new MessagingController($controller))->newMessage($method);
            break;
        case 'conversation':
            (new MessagingController($controller))->conversation();
            break;
        case 'message-send':
            (new MessagingController($controller))->sendMessage();
            break;
        case 'logout':
            $controller->logout();
            break;
        case 'admin':
            $user = $controller->requireRole('admin');
            (new DashboardController())->show($user, searchQuery());
            break;
        case 'users':
            $controller->users();
            break;
        case 'user-block':
            $controller->setUserBlocked(true);
            break;
        case 'user-unblock':
            $controller->setUserBlocked(false);
            break;
        case 'user-create':
        case 'user-edit':
            $controller->saveUser($method, $action === 'user-edit');
            break;
        case 'user-delete':
            $controller->deleteUser($method);
            break;
    }
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    errorPage(503, 'Vérifie que MySQL est démarré et que le fichier SQL de Répit Santé a été importé.');
}
