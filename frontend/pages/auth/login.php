<?php

if (!empty($_SESSION['student_id'])) {
    header('Location: ' . BASE_URL . '?page=home_main');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | CMU Enrollment System</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= BASE_URL . 'frontend/assets/css/font-style.css' ?>">
</head>

<body class="min-h-screen bg-cover bg-center bg-no-repeat text-[#0b2a4f]"
      style="background-image: url('<?= BASE_URL . 'frontend/assets/img/cmu.png' ?>');">

  <main class="flex min-h-screen items-center justify-center p-4">

    <!-- Glass card -->
    <section class="w-full max-w-sm rounded-2xl border border-white/40 bg-white/30 p-6 shadow-xl backdrop-blur-md">

      <!-- Top row: back + register -->
      <div class="flex items-center justify-between text-xs">
        <a href="<?= BASE_URL ?>" aria-label="Go back"
           class="rounded p-1 text-base hover:bg-white/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
          <i class="bi bi-arrow-left"></i>
        </a>
      </div>

      <!-- Heading -->
      <h1 class="mt-6 text-3xl font-bold">Welcome Back!</h1>
      <p class="mt-1 text-xs">Log in to your account to continue to the CMU Enrollment System</p>

      <!-- Form message (success / error) -->
      <div id="form-message" class="mt-4 hidden rounded-lg px-3 py-2 text-sm" role="alert"></div>

      <form id="login-form" action="<?= BASE_URL . 'backend/api/login.php' ?>" method="POST" class="mt-4" novalidate>

        <!-- Student number -->
        <label for="student_number" class="block text-sm font-semibold">Student Number</label>
        <div class="mt-1.5 flex items-center gap-3 rounded-lg bg-blue-50/90 px-3 focus-within:ring-2 focus-within:ring-blue-600">
          <i class="bi bi-person-badge text-lg"></i>
          <input type="text" id="student_number" name="student_number" autocomplete="username"
                 autocapitalize="off" spellcheck="false" required
                 placeholder="Enter your student number"
                 class="w-full bg-transparent py-3 text-sm placeholder:text-slate-500 focus:outline-none">
        </div>
        <p class="field-error mt-1 hidden text-xs text-red-700" data-for="student_number"></p>

        <!-- Password -->
        <label for="password" class="mt-4 block text-sm font-semibold">Password</label>
        <div class="mt-1.5 flex items-center gap-3 rounded-lg bg-blue-50/90 px-3 focus-within:ring-2 focus-within:ring-blue-600">
          <i class="bi bi-key text-lg"></i>
          <input type="password" id="password" name="password" autocomplete="current-password" required
                 placeholder="Enter your password"
                 class="w-full bg-transparent py-3 text-sm placeholder:text-slate-500 focus:outline-none">
        </div>
        <p class="field-error mt-1 hidden text-xs text-red-700" data-for="password"></p>

        <!-- Remember me / Forgot password -->
        <div class="mt-3 flex items-center justify-between text-xs">
          <a href="<?= BASE_URL . '?page=forgot_password' ?>"
             class="text-blue-600 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
            Forgot Password?
          </a>
        </div>

        <!-- Submit -->
        <button type="submit"
                class="mt-5 w-full rounded-lg bg-blue-50 py-3 text-sm font-bold text-[#0b2a4f] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 disabled:cursor-not-allowed disabled:opacity-60">
          Log in
        </button>
      </form>

    </section>
  </main>

  <!-- AJAX submit -->
  <script>
  $(function () {
    const $form = $('#login-form');
    const $msg  = $('#form-message');
    const $btn  = $form.find('button[type="submit"]');

    function showMessage(text, ok) {
      $msg.removeClass('hidden bg-red-50 text-red-700 bg-green-50 text-green-700')
          .addClass(ok ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700')
          .text(text);
    }

    function clearErrors() {
      $msg.addClass('hidden');
      $('.field-error').addClass('hidden').text('');
    }

    $form.on('submit', function (e) {
      e.preventDefault();
      clearErrors();
      $btn.prop('disabled', true).text('Logging in...');

      $.ajax({
        url: $form.attr('action'),
        method: 'POST',
        data: $form.serialize(),
        dataType: 'json'
      })
      .done(function (res) {
        $form[0].reset();
        showMessage(res.message, true);
        window.location.replace(res.redirect);
      })
      .fail(function (xhr) {
        const res = xhr.responseJSON || {};
        if (res.errors) {
          $.each(res.errors, function (field, text) {
            $('.field-error[data-for="' + field + '"]').text(text).removeClass('hidden');
          });
        } else {
          showMessage(res.message || 'Network error. Please try again.', false);
        }
        $('#password').val('');
        $btn.prop('disabled', false).text('Log in');
      });
    });

    // Back/forward cache: don't show stale values
    $(window).on('pageshow', function (e) {
      if (e.originalEvent.persisted) {
        $form[0].reset();
        clearErrors();
        $btn.prop('disabled', false).text('Log in');
      }
    });
  });
  </script>

</body>
</html>