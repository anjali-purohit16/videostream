<?php

abstract class BaseController
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);

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

    protected function url(string $path = ''): string
    {
        return BASE_URL . ltrim($path, '/');
    }
}
