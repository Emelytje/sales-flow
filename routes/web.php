<?php
/**
 * Web routes.
 *
 * Returns a closure that registers all browser-facing routes on the router.
 * Protected routes use AuthMiddleware; guest routes use GuestMiddleware.
 */

declare(strict_types=1);

use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\PasswordResetController;
use App\Controllers\Auth\TwoFactorController;
use App\Controllers\DashboardController;
use App\Controllers\CustomerController;
use App\Controllers\ContactController;
use App\Controllers\CallboardController;
use App\Controllers\AgendaController;
use App\Controllers\BookingController;
use App\Controllers\QuotationController;
use App\Controllers\ProjectController;
use App\Controllers\ReportController;
use App\Controllers\MapController;
use App\Controllers\SearchController;
use App\Controllers\NotificationController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Controllers\ProfileController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

return static function (Router $router): void {
    $auth = [AuthMiddleware::class];
    $guest = [GuestMiddleware::class];

    // Landing → dashboard or login.
    $router->get('/', [DashboardController::class, 'root']);

    // Authentication.
    $router->get('/login', [LoginController::class, 'show'], $guest);
    $router->post('/login', [LoginController::class, 'login'], $guest);
    $router->post('/logout', [LoginController::class, 'logout'], $auth);

    $router->get('/2fa', [TwoFactorController::class, 'show']);
    $router->post('/2fa', [TwoFactorController::class, 'verify']);

    $router->get('/forgot-password', [PasswordResetController::class, 'request'], $guest);
    $router->post('/forgot-password', [PasswordResetController::class, 'sendLink'], $guest);
    $router->get('/reset-password/{token}', [PasswordResetController::class, 'show'], $guest);
    $router->post('/reset-password', [PasswordResetController::class, 'reset'], $guest);

    // Public self-booking page (no auth — customers book a rep).
    $router->get('/book/{slug}', [BookingController::class, 'show']);
    $router->post('/book/{slug}', [BookingController::class, 'store']);
    $router->get('/book/{slug}/confirmed', [BookingController::class, 'confirmed']);

    // Email open/click tracking pixels (public, no auth).
    $router->get('/t/o/{token}', [\App\Controllers\TrackingController::class, 'open']);
    $router->get('/t/c/{token}', [\App\Controllers\TrackingController::class, 'click']);

    // CTI screen-pop page for softphones (session-authenticated inside).
    $router->get('/cti/popup', [\App\Controllers\CtiController::class, 'popup']);

    // Public quotation view + digital signature (customer-facing, no login).
    $router->get('/q/{token}', [QuotationController::class, 'publicShow']);
    $router->post('/q/{token}/sign', [QuotationController::class, 'sign']);

    // ---- Authenticated application ----
    $router->group(['middleware' => $auth], static function (Router $r): void {
        $r->get('/dashboard', [DashboardController::class, 'index']);

        // Customers.
        $r->get('/customers', [CustomerController::class, 'index']);
        $r->get('/customers/create', [CustomerController::class, 'create']);
        $r->post('/customers', [CustomerController::class, 'store']);
        $r->get('/customers/{id}', [CustomerController::class, 'show']);
        $r->get('/customers/{id}/edit', [CustomerController::class, 'edit']);
        $r->put('/customers/{id}', [CustomerController::class, 'update']);
        $r->delete('/customers/{id}', [CustomerController::class, 'destroy']);
        $r->post('/customers/{id}/notes', [CustomerController::class, 'addNote']);
        $r->post('/customers/{id}/activities', [CustomerController::class, 'addActivity']);
        $r->post('/customers/{id}/attachments', [\App\Controllers\AttachmentController::class, 'store']);

        // Attachments (download/delete).
        $r->get('/attachments/{id}/download', [\App\Controllers\AttachmentController::class, 'download']);
        $r->delete('/attachments/{id}', [\App\Controllers\AttachmentController::class, 'destroy']);

        // Email.
        $r->get('/emails', [\App\Controllers\EmailController::class, 'index']);
        $r->post('/emails/send', [\App\Controllers\EmailController::class, 'send']);
        $r->post('/emails/templates', [\App\Controllers\EmailController::class, 'storeTemplate']);
        $r->delete('/emails/templates/{id}', [\App\Controllers\EmailController::class, 'deleteTemplate']);

        // Contacts.
        $r->get('/contacts', [ContactController::class, 'index']);
        $r->post('/contacts', [ContactController::class, 'store']);
        $r->put('/contacts/{id}', [ContactController::class, 'update']);
        $r->delete('/contacts/{id}', [ContactController::class, 'destroy']);

        // Callboard.
        $r->get('/callboard', [CallboardController::class, 'index']);
        $r->get('/callboard/next', [CallboardController::class, 'next']);
        $r->post('/callboard/outcome', [CallboardController::class, 'outcome']);
        $r->get('/callboard/leaderboard', [CallboardController::class, 'leaderboard']);

        // Agenda.
        $r->get('/agenda', [AgendaController::class, 'index']);
        $r->get('/agenda/events', [AgendaController::class, 'events']);
        $r->post('/agenda/events', [AgendaController::class, 'store']);
        $r->put('/agenda/events/{id}', [AgendaController::class, 'update']);
        $r->delete('/agenda/events/{id}', [AgendaController::class, 'destroy']);

        // Booking administration.
        $r->get('/bookings', [BookingController::class, 'index']);
        $r->post('/bookings/{id}/approve', [BookingController::class, 'approve']);
        $r->post('/bookings/{id}/reject', [BookingController::class, 'reject']);

        // Quotations.
        $r->get('/quotations', [QuotationController::class, 'index']);
        $r->get('/quotations/create', [QuotationController::class, 'create']);
        $r->post('/quotations', [QuotationController::class, 'store']);
        $r->get('/quotations/{id}', [QuotationController::class, 'show']);
        $r->get('/quotations/{id}/edit', [QuotationController::class, 'edit']);
        $r->put('/quotations/{id}', [QuotationController::class, 'update']);
        $r->delete('/quotations/{id}', [QuotationController::class, 'destroy']);
        $r->get('/quotations/{id}/pdf', [QuotationController::class, 'pdf']);

        // Projects.
        $r->get('/projects', [ProjectController::class, 'index']);
        $r->post('/projects', [ProjectController::class, 'store']);
        $r->get('/projects/{id}', [ProjectController::class, 'show']);
        $r->put('/projects/{id}', [ProjectController::class, 'update']);
        $r->delete('/projects/{id}', [ProjectController::class, 'destroy']);

        // Map.
        $r->get('/map', [MapController::class, 'index']);
        $r->post('/map/geocode/{id}', [MapController::class, 'geocode']);

        // Reports.
        $r->get('/reports', [ReportController::class, 'index']);
        $r->get('/reports/export/{type}', [ReportController::class, 'export']);

        // Search + notifications.
        $r->get('/search', [SearchController::class, 'index']);
        $r->get('/notifications', [NotificationController::class, 'index']);

        // Profile.
        $r->get('/profile', [ProfileController::class, 'show']);
        $r->put('/profile', [ProfileController::class, 'update']);
        $r->put('/profile/password', [ProfileController::class, 'updatePassword']);

        // Settings + user management (admin).
        $r->get('/settings', [SettingsController::class, 'index']);
        $r->put('/settings', [SettingsController::class, 'update']);
        $r->get('/settings/users', [UserController::class, 'index']);
        $r->post('/settings/users', [UserController::class, 'store']);
        $r->put('/settings/users/{id}', [UserController::class, 'update']);
        $r->delete('/settings/users/{id}', [UserController::class, 'destroy']);
        $r->get('/settings/audit', [SettingsController::class, 'audit']);
        $r->get('/settings/integrations', [SettingsController::class, 'integrations']);
    });
};
