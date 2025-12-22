<?php
session_start();
include 'db_conn.php';

// --- TIMEZONE FIX ---
date_default_timezone_set('Asia/Colombo');

// 1. LOGIN CHECK
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 2. ALERT LOGIC (Check if today has a note)
$today_date = date('Y-m-d');
$alert_sql = "SELECT * FROM dashboard_reminders WHERE reminder_date = '$today_date'";
$alert_res = mysqli_query($conn, $alert_sql);
$todays_alerts = [];
while ($row = mysqli_fetch_assoc($alert_res)) {
    $todays_alerts[] = $row;
}

// 3. STATS & DATA COUNTS
// A. Total Students
$student_query = "SELECT COUNT(*) as total FROM students";
$student_data = mysqli_fetch_assoc(mysqli_query($conn, $student_query));
$total_students = isset($student_data['total']) ? intval($student_data['total']) : 0;

// B. Attendance Today
$att_query = "SELECT COUNT(DISTINCT student_id) as present_count FROM attendance WHERE date = '$today_date' AND status = 'Present'";
$att_data = mysqli_fetch_assoc(mysqli_query($conn, $att_query));
$present_today = isset($att_data['present_count']) ? intval($att_data['present_count']) : 0;

// C. Attendance Percentage
$active_students_query = "SELECT COUNT(*) as total FROM students WHERE status='Active'";
$active_data = mysqli_fetch_assoc(mysqli_query($conn, $active_students_query));
$active_count = $active_data['total'];
$att_percentage = ($active_count > 0) ? round(($present_today / $active_count) * 100) : 0;
if ($att_percentage > 100) $att_percentage = 100;

// D. Total Documents
$doc_query = "SELECT COUNT(*) as total FROM documents";
$doc_data = mysqli_fetch_assoc(mysqli_query($conn, $doc_query));
$total_docs = isset($doc_data['total']) ? intval($doc_data['total']) : 0;

// E. Programs
$prog_query = "SELECT COUNT(*) as total FROM programs";
$prog_data = mysqli_fetch_assoc(mysqli_query($conn, $prog_query));
$total_programs = isset($prog_data['total']) ? intval($prog_data['total']) : 0;

