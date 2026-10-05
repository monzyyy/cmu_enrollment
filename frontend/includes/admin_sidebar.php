<!-- <aside class="sidebar">

    <nav class="menu">

        <div class="menu-title">
            Overview
        </div>

        <a href="<?= BASE_URL ?>?page=admin_home"
           class="menu-link active">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>


        <div class="menu-title">
            Enrollment
        </div>

        <a href="<?= BASE_URL ?>?page=enrollment_period"
           class="menu-link">
            <i class="fa-regular fa-calendar"></i>
            <span>Enrollment Period</span>
        </a>

        <a href="<?= BASE_URL ?>?page=student_enrollment"
           class="menu-link">
            <i class="fa-solid fa-users"></i>
            <span>Students Enrollment</span>
        </a>

        <a href="<?= BASE_URL ?>?page=cor_submissions"
           class="menu-link">
            <i class="fa-regular fa-file-lines"></i>
            <span>COR Submission</span>
        </a>


        <div class="menu-title">
            Academic
        </div>

        <a href="<?= BASE_URL ?>?page=instructors"
           class="menu-link">
            <i class="fa-regular fa-user"></i>
            <span>Instructor</span>
        </a>

        <a href="<?= BASE_URL ?>?page=course_offerings"
           class="menu-link">
            <i class="fa-solid fa-table-list"></i>
            <span>Course Offerings</span>
        </a>

        <a href="<?= BASE_URL ?>?page=evaluations"
           class="menu-link">
            <i class="fa-regular fa-star"></i>
            <span>Evaluation</span>
        </a>


        <div class="menu-title">
            Management
        </div>

        <a href="<?= BASE_URL ?>?page=students"
           class="menu-link">
            <i class="fa-solid fa-users"></i>
            <span>Students</span>
        </a>

        <a href="<?= BASE_URL ?>?page=notifications"
           class="menu-link">
            <i class="fa-regular fa-bell"></i>
            <span>Notification</span>
        </a>

        <a href="<?= BASE_URL ?>?page=reports"
           class="menu-link">
            <i class="fa-solid fa-chart-column"></i>
            <span>Reports</span>
        </a>

        <a href="<?= BASE_URL ?>?page=profile"
           class="menu-link">
            <i class="fa-regular fa-user"></i>
            <span>Profile</span>
        </a>

        <a href="<?= BASE_URL ?>?page=settings"
           class="menu-link">
            <i class="fa-solid fa-gear"></i>
            <span>Settings</span>
        </a>

    </nav>


    <form action="<?= BASE_URL ?>backend/api/logout.php"
          method="POST"
          class="logout-form">

        <button type="submit" class="logout">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Logout</span>
        </button>

    </form>

</aside> -->

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
            ['key' => 'enrollment_period', 'label' => 'Enrollment Period', 'icon' => 'fa-regular fa-calendar', 'link' => '?page=enrollment_period'],
            ['key' => 'student_enrollment', 'label' => 'Student Enrollment', 'icon' => 'fa-solid fa-users', 'link' => '?page=student_enrollment'],
            ['key' => 'cor_submissions', 'label' => 'COR Submissions', 'icon' => 'fa-regular fa-file-lines', 'link' => '?page=cor_submissions'],
        ],
    ],
    [
        'title' => 'Academic',
        'items' => [
            ['key' => 'instructor',  'label' => 'Instructor', 'icon' => 'fa-regular fa-user', 'link' => '?page=instructor'],
            ['key' => 'course_offerings',     'label' => "Course Offerings",  'icon' => 'fa-solid fa-table-list', 'link' => '?page=course_offerings'],
            ['key' => 'evaluation', 'label' => 'Evaluation', 'icon' => 'fa-regular fa-star','link' => '?page=evaluation'],
        ],
    ],
    [
        'title' => 'Management',
        'items' => [
            ['key' => 'students',  'label' => 'Students', 'icon' => 'fa-regular fa-user', 'link' => '?page=students'],
            ['key' => 'notifications',     'label' => "Notifications",  'icon' => 'fa-regular fa-bell', 'link' => '?page=notifications'],
            ['key' => 'reports', 'label' => 'Reports', 'icon' => 'fa-solid fa-chart-column','link' => '?page=reports'],
            ['key' => 'profile',  'label' => 'Profile', 'icon' => 'fa-regular fa-user', 'link' => '?page=profile'],
            ['key' => 'settings',     'label' => "Settings",  'icon' => 'fa-solid fa-gear', 'link' => '?page=settings'],
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