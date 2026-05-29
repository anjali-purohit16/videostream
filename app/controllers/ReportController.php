<?php

class ReportController extends AdminController
{
    public function index(): void
    {
        $this->adminView('reports', [
            'title' => 'Reports',
            'section' => 'reports',
            'reports' => (new ReportModel())->getAll(),
        ]);
    }

    public function resolve(): void
    {
        (new ReportModel())->updateStatus($this->readId(), 'resolved');
        $this->logAdminAction('Report Resolved', 'Reports', 'Resolved report ID ' . $this->readId());
        WsPublisher::push('reports');
        $this->flash('success', 'Report marked resolved.');
        $this->redirect(BASE_URL . '?module=admin&page=reports');
    }

    public function dismiss(): void
    {
        (new ReportModel())->updateStatus($this->readId(), 'dismissed');
        $this->logAdminAction('Report Dismissed', 'Reports', 'Dismissed report ID ' . $this->readId());
        WsPublisher::push('reports');
        $this->flash('success', 'Report dismissed.');
        $this->redirect(BASE_URL . '?module=admin&page=reports');
    }
}
