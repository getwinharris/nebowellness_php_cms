document.addEventListener('click', async (event) => {
  const button = event.target.closest('.otp-send');
  if (!button) return;
  const group = button.closest('.email-otp');
  const form = button.closest('form');
  const emailId = group.dataset.emailInput || 'contact-email';
  const email = form.querySelector('#' + CSS.escape(emailId));
  const status = group.querySelector('.otp-status');
  if (!email || !email.checkValidity()) { email?.reportValidity(); return; }
  button.disabled = true;
  status.textContent = 'Sending code…';
  const body = new FormData();
  body.set('_csrf', form.querySelector('[name="_csrf"]').value);
  body.set('email', email.value);
  body.set('context', group.dataset.otpContext || 'contact');
  try {
    const response = await fetch('/forms/email-otp', {method:'POST', body, headers:{Accept:'application/json'}});
    const result = await response.json();
    status.textContent = result.message || result.error || 'Could not send the code.';
    if (response.ok) setTimeout(() => { button.disabled = false; button.textContent = 'Resend code'; }, 60000);
    else button.disabled = false;
  } catch (_) { status.textContent = 'Could not send the code. Please try again.'; button.disabled = false; }
});
