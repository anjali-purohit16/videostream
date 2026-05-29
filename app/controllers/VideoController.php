<?php

class VideoController extends AdminController
{
    public function index(): void
    {
        $model = new VideoModel();
        $categoryModel = new CategoryModel();

        $search = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $editId = (int)($_GET['edit_id'] ?? 0);

        $this->adminView('videos', [
            'title' => 'Videos',
            'section' => 'videos',
            'videos' => $model->getAll($search, $status, $category),
            'categories' => $categoryModel->getAllForSelect(),
            'filters' => compact('search', 'category', 'status'),
            'editVideo' => $editId ? $model->getById($editId) : null,
        ]);
    }

    public function save(): void
    {
        $model = new VideoModel();
        $id = (int)($_POST['id'] ?? 0);
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['category_ids'] ?? [])))));
        if (!$categoryIds && (int)($_POST['category_id'] ?? 0) > 0) {
            $categoryIds = [(int)$_POST['category_id']];
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'category_id' => $categoryIds[0] ?? 0,
            'category_ids' => $categoryIds,
            'access_level' => strtolower(trim($_POST['access_level'] ?? 'free')),
            'duration_sec' => ((int)($_POST['hours'] ?? 0) * 3600) + ((int)($_POST['minutes'] ?? 0) * 60),
            'thumbnail' => $this->uploadAsset('thumbnail_file', $this->normaliseMediaPath(trim($_POST['thumbnail'] ?? ''), 'thumbnail')),
            'file_path' => $this->uploadAsset('video_file', $this->normaliseMediaPath(trim($_POST['file_path'] ?? ''), 'video')),
            'status' => $_POST['status'] ?? 'draft',
            'uploaded_by' => 1,
        ];

        if ($data['title'] === '' || $data['category_id'] <= 0) {
            $this->flash('error', 'Video title and at least one category are required.');
            $this->redirect(BASE_URL . 'admin/videos' . ($id > 0 ? '?edit_id=' . $id : '?new=1'));
        }

        if ($id > 0) {
            $model->update($id, $data);
            $this->logAdminAction('Video Updated', 'Videos', $data['title']);
            $this->flash('success', 'Video updated.');
        } else {
            $model->create($data);
            $this->logAdminAction('New video uploaded', 'Videos', $data['title']);
            (new NotificationModel())->create('New video uploaded', $data['title'], BASE_URL . 'admin/videos?search=' . urlencode($data['title']));
            WsPublisher::push('videos', ['audience' => 'broadcast']);
            $this->flash('success', 'Video uploaded.');
        }

        $this->redirect(BASE_URL . 'admin/videos' . ($id > 0 ? '?edit_id=' . $id : '?new=1'));
    }

    public function delete(): void
    {
        (new VideoModel())->delete($this->readId());
        $this->logAdminAction('Video Deleted', 'Videos', 'Deleted video ID ' . $this->readId());
        WsPublisher::push('videos', ['audience' => 'broadcast']);
        $this->flash('success', 'Video deleted.');
        $this->back(BASE_URL . 'admin/videos');
    }

    private function uploadAsset(string $field, string $fallback = ''): ?string
    {
        if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $fallback !== '' ? $fallback : null;
        }

        $uploadRoot = ROOT_PATH . '/public/uploads';
        $subdir = $field === 'thumbnail_file' ? 'thumbnails' : 'videos';
        $targetDir = $uploadRoot . '/' . $subdir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $original = basename((string)$_FILES[$field]['name']);
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $safeBase = preg_replace('/[^a-z0-9]+/i', '-', pathinfo($original, PATHINFO_FILENAME));
        $filename = trim($safeBase, '-') . '-' . uniqid() . ($ext ? '.' . $ext : '');
        $target = $targetDir . '/' . $filename;

        if (move_uploaded_file((string)$_FILES[$field]['tmp_name'], $target)) {
            return 'uploads/' . $subdir . '/' . $filename;
        }

        return $fallback !== '' ? $fallback : null;
    }

    private function normaliseMediaPath(string $path, string $type): string
    {
        $path = trim($path);
        if ($path === '' || preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        $publicPos = stripos($path, '/public/');
        if ($publicPos !== false) {
            $path = substr($path, $publicPos + 8);
        }
        $path = preg_replace('#^[A-Za-z]:/#', '/', $path);
        $path = preg_replace('#^/?public/#i', '', $path);
        $path = ltrim($path, '/');

        if (!str_contains($path, '/')) {
            $path = ($type === 'video' ? 'uploads/videos/' : 'uploads/thumbnails/') . $path;
        }

        return $path;
    }
}
