<?php

class SubscriptionController extends AdminController
{
    public function index(): void
    {
        $model = new SubscriptionModel();

        $this->adminView('subscriptions', [
            'title' => 'Subscriptions',
            'section' => 'subscriptions',
            'distribution' => $model->getDistribution(),
            'renewals' => $model->getRenewals(),
        ]);
    }
}
