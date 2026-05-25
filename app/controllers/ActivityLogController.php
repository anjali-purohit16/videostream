<?php

class ActivityLogController extends AdminController
{
    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $action = trim($_GET['action'] ?? '');
        $date = trim($_GET['date'] ?? '');

        $this->adminView('activity', [
            'title' => 'Activity Logs',
            'section' => 'activity',
            'logs' => (new ActivityLogModel())->getAll($search, $action, $date),
            'filters' => compact('search', 'action', 'date'),
        ]);
    }

    public function clear(): void
    {
        (new ActivityLogModel())->clearAll();
        $this->flash('success', 'Activity logs cleared.');
        $this->redirect(BASE_URL . '?module=admin&page=activity');
    }
}
