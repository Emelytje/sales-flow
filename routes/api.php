<?php
/**
 * REST API routes (JSON). Authenticated with an API key (Bearer token) via
 * ApiAuthMiddleware. Versioned under /api/v1.
 */

declare(strict_types=1);

use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\CustomerApiController;
use App\Controllers\Api\ContactApiController;
use App\Controllers\Api\QuotationApiController;
use App\Controllers\Api\ActivityApiController;
use App\Controllers\Api\WebhookApiController;
use App\Controllers\Api\PushApiController;
use App\Core\Router;
use App\Middleware\ApiAuthMiddleware;

return static function (Router $router): void {
    $router->group(['prefix' => '/api/v1'], static function (Router $r): void {
        // Token issue (login with credentials → returns bearer token).
        $r->post('/auth/token', [AuthApiController::class, 'token']);

        // Web Push subscription (session-authenticated from the PWA).
        $r->post('/push/subscribe', [PushApiController::class, 'subscribe']);
        $r->post('/push/unsubscribe', [PushApiController::class, 'unsubscribe']);

        $api = [ApiAuthMiddleware::class];

        $r->group(['middleware' => $api], static function (Router $g): void {
            $g->get('/me', [AuthApiController::class, 'me']);

            $g->get('/customers', [CustomerApiController::class, 'index']);
            $g->post('/customers', [CustomerApiController::class, 'store']);
            $g->get('/customers/{id}', [CustomerApiController::class, 'show']);
            $g->put('/customers/{id}', [CustomerApiController::class, 'update']);
            $g->delete('/customers/{id}', [CustomerApiController::class, 'destroy']);

            $g->get('/contacts', [ContactApiController::class, 'index']);
            $g->post('/contacts', [ContactApiController::class, 'store']);

            $g->get('/quotations', [QuotationApiController::class, 'index']);
            $g->get('/quotations/{id}', [QuotationApiController::class, 'show']);

            $g->get('/activities', [ActivityApiController::class, 'index']);
            $g->post('/activities', [ActivityApiController::class, 'store']);

            // Outbound webhook management.
            $g->get('/webhooks', [WebhookApiController::class, 'index']);
            $g->post('/webhooks', [WebhookApiController::class, 'store']);
            $g->delete('/webhooks/{id}', [WebhookApiController::class, 'destroy']);
        });
    });
};
