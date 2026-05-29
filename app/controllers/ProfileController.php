<?php

class ProfileController extends BaseController
{
    public function update_profile(): void
    {
        header('Content-Type: application/json');
        $userId  = $this->requireActiveUser(true);
        $name    = trim($_POST['name'] ?? '');
        $current = $_POST['current_password'] ?? '';
        $newPw   = $_POST['new_password'] ?? '';

        if ($name === '') { echo json_encode(['ok' => false, 'message' => 'Name cannot be empty.']); exit; }

        try {
            $userModel = new UserModel();
            $passwordHash = $userModel->getPasswordHash($userId);

            if ($newPw !== '') {
                if (!password_verify($current, $passwordHash ?? '')) {
                    echo json_encode(['ok' => false, 'message' => 'Current password is incorrect.']); exit;
                }
                if (strlen($newPw) < 6) {
                    echo json_encode(['ok' => false, 'message' => 'New password must be at least 6 characters.']); exit;
                }
                $hash = password_hash($newPw, PASSWORD_DEFAULT);
                $userModel->updateProfileNameAndPassword($userId, $name, $hash);
            } else {
                $userModel->updateProfileName($userId, $name);
            }

            $_SESSION['user_name'] = $name;
            echo json_encode(['ok' => true, 'message' => 'Profile updated successfully.', 'name' => $name]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'message' => 'Update failed. Please try again.']);
        }
        exit;
    }

    public function delete_account(): void
    {
        $userId = $this->requireActiveUser();

        try {
            (new UserModel())->delete($userId);
        } catch (Throwable) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Unable to delete your account. Please try again.'];
            $this->redirect(BASE_URL . 'profile');
        }

        $this->destroyCurrentSession();
        $this->redirect(BASE_URL . 'login');
    }

    private function destroyCurrentSession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }

        session_destroy();
    }
}
