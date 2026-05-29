/**
 * VideoStream — auth.js
 * Handles: password visibility toggle, password strength meter,
 * form validation, loading states, navbar scroll effect
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ── Password toggle ─────────────────────────────────── */
    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(btn.dataset.target);
            if (!target) return;
            const isText = target.type === 'text';
            target.type = isText ? 'password' : 'text';
            btn.textContent = isText ? '👁' : 'hide';
            btn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
        });
    });

    /* ── Password strength meter ─────────────────────────── */
    const pwInput = document.getElementById('reg_password');
    if (pwInput) {
        pwInput.addEventListener('input', function () {
            const val = pwInput.value;
            const strength = calcStrength(val);
            const bars = [
                document.getElementById('sb1'),
                document.getElementById('sb2'),
                document.getElementById('sb3'),
                document.getElementById('sb4'),
            ];
            const colors = ['#e50914', '#f59e0b', '#888888', '#22c55e'];
            const labels = ['Weak', 'Fair', 'Good', 'Strong'];
            bars.forEach(function (bar, i) {
                if (!bar) return;
                bar.style.background = i < strength ? colors[strength - 1] : 'rgba(255,255,255,.1)';
            });
            const txt = document.getElementById('strengthText');
            if (txt) {
                txt.textContent = val.length === 0 ? '' : labels[strength - 1] || 'Very Weak';
                txt.style.color = strength > 0 ? colors[strength - 1] : '#555';
            }
        });
    }

    function calcStrength(pw) {
        let score = 0;
        if (pw.length >= 6)  score++;
        if (pw.length >= 10) score++;
        if (/[A-Z]/.test(pw) || /[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return Math.min(4, score);
    }

    /* ── Admin login form ────────────────────────────────── */
    const adminForm = document.getElementById('adminLoginForm');
    if (adminForm) {
        adminForm.addEventListener('submit', function (e) {
            let valid = true;
            const email    = document.getElementById('admin_email');
            const password = document.getElementById('admin_password');

            if (email && !email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                showError('email-error', 'Please enter a valid email address.');
                email.classList.add('input-error');
                valid = false;
            } else {
                hideError('email-error');
                if (email) email.classList.remove('input-error');
            }

            if (password && password.value.length < 1) {
                showError('pw-error', 'Password is required.');
                password.classList.add('input-error');
                valid = false;
            } else {
                hideError('pw-error');
                if (password) password.classList.remove('input-error');
            }

            if (!valid) { e.preventDefault(); return; }
            setLoading('adminBtnText', 'adminBtnSpinner', 'adminSubmitBtn', true);
        });
    }

    /* ── User login form ─────────────────────────────────── */
    const userForm = document.getElementById('userLoginForm');
    if (userForm) {
        userForm.addEventListener('submit', function () {
            setLoading('userBtnText', 'userBtnSpinner', 'userSubmitBtn', true);
        });
    }

    /* ── Register form ───────────────────────────────────── */
    const regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            if (!regForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                regForm.classList.add('was-validated');
                const firstInvalid = regForm.querySelector(':invalid');
                if (firstInvalid) firstInvalid.focus();
                return;
            }

            const pw = document.getElementById('reg_password');
            if (pw && pw.value.length < 6) {
                e.preventDefault();
                e.stopPropagation();
                regForm.classList.add('was-validated');
                pw.focus();
                const txt = document.getElementById('strengthText');
                if (txt) { txt.textContent = 'Password must be at least 6 characters'; txt.style.color = '#e50914'; }
                return;
            }
            setLoading('regBtnText', 'regBtnSpinner', 'regSubmitBtn', true);
        });
    }

    /* ── User nav scroll effect ──────────────────────────── */
    const userNav = document.querySelector('.user-nav');
    if (userNav) {
        window.addEventListener('scroll', function () {
            userNav.classList.toggle('scrolled', window.scrollY > 20);
        }, { passive: true });
    }

    /* ── Auto-dismiss flash alerts ───────────────────────── */
    document.querySelectorAll('.flash-alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity .5s, max-height .5s';
            alert.style.opacity = '0';
            alert.style.maxHeight = '0';
            alert.style.overflow = 'hidden';
            alert.style.marginBottom = '0';
        }, 5000);
    });

    /* ── Input focus rings ───────────────────────────────── */
    document.querySelectorAll('.vs-input').forEach(function (input) {
        input.addEventListener('focus', function () {
            this.closest('.input-wrapper')?.classList.add('focused');
        });
        input.addEventListener('blur', function () {
            this.closest('.input-wrapper')?.classList.remove('focused');
        });
    });

    /* ── Helpers ─────────────────────────────────────────── */
    function showError(id, msg) {
        const el = document.getElementById(id);
        if (el) { el.textContent = msg; el.style.display = 'block'; }
    }
    function hideError(id) {
        const el = document.getElementById(id);
        if (el) { el.style.display = 'none'; }
    }
    function setLoading(textId, spinnerId, btnId, loading) {
        const txt = document.getElementById(textId);
        const sp  = document.getElementById(spinnerId);
        const btn = document.getElementById(btnId);
        if (txt) txt.style.display = loading ? 'none' : '';
        if (sp)  sp.style.display  = loading ? '' : 'none';
        if (btn) btn.disabled = loading;
    }
});
