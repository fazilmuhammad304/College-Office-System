<?php
session_start();
include 'db_conn.php';

// 1. Login Check
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 2. Check Student ID
if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = mysqli_real_escape_string($conn, $_GET['id']);
$message = "";

// ---------------------------------------------------------
// 1. UPDATE CLASS & YEAR LOGIC
// ---------------------------------------------------------
if (isset($_POST['update_class_info'])) {
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);

    $new_class_year = (!empty($year)) ? "$program $year" : $program;

    $new_status = 'Active';
    if ($year == "Graduated" || $program == "Graduated") {
        $new_status = "Graduated";
        $new_class_year = "Graduated";
    }

    $sql = "UPDATE students SET class_year = '$new_class_year', status = '$new_status' WHERE student_id = '$student_id'";

    if (mysqli_query($conn, $sql)) {
        $message = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Class Updated Successfully!</div>";
        echo "<meta http-equiv='refresh' content='1'>";
    } else {
        $message = "<div class='alert error'>Update Failed: " . mysqli_error($conn) . "</div>";
    }
}

// ---------------------------------------------------------
// 2. DOCUMENT UPLOAD LOGIC
// ---------------------------------------------------------
if (isset($_POST['upload_student_doc'])) {
    $title = mysqli_real_escape_string($conn, $_POST['doc_title']);
    $filename = $_FILES['doc_file']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $new_filename = uniqid() . "." . $ext;
    $target = "uploads/" . $new_filename;

    $filesize = $_FILES['doc_file']['size'];
    $size_text = ($filesize >= 1048576) ? number_format($filesize / 1048576, 2) . ' MB' : number_format($filesize / 1024, 2) . ' KB';

    if (move_uploaded_file($_FILES['doc_file']['tmp_name'], $target)) {
        $sql = "INSERT INTO documents (title, category, file_path, file_type, file_size, student_id) 
                VALUES ('$title', 'Student File', '$new_filename', '$ext', '$size_text', '$student_id')";
        mysqli_query($conn, $sql);
        $message = "<div class='alert success'><i class='fa-solid fa-file-circle-check'></i> Document Added!</div>";
    }
}

// ---------------------------------------------------------
// 2.5. RENAME DOCUMENT LOGIC
// ---------------------------------------------------------
if (isset($_POST['rename_doc'])) {
    $doc_id = mysqli_real_escape_string($conn, $_POST['rename_id']);
    $new_title = mysqli_real_escape_string($conn, $_POST['rename_title']);

    $sql = "UPDATE documents SET title = '$new_title' WHERE doc_id = '$doc_id'";
    if (mysqli_query($conn, $sql)) {
        $message = "<div class='alert success'><i class='fa-solid fa-pen-to-square'></i> Document Renamed!</div>";
    } else {
        $message = "<div class='alert error'>Rename Failed: " . mysqli_error($conn) . "</div>";
    }
}

// ---------------------------------------------------------
// 3. DELETE DOCUMENT LOGIC (NEW)
// ---------------------------------------------------------
if (isset($_GET['delete_doc'])) {
    $doc_id = $_GET['delete_doc'];
    $path = $_GET['path'];

    // Delete from DB
    mysqli_query($conn, "DELETE FROM documents WHERE doc_id='$doc_id'");

    // Delete file from folder
    if (file_exists("uploads/" . $path)) {
        unlink("uploads/" . $path);
    }

    // Refresh to clear URL params
    header("Location: student_view.php?id=$student_id");
    exit();
}

// ---------------------------------------------------------
// 4. FETCH DATA
// ---------------------------------------------------------
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$student_id'"));

// Attendance Stats
$total_days = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM attendance WHERE student_id = '$student_id'"));
$present_days = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM attendance WHERE student_id = '$student_id' AND status='Present'"));
$att_percentage = ($total_days > 0) ? round(($present_days / $total_days) * 100) : 0;
$att_history = mysqli_query($conn, "SELECT * FROM attendance WHERE student_id = '$student_id' ORDER BY date DESC LIMIT 30");

// Documents
$stu_docs = mysqli_query($conn, "SELECT * FROM documents WHERE student_id = '$student_id' ORDER BY doc_id DESC");

// Pre-select Logic
$current_class_str = $student['class_year'];
$current_prog = "";
$current_year = "";

