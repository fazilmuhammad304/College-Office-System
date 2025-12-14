<?php
// 1. டேட்டாபேஸ் இணைப்பு (Database Connection)
include 'db_conn.php';

// ---------------------------------------------------------
// DATA COUNTING (எண்ணிக்கை எடுத்தல்)
// ---------------------------------------------------------

// 1. மொத்த மாணவர்கள் (Total Students)
$student_query = "SELECT COUNT(*) as total FROM students";
$student_result = mysqli_query($conn, $student_query);
$student_data = mysqli_fetch_assoc($student_result);
$total_students = $student_data['total'];

// 2. மொத்த ஆசிரியர்கள் (Total Teachers)
$teacher_query = "SELECT COUNT(*) as total FROM teachers";
$teacher_result = mysqli_query($conn, $teacher_query);
$teacher_data = mysqli_fetch_assoc($teacher_result);
$total_teachers = $teacher_data['total'];

// 3. ஆவணங்கள் (Total Documents)
$doc_query = "SELECT COUNT(*) as total FROM documents";
$doc_result = mysqli_query($conn, $doc_query);
$doc_data = mysqli_fetch_assoc($doc_result);
$total_docs = $doc_data['total'];

// 4. Active மாணவர்கள் (பச்சை நிற எழுத்திற்காக)
$active_query = "SELECT COUNT(*) as total FROM students WHERE status='Active'";
$active_result = mysqli_query($conn, $active_query);
$active_data = mysqli_fetch_assoc($active_result);
$active_students = $active_data['total'];

// ---------------------------------------------------------
// ATTENDANCE CALCULATION (வருகை சதவீதம்)
// ---------------------------------------------------------
$today = date('Y-m-d'); // இன்றைய தேதி

// இன்றைக்கு எத்தனை பேர் 'Present'
$att_query = "SELECT COUNT(*) as present_count FROM attendance WHERE date = '$today' AND status = 'Present'";
$att_result = mysqli_query($conn, $att_query);
$att_data = mysqli_fetch_assoc($att_result);
$present_today = $att_data['present_count'];

// சதவீதம் கணக்கிடுதல்
if ($active_students > 0) {
    $attendance_percentage = round(($present_today / $active_students) * 100);
} else {
    $attendance_percentage = 0; // மாணவர்கள் யாரும் இல்லை என்றால் 0%
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>

    <div class="dashboard-container">

        <?php
        $page = 'dashboard'; // இது Sidebar-ல் Dashboard நிறத்தை மாற்றும்
        include 'sidebar.php';
        ?>

        <main class="main-content">

            <header class="top-header">
                <h2>Admin Dashboard</h2>
                <div class="header-right">
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search students, staff...">
                    </div>
                    <div class="notification-btn">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                </div>
            </header>

            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Total Students</p>
                        <h3><?php echo $total_students; ?></h3>
                        <span class="status-active">● <?php echo $active_students; ?> Active</span>
                    </div>
                    <div class="stat-icon orange-bg">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Attendance Today</p>
                        <h3><?php echo $attendance_percentage; ?>%</h3>
                    </div>
                    <div class="stat-icon green-bg">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Faculty</p>
                        <h3><?php echo $total_teachers; ?></h3>
                    </div>
                    <div class="stat-icon purple-bg">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Documents</p>
                        <h3><?php echo $total_docs; ?></h3>
                    </div>
                    <div class="stat-icon orange-light-bg">
                        <i class="fa-regular fa-folder"></i>
                    </div>
                </div>
            </div>

            <div class="content-split">

                <div class="section-card recent-activity">
                    <div class="section-header">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Office Activities</h3>
                    </div>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <div class="activity-details">
                                <h4>New Student Admission</h4>
                                <p>Latest student added to the system</p>
                                <span class="time">Just Now</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-card quick-actions">
                    <div class="section-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="actions-grid">

                        <a href="add_student.html" class="action-btn" style="text-decoration: none;">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Add Student</span>
                        </a>

                        <button class="action-btn">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span>Attendance</span>
                        </button>
                        <button class="action-btn">
                            <i class="fa-solid fa-upload"></i>
                            <span>Upload Doc</span>
                        </button>
                        <button class="action-btn">
                            <i class="fa-solid fa-print"></i>
                            <span>Print Report</span>
                        </button>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>

</html>