<?php

// | This file is the front controller. Every request enters here first.
// | It loads config, optional Composer autoload, app autoload, and session.

require_once dirname(__DIR__) . '/config/app.php';

$composerAutoload = ROOT_PATH . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

if (PHP_SAPI === 'cli' && !empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $_GET);  
} 

spl_autoload_register(function (string $className): void {
    $locations = [
        ROOT_PATH . '/app/core/' . $className . '.php',
        ROOT_PATH . '/app/controllers/' . $className . '.php',
        ROOT_PATH . '/app/models/' . $className . '.php',
    ];

    foreach ($locations as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

session_name(SESSION_NAME);
session_start();

/*
|--------------------------------------------------------------------------
| 2. Read URL segments
|--------------------------------------------------------------------------
| .htaccess sends pretty URLs here as ?url=...
| Example: /admin/videos/edit/5 becomes:
| module=admin, page=videos, action=edit, id=5
|
| Explicit query params still win:
| ?module=admin&page=videos&action=edit&id=5
*/
$url = trim($_GET['url'] ?? '', '/');//admin/videos/edit/5
$segments = explode('/', $url); //array to string conversion, /admin/videos/edit/5 -> ['admin', 'videos', 'edit', '5']

$hasExplicitModule = array_key_exists('module', $_GET); //localhost/index.php?module=admin&page=videos&action=edit&id=5
$hasExplicitPage = array_key_exists('page', $_GET);
$hasExplicitAction = array_key_exists('action', $_GET);
$hasExplicitId = array_key_exists('id', $_GET);

if (!$hasExplicitModule && !empty($segments[0])) {
    $_GET['module'] = $segments[0];
}

if (!$hasExplicitPage && !empty($segments[1])) {
    $_GET['page'] = $segments[1];
}

if (!$hasExplicitAction && !empty($segments[2])) {
    $_GET['action'] = $segments[2];
}

if (!$hasExplicitId && !empty($segments[3])) {
    $_GET['id'] = $segments[3];
}

/*
|--------------------------------------------------------------------------
| 3. Pretty route map
|--------------------------------------------------------------------------
| Friendly URLs are converted into internal module/page/action values.
| Examples:
| /login        -> auth / user_login / index
| /admin/videos -> admin / videos / index
*/
$prettyRoutes = [
    ''                      => ['user',  'home'],
    'login'                 => ['auth',  'user_login'],
    'login/login'           => ['auth',  'user_login',  'login'],
    'register'              => ['auth',  'register'],
    'register/save'         => ['auth',  'register',    'save'],
    'register/verify'       => ['auth',  'register',    'verify'],
    'register/confirm'      => ['auth',  'register',    'confirm'],
    'logout'                => ['auth',  'logout'],
    'admin'                 => ['admin', 'dashboard'],
    'admin/login'           => ['auth',  'admin_login'],
    'admin/login/login'     => ['auth',  'admin_login', 'login'],
    'admin/dashboard'       => ['admin', 'dashboard'],
    'admin/videos'          => ['admin', 'videos'],
    'admin/categories'      => ['admin', 'categories'],
    'admin/users'           => ['admin', 'users'],
    'admin/payments'        => ['admin', 'payments'],
    'admin/subscriptions'   => ['admin', 'subscriptions'],
    'admin/reviews'         => ['admin', 'reviews'],
    'admin/reports'         => ['admin', 'reports'],
    'admin/activity'        => ['admin', 'activity'],
    'admin/settings'        => ['admin', 'settings'],
    'admin/notifications'   => ['admin', 'notifications'],
    'admin/messages'        => ['admin', 'messages'],
];

/*
|--------------------------------------------------------------------------
| 4. User pretty pages
|--------------------------------------------------------------------------
| These are user-panel pages rendered by HomeController with an upage value.
| Example: /profile -> user / home / index + upage=profile
*/
$userPrettyPages = ['home', 'movies', 'trending', 'categories', 'watchlist', 'history', 'profile', 'subscription'];
$twoSegment = implode('/', array_slice($segments, 0, 2));
$prettyRoute = $prettyRoutes[$url] ?? $prettyRoutes[$twoSegment] ?? null;

if (!$hasExplicitModule && !$hasExplicitPage && $prettyRoute !== null) {
    $_GET['module'] = $prettyRoute[0];
    $_GET['page'] = $prettyRoute[1];
    if (isset($prettyRoute[2])) {
        $_GET['action'] = $prettyRoute[2];
    }
}

if (!$hasExplicitModule && !$hasExplicitPage && in_array($url, $userPrettyPages, true)) {
    $_GET['module'] = 'user';
    $_GET['page'] = 'home';
    $_GET['upage'] = $url;
}

/*
|--------------------------------------------------------------------------
| 5. Normalize route inputs
|--------------------------------------------------------------------------
| Keep only safe characters before looking up controllers and methods.
*/
$module = preg_replace('/[^a-z]/', '', strtolower($_GET['module'] ?? 'user'));
$page = preg_replace('/[^a-z_]/', '', strtolower($_GET['page'] ?? 'home'));
$action = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? 'index'));

/*
|--------------------------------------------------------------------------
| 6. Main controller route table
|--------------------------------------------------------------------------
| module + page selects the base controller for normal page requests.
*/
$routes = [
    'auth' => [
        'admin_login' => AuthController::class,
        'user_login'  => AuthController::class,
        'register'    => AuthController::class,
        'logout'      => AuthController::class,
    ],
    'user' => [
        'home' => HomeController::class,
    ],
    'admin' => [
        'dashboard'     => DashboardController::class,
        'videos'        => VideoController::class,
        'categories'    => CategoryController::class,
        'users'         => UserController::class,
        'payments'      => PaymentController::class,
        'subscriptions' => SubscriptionController::class,
        'reviews'       => ReviewController::class,
        'reports'       => ReportController::class,
        'activity'      => ActivityLogController::class,
        'settings'      => SettingsController::class,
        'notifications' => NotificationController::class,
        'messages'      => MessageController::class,
    ],
];

if ($module === 'admin' && $page === 'home') {
    $page = 'dashboard';
}

/*
|--------------------------------------------------------------------------
| 7. 404 helper
|--------------------------------------------------------------------------
| Invalid module/page/action combinations render the shared 404 view.
*/
function app_not_found(): void
{
    http_response_code(404);
    require ROOT_PATH . '/app/views/errors/404.php';
    exit;
}

if (!isset($routes[$module][$page])) {
    app_not_found();
}

$controllerClass = $routes[$module][$page];
$controller = new $controllerClass();

/*
|--------------------------------------------------------------------------
| 8. Auth method aliases
|--------------------------------------------------------------------------
| Auth routes use friendly action names, but AuthController method names are
| descriptive internally.
*/
$authMethods = [
    'admin_login' => ['index' => 'adminLogin',   'login' => 'adminAuthenticate'],
    'user_login'  => ['index' => 'userLogin',    'login' => 'userAuthenticate'],
    'register'    => ['index' => 'register',     'save'  => 'registerUser', 'verify' => 'showOtpVerification', 'confirm' => 'verifyRegistrationOtp'],
    'logout'      => ['index' => 'logout'],
];

/*
|--------------------------------------------------------------------------
| 9. User-panel action routing
|--------------------------------------------------------------------------
| User action URLs stay the same (?action=...), but actions can dispatch to
| smaller controllers after the HomeController split.
*/
$userActions = [
    'wishlist_toggle',
    'remove_wishlist',
    'update_profile',
    'delete_account',
    'save_progress',
    'record_view',
    'save_review',
    'save_report',
    'clear_history',
    'clear_notifications',
    'subscription_request',
    'feed_json',
];

$userActionControllers = [
    'wishlist_toggle'      => WishlistController::class,
    'remove_wishlist'      => WishlistController::class,
    'update_profile'       => ProfileController::class,
    'delete_account'       => ProfileController::class,
    'save_progress'        => PlaybackController::class,
    'record_view'          => PlaybackController::class,
    'subscription_request' => UserSubscriptionController::class,
    'save_review'          => UserFeedbackController::class,
    'save_report'          => UserFeedbackController::class,
    'clear_history'        => HistoryController::class,
    'clear_notifications'  => UserNotificationController::class,
];

if ($module === 'user' && in_array($action, $userActions, true)) {
    if (isset($userActionControllers[$action])) {
        $controller = new $userActionControllers[$action]();
    }

    if (!method_exists($controller, $action)) {
        app_not_found();
    }

    $controller->{$action}();
}

/*
|--------------------------------------------------------------------------
| 10. Default dispatch
|--------------------------------------------------------------------------
| Normal pages resolve the requested method and call it.
*/
$method = $authMethods[$page][$action] ?? null;

if ($method === null && method_exists($controller, $action)) {
    $method = $action;
}

if ($method === null && $action === 'index' && method_exists($controller, 'index')) {
    $method = 'index';
}

if ($method === null) {
    app_not_found();
}

$controller->{$method}();
