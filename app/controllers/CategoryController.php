<?php

class CategoryController extends AdminController
{
    public function index(): void
    {
        $model = new CategoryModel();
        $videoModel = new VideoModel();
        $categories = $model->getAll();
        $categoryVideos = [];
        foreach ($categories as $category) {
            $categoryVideos[(int)$category['id']] = $videoModel->getByCategoryId((int)$category['id']);
        }
        $total = count($categories);
        $mostVideos = $categories[0] ?? null;
        $highestViews = $categories;
        usort($highestViews, fn ($a, $b) => (int)$b['total_views'] <=> (int)$a['total_views']);

        $this->adminView('categories', [
            'title' => 'Categories',
            'section' => 'categories',
            'categories' => $categories,
            'categoryVideos' => $categoryVideos,
            'stats' => [
                'total' => $total,
                'added_this_month' => 2,
                'most_videos' => $mostVideos,
                'highest_views' => $highestViews[0] ?? null,
            ],
            'editCategory' => !empty($_GET['edit_id']) ? $this->findCategory($categories, (int)$_GET['edit_id']) : null,
        ]);
    }

    public function save(): void
    {
        $model = new CategoryModel();
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'film');
        $status = $_POST['status'] ?? 'active';

        if ($name === '') {
            $this->flash('error', 'Category name is required.');
            $this->back(BASE_URL . '?module=admin&page=categories');
        }

        if ($id > 0) {
            $model->update($id, $name, $icon, $status);
            $this->logAdminAction('Category Updated', 'Categories', $name);
            $this->flash('success', 'Category updated.');
        } else {
            $model->create($name, $icon);
            $this->logAdminAction('Category Added', 'Categories', $name);
            $this->flash('success', 'Category added.');
        }

        $this->redirect(BASE_URL . '?module=admin&page=categories');
    }

    public function suspend(): void
    {
        $id = $this->readId();
        $status = (new CategoryModel())->toggleStatus($id);

        if ($status) {
            $this->logAdminAction('Category Status Updated', 'Categories', 'Category ID ' . $id . ' set to ' . $status);
            $this->flash('success', $status === 'active' ? 'Category activated.' : 'Category suspended.');
        } else {
            $this->flash('error', 'Category not found.');
        }

        $this->redirect(BASE_URL . '?module=admin&page=categories');
    }

    public function delete(): void
    {
        $id = $this->readId();
        $model = new CategoryModel();
        $category = $this->findCategory($model->getAll(), $id);

        if (!$category) {
            $this->flash('error', 'Category not found.');
            $this->redirect(BASE_URL . '?module=admin&page=categories');
        }

        if ((int)($category['video_count'] ?? 0) > 0) {
            $this->flash('error', 'Move or delete this category videos before deleting the category.');
            $this->redirect(BASE_URL . '?module=admin&page=categories');
        }

        $model->delete($id);
        $this->logAdminAction('Category Deleted', 'Categories', $category['name'] ?? ('Category ID ' . $id));
        $this->flash('success', 'Category deleted.');
        $this->redirect(BASE_URL . '?module=admin&page=categories');
    }

    private function findCategory(array $categories, int $id): ?array
    {
        foreach ($categories as $category) {
            if ((int)$category['id'] === $id) {
                return $category;
            }
        }
        return null;
    }
}
