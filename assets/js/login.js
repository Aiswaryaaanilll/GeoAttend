/* GeoAttend – Login page interactivity (vanilla JS) */
(function () {
  'use strict';

  const form      = document.getElementById('loginForm');
  const roleInput = document.getElementById('role');
  const tabs      = document.querySelectorAll('.role-tab');
  const emailF    = document.getElementById('emailField');
  const passF     = document.getElementById('passwordField');
  const email     = document.getElementById('email');
  const password  = document.getElementById('password');
  const submitBtn = document.getElementById('submitBtn');
  const pwToggle  = document.getElementById('pwToggle');

  // Role tab switching
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.setAttribute('aria-selected', 'false'));
      tab.setAttribute('aria-selected', 'true');
      roleInput.value = tab.dataset.role;
      email.setAttribute(
        'placeholder',
        tab.dataset.role === 'teacher' ? 'teacher@college.edu' : 'you@college.edu'
      );
    });
  });

  // Show / hide password
  pwToggle.addEventListener('click', () => {
    const isPw = password.type === 'password';
    password.type = isPw ? 'text' : 'password';
    pwToggle.textContent = isPw ? 'Hide' : 'Show';
    pwToggle.setAttribute('aria-label', isPw ? 'Hide password' : 'Show password');
  });

  // Simple client-side validation
  function validate() {
    let ok = true;
    emailF.classList.remove('has-error');
    passF.classList.remove('has-error');

    const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRe.test(email.value.trim())) {
      emailF.classList.add('has-error');
      ok = false;
    }
    if (password.value.length < 6) {
      passF.classList.add('has-error');
      ok = false;
    }
    return ok;
  }

  form.addEventListener('submit', (e) => {
    if (!validate()) {
      e.preventDefault();
      return;
    }
    submitBtn.classList.add('is-loading');
    submitBtn.setAttribute('aria-busy', 'true');
  });

  // Auto-dismiss flash toasts
  document.querySelectorAll('.flash').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .3s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 300);
    }, 4000);
  });
})();
