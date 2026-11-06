<?php
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . (($_SESSION['role'] ?? '') === 'ADMIN' ? '?page=admin_home' : '?page=home_main'));
    exit;
}
if (empty($_SESSION['registration_csrf'])) {
    $_SESSION['registration_csrf'] = bin2hex(random_bytes(32));
}
$csrf = htmlspecialchars($_SESSION['registration_csrf'], ENT_QUOTES);
$api  = fn(string $file) => htmlspecialchars(BASE_URL . 'backend/api/' . $file, ENT_QUOTES);
$inputClass = 'mt-1.5 w-full rounded-lg bg-blue-50/90 px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600';
$buttonClass = 'mt-5 w-full rounded-lg bg-blue-100 py-3 text-sm font-bold transition hover:bg-blue-300 disabled:opacity-60';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account | CMU Enrollment System</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL . 'frontend/assets/css/font-style.css', ENT_QUOTES) ?>">
</head>
<body class="min-h-screen bg-cover bg-center bg-no-repeat text-[#0b2a4f]" style="background-image: url('<?= htmlspecialchars(BASE_URL . 'frontend/assets/img/CMUBG.png', ENT_QUOTES) ?>');">
  <main class="flex min-h-screen items-center justify-center p-4">
    <section class="w-full max-w-sm rounded-2xl border border-white/40 bg-white/30 p-6 shadow-xl backdrop-blur-md">
      <h1 class="mt-6 text-3xl font-bold">Create Account</h1>
      <p id="step-hint" class="mt-1 text-xs">Enter your student information to request an account.</p>
      <div id="form-message" class="mt-4 hidden rounded-lg px-3 py-2 text-sm" role="status" aria-live="polite"></div>

      <form id="registration-form" class="mt-4" action="<?= $api('submit_registration_request.php') ?>" method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <label for="student_number" class="block text-sm font-semibold">Student Number</label>
        <input id="student_number" name="student_number" type="text" maxlength="50" autocomplete="off" required placeholder="Enter your student number" class="<?= $inputClass ?>">
        <label for="last_name" class="mt-4 block text-sm font-semibold">Last Name</label>
        <input id="last_name" name="last_name" type="text" maxlength="100" autocomplete="family-name" required placeholder="Enter your last name" class="<?= $inputClass ?>">
        <label for="date_of_birth" class="mt-4 block text-sm font-semibold">Date of Birth</label>
        <input id="date_of_birth" name="date_of_birth" type="date" required max="<?= date('Y-m-d') ?>" class="<?= $inputClass ?>">
        <label for="password" class="mt-4 block text-sm font-semibold">Password</label>
        <div class="relative">
          <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required placeholder="At least 8 characters" class="<?= $inputClass ?> pr-12">
          <button type="button" id="toggle-password" class="absolute right-3 top-1/2 -translate-y-1/2" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
        <label for="confirm_password" class="mt-4 block text-sm font-semibold">Confirm Password</label>
        <input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required placeholder="Re-enter your password" class="<?= $inputClass ?>">
        <p class="mt-3 text-xs">Use at least 8 characters, including a letter and a number. Your account will be activated only after Registrar approval.</p>
        <button type="submit" class="<?= $buttonClass ?>">Submit Registration Request</button>
      </form>

      <p class="mt-5 text-center text-xs">Already have an account? <a href="<?= htmlspecialchars(BASE_URL . '?page=login', ENT_QUOTES) ?>" class="font-bold text-blue-700 hover:underline">Log in</a></p>
    </section>
  </main>
<script>
$(function () {
  const form = $('#registration-form');
  const message = $('#form-message');
  const button = form.find('button[type="submit"]');
  function showMessage(ok, text) {
    message.removeClass('hidden bg-red-50 text-red-700 bg-green-50 text-green-800')
      .addClass(ok ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-700').text(text);
  }
  $('#toggle-password').on('click', function () {
    const input = $('#password');
    const show = input.attr('type') === 'password';
    input.attr('type', show ? 'text' : 'password');
    $(this).attr('aria-label', show ? 'Hide password' : 'Show password').find('i').toggleClass('bi-eye bi-eye-slash');
  });
  form.on('submit', function (event) {
    if ($('#password').val() !== $('#confirm_password').val()) {
      event.preventDefault();
      showMessage(false, 'The passwords do not match.');
      return;
    }
    if (!/(?=.*[A-Za-z])(?=.*[0-9]).{8,}/.test($('#password').val())) {
      event.preventDefault();
      showMessage(false, 'Use at least 8 characters with a letter and a number.');
      return;
    }
    event.preventDefault();
    message.addClass('hidden');
    button.prop('disabled', true).text('Submitting...');
    $.ajax({url: form.attr('action'), method: 'POST', data: form.serialize(), dataType: 'json'})
      .done(function (response) {
        showMessage(true, response.message || 'Your request is pending Registrar approval.');
        form.find('input:not([type="hidden"])').prop('disabled', true);
        button.prop('disabled', true).text('Request Submitted');
        showMessage(true, (response.message || 'Registration request submitted.') + ' Redirecting to Login in 3 seconds...');
        setTimeout(function () { window.location.href = <?= json_encode(BASE_URL . '?page=login') ?>; }, 3000);
      })
      .fail(function (xhr) {
        const response = xhr.responseJSON || {};
        showMessage(false, response.message || 'Unable to submit registration request.');
        button.prop('disabled', false).text('Submit Registration Request');
      });
  });
});
</script>
</body>
</html>
