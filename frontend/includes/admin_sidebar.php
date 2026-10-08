<?php

if (!function_exists('e')) {
    function e($value)
    {
        if (is_array($value)) {
            return '';
        }

        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

$currentPage = $currentPage ?? '';

$sidebarPagesUrl = BASE_URL;

// Single source of truth for the menu. 'key' is matched against $currentPage.

$sidebarMenu = [
    [
        'title' => 'Enrollment',
        'items' => [
            ['key' => 'enrollment_period', 'label' => 'Enrollment Period', 'icon' => 'fa-calendar', 'link' => '?page=enrollment_period'],
            ['key' => 'student_enrollment', 'label' => 'Student Enrollment', 'icon' => 'fa-users', 'link' => '?page=student_enrollment'],
            ['key' => 'cor_submissions', 'label' => 'COR Submissions', 'icon' => 'fa-file-lines', 'link' => '?page=cor_submissions'],
        ],
    ],
    [
        'title' => 'Academic',
        'items' => [
            ['key' => 'instructor',  'label' => 'Instructor', 'icon' => 'fa-user', 'link' => '?page=instructor'],
            ['key' => 'course_management',     'label' => "Course Management",  'icon' => 'fa-book-open', 'link' => '?page=course_management'],
            ['key' => 'course_offerings',     'label' => "Course Offerings",  'icon' => 'fa-table-list', 'link' => '?page=course_offerings'],
            ['key' => 'evaluation', 'label' => 'Evaluation', 'icon' => 'fa-star','link' => '?page=evaluation'],
        ],
    ],
    [
        'title' => 'Management',
        'items' => [
            ['key' => 'student_management',  'label' => 'Students', 'icon' => 'fa-user', 'link' => '?page=student_management'],
            ['key' => 'notifications',     'label' => "Notifications",  'icon' => 'fa-bell', 'link' => '?page=notifications'],
            ['key' => 'reports', 'label' => 'Reports', 'icon' => 'fa-chart-column','link' => '?page=reports'],
            ['key' => 'profile',  'label' => 'Profile', 'icon' => 'fa-user', 'link' => '?page=profile'],
            ['key' => 'settings',     'label' => "Settings",  'icon' => 'fa-gear', 'link' => '?page=settings'],
        ],
    ],
];
?>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <nav class="menu">

        <p class="menu-title">Dashboard</p>

        <a href="<?= e($sidebarPagesUrl) ?>?page=admin_home" class="menu-link <?= $currentPage === 'admin_home' ? 'active' : '' ?>">
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