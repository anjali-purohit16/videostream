      <div id="profileViewMode">

        <div class="u-profile-card" style="margin-bottom:20px;">
          <!-- Header row: avatar+name on left, Edit button on right -->
          <div class="u-profile-header" style="justify-content:space-between;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:16px;">
              <div class="u-profile-avatar-lg" id="profileAvatarDisp"><?= h($userInitials) ?></div>
              <div>
                <div id="profileDispName" style="font-family:'Bebas Neue',sans-serif;font-size:26px;letter-spacing:1.5px;"><?= h($userName) ?></div>
                <div style="font-size:13px;color:var(--muted2);margin-top:2px;"><?= h($userProfile['email'] ?? '') ?></div>
                <div style="margin-top:6px;"><span class="u-pill u-pill-<?= strtolower($userProfile['status'] ?? 'active') === 'active' ? 'green' : 'red' ?>"><?= h(ucfirst($userProfile['status'] ?? 'Active')) ?></span></div>
              </div>
            </div>
            <!-- Edit Profile button - top-right -->
            <button id="profileEditOpenBtn" type="button" class="u-btn u-btn-ghost"
                    style="display:flex;align-items:center;gap:7px;padding:9px 18px;font-size:13px;border-radius:9px;flex-shrink:0;align-self:flex-start;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Profile
            </button>
          </div>

          <!-- Info grid -->
          <div class="u-profile-info-grid">
            <div class="u-info-box">
              <div class="u-info-lbl">Email</div>
              <div class="u-info-val"><?= h($userProfile['email'] ?? '—') ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Current Plan</div>
              <div class="u-info-val"><?= h($userProfile['plan'] ?? '—') ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Member Since</div>
              <div class="u-info-val"><?= $userProfile['joined_at'] ? date('M j, Y', strtotime($userProfile['joined_at'])) : '—' ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Last Active</div>
              <div class="u-info-val"><?= $userProfile['last_seen'] ? ago($userProfile['last_seen']) : 'Just now' ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Videos Watched</div>
              <div class="u-info-val" data-history-count><?= number_format((int)$historyCount) ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Watchlist Items</div>
              <div class="u-info-val" data-watchlist-count><?= number_format((int)$wishlistCount) ?></div>
            </div>
          </div>
        </div>

        <!-- Security summary (view mode only) -->
        <div class="u-section-header" style="margin-bottom:16px;">
          <span class="u-section-title"><?= u_icon('bi-shield-lock') ?> Security</span>
        </div>
        <div class="u-panel">
          <div class="u-panel-body">
            <div class="u-profile-info-grid">
              <div class="u-info-box"><div class="u-info-lbl">Password</div><div class="u-info-val">•••••••••• (encrypted)</div></div>
              <div class="u-info-box"><div class="u-info-lbl">Account Status</div><div class="u-info-val"><span class="u-pill u-pill-green">Active</span></div></div>
              <div class="u-info-box"><div class="u-info-lbl">Session</div><div class="u-info-val">Active now</div></div>
            </div>
          </div>
        </div>

        <div class="u-section-header" style="margin:22px 0 16px;">
          <span class="u-section-title"><?= u_icon('bi-exclamation-triangle') ?> Danger Zone</span>
        </div>
        <div class="u-panel">
          <div class="u-panel-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
              <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px;">Delete account</div>
              <div style="font-size:12px;color:var(--muted2);">This permanently removes your account and related activity.</div>
            </div>
            <form method="post"
                  action="<?= BASE_URL ?>?action=delete_account"
                  onsubmit="return confirm('Delete your account permanently? This cannot be undone.');"
                  style="margin:0;">
              <button type="submit" class="u-btn u-btn-red" style="padding:10px 18px;font-size:13px;">
                <?= u_icon('bi-trash') ?> Delete Account
              </button>
            </form>
          </div>
        </div>

      </div><!-- /#profileViewMode -->

      <div id="profileEditMode" style="display:none;">

        <!-- Header matching view mode layout -->
        <div class="u-profile-card" style="margin-bottom:20px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
            <div style="display:flex;align-items:center;gap:14px;">
              <div class="u-profile-avatar-lg"><?= h($userInitials) ?></div>
              <div>
                <div style="font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:1.5px;color:var(--red);">Edit Profile</div>
                <div style="font-size:12px;color:var(--muted2);margin-top:2px;">Update your name or change your password</div>
              </div>
            </div>
            <!-- Cancel button - top-right -->
            <button id="profileEditCancelBtn" type="button" class="u-btn u-btn-ghost"
                    style="display:flex;align-items:center;gap:7px;padding:9px 18px;font-size:13px;border-radius:9px;flex-shrink:0;align-self:flex-start;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              Cancel
            </button>
          </div>

          <!-- Alert message -->
          <div id="profileEditMsg" style="display:none;margin-bottom:18px;padding:11px 16px;border-radius:9px;font-size:13px;font-weight:500;"></div>

          <div style="margin-bottom:22px;">
            <div style="font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              Account Details
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;">
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">Display Name</label>
                <input id="profileName" type="text" value="<?= h($userName) ?>"
                       autocomplete="name"
                       style="width:100%;padding:10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border-color .2s;"
                       onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
              </div>
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">Email <span style="color:var(--muted2);font-weight:400;">(read-only)</span></label>
                <input type="email" value="<?= h($userProfile['email'] ?? '') ?>" disabled
                       style="width:100%;padding:10px 14px;background:var(--card2);border:1px solid var(--border);border-radius:8px;color:var(--muted);font-size:14px;cursor:not-allowed;">
              </div>
            </div>
          </div>

          <div style="margin-bottom:24px;">
            <div style="font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              Change Password <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:var(--muted2);">— leave blank to keep current password</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;">
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">Current Password</label>
                <div style="position:relative;">
                  <input id="profileCurrentPw" type="password" placeholder="Enter your current password"
                         autocomplete="current-password"
                         style="width:100%;padding:10px 42px 10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border-color .2s;"
                         onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
                  <span onclick="togglePw('profileCurrentPw',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:13px;" title="Show/hide"><?= u_icon('bi-eye') ?></span>
                </div>
              </div>
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">New Password</label>
                <div style="position:relative;">
                  <input id="profileNewPw" type="password" placeholder="Min 6 characters"
                         autocomplete="new-password"
                         style="width:100%;padding:10px 42px 10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border-color .2s;"
                         onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
                  <span onclick="togglePw('profileNewPw',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:13px;" title="Show/hide"><?= u_icon('bi-eye') ?></span>
                </div>
              </div>
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">Confirm New Password</label>
                <div style="position:relative;">
                  <input id="profileConfirmPw" type="password" placeholder="Repeat new password"
                         autocomplete="new-password"
                         style="width:100%;padding:10px 42px 10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border-color .2s;"
                         onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
                  <span onclick="togglePw('profileConfirmPw',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:13px;" title="Show/hide"><?= u_icon('bi-eye') ?></span>
                </div>
              </div>
            </div>
            <!-- Strength bar (visible only when typing new password) -->
            <div id="pwStrengthWrap" style="display:none;margin-top:10px;max-width:300px;">
              <div style="height:4px;border-radius:4px;background:var(--border);overflow:hidden;">
                <div id="pwStrengthBar" style="height:100%;width:0%;border-radius:4px;transition:width .3s,background .3s;"></div>
              </div>
              <div id="pwStrengthLbl" style="font-size:11px;color:var(--muted);margin-top:4px;"></div>
            </div>
          </div>

          <!-- Action row -->
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <button id="profileSaveBtn" type="button" class="u-btn u-btn-red" style="padding:11px 32px;font-size:14px;font-weight:700;letter-spacing:.5px;">
              <?= u_icon('bi-check2') ?> Save Changes
            </button>
            <button id="profileEditCancelBtn2" type="button" class="u-btn u-btn-ghost" style="padding:11px 24px;font-size:14px;">
              Cancel
            </button>
          </div>
        </div>

      </div><!-- /#profileEditMode -->
