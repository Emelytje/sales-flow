<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Project;

final class ProjectController extends Controller
{
    private Project $projects;

    public function __construct()
    {
        $this->projects = new Project();
    }

    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->view('projects/index', ['title' => 'Projecten', 'projects' => $this->projects->withStats()]);
    }

    public function store(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('projects.manage', $request);
        $this->verifyCsrf($request);
        $this->validate($request, ['name' => 'required|max:160'], ['name' => 'Naam']);

        $id = $this->projects->create([
            'name'        => (string) $request->input('name'),
            'description' => $request->input('description'),
            'color'       => $request->input('color') ?: '#B33B62',
            'status'      => 'active',
            'created_by'  => Auth::id(),
        ]);
        AuditLog::record('project.created', 'project', $id);
        if ($request->wantsJson()) {
            $this->json(['ok' => true, 'redirect' => '/projects/' . $id]);
        }
        Session::flash('success', 'Project aangemaakt.');
        $this->redirect('/projects/' . $id);
    }

    public function show(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $id = (int) $params['id'];
        $project = $this->projects->find($id);
        if ($project === null) {
            $this->redirect('/projects');
        }
        $this->view('projects/show', [
            'title'     => $project['name'],
            'project'   => $project,
            'templates' => Database::instance()->all('SELECT * FROM email_templates WHERE project_id = ? ORDER BY name', [$id]),
            'customers' => (int) Database::instance()->scalar('SELECT COUNT(*) FROM customers WHERE project_id = ?', [$id]),
        ]);
    }

    public function update(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('projects.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        if ($this->projects->find($id) === null) {
            $this->redirect('/projects');
        }
        $this->projects->update($id, [
            'name'           => (string) $request->input('name'),
            'description'    => $request->input('description'),
            'color'          => $request->input('color') ?: '#B33B62',
            'call_script'    => $request->input('call_script'),
            'faq'            => $request->input('faq'),
            'demo_video_url' => $request->input('demo_video_url'),
            'status'         => in_array($request->input('status'), ['active', 'archived'], true) ? $request->input('status') : 'active',
        ]);
        AuditLog::record('project.updated', 'project', $id);
        Session::flash('success', 'Project bijgewerkt.');
        $this->redirect('/projects/' . $id);
    }

    public function destroy(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('projects.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $this->projects->update($id, ['status' => 'archived']);
        AuditLog::record('project.archived', 'project', $id);
        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        Session::flash('success', 'Project gearchiveerd.');
        $this->redirect('/projects');
    }
}
