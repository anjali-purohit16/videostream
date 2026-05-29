<?php

abstract class BaseController
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP); //extr_skip prevents overwriting existing variables like $content in layout
        ob_start();
        require ROOT_PATH . '/app/views/' . $view . '.php';
        $content = ob_get_clean();

        require ROOT_PATH . '/app/views/layouts/' . $layout . '.php';
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function requireActiveUser(bool $json = false): int
    {
        if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
            $this->denyUserAccess($json, 'Please log in first.');
        }

        $userId = (int)$_SESSION['user_id'];
        $user = (new UserModel())->getById($userId);

        if (!$user || ($user['status'] ?? 'active') !== 'active') {
            $message = !$user
                ? 'Your account no longer exists.'
                : 'Your account has been suspended. Please contact support.';
            $this->clearUserSession($message);
            $this->denyUserAccess($json, $message, 403);
        }

        return $userId;
    }

    protected function clearUserSession(?string $flashMessage = null): void
    {
        unset($_SESSION['user_id'], $_SESSION['user_name']);
        if (($_SESSION['role'] ?? '') === 'user') {
            unset($_SESSION['role']);
        }
        if ($flashMessage !== null) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => $flashMessage];
        }
    }

    protected function url(string $path = ''): string
    {
        return BASE_URL . ltrim($path, '/');
    }

    private function denyUserAccess(bool $json, string $message, int $status = 401): void
    {
        if ($json) {
            http_response_code($status);  
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => $message]); //json_encode converts array to json string
            exit;
        }

        $this->redirect(BASE_URL . 'login');
    }
}
