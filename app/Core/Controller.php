<?php
/**
 * Base controller with view rendering, JSON responses, validation helpers and
 * CSRF verification for state-changing requests.
 */

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], ?string $layout = null): never
    {
        echo View::render($template, $data, $layout);
        exit;
    }

    protected function json(mixed $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function redirect(string $url): never
    {
        Response::redirect($url);
    }

    protected function back(): never
    {
        Response::back();
    }

    /**
     * Verify the CSRF token for unsafe HTTP methods. Aborts on failure.
     */
    protected function verifyCsrf(Request $request): void
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_csrf') ?? $request->header('X-CSRF-Token');
            if (!Csrf::verify(is_string($token) ? $token : null)) {
                if ($request->wantsJson()) {
                    Response::json(['error' => 'CSRF-token ongeldig of verlopen.'], 419);
                }
                http_response_code(419);
                echo View::render('errors/419', ['title' => 'Sessie verlopen']);
                exit;
            }
        }
    }

    /**
     * @param array<string, string> $rules
     * @param array<string, string> $labels
     */
    protected function validate(Request $request, array $rules, array $labels = []): array
    {
        $validator = new Validator($request->all(), $rules, $labels);
        if ($validator->fails()) {
            if ($request->wantsJson()) {
                Response::json(['errors' => $validator->errors()], 422);
            }
            Session::flash('errors', $validator->errors());
            Session::flash('old', $request->all());
            Response::back();
        }
        return $request->only(array_keys($rules));
    }

    protected function requireAuth(Request $request): void
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                Response::json(['error' => 'Niet geautoriseerd.'], 401);
            }
            Session::flash('intended', $request->path());
            Response::redirect('/login');
        }
    }

    protected function authorize(string $permission, Request $request): void
    {
        if (!Auth::can($permission)) {
            if ($request->wantsJson()) {
                Response::json(['error' => 'Geen toegang.'], 403);
            }
            http_response_code(403);
            echo View::render('errors/403', ['title' => 'Geen toegang']);
            exit;
        }
    }
}
