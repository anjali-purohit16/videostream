<?php

class UserController extends AdminController
{
    public function index(): void
    {
        $model = new UserModel();
        $search = trim($_GET['search'] ?? '');
        $plan = trim($_GET['plan'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $users = $model->getAll($search, $plan, $status);

        if (!empty($_GET['export'])) {
            $this->exportCsv($users);
        }

        $userDetails = [];
        foreach ($users as $user) {
            $userDetails[(int)$user['id']] = $model->getDetails((int)$user['id']);
        }

        $this->adminView('users', [
            'title' => 'Users',
            'section' => 'users',
            'users' => $users,
            'userDetails' => $userDetails,
            'plans' => $model->getPlans(),
            'showAddForm' => !empty($_GET['new']),
            'filters' => compact('search', 'plan', 'status'),
            'totalUsers' => $this->navCounts()['users'] ?? count($users),
        ]);
    }

    public function save(): void
    {
        $model = new UserModel();
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'plan_id' => (int)($_POST['plan_id'] ?? 0),
            'status' => $_POST['status'] ?? 'active',
            'create_subscription' => !empty($_POST['create_subscription']),
        ];

        if ($data['name'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['password']) < 6 || $data['plan_id'] <= 0) {
            $this->flash('error', 'Enter name, valid email, password of at least 6 characters, and a plan.');
            $this->back(BASE_URL . 'admin/users?new=1');
        }

        try {
            $userId = $model->createManual($data);
            $this->sendWelcomeMail($data['email'], $data['name']);
            $this->logAdminAction('User Added', 'Users', 'Created user ID ' . $userId);
            $this->flash('success', 'User added successfully.');
            $this->redirect(BASE_URL . 'admin/users');
        } catch (Throwable) {
            $this->flash('error', 'Unable to add user. This email may already exist.');
            $this->back(BASE_URL . 'admin/users?new=1');
        }
    }

    public function suspend(): void
    {
        $this->requireAdmin();
        $id = $this->readId();

        if ($id <= 0) {
            $this->flash('error', 'Invalid user selected.');
            $this->back(BASE_URL . 'admin/users');
        }

        (new UserModel())->updateStatus($id, 'suspended');
        $this->purgeUserSessions($id);
        WsPublisher::push('account_status', ['audience' => 'user', 'user_id' => $id]);
        $this->logAdminAction('User Suspended', 'Users', 'Suspended user ID ' . $id);
        $this->flash('success', 'User suspended and logged out.');
        $this->back(BASE_URL . 'admin/users');
    }

    public function activate(): void
    {
        $this->requireAdmin();
        $id = $this->readId();

        if ($id <= 0) {
            $this->flash('error', 'Invalid user selected.');
            $this->back(BASE_URL . 'admin/users');
        }

        (new UserModel())->updateStatus($id, 'active');
        $this->logAdminAction('User Activated', 'Users', 'Activated user ID ' . $id);
        $this->flash('success', 'User activated.');
        $this->back(BASE_URL . 'admin/users');
    }

    public function delete(): void
    {
        $this->requireAdmin();
        $id = $this->readId();

        if ($id <= 0) {
            $this->flash('error', 'Invalid user selected.');
            $this->back(BASE_URL . 'admin/users');
        }

        try {
            $deleted = (new UserModel())->delete($id);
            if ($deleted > 0) {
                $this->purgeUserSessions($id);
                WsPublisher::push('account_status', ['audience' => 'user', 'user_id' => $id]);
                $this->logAdminAction('User Deleted', 'Users', 'Deleted user ID ' . $id);
                $this->flash('success', 'User deleted successfully.');
            } else {
                $this->flash('error', 'User was not found.');
            }
        } catch (Throwable) {
            $this->flash('error', 'Unable to delete user. Please try again.');
        }

        $this->back(BASE_URL . 'admin/users');
    }

    private function exportCsv(array $users): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users-export-' . date('Ymd-His') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Name', 'Email', 'Plan', 'Joined', 'Last Active', 'Status']);
        foreach ($users as $user) {
            fputcsv($out, [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['plan'],
                $user['joined_at'],
                $user['last_seen'],
                $user['status'],
            ]);
        }
        fclose($out);
        exit;
    }

    private function purgeUserSessions(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $sessionPath = session_save_path() ?: (ROOT_PATH . '/storage/sessions');
        if (!is_dir($sessionPath)) {
            return;
        }

        $needle = 'user_id|i:' . $userId . ';';
        foreach (glob(rtrim($sessionPath, '/\\') . DIRECTORY_SEPARATOR . 'sess_*') ?: [] as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }

            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            if (strpos($content, $needle) !== false && strpos($content, 'role|s:4:"user";') !== false) {
                @unlink($file);
            }
        }
    }

    private function sendWelcomeMail(string $email, string $name): void
    {
        $subject = 'Welcome to ' . APP_NAME;
        $message = "Hi {$name},\n\nYour account on " . APP_NAME . " was created successfully.\n\nHappy streaming,\n" . APP_NAME;

        try {
            $settings = (new SettingsModel())->getAll();
            if (($settings['email_notifications'] ?? '1') !== '1') {
                $this->logMailFallback($email, $subject, $message, 'Email notifications are disabled.');
                return;
            }

            $smtpHost = trim((string)($settings['smtp_host'] ?? ''));
            $smtpUser = trim((string)($settings['smtp_user'] ?? ''));
            $smtpPass = trim((string)($settings['smtp_pass'] ?? ''));
            $smtpPort = (int)($settings['smtp_port'] ?? 587);

            if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
                $this->logMailFallback($email, $subject, $message, 'SMTP credentials are not configured.');
                return;
            }

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = $smtpPort;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpPort === 465
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false,
                ],
            ];
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($smtpUser, APP_NAME);
            $mail->addReplyTo($smtpUser, APP_NAME);
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
            $mail->AltBody = $message;
            $mail->send();

            $this->logMailFallback($email, $subject, '[HTML email]', null);
        } catch (Throwable $error) {
            $this->logMailFallback($email, $subject, $message, $error->getMessage());
        }
    }

    private function logMailFallback(string $email, string $subject, string $message, ?string $error = 'mail() fallback'): void
    {
        $dir = ROOT_PATH . '/storage/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $status = $error === null ? 'SENT_OK' : 'FAILED: ' . $error;
        file_put_contents($dir . '/welcome.log', '[' . date('c') . "] [{$status}] To: {$email} | {$subject}\n{$message}\n\n", FILE_APPEND);
    }
}
