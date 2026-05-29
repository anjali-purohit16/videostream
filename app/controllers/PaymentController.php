<?php

class PaymentController extends AdminController
{
    public function index(): void
    {
        $model = new PaymentModel();
        $status = trim($_GET['status'] ?? '');
        $method = trim($_GET['method'] ?? '');

        $this->adminView('payments', [
            'title' => 'Payments',
            'section' => 'payments',
            'payments' => $model->getAll($status, $method),
            'summary' => $model->getRevenueSummary(),
            'filters' => compact('status', 'method'),
        ]);
    }
}
