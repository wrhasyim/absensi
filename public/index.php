<?php
// Public entry point / router
require_once __DIR__ . '/../config/config.php';

// Ensure log dir exists
if (!is_dir(LOG_PATH)) @mkdir(LOG_PATH, 0777, true);

// Session
session_set_cookie_params([
    'lifetime' => (int)env('SESSION_LIFETIME', 7200),
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

use App\Core\Router;

$router = new Router();

// Auth
$router->get('/', fn() => \App\Core\Auth::check() ? redirect('/dashboard') : redirect('/login'));
$router->get('/login', [\App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [\App\Controllers\AuthController::class, 'login']);
$router->any('/logout', [\App\Controllers\AuthController::class, 'logout']);

// Dashboard
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index']);

// Students
$router->get('/students', [\App\Controllers\StudentController::class, 'index']);
$router->get('/students/create', [\App\Controllers\StudentController::class, 'create']);
$router->post('/students/store', [\App\Controllers\StudentController::class, 'store']);
$router->get('/students/{id}/edit', [\App\Controllers\StudentController::class, 'edit']);
$router->post('/students/{id}/update', [\App\Controllers\StudentController::class, 'update']);
$router->post('/students/{id}/delete', [\App\Controllers\StudentController::class, 'delete']);

// Parents
$router->get('/parents', [\App\Controllers\ParentController::class, 'index']);
$router->get('/parents/create', [\App\Controllers\ParentController::class, 'create']);
$router->post('/parents/store', [\App\Controllers\ParentController::class, 'store']);
$router->get('/parents/{id}/edit', [\App\Controllers\ParentController::class, 'edit']);
$router->post('/parents/{id}/update', [\App\Controllers\ParentController::class, 'update']);
$router->post('/parents/{id}/delete', [\App\Controllers\ParentController::class, 'delete']);

// Master
$router->get('/majors', [\App\Controllers\MasterController::class, 'majorsIndex']);
$router->post('/majors/store', [\App\Controllers\MasterController::class, 'majorStore']);
$router->post('/majors/{id}/delete', [\App\Controllers\MasterController::class, 'majorDelete']);

$router->get('/classes', [\App\Controllers\MasterController::class, 'classesIndex']);
$router->post('/classes/store', [\App\Controllers\MasterController::class, 'classStore']);
$router->post('/classes/{id}/delete', [\App\Controllers\MasterController::class, 'classDelete']);

$router->get('/teachers', [\App\Controllers\MasterController::class, 'teachersIndex']);
$router->post('/teachers/store', [\App\Controllers\MasterController::class, 'teacherStore']);
$router->post('/teachers/{id}/delete', [\App\Controllers\MasterController::class, 'teacherDelete']);

// Attendance
$router->get('/attendance/monitor', [\App\Controllers\AttendanceController::class, 'monitor']);
$router->get('/attendance/monitor.json', [\App\Controllers\AttendanceController::class, 'monitorJson']);
$router->get('/attendance', [\App\Controllers\AttendanceController::class, 'index']);
$router->get('/attendance/permit', [\App\Controllers\AttendanceController::class, 'permitCreate']);
$router->post('/attendance/permit', [\App\Controllers\AttendanceController::class, 'permitStore']);

// Devices
$router->get('/devices', [\App\Controllers\DeviceController::class, 'index']);
$router->post('/devices/store', [\App\Controllers\DeviceController::class, 'store']);
$router->post('/devices/{id}/delete', [\App\Controllers\DeviceController::class, 'delete']);
$router->get('/devices/{id}/test', [\App\Controllers\DeviceController::class, 'testConnection']);
$router->post('/devices/{id}/sync', [\App\Controllers\DeviceController::class, 'sync']);
$router->get('/fingerprint/logs', [\App\Controllers\DeviceController::class, 'logs']);

// WhatsApp
$router->get('/whatsapp', [\App\Controllers\WhatsappController::class, 'dashboard']);
$router->get('/whatsapp/status.json', [\App\Controllers\WhatsappController::class, 'statusJson']);
$router->get('/whatsapp/qr.json', [\App\Controllers\WhatsappController::class, 'qrJson']);
$router->post('/whatsapp/reconnect', [\App\Controllers\WhatsappController::class, 'reconnect']);
$router->post('/whatsapp/logout-gw', [\App\Controllers\WhatsappController::class, 'logout']);
$router->get('/whatsapp/templates', [\App\Controllers\WhatsappController::class, 'templates']);
$router->post('/whatsapp/templates/save', [\App\Controllers\WhatsappController::class, 'templateSave']);
$router->get('/whatsapp/queue', [\App\Controllers\WhatsappController::class, 'queue']);
$router->post('/whatsapp/queue/{id}/resend', [\App\Controllers\WhatsappController::class, 'resend']);
$router->post('/whatsapp/queue/process', [\App\Controllers\WhatsappController::class, 'processQueueNow']);
$router->post('/whatsapp/test-send', [\App\Controllers\WhatsappController::class, 'testSend']);

// Reports
$router->get('/reports/daily', [\App\Controllers\ReportController::class, 'daily']);
$router->get('/reports/monthly', [\App\Controllers\ReportController::class, 'monthly']);
$router->get('/reports/whatsapp', [\App\Controllers\ReportController::class, 'whatsapp']);
$router->get('/system/health', [\App\Controllers\ReportController::class, 'health']);
$router->get('/settings', [\App\Controllers\ReportController::class, 'settings']);
$router->post('/settings/save', [\App\Controllers\ReportController::class, 'settingsSave']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
