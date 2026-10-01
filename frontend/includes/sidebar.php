<?php


if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$currentPage = $currentPage ?? '';

$sidebarPagesUrl = BASE_URL;

// Single source of truth for the menu. 'key' is matched against $currentPage.

$sidebarMenu = [
    [
        'title' => 'Enrollment',
        'items' => [
            ['key' => 'evaluation', 'label' => 'Evaluation', 'icon' => 'fa-screwdriver-wrench', 'link' => '?page=services_main'],
            ['key' => 'clearance', 'label' => 'Clearance', 'icon' => 'fa-screwdriver-wrench', 'link' => '?page=services_main'],
            ['key' => 'cor', 'label' => 'COR', 'icon' => 'fa-screwdriver-wrench', 'link' => '?page=services_main'],
        ],
    ],
    [
        'title' => 'Status',
        'items' => [
            ['key' => 'myenrollment', 'label' => 'My Enrollment', 'icon' => 'fa-cart-shopping', 'link' => '?page=cart_main'],
            ['key' => 'myschedule', 'label' => 'My Schedule', 'icon' => 'fa-cart-shopping', 'link' => '?page=cart_main'],
        ],
    ],
    [
        'title' => 'System',
        'items' => [
            ['key' => 'profile', 'label' => 'Profile', 'icon' => 'fa-clipboard-list', 'link' => '?page=request_main'],
            ['key' => 'settings', 'label' => 'Settings', 'icon' => 'fa-clipboard-list', 'link' => '?page=request_main'],
        ],
    ],
    [
        'title' => 'System',
        'items' => [
            ['key' => 'profile',  'label' => 'Profile',          'icon' => 'fa-user',            'link' => '?page=profile_main'],
            ['key' => 'faqs',     'label' => "Helps and FAQ's",  'icon' => 'fa-circle-question', 'link' => 'faqs.php'],
            ['key' => 'settings', 'label' => 'Settings',         'icon' => 'fa-gear',            'link' => 'settings.php'],
        ],
    ],
];
?>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <nav class="menu">

        <p class="menu-title">Dashboard</p>

        <a href="<?= e($sidebarPagesUrl) ?>?page=home_main" class="menu-link <?= $currentPage === 'home_main' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i>
            Home
        </a>

        <?php foreach ($sidebarMenu as $section): ?>

            <p class="menu-title"><?= e($section['title']) ?></p>

            <?php foreach ($section['items'] as $item): ?>

                <a href="<?= e($sidebarPagesUrl . $item['link']) ?>"
                   class="menu-link <?= $currentPage === $item['key'] ? 'active' : '' ?>">
                    <i class="fa-solid <?= e($item['icon']) ?>"></i>
                    <?= e($item['label']) ?>
                </a>

            <?php endforeach; ?>

        <?php endforeach; ?>

    </nav>

    <!-- LOGOUT -->
    <form action="<?= BASE_URL ?>backend/api/logout.php" method="POST" class="logout-form">
        <button type="submit" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            Log out
        </button>
    </form>


</aside>