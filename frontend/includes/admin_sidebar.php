<aside class="sidebar">

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

</aside>