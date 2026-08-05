<?php
/**
 * Ensures the request is authenticated; redirects guests to the login page.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                Response::json(['error' => 'Niet geautoriseerd.'], 401);
            }
            Session::flash('intended', $request->path());
            Response::redirect('/login');
        }
    }
}
