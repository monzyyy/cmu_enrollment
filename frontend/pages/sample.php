<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$userName    = $_SESSION['username'] ?? 'Customer';
$currentPage = 'home';   // change based on page home | services | cart | requests | profile | faqs | settings
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Testing page</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
  <div class="layout">
    <?php require FRONTEND_PATH . 'includes/sidebar.php' ?>
    <div class="main">
      <?php require FRONTEND_PATH . 'includes/topbar.php'?>
    </div>
  </div>
  
</body>
</html>