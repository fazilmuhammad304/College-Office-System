<?php
session_start();
include 'db_conn.php';

// 1. லாகின் செக்
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// ---------------------------------------------------------
// 1. DATA COUNTING (PHP LOGIC)
// ---------------------------------------------------------

// A. Total Students
$student_query = "SELECT COUNT(*) as total FROM students";
$student_result = mysqli_query($conn, $student_query);
$student_data = mysqli_fetch_assoc($student_result);
$total_students = isset($student_data['total']) ? intval($student_data['total']) : 0;

// B. Active Students (For Percentage Calc)
$active_query = "SELECT COUNT(*) as total FROM students WHERE status='Active'";
$active_result = mysqli_query($conn, $active_query);
$active_data = mysqli_fetch_assoc($active_result);
$active_students = isset($active_data['total']) ? intval($active_data['total']) : 0;

// C. PROGRAM BREAKDOWN (Faculty Removed)
// Hifz Students Count
$hifz_query = "SELECT COUNT(*) as total FROM students WHERE class_year LIKE '%Hifz%'";
$hifz_data = mysqli_fetch_assoc(mysqli_query($conn, $hifz_query));
$hifz_count = $hifz_data['total'];

// Al-Alim Students Count
$alim_query = "SELECT COUNT(*) as total FROM students WHERE class_year LIKE '%Alim%'";
$alim_data = mysqli_fetch_assoc(mysqli_query($conn, $alim_query));
$alim_count = $alim_data['total'];

// Total Programs (Hardcoded as 2 based on your input)
$total_programs = 2;

// D. Attendance Today
$today = date('Y-m-d');
$att_query = "SELECT COUNT(*) as present_count FROM attendance WHERE date = '$today' AND status = 'Present'";
$att_result = mysqli_query($conn, $att_query);
$att_data = mysqli_fetch_assoc($att_result);
$present_today = isset($att_data['present_count']) ? intval($att_data['present_count']) : 0;

// Percentage Logic
$att_percentage = ($active_students > 0) ? round(($present_today / $active_students) * 100) : 0;

// E. Total Documents
$doc_query = "SELECT COUNT(*) as total FROM documents";
$doc_result = mysqli_query($conn, $doc_query);
$doc_data = mysqli_fetch_assoc($doc_result);
$total_docs = isset($doc_data['total']) ? intval($doc_data['total']) : 0;

// ---------------------------------------------------------
// 2. RECENT ACTIVITIES LOGIC
// ---------------------------------------------------------

// Get last 3 New Students (Added created_at to get time)
$recent_students = mysqli_query($conn, "SELECT full_name, class_year, created_at FROM students ORDER BY student_id DESC LIMIT 3");

