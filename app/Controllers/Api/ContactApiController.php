<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Contact;

final class ContactApiController extends Controller
{
    public function index(Request $request): never
    {
        $this->json((new Contact())->listing(
            (int) $request->query('page', '1'),
            min(100, (int) $request->query('per_page', '25')),
            trim((string) $request->query('q', ''))
        ));
    }

    public function store(Request $request): never
    {
        $this->validate($request, [
            'customer_id' => 'required|integer',
            'first_name'  => 'required|max:80',
            'email'       => 'email',
        ], ['first_name' => 'Voornaam']);
        $id = (new Contact())->create($request->only([
            'customer_id', 'first_name', 'last_name', 'job_title', 'email', 'phone', 'mobile', 'linkedin',
        ]));
        $this->json((new Contact())->find($id), 201);
    }
}
