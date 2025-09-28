<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\Request;

date_default_timezone_set('Asia/Manila');

// header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->load();

$request = new Request();
$router = new Router($request);

// ====== AUTH START ====== //
$router->post('/callback', [App\Controllers\AuthController::class, 'googleAuth']);
// ====== AUTH END ====== //

// ====== ADMIN START ====== //
// Profile
$router->get('/admin/profile', [App\Controllers\AdminController::class, 'getProfile']);
$router->put('/admin/profile', [App\Controllers\AdminController::class, 'updateProfile']);

// News & Events
$router->post('/admin/news', [App\Controllers\AdminController::class, 'createNews']);
$router->get('/admin/news', [App\Controllers\AdminController::class, 'getNews']);
$router->put('/admin/news', [App\Controllers\AdminController::class, 'updateNews']);
$router->delete('/admin/news', [App\Controllers\AdminController::class, 'deleteNews']);

// Accounts
$router->get('/admin/accounts', [App\Controllers\AdminController::class, 'getAccounts']);
// ====== ADMIN START ====== //

$router->resolve();
