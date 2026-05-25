<?php

class MessageController extends AdminController
{
   
    public function index(): void
    {
        $model = new MessageModel();
        $messages = $model->getAll();
        $selectedId = (int)($_GET['id'] ?? ($messages[0]['id'] ?? 0));
        $selected = $selectedId > 0 ? $model->find($selectedId) : null;

        if ($selected) {
            $model->markRead((int)$selected['id']);
            WsPublisher::push('messages');
            $selected['is_read'] = 1;
            foreach ($messages as &$message) {
                if ((int)$message['id'] === (int)$selected['id']) {
                    $message['is_read'] = 1;
                    break;
                }
            }
            unset($message);
        }

        $this->adminView('messages', [
            'title' => 'Messages',
            'section' => 'messages',
            'messagesList' => $messages,
            'selectedMessage' => $selected,
        ]);
    }

    public function read(): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $model = new MessageModel();
        if ($id > 0) {
            $model->markRead($id);
        } else {
            $model->markAllRead();
        }
        $this->logAdminAction('Messages Read', 'Messages', 'Marked all messages as read');
        WsPublisher::push('messages');
        $this->back(BASE_URL . '?module=admin&page=messages');
    }

    public function clear(): void
    {
        $this->requireAdmin();
        (new MessageModel())->clearAll();
        $this->logAdminAction('Messages Cleared', 'Messages', 'Cleared all admin messages');
        WsPublisher::push('messages');
        $this->flash('success', 'All messages cleared.');
        $this->redirect(BASE_URL . '?module=admin&page=messages');
    }

    public function approve(): void
    {
        $this->handlePlanDecision('approved');
    }

    public function reject(): void
    {
        $this->handlePlanDecision('rejected');
    }

    private function handlePlanDecision(string $status): void
    {
        $this->requireAdmin();
        $id = $this->readId();
        $model = new MessageModel();
        $message = $model->find($id);

        if (!$message || ($message['request_type'] ?? '') !== 'plan_request' || ($message['request_status'] ?? '') !== 'pending') {
            $this->flash('error', 'This plan request is no longer pending.');
               $this->redirect(BASE_URL . '?module=admin&page=messages&id=' . $id);
        }

        if ($status === 'approved') {
            try {
                $pdo = Database::getInstance();
                $pdo->beginTransaction();

                $userId = (int)$message['user_id'];
                $planId = (int)$message['plan_id'];
                $duration = max(1, (int)($message['duration_days'] ?? 30));
                $amount = (float)($message['price'] ?? 0);
                $method = $this->normalisePaymentMethod((string)($message['payment_method'] ?? 'Card'));
                $paymentId = (int)($message['payment_id'] ?? 0);

                $pdo->prepare("UPDATE users SET plan_id = :plan WHERE id = :user")
                    ->execute([':plan' => $planId, ':user' => $userId]);

                $pdo->prepare("UPDATE subscriptions SET status = 'expired' WHERE user_id = :user AND status = 'active'")
                    ->execute([':user' => $userId]);

                $pdo->prepare(
                    "INSERT INTO subscriptions (user_id, plan_id, starts_at, expires_at, status, created_at)
                     VALUES (:user, :plan, CURDATE(), DATE_ADD(CURDATE(), INTERVAL {$duration} DAY), 'active', NOW())"
                )->execute([':user' => $userId, ':plan' => $planId]);

                if ($paymentId > 0) {
                    $pdo->prepare(
                        "UPDATE payments
                         SET user_id = :user, plan_id = :plan, amount = :amount, method = :method, status = 'success', paid_at = NOW()
                         WHERE id = :payment"
                    )->execute([
                        ':user' => $userId,
                        ':plan' => $planId,
                        ':amount' => $amount,
                        ':method' => $method,
                        ':payment' => $paymentId,
                    ]);
                } else {
                    $txnId = $this->transactionId($userId);
                    $pdo->prepare(
                        "INSERT INTO payments (txn_id, user_id, plan_id, amount, method, status, paid_at, created_at)
                         VALUES (:txn, :user, :plan, :amount, :method, 'success', NOW(), NOW())"
                    )->execute([
                        ':txn' => $txnId,
                        ':user' => $userId,
                        ':plan' => $planId,
                        ':amount' => $amount,
                        ':method' => $method,
                    ]);
                    $paymentId = (int)$pdo->lastInsertId();
                    $pdo->prepare("UPDATE admin_messages SET payment_id = :payment WHERE id = :message")
                        ->execute([':payment' => $paymentId, ':message' => $id]);
                }

                $model->updateRequestStatus($id, 'approved');
                $pdo->commit();
            } catch (Throwable $error) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $this->flash('error', 'Could not approve this plan request.');
                $this->redirect(BASE_URL . '?module=admin&page=messages&id=' . $id);
            }
        } else {
            try {
                $pdo = Database::getInstance();
                $pdo->beginTransaction();
                $paymentId = (int)($message['payment_id'] ?? 0);
                if ($paymentId > 0) {
                    $pdo->prepare("UPDATE payments SET status = 'failed', paid_at = NULL WHERE id = :payment")
                        ->execute([':payment' => $paymentId]);
                }
                $model->updateRequestStatus($id, 'rejected');
                $pdo->commit();
            } catch (Throwable) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $this->flash('error', 'Could not reject this plan request.');
                   $this->redirect(BASE_URL . '?module=admin&page=messages&id=' . $id);
            }
        }

        $this->logAdminAction(ucfirst($status) . ' Plan Request', 'Messages', 'Message ID ' . $id);
        WsPublisher::push('messages');
        WsPublisher::push('payments');
        if ($status === 'approved') {
            WsPublisher::push('subscriptions');
        }
        $targetUserId = (int)($message['user_id'] ?? 0);
        if ($targetUserId > 0) {
            WsPublisher::push('subscription', ['audience' => 'user', 'user_id' => $targetUserId]);
        }
        $this->flash('success', $status === 'approved' ? 'Plan request approved. Payment and subscription were created.' : 'Plan request rejected.');
          $this->redirect(BASE_URL . '?module=admin&page=messages&id=' . $id);
    }

    private function normalisePaymentMethod(string $method): string
    {
        return in_array($method, ['UPI', 'Card', 'Wallet', 'NetBanking', 'Paypal'], true) ? $method : 'Card';
    }

    private function transactionId(int $userId): string
    {
        return substr('TXN-' . date('YmdHis') . '-' . $userId . '-' . strtoupper(bin2hex(random_bytes(2))), 0, 40);
    }
}
