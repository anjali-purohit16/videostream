<?php

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
        if (is_file($file)) { require_once $file; return; }
    }
});

session_name(SESSION_NAME);
$sessionPath = ROOT_PATH . '/storage/sessions';
if (is_dir($sessionPath)) session_save_path($sessionPath);
session_start();


// segment->example.com/admin/videos/edit/5->Yeh automatic nahi hai — manually karna padta hai.
$url = $_GET['url'] ?? '';  //.htaccess->rewrite hoke ara hai
$url = trim($url, '/');
$segments = explode('/', $url);  

$hasExplicitModule = array_key_exists('module', $_GET); 
$hasExplicitPage = array_key_exists('page', $_GET);    
$hasExplicitAction = array_key_exists('action', $_GET); 
$hasExplicitId = array_key_exists('id', $_GET);       

/*
|--------------------------------------------------------------------------
| URL Structure
|--------------------------------------------------------------------------
| /module/page/action/id
*/

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

// Pretty route map — single-word and two-segment clean URLs
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

$twoSegment = implode('/', array_slice($segments, 0, 2));
$prettyRoute = $prettyRoutes[$url] ?? $prettyRoutes[$twoSegment] ?? null;

if (!$hasExplicitModule && !$hasExplicitPage && $prettyRoute !== null) {
    $_GET['module'] = $prettyRoute[0];
    $_GET['page'] = $prettyRoute[1];
    if (isset($prettyRoute[2])) {
        $_GET['action'] = $prettyRoute[2];
    }
}

$module = preg_replace('/[^a-z]/', '', strtolower($_GET['module'] ?? 'user'));
$page   = preg_replace('/[^a-z_]/', '', strtolower($_GET['page']   ?? 'home'));
$action = preg_replace('/[^a-z_]/', '', strtolower($_GET['action'] ?? 'index'));

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

if ($module === 'admin' && $page === 'home') $page = 'dashboard';

$controllerClass = $routes[$module][$page] ?? HomeController::class;
$controller      = new $controllerClass();

$authMethods = [
    'admin_login' => ['index' => 'adminLogin',   'login' => 'adminAuthenticate'],
    'user_login'  => ['index' => 'userLogin',    'login' => 'userAuthenticate'],
    'register'    => ['index' => 'register',     'save'  => 'registerUser', 'verify' => 'showOtpVerification', 'confirm' => 'verifyRegistrationOtp'],
    'logout'      => ['index' => 'logout'],
];

// User-panel action routing (wishlist toggle, remove, profile, progress, review, history)
$userActions = ['wishlist_toggle', 'remove_wishlist', 'update_profile', 'delete_account', 'save_progress', 'record_view', 'save_review', 'save_report', 'clear_history', 'clear_notifications', 'subscription_request', 'feed_json'];

if ($module === 'user' && in_array($action, $userActions, true) && method_exists($controller, $action)) {
    $controller->{$action}();
} else {
    $method = $authMethods[$page][$action] ?? (method_exists($controller, $action) ? $action : 'index');
    $controller->{$method}();
}