// 4. RECENT ACTIVITIES
$recent_students = mysqli_query($conn, "SELECT full_name, class_year, created_at FROM students WHERE created_at >= NOW() - INTERVAL 1 DAY ORDER BY created_at DESC");
$recent_docs = mysqli_query($conn, "SELECT title, category, uploaded_at FROM documents WHERE uploaded_at >= NOW() - INTERVAL 1 DAY ORDER BY uploaded_at DESC");
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
        /* DASHBOARD STYLES */
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
            transition: transform 0.2s;
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

        .activity-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            border: 1px solid #F3F4F6;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            margin-top: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 20px;
            border-bottom: 1px solid #F3F4F6;
            padding-bottom: 15px;
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

        /* SHORTCUT BUTTON STYLE */
        .shortcut-bar {
            background: white;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #F3F4F6;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sc-btn {
            background: #ED8936;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.2s;
            box-shadow: 0 4px 10px rgba(237, 137, 54, 0.2);
        }

        .sc-btn:hover {
            background: #d9650c;
            transform: translateY(-2px);
        }

        /* ALERT POPUP STYLES */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
        }

        .modal-box {
            background: white;
            width: 400px;
            padding: 25px;
            border-radius: 12px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s;
        }

        .alert-active {
            display: flex !important;
        }

        .alert-header {
            background: #FEF2F2;
            color: #991B1B;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .alert-title {
            font-weight: 700;
            font-size: 16px;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
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
                            <span class="card-subtext">View Directory <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
                <a href="programs.php" class="card-link">
                    <div class="card">
                        <div class="card-icon bg-orange"><i class="fa-solid fa-book-quran"></i></div>
                        <div class="card-info">
                            <h3><?php echo $total_programs; ?></h3>
                            <span>Programs</span>
                            <span style="font-size:11px; color:#6B7280; margin-top:5px; display:block;">Manage Courses <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
                <a href="attendance.php" class="card-link">
                    <div class="card">
                        <div class="card-icon bg-green"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="card-info">
                            <h3><?php echo $att_percentage; ?>%</h3>
                            <span>Attendance Today</span>
                            <span class="card-subtext"><?php echo $present_today; ?> / <?php echo $active_count; ?> Present</span>
                        </div>
                    </div>
                </a>
                <a href="documents.php" class="card-link">
                    <div class="card">
                        <div class="card-icon bg-purple"><i class="fa-solid fa-folder-open"></i></div>
                        <div class="card-info">
                            <h3><?php echo $total_docs; ?></h3>
                            <span>Documents</span>
                            <span class="card-subtext">File Repository <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="shortcut-bar">
                <div>
                    <h3 style="margin:0; font-size:16px;">Quick Actions</h3>
                    <p style="margin:5px 0 0; color:#6B7280; font-size:13px;">Manage students and admissions efficiently.</p>
                </div>
                <a href="add_student.php" class="sc-btn">
                    <i class="fa-solid fa-user-plus"></i> Add Student Box
                </a>
            </div>

            <div class="activity-section">
                <h3 class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Office Activities</h3>
                <ul class="activity-list">
                    <?php if (mysqli_num_rows($recent_students) > 0): ?>
                        <?php while ($rs = mysqli_fetch_assoc($recent_students)):
                            $time_label = date('d M, h:i A', strtotime($rs['created_at'])); ?>
                            <li class="activity-item">
                                <div class="act-icon bg-blue"><i class="fa-solid fa-user-plus"></i></div>
                                <div class="act-details">
                                    <h4>New Admission</h4>
                                    <p><b><?php echo $rs['full_name']; ?></b> - <?php echo $rs['class_year']; ?></p>
                                </div>
                                <span class="act-time"><?php echo $time_label; ?></span>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <?php if (mysqli_num_rows($recent_docs) > 0): ?>
                        <?php while ($rd = mysqli_fetch_assoc($recent_docs)):
                            $time_label = date('d M, h:i A', strtotime($rd['uploaded_at'])); ?>
                            <li class="activity-item">
                                <div class="act-icon bg-purple"><i class="fa-solid fa-file-arrow-up"></i></div>
                                <div class="act-details">
                                    <h4>Document Uploaded</h4>
                                    <p>File <b><?php echo $rd['title']; ?></b> (<?php echo $rd['category']; ?>)</p>
                                </div>
                                <span class="act-time"><?php echo $time_label; ?></span>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <?php if (mysqli_num_rows($recent_students) == 0 && mysqli_num_rows($recent_docs) == 0) { ?>
                        <li style="text-align:center; color:#9CA3AF; padding:20px;">No recent activities.</li>
                    <?php } ?>
                </ul>
            </div>

        </main>
    </div>

    <?php if (!empty($todays_alerts)): ?>
        <div class="modal-overlay alert-active" id="alertOverlay">
            <div class="modal-box">
                <div class="alert-header">
                    <i class="fa-solid fa-bell fa-bounce"></i>
                    <span class="alert-title">Reminder for Today!</span>
                </div>
                <div style="margin-bottom:20px;">
                    <?php foreach ($todays_alerts as $alert): ?>
                        <p style="font-weight:600; color:#374151; margin-bottom:5px;">Note:</p>
                        <p style="background:#F9FAFB; padding:10px; border-radius:6px; color:#4B5563; font-size:14px; margin-bottom:15px;">
                            <?php echo htmlspecialchars($alert['note_text']); ?>
                        </p>
                        <?php if ($alert['document_path']): ?>
                            <a href="<?php echo $alert['document_path']; ?>" download style="display:block; text-align:center; background:#EFF6FF; color:#2563EB; padding:8px; border-radius:6px; text-decoration:none; font-weight:600; font-size:13px;">
                                <i class="fa-solid fa-download"></i> Download Attached Document
                            </a>
                        <?php endif; ?>
                        <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
                    <?php endforeach; ?>
                </div>
                <button onclick="document.getElementById('alertOverlay').style.display='none'" style="width:100%; padding:10px; border:1px solid #D1D5DB; background:white; border-radius:6px; cursor:pointer;">Close Alert</button>
            </div>
        </div>
    <?php endif; ?>

</body>

</html>