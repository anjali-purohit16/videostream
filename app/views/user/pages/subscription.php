<div data-feed-target="subscription">
      <?php if ($subscription): ?>
      <div class="u-sub-card" style="margin-bottom:20px;">
        <div class="u-sub-header">
          <div>
            <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--red);margin-bottom:4px;">Current Plan</div>
            <div class="u-sub-plan"><?= h($subscription['plan_name'] ?? 'Free') ?></div>
          </div>
          <span class="u-sub-badge"><?= h(ucfirst($subscription['sub_status'] ?? 'active')) ?></span>
        </div>
        <div class="u-sub-grid">
          <div class="u-sub-item"><div class="u-sub-label">Price</div><div class="u-sub-value"><?= h($subscription['currency'] ?? 'USD') ?> <?= number_format((float)($subscription['price'] ?? 0), 2) ?>/mo</div></div>
          <div class="u-sub-item"><div class="u-sub-label">Started</div><div class="u-sub-value"><?= $subscription['starts_at'] ? date('M j, Y', strtotime($subscription['starts_at'])) : '—' ?></div></div>
          <div class="u-sub-item"><div class="u-sub-label">Expires</div><div class="u-sub-value"><?= $subscription['expires_at'] ? date('M j, Y', strtotime($subscription['expires_at'])) : '—' ?></div></div>
          <div class="u-sub-item"><div class="u-sub-label">Days Left</div><div class="u-sub-value" style="color:var(--<?= (int)($subscription['days_remaining'] ?? 0) < 7 ? 'red' : 'green' ?>)"><?= max(0, (int)($subscription['days_remaining'] ?? 0)) ?> days</div></div>
          <div class="u-sub-item"><div class="u-sub-label">Status</div><div class="u-sub-value"><span class="u-pill u-pill-<?= ($subscription['sub_status'] ?? '') === 'active' ? 'green' : 'red' ?>"><?= h(ucfirst($subscription['sub_status'] ?? '')) ?></span></div></div>
        </div>
      </div>
      <?php else: ?>
      <div class="u-upgrade-banner" style="margin-bottom:20px;">
        <div>
          <div class="u-upgrade-kicker">No Active Plan</div>
          <div class="u-upgrade-title">You are on the Free plan</div>
          <div class="u-upgrade-sub">Upgrade to unlock HD streaming, downloads and no ads.</div>
        </div>
      </div>
      <?php endif; ?>
      </div>

      <!-- Plan options -->
      <div class="u-section-header" style="margin-bottom:16px;"><span class="u-section-title"><?= u_icon('bi-gem') ?> Available Plans</span></div>
      <div class="u-plan-grid">
        <?php foreach ($plans as $plan): ?>
        <?php
          $planFeatures = [
            'Free' => ['Limited monthly streaming', '720p quality', 'Standard support'],
            'Basic' => ['Unlimited streaming', '1080p quality', 'Priority support'],
            'Premium' => ['Unlimited streaming', '4K quality', 'Downloads and no ads'],
          ][$plan['name']] ?? ['Streaming access', 'Admin approval required'];
          $isCurrentPlan = strtolower($userProfile['plan'] ?? '') === strtolower($plan['name']);
          $planPrice = ($plan['currency'] ?? 'USD') . ' ' . number_format((float)$plan['price'], 2);
        ?>
        <div class="u-plan-card <?= $isCurrentPlan ? 'active' : '' ?>">
          <?php if ($isCurrentPlan): ?>
          <div class="u-plan-current">CURRENT PLAN</div>
          <?php endif; ?>
          <div class="u-plan-name"><?= h($plan['name']) ?></div>
          <div class="u-plan-price"><?= h($planPrice) ?><span>/mo</span></div>
          <?php foreach ($planFeatures as $f): ?>
          <div class="u-plan-feature"><?= u_icon('bi-check2') ?> <?= h($f) ?></div>
          <?php endforeach; ?>
          <?php if (!$isCurrentPlan): ?>
          <button type="button" class="u-btn u-btn-red js-plan-open"
                  data-plan-id="<?= (int)$plan['id'] ?>"
                  data-plan-name="<?= h($plan['name']) ?>"
                  data-plan-price="<?= h($planPrice) ?>">
            Get <?= h($plan['name']) ?>
          </button>
          <?php else: ?>
          <div class="u-btn u-btn-ghost active-plan-btn">Active</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="u-panel u-payment-panel" id="paymentRequestPanel" style="display:none;">
        <div class="u-panel-head">
          <span class="u-panel-title"><?= u_icon('bi-receipt') ?> Payment Request</span>
        </div>
        <div class="u-panel-body">
          <div class="u-payment-summary">
            <div><div class="u-info-lbl">Selected Plan</div><div class="u-info-val" id="paymentPlanName"></div></div>
            <div><div class="u-info-lbl">Amount</div><div class="u-info-val" id="paymentPlanPrice"></div></div>
            <div><div class="u-info-lbl">Approval</div><div class="u-info-val">Admin review required</div></div>
          </div>
          <div class="u-payment-form">
            <input type="hidden" id="paymentPlanId">
            <label>Payment Method
              <select id="paymentMethod">
                <option value="UPI">UPI</option>
                <option value="Card">Card</option>
                <option value="NetBanking">NetBanking</option>
                <option value="Wallet">Wallet</option>
              </select>
            </label>
            <label>Payment Note
              <textarea id="paymentNote" rows="3" placeholder="Transaction ID or note for admin"></textarea>
            </label>
            <div class="u-payment-actions">
              <button type="button" class="u-btn u-btn-red" id="sendPlanRequestBtn"><?= u_icon('bi-send') ?> Send to Admin</button>
              <button type="button" class="u-btn u-btn-ghost" id="cancelPlanRequestBtn">Cancel</button>
              <span id="paymentRequestMsg"></span>
            </div>
          </div>
        </div>
      </div>