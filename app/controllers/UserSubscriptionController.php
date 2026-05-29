<?php

class UserSubscriptionController extends BaseController
{
    public function subscription_request(): void
    {
        header('Content-Type: application/json');
        $userId = $this->requireActiveUser(true);
        $planId = (int)($_POST['plan_id'] ?? 0);
        $method = trim($_POST['payment_method'] ?? 'Manual payment');
        $note = trim($_POST['payment_note'] ?? '');

        try {
            $userModel = new UserModel();
            $context = $userModel->getSubscriptionRequestContext($userId, $planId);
            $user = $context['user'];
            $plan = $context['plan'];

            if (!$user || !$plan) {
                echo json_encode(['ok' => false, 'message' => 'Invalid plan request.']);
                exit;
            }

            (new MessageModel())->createPlanRequest(
                $userId,
                $planId,
                $user['name'],
                $user['email'],
                $plan['name'],
                $method,
                $note
            );

            WsPublisher::push('messages');
            echo json_encode(['ok' => true, 'message' => 'Plan request sent to admin for approval.']);
        } catch (Throwable) {
            echo json_encode(['ok' => false, 'message' => 'Could not send plan request.']);
        }
        exit;
    }
}
