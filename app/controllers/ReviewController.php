<?php

class ReviewController extends AdminController
{
    public function index(): void
    {
        $this->adminView('reviews', [
            'title' => 'Reviews',
            'section' => 'reviews',
            'reviews' => (new ReviewModel())->getAll(),
            'avgRating' => (new ReviewModel())->getAvgRating(),
        ]);
    }

    public function approve(): void
    {
        $model = new ReviewModel();
        $review = $model->getById($this->readId());
        $nextStatus = (($review['status'] ?? '') === 'approved') ? 'pending' : 'approved';
        $model->updateStatus($this->readId(), $nextStatus);
        $this->logAdminAction('Review Status Updated', 'Reviews', 'Set review ID ' . $this->readId() . ' to ' . $nextStatus);
        WsPublisher::push('reviews');
        $this->flash('success', 'Review status updated.');
        $this->redirect(BASE_URL . '?module=admin&page=reviews');
    }

    public function delete(): void
    {
        $model = new ReviewModel();
        $review = $model->getById($this->readId());
        $nextStatus = (($review['status'] ?? '') === 'rejected') ? 'pending' : 'rejected';
        $model->updateStatus($this->readId(), $nextStatus);
        $this->logAdminAction('Review Status Updated', 'Reviews', 'Set review ID ' . $this->readId() . ' to ' . $nextStatus);
        WsPublisher::push('reviews');
        $this->flash('success', 'Review status updated.');
        $this->redirect(BASE_URL . '?module=admin&page=reviews');
    }
}