if (strpos($current_class_str, "Hifz") !== false) {
    $current_prog = "Hifz Class";
    $current_year = trim(str_replace("Hifz Class", "", $current_class_str));
} elseif (strpos($current_class_str, "Al-Alim") !== false || strpos($current_class_str, "Alim") !== false) {
    $current_prog = "Al-Alim";
    $current_year = trim(str_replace(["Al-Alim", "Alim"], "", $current_class_str));
} elseif (strpos($current_class_str, "Al-Alimah") !== false) {
    $current_prog = "Al-Alimah";
    $current_year = trim(str_replace("Al-Alimah", "", $current_class_str));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* --- LAYOUT --- */
        .profile-layout {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 25px;
            align-items: start;
        }

        /* --- LEFT CARD: PROFILE --- */
        .profile-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #F3F4F6;
            overflow: hidden;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        .profile-header-bg {
            background: linear-gradient(135deg, #F17C1C 0%, #EA580C 100%);
            height: 110px;
            width: 100%;
        }

        .profile-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 5px solid white;
            background: #F3F4F6;
            margin-top: -55px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-body {
            padding: 15px 25px 30px;
        }

        .st-name {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin: 10px 0 5px;
        }

        .st-meta {
            color: #6B7280;
            font-size: 14px;
            font-weight: 500;
        }

        .class-year-text {
            margin-top: 8px;
            font-weight: 700;
            color: #F17C1C;
            font-size: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 10px;
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }

        /* Update Box Styling */
        .update-box {
            background: #FFF7ED;
            border: 1px solid #FFEDD5;
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
            text-align: left;
        }

        .update-title {
            font-size: 12px;
            font-weight: 700;
            color: #C2410C;
            text-transform: uppercase;
            margin-bottom: 12px;
            display: block;
            border-bottom: 1px solid #FFEDD5;
            padding-bottom: 8px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
            display: block;
        }

        .modern-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            outline: none;
            background: white;
            color: #334155;
            transition: 0.2s;
        }

        .modern-select:focus {
            border-color: #F17C1C;
            box-shadow: 0 0 0 3px rgba(241, 124, 28, 0.1);
        }

        .btn-update {
            width: 100%;
            background: #F17C1C;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-update:hover {
            background: #D9650C;
            transform: translateY(-1px);
        }

        /* --- RIGHT CARD: TABS --- */
        .tabs-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #F3F4F6;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            min-height: 550px;
            overflow: hidden;
        }

        .tabs-header {
            display: flex;
            border-bottom: 1px solid #F1F5F9;
            background: #FFFFFF;
            padding: 0 10px;
        }

        .tab-btn {
            padding: 18px 25px;
            cursor: pointer;
            font-weight: 600;
            color: #64748B;
            border-bottom: 3px solid transparent;
            transition: 0.2s;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: #334155;
            background: #FFF7ED;
        }

        .tab-btn.active {
            color: #F17C1C;
            border-bottom-color: #F17C1C;
        }

        .tab-content {
            padding: 30px;
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Info Rows */
        .info-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #F1F5F9;
        }

        .info-label {
            width: 160px;
            color: #64748B;
            font-weight: 500;
            font-size: 14px;
        }

        .info-val {
            color: #1E293B;
            font-weight: 600;
            font-size: 15px;
        }

        /* Attendance Styles */
        .stat-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            flex: 1;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            text-align: center;
            background: #F8FAFC;
        }

        .stat-num {
            font-size: 24px;
            font-weight: 800;
            color: #1E293B;
        }

        .stat-txt {
            font-size: 12px;
            color: #64748B;
            text-transform: uppercase;
            margin-top: 5px;
            font-weight: 600;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table td {
            padding: 14px 10px;
            border-bottom: 1px solid #F1F5F9;
            color: #334155;
        }

        .history-table tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }

        .bg-p {
            background: #DCFCE7;
            color: #166534;
        }

        .bg-a {
            background: #FEE2E2;
            color: #991B1B;
        }

        .bg-l {
            background: #FFEDD5;
            color: #9A3412;
        }

        .bg-h {
            background: #FFEDD5;
            color: #9A3412;
            border: 1px solid #FDBA74;
        }

        /* Document Area */
        .upload-area {
            background: #F8FAFC;
            border: 2px dashed #CBD5E1;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 25px;
        }

        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        /* 🔥 Updated File Card Styling */
        .file-box {
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 15px;
            transition: 0.2s;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .file-box:hover {
            border-color: #F17C1C;
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .file-icon {
            font-size: 32px;
            margin-bottom: 10px;
            text-align: center;
        }

        .file-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
            color: #334155;
        }

        .file-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #F1F5F9;
        }

        .action-link {
            font-size: 13px;
            color: #64748B;
            cursor: pointer;
            transition: 0.2s;
        }

        .action-link:hover {
            color: #F17C1C;
        }

        .action-link.delete:hover {
            color: #EF4444;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 12px;
            width: 800px;
            height: 85vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #64748B;
        }

        .preview-frame {
            flex: 1;
            border: none;
            background: #F3F4F6;
            margin-top: 10px;
            border-radius: 8px;
        }

        .preview-img {
            max-width: 100%;
            max-height: 70vh;
            margin: auto;
            display: block;
            border-radius: 8px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .success {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        @media (max-width: 900px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
        }

        .header-back-link {
            color: #6B7280;
            font-size: 20px;
            transition: 0.2s;
        }

        .header-back-link:hover {
            color: #F17C1C;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php $page = 'students';
        include 'sidebar.php'; ?>

        <main class="main-content">

            <header class="top-header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <a href="students.php" class="header-back-link">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <h2>Student Profile</h2>
                </div>
            </header>

            <?php echo $message; ?>

            <div class="profile-layout">

                <div class="profile-card">
                    <div class="profile-header-bg"></div>
                    <?php $photo = !empty($student['photo']) ? "uploads/" . $student['photo'] : "https://cdn-icons-png.flaticon.com/512/3135/3135715.png"; ?>
                    <img src="<?php echo $photo; ?>" class="profile-avatar">

                    <div class="profile-body">
                        <h2 class="st-name"><?php echo $student['full_name']; ?></h2>
                        <p class="st-meta"><?php echo $student['admission_no']; ?></p>
                        <div class="class-year-text"><?php echo $student['class_year']; ?></div>
                        <span class="status-badge"><?php echo $student['status']; ?></span>

                        <form method="POST" class="update-box" action="student_view.php?id=<?php echo $student_id; ?>">
                            <span class="update-title">Academic Actions</span>
                            <label class="form-label">Update Course</label>
                            <select name="program" class="modern-select">
                                <option value="Hifz Class" <?php if ($current_prog == 'Hifz Class') echo 'selected'; ?>>Hifz Class</option>
                                <option value="Al-Alim" <?php if ($current_prog == 'Al-Alim') echo 'selected'; ?>>Al-Alim</option>
                                <option value="Al-Alimah" <?php if ($current_prog == 'Al-Alimah') echo 'selected'; ?>>Al-Alimah</option>
                            </select>
                            <label class="form-label">Update Year</label>
                            <select name="year" class="modern-select">
                                <option value="">(Select Year)</option>
                                <option value="1st Year" <?php if (strpos($current_year, '1st') !== false) echo 'selected'; ?>>1st Year</option>
                                <option value="2nd Year" <?php if (strpos($current_year, '2nd') !== false) echo 'selected'; ?>>2nd Year</option>
                                <option value="3rd Year" <?php if (strpos($current_year, '3rd') !== false) echo 'selected'; ?>>3rd Year</option>
                                <option value="4th Year" <?php if (strpos($current_year, '4th') !== false) echo 'selected'; ?>>4th Year</option>
                                <option value="Final Year" <?php if (strpos($current_year, 'Final') !== false) echo 'selected'; ?>>Final Year</option>
                                <option value="Graduated">Graduated</option>
                            </select>
                            <button type="submit" name="update_class_info" class="btn-update">Update Academic Info <i class="fa-solid fa-rotate"></i></button>
                        </form>
                    </div>
                </div>

                <div class="tabs-card">
                    <div class="tabs-header">
                        <div class="tab-btn active" onclick="switchTab(event, 'tab-info')"><i class="fa-regular fa-id-card"></i> Personal Info</div>
                        <div class="tab-btn" onclick="switchTab(event, 'tab-att')"><i class="fa-solid fa-chart-pie"></i> Attendance</div>
                        <div class="tab-btn" onclick="switchTab(event, 'tab-docs')"><i class="fa-regular fa-folder-open"></i> Documents</div>
                    </div>

                    <div id="tab-info" class="tab-content active">
                        <h4 style="margin-bottom:20px; color:#334155; font-size:16px;">Student Information</h4>
                        <div class="info-row"><span class="info-label">Full Name</span> <span class="info-val"><?php echo $student['full_name']; ?></span></div>
                        <div class="info-row"><span class="info-label">Admission No</span> <span class="info-val"><?php echo $student['admission_no']; ?></span></div>
                        <div class="info-row"><span class="info-label">Father's Name</span> <span class="info-val"><?php echo $student['father_name']; ?></span></div>
                        <div class="info-row"><span class="info-label">Phone Number</span> <span class="info-val"><?php echo $student['phone']; ?></span></div>
                        <div class="info-row"><span class="info-label">Address</span> <span class="info-val"><?php echo $student['address']; ?></span></div>
                        <div class="info-row" style="border:none;"><span class="info-label">Date of Join</span> <span class="info-val"><?php echo $student['admission_date']; ?></span></div>
                    </div>

                    <div id="tab-att" class="tab-content">
                        <div class="stat-grid">
                            <div class="stat-card">
                                <div class="stat-num" style="color:#059669;"><?php echo $att_percentage; ?>%</div>
                                <div class="stat-txt">Attendance Rate</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-num"><?php echo $present_days; ?></div>
                                <div class="stat-txt">Days Present</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-num" style="color:#EF4444;"><?php echo $total_days - $present_days; ?></div>
                                <div class="stat-txt">Days Absent</div>
                            </div>
                        </div>
                        <h4 style="margin-bottom:15px; color:#334155;">Recent History</h4>
                        <table class="history-table">
                            <?php
                            if (mysqli_num_rows($att_history) > 0) {
                                while ($att = mysqli_fetch_assoc($att_history)) {
                                    $badge_class = "bg-p";
                                    $txt = "Present";
                                    if ($att['status'] == 'Absent') {
                                        $badge_class = "bg-a";
                                        $txt = "Absent";
                                    } elseif ($att['status'] == 'Late') {
                                        $badge_class = "bg-l";
                                        $txt = "Late";
                                    } elseif ($att['status'] == 'Holiday') {
                                        $badge_class = "bg-h";
                                        $txt = "Holiday";
                                    }
                                    echo "<tr><td><i class='fa-regular fa-calendar' style='color:#94A3B8; margin-right:10px;'></i> " . date('d M, Y', strtotime($att['date'])) . "</td><td style='text-align:right;'><span class='badge $badge_class'>$txt</span></td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='2' style='text-align:center; color:#94A3B8; padding:20px;'>No records found.</td></tr>";
                            }
                            ?>
                        </table>
                    </div>

                    <div id="tab-docs" class="tab-content">
                        <form method="POST" enctype="multipart/form-data" class="upload-area">
                            <input type="text" name="doc_title" placeholder="File Name (e.g. ID Card)" required style="flex:1; padding:10px; border:1px solid #CBD5E1; border-radius:6px; outline:none;">
                            <input type="file" name="doc_file" required style="font-size:13px;">
                            <button type="submit" name="upload_student_doc" style="background:#F17C1C; color:white; border:none; padding:10px 15px; border-radius:6px; cursor:pointer; font-weight:600;">Upload</button>
                        </form>

                        <div class="file-list">
                            <?php
                            if (mysqli_num_rows($stu_docs) > 0) {
                                while ($doc = mysqli_fetch_assoc($stu_docs)) {
                                    $ext = strtolower($doc['file_type']);
                                    $icon = "fa-file";
                                    $color = "#64748B"; // Default gray

                                    if ($ext == 'pdf') {
                                        $icon = 'fa-file-pdf';
                                        $color = '#EF4444';
                                    } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                        $icon = 'fa-image';
                                        $color = '#8B5CF6';
                                    } elseif (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                                        $icon = 'fa-file-video';
                                        $color = '#F97316';
                                    } elseif (in_array($ext, ['mp3', 'wav'])) {
                                        $icon = 'fa-file-audio';
                                        $color = '#EAB308';
                                    } elseif ($ext == 'txt') {
                                        $icon = 'fa-file-lines';
                                        $color = '#64748B';
                                    }

                                    echo "<div class='file-box'>
                                        <div class='file-icon' style='color:$color; cursor:pointer;' onclick=\"openPreview('" . htmlspecialchars($doc['title'], ENT_QUOTES) . "', 'uploads/" . $doc['file_path'] . "', '" . $doc['file_type'] . "')\"><i class='fa-regular $icon'></i></div>
                                        <div class='file-title'>" . $doc['title'] . "</div>
                                        <div class='file-actions'>
                                            <span class='action-link' onclick=\"openPreview('" . htmlspecialchars($doc['title'], ENT_QUOTES) . "', 'uploads/" . $doc['file_path'] . "', '" . $doc['file_type'] . "')\"><i class='fa-regular fa-eye'></i> View</span>
                                            <span class='action-link' onclick=\"openRenameModal('" . $doc['doc_id'] . "', '" . htmlspecialchars($doc['title'], ENT_QUOTES) . "')\"><i class='fa-solid fa-pen'></i> Edit</span>
                                            <a href='uploads/" . $doc['file_path'] . "' download class='action-link'><i class='fa-solid fa-download'></i> Get</a>
                                            <a href='student_view.php?id=$student_id&delete_doc=" . $doc['doc_id'] . "&path=" . $doc['file_path'] . "' class='action-link delete' onclick='return confirm(\"Delete this file?\")'><i class='fa-solid fa-trash'></i> Del</a>
                                        </div>
                                      </div>";
                                }
                            } else {
                                echo "<p style='width:100%; text-align:center; color:#94A3B8; margin-top:30px;'>No documents uploaded.</p>";
                            }
                            ?>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <div id="previewModal" class="modal-overlay">
        <div class="modal-content" style="width:800px; height:85vh; display:flex; flex-direction:column; padding:0; background:transparent; box-shadow:none;">
            <div style="background:white; padding:15px 20px; border-radius:12px 12px 0 0; display:flex; justify-content:space-between; align-items:center;">
                <h3 id="previewTitle" style="color:#1F293B; margin:0; font-size:18px;">Preview</h3>
                <div style="display:flex; gap:10px;">
                    <a id="downloadBtn" href="#" download style="padding:8px 15px; background:#F17C1C; color:white; text-decoration:none; border-radius:6px; font-size:13px; font-weight:600;"><i class="fa-solid fa-download"></i> Download</a>
                    <i class="fa-solid fa-xmark" onclick="closeModal('previewModal')" style="font-size:24px; color:#64748B; cursor:pointer; display:flex; align-items:center;"></i>
                </div>
            </div>
            <div id="previewBody" style="flex:1; background:#F1F5F9; border-radius:0 0 12px 12px; overflow:hidden; display:flex; align-items:center; justify-content:center; position:relative;"></div>
        </div>
    </div>

    <div id="renameModal" class="modal-overlay">
        <div class="modal-content" style="width:400px; height:auto;">
            <span class="close-modal" onclick="closeModal('renameModal')">&times;</span>
            <h3 style="margin-bottom:20px;">Rename Document</h3>
            <form action="" method="POST">
                <input type="hidden" name="rename_id" id="rename_id">
                <input type="text" name="rename_title" id="rename_title" class="modern-select" placeholder="New Title" required>
                <button type="submit" name="rename_doc" class="btn-update">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(evt, tabName) {
            var i, content, tablinks;
            content = document.getElementsByClassName("tab-content");
            for (i = 0; i < content.length; i++) {
                content[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        function openPreview(name, path, ext) {
            document.getElementById('previewModal').style.display = 'flex';
            document.getElementById('previewTitle').innerText = name;
            document.getElementById('downloadBtn').href = path;
            const container = document.getElementById('previewBody');
            container.innerHTML = '';

            var extLc = ext.toLowerCase();

            if (['jpg', 'jpeg', 'png', 'gif'].includes(extLc)) {
                container.innerHTML = `<img src="${path}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
            } else if (extLc === 'pdf') {
                container.innerHTML = `<iframe src="${path}" style="width:100%; height:100%; border:none;"></iframe>`;
            } else if (['mp4', 'webm', 'ogg'].includes(extLc)) {
                container.innerHTML = `<video src="${path}" controls style="max-width:100%; max-height:100%; outline:none; box-shadow:0 4px 10px rgba(0,0,0,0.1); border-radius:8px;"></video>`;
            } else if (['mp3', 'wav'].includes(extLc)) {
                container.innerHTML = `<audio src="${path}" controls style="width:80%; outline:none;"></audio>`;
            } else if (extLc === 'txt') {
                container.innerHTML = `<iframe src="${path}" style="width:100%; height:100%; border:none; background:white;"></iframe>`;
            } else {
                container.innerHTML = `<div style="text-align:center; color:#64748B;">No preview available for this file type.<br>Please download to view.</div>`;
            }
        }

        function closeModal(id) {
            // If id is provided, close that specific modal. Otherwise close previewModal by default for backward compatibility or specifics
            if (id) {
                document.getElementById(id).style.display = 'none';
                if (id === 'previewModal') document.getElementById('previewBody').innerHTML = '';
            } else {
                document.getElementById('previewModal').style.display = 'none';
                document.getElementById('previewBody').innerHTML = '';
            }
        }

        function openRenameModal(id, title) {
            document.getElementById('renameModal').style.display = 'flex';
            document.getElementById('rename_id').value = id;
            document.getElementById('rename_title').value = title;
        }
    </script>

</body>

</html>