// Get last 3 Uploaded Docs
$recent_docs = mysqli_query($conn, "SELECT title, category, uploaded_at FROM documents ORDER BY doc_id DESC LIMIT 3");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* --- DASHBOARD SPECIFIC STYLES --- */

        /* Clickable Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-link:hover {
            transform: translateY(-5px);
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            border: 1px solid #F3F4F6;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .card-info h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }

        .card-info span {
            font-size: 14px;
            color: #6B7280;
            font-weight: 500;
        }

        .card-subtext {
            font-size: 12px;
            color: #059669;
            font-weight: 600;
            margin-top: 5px;
            display: block;
        }

        /* Recent Activity Section */
        .activity-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            border: 1px solid #F3F4F6;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 20px;
            border-bottom: 1px solid #F3F4F6;
            padding-bottom: 15px;
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #F9FAFB;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .act-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .act-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }

        .act-details p {
            font-size: 12px;
            color: #9CA3AF;
            margin: 2px 0 0;
        }

        .act-time {
            margin-left: auto;
            font-size: 11px;
            color: #9CA3AF;
            font-weight: 500;
        }

        /* Colors */
        .bg-blue {
            background: #E0E7FF;
            color: #4338CA;
        }

        .bg-green {
            background: #ECFDF5;
            color: #059669;
        }

        .bg-orange {
            background: #FFEDD5;
            color: #C2410C;
        }

        .bg-purple {
            background: #F3E8FF;
            color: #7E22CE;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php $page = 'dashboard';
        include 'sidebar.php'; ?>

        <main class="main-content">

            <header class="top-header">
                <h2>Dashboard Overview</h2>
                <div class="header-right">
                    <div style="background:white; padding:8px 15px; border-radius:8px; border:1px solid #E5E7EB; font-weight:600; color:#374151;">
                        <i class="fa-regular fa-calendar"></i> <?php echo date('F d, Y'); ?>
                    </div>
                </div>
            </header>

            <div class="cards-grid">

                <a href="students.php" class="card-link">
                    <div class="card">
                        <div class="card-icon bg-blue"><i class="fa-solid fa-user-graduate"></i></div>
                        <div class="card-info">
                            <h3><?php echo $total_students; ?></h3>
                            <span>Total Students</span>
                            <span class="card-subtext">View All <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>

                <a href="students.php" class="card-link">
                    <div class="card">
                        <div class="card-icon bg-orange"><i class="fa-solid fa-book-quran"></i></div>
                        <div class="card-info">
                            <h3><?php echo $total_programs; ?></h3>
                            <span>Programs</span>
                            <span style="font-size:11px; color:#6B7280; margin-top:5px; display:block;">
                                Hifz: <b><?php echo $hifz_count; ?></b> | Alim: <b><?php echo $alim_count; ?></b>
                            </span>
                        </div>
                    </div>
                </a>

                <a href="attendance.php" class="card-link">
                    <div class="card">
                        <div class="card-icon bg-green"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="card-info">
                            <h3><?php echo $att_percentage; ?>%</h3>
                            <span>Attendance Today</span>
                            <span class="card-subtext"><?php echo $present_today; ?> Present Today</span>
                        </div>
                    </div>
                </a>

                <a href="documents.php" class="card-link">
                    <div class="card">
                        <div class="card-icon bg-purple"><i class="fa-solid fa-folder-open"></i></div>
                        <div class="card-info">
                            <h3><?php echo $total_docs; ?></h3>
                            <span>Documents</span>
                            <span class="card-subtext">Manage Files <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>

            </div>

            <div class="activity-section">
                <h3 class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Office Activities</h3>

                <ul class="activity-list">

                    <?php
                    if (mysqli_num_rows($recent_students) > 0) {
                        while ($rs = mysqli_fetch_assoc($recent_students)) {
                            // தேதி மற்றும் நேரம் மாற்றம் (Format: 14 Dec, 10:30 AM)
                            $time_label = date('d M, h:i A', strtotime($rs['created_at']));
                    ?>
                            <li class="activity-item">
                                <div class="act-icon bg-blue"><i class="fa-solid fa-user-plus"></i></div>
                                <div class="act-details">
                                    <h4>New Student Admitted</h4>
                                    <p><b><?php echo $rs['full_name']; ?></b> joined <?php echo $rs['class_year']; ?></p>
                                </div>
                                <span class="act-time"><?php echo $time_label; ?></span>
                            </li>
                    <?php
                        }
                    }
                    ?>

                    <?php
                    if (mysqli_num_rows($recent_docs) > 0) {
                        while ($rd = mysqli_fetch_assoc($recent_docs)) {
                            // தேதி மற்றும் நேரம் மாற்றம்
                            $time_label = date('d M, h:i A', strtotime($rd['uploaded_at']));
                    ?>
                            <li class="activity-item">
                                <div class="act-icon bg-purple"><i class="fa-solid fa-file-arrow-up"></i></div>
                                <div class="act-details">
                                    <h4>Document Uploaded</h4>
                                    <p>File <b><?php echo $rd['title']; ?></b> added to <?php echo $rd['category']; ?></p>
                                </div>
                                <span class="act-time"><?php echo $time_label; ?></span>
                            </li>
                    <?php
                        }
                    }
                    ?>

                    <?php if (mysqli_num_rows($recent_students) == 0 && mysqli_num_rows($recent_docs) == 0) { ?>
                        <li style="text-align:center; color:#9CA3AF; padding:20px;">No recent activities found.</li>
                    <?php } ?>

                </ul>
            </div>

        </main>
    </div>

</body>

</html>