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
$router->put('/admin/accounts', [App\Controllers\AdminController::class, 'updateAccount']);

// FAQ
$router->post('/admin/faqs', [App\Controllers\AdminController::class, 'createFAQ']);
$router->get('/admin/faqs', [App\Controllers\AdminController::class, 'getFAQ']);
$router->put('/admin/faqs', [App\Controllers\AdminController::class, 'updateFAQ']);
$router->delete('/admin/faqs', [App\Controllers\AdminController::class, 'deleteFAQ']);

// Resources
$router->post('/admin/resources', [App\Controllers\AdminController::class, 'createResource']);
$router->get('/admin/resources', [App\Controllers\AdminController::class, 'getResources']);
$router->put('/admin/resources', [App\Controllers\AdminController::class, 'updateResource']);

// Bookings & Reservations
$router->post('/admin/bookings', [App\Controllers\AdminController::class, 'createBooking']);
$router->get('/admin/bookings', [App\Controllers\AdminController::class, 'getBookings']);
$router->put('/admin/bookings', [App\Controllers\AdminController::class, 'updateBooking']);

// Guests List
$router->get('/admin/guests', [App\Controllers\AdminController::class, 'getGuests']);
// ====== ADMIN END ====== //

// ====== GUEST START ====== //
// Profile
$router->get('/guest/profile', [App\Controllers\GuestController::class, 'getProfile']);
$router->put('/guest/profile', [App\Controllers\GuestController::class, 'updateProfile']);
// ====== GUEST END ====== //

$router->resolve();
