<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Activity;

final class ActivityApiController extends Controller
{
    public function index(Request $request): never
    {
        $customerId = (int) $request->query('customer_id', '0');
        $params = [];
        $where = '1';
        if ($customerId > 0) {
            $where = 'a.customer_id = ?';
            $params[] = $customerId;
        }
        $rows = Database::instance()->all(
            "SELECT a.*, u.name AS user_name FROM activities a LEFT JOIN users u ON u.id=a.user_id
             WHERE $where ORDER BY a.occurred_at DESC LIMIT 100",
            $params
        );
        $this->json(['data' => $rows]);
    }

    public function store(Request $request): never
    {
        $this->validate($request, [
            'type'        => 'required|in:call,email,meeting,note,task',
            'customer_id' => 'required|integer',
        ], ['type' => 'Type']);
        $id = (new Activity())->log([
            'customer_id' => (int) $request->input('customer_id'),
            'user_id'     => Auth::id(),
            'type'        => (string) $request->input('type'),
            'subject'     => (string) $request->input('subject', ''),
            'body'        => (string) $request->input('body', ''),
            'outcome'     => $request->input('outcome'),
        ]);
        $this->json(['ok' => true, 'id' => $id], 201);
    }
}
