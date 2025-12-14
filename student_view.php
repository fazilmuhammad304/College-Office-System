<?php
session_start();
include 'db_conn.php';

// 1. லாகின் செக்
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 2. மாணவர் ID உள்ளதா என பார்த்தல்
if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = mysqli_real_escape_string($conn, $_GET['id']);
$message = "";

// ---------------------------------------------------------
// 1. UPDATE CLASS & YEAR LOGIC (Fixed)
// ---------------------------------------------------------
if (isset($_POST['update_class_info'])) {
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);

    // Course மற்றும் Year-ஐ இணைத்தல்
    // Hifz Class-க்கும் வருடம் சேரும்படி மாற்றப்பட்டுள்ளது
    $new_class_year = (!empty($year)) ? "$program $year" : $program;

    // Status Update (Graduated Logic)
    $new_status = 'Active';
    if ($year == "Graduated" || $program == "Graduated") {
        $new_status = "Graduated";
        $new_class_year = "Graduated";
    }

    $sql = "UPDATE students SET class_year = '$new_class_year', status = '$new_status' WHERE student_id = '$student_id'";

    if (mysqli_query($conn, $sql)) {
        $message = "<div class='alert success'>✅ Class & Year Updated Successfully!</div>";
        // பக்கத்தை உடனே ரீப்ரெஷ் செய்ய (To show changes immediately)
        echo "<meta http-equiv='refresh' content='1'>";
    } else {
        $message = "<div class='alert error'>❌ Update Failed: " . mysqli_error($conn) . "</div>";
    }
}

// ---------------------------------------------------------
// 2. STUDENT DOCUMENT UPLOAD
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
        $message = "<div class='alert success'>Document added!</div>";
    }
}

// ---------------------------------------------------------
// 3. DATA FETCHING
// ---------------------------------------------------------
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$student_id'"));

// Attendance Stats
$total_days = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM attendance WHERE student_id = '$student_id'"));
$present_days = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM attendance WHERE student_id = '$student_id' AND status='Present'"));
$att_percentage = ($total_days > 0) ? round(($present_days / $total_days) * 100) : 0;
$att_history = mysqli_query($conn, "SELECT * FROM attendance WHERE student_id = '$student_id' ORDER BY date DESC LIMIT 30");
$stu_docs = mysqli_query($conn, "SELECT * FROM documents WHERE student_id = '$student_id' ORDER BY doc_id DESC");

// ---------------------------------------------------------
// 4. PRE-SELECT DROPDOWN LOGIC
// ---------------------------------------------------------
$current_class_str = $student['class_year'];
$current_prog = "";
$current_year = "";

