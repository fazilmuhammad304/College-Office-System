<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo-text">
            <i class="fa-solid fa-graduation-cap"></i> College<span>Office</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <p class="menu-label">Main</p>

        <a href="dashboard.php" class="menu-item <?php if ($page == 'dashboard') {
                                                        echo 'active';
                                                    } ?>">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>

        <p class="menu-label">Directory</p>

        <a href="students.php" class="menu-item <?php if ($page == 'students') {
                                                    echo 'active';
                                                } ?>">
            <i class="fa-solid fa-user-graduate"></i> Students
        </a>

        <a href="#" class="menu-item <?php if ($page == 'teachers') {
                                            echo 'active';
                                        } ?>">
            <i class="fa-solid fa-chalkboard-user"></i> Teachers
        </a>

        <a href="#" class="menu-item <?php if ($page == 'staff') {
                                            echo 'active';
                                        } ?>">
            <i class="fa-solid fa-users"></i> Staff
        </a>

        <p class="menu-label">Academic</p>

        <a href="programs.php" class="menu-item <?php if ($page == 'programs') {
                                                    echo 'active';
                                                } ?>">
            <i class="fa-solid fa-layer-group"></i> Programs
        </a>

        <a href="attendance.php" class="menu-item <?php if ($page == 'attendance') {
                                                        echo 'active';
                                                    } ?>">
            <i class="fa-solid fa-calendar-check"></i> Attendance
        </a>

        <p class="menu-label">Office</p>
        <a href="documents.php" class="menu-item <?php if ($page == 'documents') {
                                                        echo 'active';
                                                    } ?>">
            <i class="fa-regular fa-folder-open"></i> Documents
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">A</div>
            <div class="user-info">
                <h4>Administrator</h4>
                <p>Super Admin</p>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </div>
</aside>