<?php


// Start here. Change 
require_once dirname(__DIR__, 3) . '/backend/bootstrap.php';

// Log in name ng customer: example - markesg
$userName = $_SESSION['username'] ?? 'Customer';
$currentPage = 'home_main';


// Services 
$services = [
    ['id' => 1, 'title' => 'AC Cleaning', 'description' => 'Keep your AC clean and efficient', 'icon' => 'fa-fan'],
    ['id' => 2, 'title' => 'AC Repair', 'description' => 'Fix your AC problems quickly', 'icon' => 'fa-screwdriver-wrench'],
    ['id' => 3, 'title' => 'AC Maintenance', 'description' => 'Prevents Problems', 'icon' => 'fa-gear'],
    ['id' => 4, 'title' => 'AC Installation', 'description' => 'Installation for your new AC', 'icon' => 'fa-wind'],
    ['id' => 5, 'title' => 'Parts Replacements', 'description' => 'Replace Damaged Parts', 'icon' => 'fa-toolbox'],
];

// Temporary request data
// Status values: Pending | Confirmed | On going | Cancelled
$recentRequests = [
    ['id' => 125, 'service' => 'Parts Replacement', 'date' => 'September 26, 2025', 'status' => 'Pending'],
    ['id' => 124, 'service' => 'AC Cleaning', 'date' => 'September 26, 2025', 'status' => 'Confirmed'],
    ['id' => 123, 'service' => 'AC Cleaning', 'date' => 'Sep 15, 2025', 'status' => 'On going'],
    ['id' => 122, 'service' => 'AC Cleaning', 'date' => 'Sep 15, 2025', 'status' => 'Cancelled'],
];

$totalRequests = count($recentRequests); 

// AC Care Tips 
$careTips = [
    ['icon' => 'fa-filter', 'title' => 'Clean Filters Monthly', 'text' => 'Regular cleaning can improve efficiency by up to 15% and extend your unit\'s lifespan.'],
    ['icon' => 'fa-temperature-half', 'title' => 'Set Optimal Temperature', 'text' => '24-26°C is the ideal range — comfortable and energy-efficient for Philippine weather.'],
    ['icon' => 'fa-calendar-check', 'title' => 'Schedule Annual Check-ups', 'text' => 'A yearly professional inspection catches small issues before they become expensive repairs.'],
];


// Turn a status label into a CSS class suffix, e.g. "On going" -> "ongoing"
function statusClass($status)
{
    return strtolower(str_replace(' ', '', $status));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoolFreeze | Homepage</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>frontend/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="layout">
    <!-- Sidebar -->
    <?php require FRONTEND_PATH . 'includes/sidebar.php' ?>

    <!-- MOBILE OVERLAY -->
    <div class="overlay" id="overlay"></div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- Topbar -->
        <?php require FRONTEND_PATH . 'includes/topbar.php'?>


    </div>

</div>

<script src="<?= BASE_URL ?>frontend/assets/js/custom.js"></script>

</body>

</html>