if (strpos($current_class_str, "Hifz") !== false) {
    $current_prog = "Hifz Class";
    // Hifz-க்கு அடுத்து வரும் வருடத்தை எடுக்க
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
        /* --- CSS STYLES --- */
        .profile-layout {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 25px;
            align-items: start;
        }

        .profile-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            text-align: center;
        }

        .profile-header-bg {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
            height: 100px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid white;
            background: #F3F4F6;
            margin-top: -50px;
            object-fit: cover;
        }

        .profile-body {
            padding: 15px 20px 25px;
        }

        .status-pill {
            background: #ECFDF5;
            color: #059669;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-top: 5px;
        }

        /* Dropdown Box */
        .level-update-box {
            background: #F9FAFB;
            border: 1px solid #E5E7EB;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: left;
        }

        .form-label {
            font-size: 11px;
            font-weight: 700;
            color: #6B7280;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }

        .form-select {
            width: 100%;
            padding: 8px;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 13px;
            outline: none;
            background: white;
        }

        .btn-update-level {
            width: 100%;
            background: #2563EB;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-update-level:hover {
            background: #1D4ED8;
        }

        /* Tabs */
        .tabs-container {
            background: white;
            border-radius: 16px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            min-height: 500px;
        }

        .tabs-header {
            display: flex;
            border-bottom: 1px solid #E5E7EB;
            background: #FAFAFA;
        }

        .tab-btn {
            padding: 15px 25px;
            cursor: pointer;
            font-weight: 600;
            color: #6B7280;
            border-bottom: 3px solid transparent;
            transition: 0.2s;
        }

        .tab-btn:hover {
            color: #374151;
        }

        .tab-btn.active {
            color: #2563EB;
            border-bottom-color: #2563EB;
            background: white;
        }

        .tab-content {
            padding: 30px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #F3F4F6;
        }

        .detail-label {
            width: 150px;
            color: #6B7280;
            font-weight: 500;
        }

        .detail-value {
            color: #111827;
            font-weight: 600;
        }

        .att-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-box {
            flex: 1;
            background: #F9FAFB;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #F3F4F6;
        }

        .badge-p {
            background: #DCFCE7;
            color: #166534;
            padding: 3px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-a {
            background: #FEE2E2;
            color: #991B1B;
            padding: 3px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .doc-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .doc-item {
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .success {
            background: #ECFDF5;
            color: #065F46;
        }

        .error {
            background: #FEF2F2;
            color: #991B1B;
        }

        @media (max-width: 900px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
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
                    <a href="students.php" style="color:#6B7280; font-size:20px;"><i class="fa-solid fa-arrow-left"></i></a>
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
                        <h3 style="margin:0; font-size:18px;"><?php echo $student['full_name']; ?></h3>
                        <p style="color:#6B7280; font-size:14px; margin:5px 0;"><?php echo $student['admission_no']; ?></p>

                        <div style="margin-top:5px; font-weight:bold; color:#2563EB;">
                            <?php echo $student['class_year']; ?>
                        </div>
                        <span class="status-pill"><?php echo $student['status']; ?></span>

                        <form method="POST" class="level-update-box" action="student_view.php?id=<?php echo $student_id; ?>">
                            <label class="form-label">Change Course</label>
                            <select name="program" class="form-select">
                                <option value="Hifz Class" <?php if ($current_prog == 'Hifz Class') echo 'selected'; ?>>Hifz Class</option>
                                <option value="Al-Alim" <?php if ($current_prog == 'Al-Alim') echo 'selected'; ?>>Al-Alim</option>
                                <option value="Al-Alimah" <?php if ($current_prog == 'Al-Alimah') echo 'selected'; ?>>Al-Alimah</option>
                            </select>

                            <label class="form-label">Change Year</label>
                            <select name="year" class="form-select">
                                <option value="">(Select Year)</option>
                                <option value="1st Year" <?php if (strpos($current_year, '1st') !== false) echo 'selected'; ?>>1st Year</option>
                                <option value="2nd Year" <?php if (strpos($current_year, '2nd') !== false) echo 'selected'; ?>>2nd Year</option>
                                <option value="3rd Year" <?php if (strpos($current_year, '3rd') !== false) echo 'selected'; ?>>3rd Year</option>
                                <option value="4th Year" <?php if (strpos($current_year, '4th') !== false) echo 'selected'; ?>>4th Year</option>
                                <option value="Final Year" <?php if (strpos($current_year, 'Final') !== false) echo 'selected'; ?>>Final Year</option>
                                <option value="Graduated">Graduated</option>
                            </select>

                            <button type="submit" name="update_class_info" class="btn-update-level">
                                Update Class Info
                            </button>
                        </form>
                    </div>
                </div>

                <div class="tabs-container">
                    <div class="tabs-header">
                        <div class="tab-btn active" onclick="switchTab(event, 'tab-info')"><i class="fa-regular fa-user"></i> Personal Info</div>
                        <div class="tab-btn" onclick="switchTab(event, 'tab-att')"><i class="fa-regular fa-calendar-check"></i> Attendance</div>
                        <div class="tab-btn" onclick="switchTab(event, 'tab-docs')"><i class="fa-regular fa-folder-open"></i> Documents</div>
                    </div>

                    <div id="tab-info" class="tab-content active">
                        <h4 style="margin-bottom:20px; color:#374151;">Details</h4>
                        <div class="detail-row"><span class="detail-label">Full Name:</span> <span class="detail-value"><?php echo $student['full_name']; ?></span></div>
                        <div class="detail-row"><span class="detail-label">Admission No:</span> <span class="detail-value"><?php echo $student['admission_no']; ?></span></div>
                        <div class="detail-row"><span class="detail-label">Father's Name:</span> <span class="detail-value"><?php echo $student['father_name']; ?></span></div>
                        <div class="detail-row"><span class="detail-label">Phone:</span> <span class="detail-value"><?php echo $student['phone']; ?></span></div>
                        <div class="detail-row"><span class="detail-label">Address:</span> <span class="detail-value"><?php echo $student['address']; ?></span></div>
                    </div>

                    <div id="tab-att" class="tab-content">
                        <div class="att-summary">
                            <div class="stat-box">
                                <div class="stat-val" style="color:#059669;"><?php echo $att_percentage; ?>%</div>
                                <div class="stat-lbl">Attendance Rate</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-val"><?php echo $present_days; ?></div>
                                <div class="stat-lbl">Days Present</div>
                            </div>
                        </div>
                        <table class="att-list" style="width:100%;">
                            <?php
                            if (mysqli_num_rows($att_history) > 0) {
                                while ($att = mysqli_fetch_assoc($att_history)) {
                                    $badge = ($att['status'] == 'Present') ? "<span class='badge-p'>Present</span>" : "<span class='badge-a'>Absent</span>";
                                    echo "<tr><td>" . date('d M Y', strtotime($att['date'])) . "</td><td style='text-align:right;'>$badge</td></tr>";
                                }
                            } else {
                                echo "<tr><td style='color:#999;'>No records found.</td></tr>";
                            }
                            ?>
                        </table>
                    </div>

                    <div id="tab-docs" class="tab-content">
                        <form method="POST" enctype="multipart/form-data" style="background:#F9FAFB; padding:15px; border-radius:10px; border:1px dashed #D1D5DB;">
                            <div style="display:flex; gap:10px;">
                                <input type="text" name="doc_title" placeholder="Document Name" required style="flex:1; padding:8px; border:1px solid #E5E7EB; border-radius:5px;">
                                <input type="file" name="doc_file" required style="padding:5px;">
                                <button type="submit" name="upload_student_doc" style="background:#2563EB; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">Upload</button>
                            </div>
                        </form>
                        <div class="doc-list">
                            <?php
                            if (mysqli_num_rows($stu_docs) > 0) {
                                while ($doc = mysqli_fetch_assoc($stu_docs)) {
                                    echo "<div class='doc-item'>
                                        <div style='font-size:30px; color:#6B7280; margin-bottom:10px;'><i class='fa-regular fa-file'></i></div>
                                        <div style='font-weight:600; font-size:13px;'>" . $doc['title'] . "</div>
                                        <a href='uploads/" . $doc['file_path'] . "' download style='font-size:12px; color:#2563EB;'>Download</a>
                                      </div>";
                                }
                            } else {
                                echo "<p style='width:100%; text-align:center; color:#999; margin-top:20px;'>No docs.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
    </script>

</body>

</html>