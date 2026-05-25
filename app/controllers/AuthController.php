<?php

class AuthController extends BaseController
{
    // ══════════════════════════════════════════════════════════════════
    //  ADMIN AUTH
    // ══════════════════════════════════════════════════════════════════

    public function adminLogin(): void
    {
        if (!empty($_SESSION['admin_id']) && ($_SESSION['role'] ?? '') === 'admin') {
            $this->redirect(BASE_URL . 'admin');
        }

        $this->view('auth/admin_login', [
            'title' => 'Admin Login',
            'flash' => $_SESSION['flash'] ?? null,
        ], 'auth');
        unset($_SESSION['flash']);
    }

    public function adminAuthenticate(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email and password are required.'];
            $this->redirect(BASE_URL . 'admin/login');
        }

        // CAPTCHA
        if (!$this->verifyCaptcha()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'CAPTCHA verification failed. Please try again.'];
            $this->redirect(BASE_URL . 'admin/login');
        }

        $admin = (new AuthModel())->findAdminByEmail($email);

        if (!$admin || !password_verify($password, $admin['password'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid admin credentials. Please try again.'];
            $this->redirect(BASE_URL . 'admin/login');
        }

        $_SESSION['admin_id']   = (int)$admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['role']       = 'admin';

        try {
            (new ActivityLogModel())->log(
                $admin['name'], 'Admin Login', 'Auth',
                'Admin signed in', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            );
        } catch (Throwable) {}

        $this->redirect(BASE_URL . 'admin');
    }

    // ══════════════════════════════════════════════════════════════════
    //  USER AUTH
    // ══════════════════════════════════════════════════════════════════

    public function userLogin(): void
    {
        if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user') {
            $this->redirect(BASE_URL);
        }

        $this->view('auth/user_login', [
            'title' => 'Sign In',
            'flash' => $_SESSION['flash'] ?? null,
        ], 'auth');
        unset($_SESSION['flash']);
    }

    public function userAuthenticate(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Email and password are required.'];
            $this->redirect(BASE_URL . 'login');
        }

        // CAPTCHA
        if (!$this->verifyCaptcha()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'CAPTCHA verification failed. Please try again.'];
            $this->redirect(BASE_URL . 'login');
        }

        $user = (new AuthModel())->findUserByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Incorrect email or password. Please try again.'];
            $this->redirect(BASE_URL . 'login');
        }

        if (($user['status'] ?? 'active') === 'banned') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your account has been suspended. Please contact support.'];
            $this->redirect(BASE_URL . 'login');
        }

        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = 'user';

        try {
            (new AuthModel())->touchUser((int)$user['id']);
            (new ActivityLogModel())->log(
                $user['name'], 'Login', 'Auth',
                'User signed in', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            );
        } catch (Throwable) {}

        $this->redirect(BASE_URL);
    }

    // ══════════════════════════════════════════════════════════════════
    //  REGISTER
    // ══════════════════════════════════════════════════════════════════

    public function register(): void
    {
        if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user') {
            $this->redirect(BASE_URL);
        }

        $this->view('auth/user_register', [
            'title' => 'Create Account',
            'flash' => $_SESSION['flash'] ?? null,
        ], 'auth');
        unset($_SESSION['flash']);
    }

    public function registerUser(): void
    {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // 1. Name
        if ($name === '') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please enter your full name.'];
            $this->redirect(BASE_URL . 'register');
        }

        // 2. Email format
        if (!$this->isValidEmail($email)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please enter a valid email address.'];
            $this->redirect(BASE_URL . 'register');
        }

        // 3. Email authenticity — MX/DNS record check
        if (!$this->emailDomainExists($email)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'This email domain does not exist or cannot receive emails. Please use a real email address.'];
            $this->redirect(BASE_URL . 'register');
        }

        // 4. Strong password
        $pwError = $this->checkPasswordStrength($password);
        if ($pwError !== null) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => $pwError];
            $this->redirect(BASE_URL . 'register');
        }

        // 5. CAPTCHA
        if (!$this->verifyCaptcha()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'CAPTCHA verification failed. Please try again.'];
            $this->redirect(BASE_URL . 'register');
        }

        // 6. Send OTP before creating the account
        try {
            $authModel = new AuthModel();
            if ($authModel->findUserByEmail($email)) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'This email is already registered. Try signing in.'];
                $this->redirect(BASE_URL . 'register');
            }

            $otp = (string) random_int(100000, 999999);
            $_SESSION['pending_registration'] = [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
                'expires_at' => time() + 600,
                'attempts' => 0,
            ];

            $this->sendOtpMail($email, $name, $otp);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'We sent a 6-digit OTP to your email. Please verify it to finish registration.'];
            $this->redirect(BASE_URL . 'register?action=verify');

        } catch (Throwable) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Unable to start registration. Please try again.'];
            $this->redirect(BASE_URL . 'register');
        }
    }

    public function showOtpVerification(): void
    {
        if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user') {
            $this->redirect(BASE_URL);
        }

        $pending = $_SESSION['pending_registration'] ?? null;
        if (!$pending || empty($pending['email'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please register first to receive an OTP.'];
            $this->redirect(BASE_URL . 'register');
        }

        $this->view('auth/user_verify_otp', [
            'title' => 'Verify Email',
            'flash' => $_SESSION['flash'] ?? null,
            'email' => $pending['email'],
            'expiresAt' => (int)($pending['expires_at'] ?? 0),
        ], 'auth');
        unset($_SESSION['flash']);
    }

    public function verifyRegistrationOtp(): void
    {
        $pending = $_SESSION['pending_registration'] ?? null;
        if (!$pending || empty($pending['email']) || empty($pending['otp_hash'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please register first to receive an OTP.'];
            $this->redirect(BASE_URL . 'register');
        }

        $otp = preg_replace('/\D+/', '', trim($_POST['otp'] ?? ''));
        if (strlen($otp) !== 6) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Enter the 6-digit OTP sent to your email.'];
            $this->redirect(BASE_URL . 'register?action=verify');
        }

        if (time() > (int)($pending['expires_at'] ?? 0)) {
            unset($_SESSION['pending_registration']);
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'OTP expired. Please register again to get a new code.'];
            $this->redirect(BASE_URL . 'register');
        }

        $_SESSION['pending_registration']['attempts'] = (int)($pending['attempts'] ?? 0) + 1;
        if ($_SESSION['pending_registration']['attempts'] > 5) {
            unset($_SESSION['pending_registration']);
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Too many incorrect OTP attempts. Please register again.'];
            $this->redirect(BASE_URL . 'register');
        }

        if (!password_verify($otp, $pending['otp_hash'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid OTP. Please check your email and try again.'];
            $this->redirect(BASE_URL . 'register?action=verify');
        }

        try {
            $name = (string)$pending['name'];
            $email = (string)$pending['email'];
            $id = (new AuthModel())->createUserWithPasswordHash($name, $email, (string)$pending['password_hash']);
            unset($_SESSION['pending_registration']);

            $_SESSION['user_id']   = (int)$id;
            $_SESSION['user_name'] = $name;
            $_SESSION['role']      = 'user';

            try {
                (new NotificationModel())->create(
                    'New user registered',
                    $name . ' created an account.',
                    BASE_URL . 'admin/users'
                );
            } catch (Throwable) {}

            $this->sendWelcomeMail($email, $name);

            try {
                (new ActivityLogModel())->log(
                    $name, 'Registration', 'Auth',
                    'New user account created after OTP verification', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                );
            } catch (Throwable) {}

            $this->redirect(BASE_URL);

        } catch (Throwable) {
            unset($_SESSION['pending_registration']);
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'This email is already registered. Try signing in.'];
            $this->redirect(BASE_URL . 'register');
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  LOGOUT
    // ══════════════════════════════════════════════════════════════════

    public function logout(): void
    {
        $role = $_SESSION['role'] ?? 'user';

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $p['path'], $p['domain'],
                $p['secure'], $p['httponly']
            );
        }

        session_destroy();

        $this->redirect(BASE_URL . ($role === 'admin' ? 'admin/login' : 'login'));
    }

    // ══════════════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════

    /**
     * Verify Google reCAPTCHA v2 response.
     * Returns true on success, false on failure/missing.
     */
    private function verifyCaptcha(): bool
    {
        $captcha = $_POST['g-recaptcha-response'] ?? '';
        if (empty($captcha)) return false;

        $secret = '6LfWXvcsAAAAAAWI_CMXO9zeDY0kWsckLyEnuSRR';

        $context = stream_context_create([
            'http' => [
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'method'  => 'POST',
                'content' => http_build_query([
                    'secret'   => $secret,
                    'response' => $captcha,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]),
            ],
        ]);

        $result   = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        $response = $result ? json_decode($result, true) : [];

        return (bool)($response['success'] ?? false);
    }

    /**
     * Email format check — PHPMailer is stricter than filter_var.
     */
    private function isValidEmail(string $email): bool
    {
        $email = trim($email);
        if ($email === '') return false;
        if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return \PHPMailer\PHPMailer\PHPMailer::validateAddress($email);
        }
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Email authenticity — checks MX (then A) DNS record for the domain.
     * Blocks fake domains like "test@xyz.zzzzz" at registration time.
     */
    private function emailDomainExists(string $email): bool
    {
        $domain = explode('@', $email, 2)[1] ?? '';
        if ($domain === '') return false;
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    /**
     * Strong password: min 8 chars, upper, lower, digit, special char.
     * Returns error string on failure, null on pass.
     */
    private function checkPasswordStrength(string $password): ?string
    {
        if (strlen($password) < 8)            return 'Password must be at least 8 characters long.';
        if (!preg_match('/[A-Z]/', $password)) return 'Password must contain at least one uppercase letter (A–Z).';
        if (!preg_match('/[a-z]/', $password)) return 'Password must contain at least one lowercase letter (a–z).';
        if (!preg_match('/[0-9]/', $password)) return 'Password must contain at least one number (0–9).';
        if (!preg_match('/[\W_]/', $password)) return 'Password must contain at least one special character (e.g. @, #, !).';
        return null;
    }

    // ──────────────────────────────────────────────────────────────────
    //  WELCOME MAIL
    // ──────────────────────────────────────────────────────────────────

    private function sendOtpMail(string $email, string $name, string $otp): void
    {
        $appName = APP_NAME;
        $subject = "{$appName} email verification OTP";
        $plain = "Hi {$name},\n\n"
               . "Use this OTP to verify your {$appName} account: {$otp}\n\n"
               . "This code expires in 10 minutes.\n\n"
               . "If you did not request this, you can safely ignore this email.";

        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeAppName = htmlspecialchars($appName, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$safeAppName} email verification</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:30px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);max-width:560px;">
        <tr>
          <td style="background:#6c63ff;padding:28px 32px;text-align:center;">
            <h1 style="margin:0;color:#ffffff;font-size:24px;letter-spacing:1px;">{$safeAppName}</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:32px 32px 24px;">
            <h2 style="margin:0 0 12px;color:#333333;font-size:20px;">Verify your email, {$safeName}</h2>
            <p style="margin:0 0 18px;color:#555555;font-size:15px;line-height:1.6;">Enter this OTP to finish creating your account.</p>
            <div style="font-size:32px;font-weight:bold;letter-spacing:8px;color:#222222;text-align:center;background:#f3f3ff;border-radius:8px;padding:18px 12px;margin:0 0 18px;">{$safeOtp}</div>
            <p style="margin:0;color:#777777;font-size:13px;line-height:1.5;">This code expires in 10 minutes. If you did not request this, you can safely ignore this email.</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        if (EMAIL_NOTIFICATIONS !== '1') {
            $this->logMail($email, $subject, 'OTP email skipped.', 'SKIPPED: Email notifications are disabled.', 'otp.log');
            return;
        }

        $smtpHost  = SMTP_HOST;
        $smtpUser  = SMTP_USER;
        $smtpPass  = SMTP_PASS;
        $smtpPort  = (int) SMTP_PORT;
        $fromEmail = SMTP_FROM_EMAIL;

        if (!$this->isValidEmail($fromEmail)) {
            $fromEmail = $smtpUser ?: 'noreply@example.com';
        }

        if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
            $this->logMail($email, $subject, 'OTP email skipped.', 'SKIPPED: SMTP credentials not configured.', 'otp.log');
            return;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host     = $smtpHost;
            $mail->Port     = $smtpPort;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpPort === 465
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => true,
                    'verify_peer_name'  => true,
                    'allow_self_signed' => false,
                ],
            ];

            $host = parse_url(BASE_URL, PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? 'example.com');
            $host = preg_replace('/:\d+$/', '', (string)$host);
            $mail->setFrom($fromEmail, APP_NAME . ' No Reply');
            $mail->addReplyTo('no-reply@' . ($host ?: 'example.com'), 'No Reply');
            $mail->addAddress($email, $name);
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);
            $mail->Subject  = $subject;
            $mail->Body     = $html;
            $mail->AltBody  = $plain;
            $mail->send();

            $this->logMail($email, $subject, '[HTML email]', null, 'otp.log');
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $this->logMail($email, $subject, 'OTP email could not be delivered.', 'PHPMailer: ' . $e->getMessage(), 'otp.log');
        } catch (\Throwable $e) {
            $this->logMail($email, $subject, 'OTP email could not be delivered.', 'Unexpected: ' . $e->getMessage(), 'otp.log');
        }
    }

    private function sendWelcomeMail(string $email, string $name): void
    {
        $appName  = APP_NAME;
        $loginUrl = BASE_URL . 'login';
        $year     = date('Y');
        $subject  = "Welcome to {$appName} — You're all set!";

        $plain = "Hi {$name},\n\n"
               . "Welcome to {$appName}! Your account has been created successfully "
               . "and you are now logged in.\n\n"
               . "Sign in any time at: {$loginUrl}\n\n"
               . "Happy streaming,\nThe {$appName} Team";

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Welcome to {$appName}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f7;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;padding:30px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0"
             style="background:#ffffff;border-radius:8px;overflow:hidden;
                    box-shadow:0 2px 8px rgba(0,0,0,.08);max-width:560px;">
        <tr>
          <td style="background:#6c63ff;padding:28px 32px;text-align:center;">
            <h1 style="margin:0;color:#ffffff;font-size:24px;letter-spacing:1px;">{$appName}</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:32px 32px 24px;">
            <h2 style="margin:0 0 12px;color:#333333;font-size:20px;">Welcome, {$name}! 🎉</h2>
            <p style="margin:0 0 16px;color:#555555;font-size:15px;line-height:1.6;">
              Your account has been created successfully. You are now logged in and ready to start streaming.
            </p>
            <p style="margin:0 0 24px;color:#555555;font-size:15px;line-height:1.6;">
              Whenever you come back, use the button below to sign in.
            </p>
            <p style="text-align:center;margin:0 0 28px;">
              <a href="{$loginUrl}"
                 style="display:inline-block;background:#6c63ff;color:#ffffff;
                        text-decoration:none;font-size:15px;font-weight:bold;
                        padding:12px 32px;border-radius:6px;">
                Go to Sign In
              </a>
            </p>
            <p style="margin:0;color:#999999;font-size:13px;line-height:1.5;">
              If you did not create this account, you can safely ignore this email.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f9f9f9;padding:16px 32px;text-align:center;border-top:1px solid #eeeeee;">
            <p style="margin:0;color:#aaaaaa;font-size:12px;">
              &copy; {$year} {$appName}. All rights reserved.
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        if (EMAIL_NOTIFICATIONS !== '1') {
            $this->logMail($email, $subject, $plain, 'SKIPPED: Email notifications are disabled.');
            return;
        }

        $smtpHost  = SMTP_HOST;
        $smtpUser  = SMTP_USER;
        $smtpPass  = SMTP_PASS;
        $smtpPort  = (int) SMTP_PORT;
        $fromEmail = SMTP_FROM_EMAIL;

        if (!$this->isValidEmail($fromEmail)) {
            $fromEmail = $smtpUser ?: 'noreply@example.com';
        }

        if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
            $this->logMail($email, $subject, $plain, 'SKIPPED: SMTP credentials not configured.');
            return;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host     = $smtpHost;
            $mail->Port     = $smtpPort;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;

            if ($smtpPort === 465) {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtpPort === 587 || $smtpPort === 2525) {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => true,
                    'verify_peer_name'  => true,
                    'allow_self_signed' => false,
                ],
            ];

            $noReply = 'no-reply@' . (parse_url(BASE_URL, PHP_URL_HOST) ?: 'example.com');
            $mail->setFrom($fromEmail, APP_NAME . ' No Reply');
            $mail->addReplyTo($noReply, 'No Reply');
            $mail->addAddress($email, $name);

            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);
            $mail->Subject  = $subject;
            $mail->Body     = $html;
            $mail->AltBody  = $plain;

            $mail->send();
            $this->logMail($email, $subject, '[HTML email]', null);

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $this->logMail($email, $subject, $plain, 'PHPMailer: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->logMail($email, $subject, $plain, 'Unexpected: ' . $e->getMessage());
        }
    }

    private function logMail(string $to, string $subject, string $body, ?string $error = null, string $logFile = 'welcome.log'): void
    {
        $dir = ROOT_PATH . '/storage/mail';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $status = $error === null ? 'SENT_OK' : "FAILED: {$error}";
        $line   = '[' . date('c') . "] [{$status}] To:{$to} | {$subject}\n{$body}\n\n";
        file_put_contents($dir . '/' . $logFile, $line, FILE_APPEND);
    }
}
