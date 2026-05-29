<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>
<?php
$messagesList = $messagesList ?? [];
$selectedMessage = $selectedMessage ?? null;
$statusClass = function (string $status): string {
    return match ($status) {
        'pending' => 'pill-amber',
        'approved' => 'pill-green',
        'success' => 'pill-green',
        'rejected' => 'pill-red',
        'failed' => 'pill-red',
        default => 'pill-blue',
    };
};
?>

<div class="page-header">
    <div>
        <p>Read user messages and approve plan requests</p>
        <h1>Messages</h1>
    </div>
    <div class="page-actions">
          <form method="post" action="<?= BASE_URL ?>?module=admin&page=messages&action=read">
            <button class="btn btn-ghost" type="submit">Mark all read</button>
        </form>
          <form method="post" action="<?= BASE_URL ?>?module=admin&page=messages&action=clear" data-confirm="Clear all messages?">
            <button class="btn btn-ghost" type="submit">Clear messages</button>
        </form>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <div class="admin-toast <?= h($flash['type'] ?? 'success') ?> show"><?= h($flash['message'] ?? '') ?></div>
<?php endif; ?>

<section class="msg-layout">
    <div class="panel msg-inbox-panel">
        <div class="panel-head">
            <span class="panel-title">Inbox</span>
            <span class="panel-sub"><?= number_format(count($messagesList)) ?> total</span>
        </div>
        <div class="msg-list">
            <?php foreach ($messagesList as $item): ?>
                <a class="msg-item <?= !empty($item['is_read']) ? '' : 'unread' ?> <?= $selectedMessage && (int)$selectedMessage['id'] === (int)$item['id'] ? 'active' : '' ?>"
                    href="<?= BASE_URL ?>?module=admin&page=messages&id=<?= (int)$item['id'] ?>">
                    <span class="msg-dot"></span>
                    <span class="msg-main">
                        <strong><?= h($item['subject']) ?></strong>
                        <small><?= h($item['sender_name']) ?> · <?= ago($item['created_at'] ?? null) ?></small>
                    </span>
                    <?php if (($item['request_status'] ?? 'none') !== 'none'): ?>
                        <span class="pill <?= $statusClass($item['request_status']) ?>"><?= h(ucfirst($item['request_status'])) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            <?php if (empty($messagesList)): ?>
                <div class="empty-state">No messages found.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel msg-reader-panel">
        <?php if ($selectedMessage): ?>
            <div class="panel-head">
                <div>
                    <span class="panel-title"><?= h($selectedMessage['subject']) ?></span>
                    <div class="msg-reader-meta">
                        <?= h($selectedMessage['sender_name']) ?> · <?= h($selectedMessage['sender_email'] ?? '') ?> · <?= ago($selectedMessage['created_at'] ?? null) ?>
                    </div>
                </div>
                <?php if (($selectedMessage['request_status'] ?? 'none') !== 'none'): ?>
                    <span class="pill <?= $statusClass($selectedMessage['request_status']) ?>"><?= h(ucfirst($selectedMessage['request_status'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="panel-body msg-reader-body">
                <div class="msg-body"><?= nl2br(h($selectedMessage['body'] ?? '')) ?></div>

                <?php if (($selectedMessage['request_type'] ?? '') === 'plan_request'): ?>
                    <div class="msg-plan-card">
                        <div>
                            <div class="msg-plan-label">Requested Plan</div>
                            <div class="msg-plan-name"><?= h($selectedMessage['plan_name'] ?? 'Plan') ?></div>
                            <div class="msg-plan-price"><?= h($selectedMessage['currency'] ?? 'USD') ?> <?= number_format((float)($selectedMessage['price'] ?? 0), 2) ?></div>
                        </div>
                        <div>
                            <div class="msg-plan-label">User</div>
                            <div class="msg-plan-name"><?= h($selectedMessage['user_name'] ?? $selectedMessage['sender_name']) ?></div>
                            <div class="msg-plan-price"><?= h($selectedMessage['user_email'] ?? $selectedMessage['sender_email']) ?></div>
                        </div>
                        <div>
                            <div class="msg-plan-label">Payment</div>
                            <div class="msg-plan-name"><?= h($selectedMessage['payment_txn_id'] ?? 'Pending request') ?></div>
                            <div class="msg-plan-price">
                                <span class="pill <?= $statusClass($selectedMessage['payment_status'] ?? 'pending') ?>">
                                    <?= h(ucfirst($selectedMessage['payment_status'] ?? 'pending')) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if (($selectedMessage['request_status'] ?? '') === 'pending'): ?>
                        <div class="msg-actions">
                               <form method="post" action="<?= BASE_URL ?>?module=admin&page=messages&action=approve">
                                <input type="hidden" name="id" value="<?= (int)$selectedMessage['id'] ?>">
                                <button class="btn btn-primary" type="submit">Approve</button>
                            </form>
                            <form method="post" action="<?= BASE_URL ?>?module=admin&page=messages&action=reject">
                                <input type="hidden" name="id" value="<?= (int)$selectedMessage['id'] ?>">
                                <button class="btn btn-ghost" type="submit">Reject</button>
                            </form>
                              <a class="btn btn-ghost" href="<?= BASE_URL ?>?module=admin&page=payments">View Payments</a>
                        </div>
                    <?php else: ?>
                        <div class="msg-actions">
                           <a class="btn btn-ghost" href="<?= BASE_URL ?>?module=admin&page=payments">View Payments</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="panel-body">
                <div class="empty-state">Select a message to read it.</div>
            </div>
        <?php endif; ?>
    </div>
</section>
