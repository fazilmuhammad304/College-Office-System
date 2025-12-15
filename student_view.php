<?php
session_start();
include 'db_conn.php';

// 1. LOGIN CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. CHECK ID
if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}
$student_id = mysqli_real_escape_string($conn, $_GET['id']);
$message = "";

// --- FETCH PROGRAMS FOR DROPDOWN (FIXED) ---
$programs_result = mysqli_query($conn, "SELECT program_name FROM programs ORDER BY program_name ASC");

// --- ACTION 1: UPDATE CLASS/YEAR (Sidebar) ---
if (isset($_POST['update_class_info'])) {
    $program = mysqli_real_escape_string($conn, $_POST['program']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);

    // Combine Program + Year (e.g. "Hifz Class 1st Year")
    // If year is empty or N/A, just use Program name
    if (!empty($year)) {
        $new_class_year = "$program $year";
    } else {
        $new_class_year = $program;
    }

    $new_status = 'Active';
    if ($year == "Graduated" || $program == "Graduated") {
        $new_status = "Graduated";
        $new_class_year = "Graduated";
    }

    $sql = "UPDATE students SET class_year = '$new_class_year', status = '$new_status' WHERE student_id = '$student_id'";
    if (mysqli_query($conn, $sql)) {
        $message = "<div class='alert success'>✅ Class Updated!</div>";
        echo "<meta http-equiv='refresh' content='1'>";
    }
}

// --- ACTION 2: UPDATE PERSONAL INFO (Modal) ---
if (isset($_POST['update_personal_info'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $blood = $_POST['blood_group'];
    $father = mysqli_real_escape_string($conn, $_POST['father_name']);
    $mother = mysqli_real_escape_string($conn, $_POST['mother_name']);
    $phone = $_POST['phone'];
    $emergency = $_POST['emergency_phone'];
    $email = $_POST['email'];
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $health = mysqli_real_escape_string($conn, $_POST['health_issues']);

    $sql = "UPDATE students SET 
            full_name='$full_name', gender='$gender', dob='$dob', blood_group='$blood', 
            father_name='$father', mother_name='$mother', 
            phone='$phone', emergency_phone='$emergency', email='$email', 
            address='$address', health_issues='$health' 
            WHERE student_id='$student_id'";

    if (mysqli_query($conn, $sql)) {
        $message = "<div class='alert success'><i class='fa-solid fa-check-circle'></i> Profile Updated Successfully!</div>";
        echo "<meta http-equiv='refresh' content='1'>";
    } else {
        $message = "<div class='alert error'>Update Failed: " . mysqli_error($conn) . "</div>";
    }
}

// --- ACTION 3: UPLOAD DOCUMENT ---
if (isset($_POST['upload_student_doc'])) {
    $title = mysqli_real_escape_string($conn, $_POST['doc_title']);
    $filename = $_FILES['doc_file']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $new_filename = uniqid() . "." . $ext;

    $filesize = $_FILES['doc_file']['size'];
    $size_text = ($filesize >= 1048576) ? number_format($filesize / 1048576, 2) . ' MB' : number_format($filesize / 1024, 2) . ' KB';

    if (move_uploaded_file($_FILES['doc_file']['tmp_name'], "uploads/" . $new_filename)) {
        $sql = "INSERT INTO documents (title, category, file_path, file_type, file_size, student_id) 
                VALUES ('$title', 'Student File', '$new_filename', '$ext', '$size_text', '$student_id')";
        mysqli_query($conn, $sql);
        $message = "<div class='alert success'>Document added!</div>";
    }
}

// --- ACTION 4: DELETE DOCUMENT ---
if (isset($_GET['del_doc'])) {
    $doc_id = $_GET['del_doc'];
    $q = mysqli_query($conn, "SELECT file_path FROM documents WHERE doc_id='$doc_id'");
    $f = mysqli_fetch_assoc($q);
    if ($f && file_exists("uploads/" . $f['file_path'])) {
        unlink("uploads/" . $f['file_path']);
    }
    mysqli_query($conn, "DELETE FROM documents WHERE doc_id='$doc_id'");
    header("Location: student_view.php?id=$student_id");
    exit();
}

// --- FETCH DATA ---
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$student_id'"));

// Age Calc
$age = "N/A";
if (!empty($student['dob'])) {
    $age = date_diff(date_create($student['dob']), date_create('today'))->y . " Years";
}

// Stats & Docs
$att_stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present FROM attendance WHERE student_id='$student_id'"));
$att_perc = ($att_stats['total'] > 0) ? round(($att_stats['present'] / $att_stats['total']) * 100) : 0;
$att_history = mysqli_query($conn, "SELECT * FROM attendance WHERE student_id = '$student_id' ORDER BY date DESC LIMIT 30");
$stu_docs = mysqli_query($conn, "SELECT * FROM documents WHERE student_id = '$student_id' ORDER BY doc_id DESC");

// Current Class String
$curr_class = $student['class_year'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo $student['full_name']; ?> | Profile</title>
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

        .profile-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            text-align: center;
        }

        .profile-header-bg {
            background: linear-gradient(135deg, #F17C1C 0%, #EA580C 100%);
            height: 110px;
        }

        .profile-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 5px solid white;
            background: #F3F4F6;
            margin-top: -55px;
            object-fit: cover;
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

        /* --- TABS --- */
        .tabs-container {
            background: white;
            border-radius: 16px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            min-height: 550px;
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

        .tab-btn.active {
            color: #F17C1C;
            border-bottom-color: #F17C1C;
        }

        .tab-content {
            padding: 30px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* --- INFO SECTIONS --- */
        .info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 10px;
        }

        .info-title {
            font-size: 14px;
            font-weight: 700;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-edit-profile {
            background: white;
            border: 1px solid #CBD5E1;
            color: #475569;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-edit-profile:hover {
            border-color: #F17C1C;
            color: #F17C1C;
            background: #FFF7ED;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-item label {
            display: block;
            font-size: 12px;
            color: #64748B;
            margin-bottom: 4px;
        }

        .info-item span {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1E293B;
        }

        .health-box {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            padding: 15px;
            border-radius: 8px;
            color: #991B1B;
            font-size: 14px;
            margin-top: 10px;
        }

        /* --- DOCS --- */
        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .file-card {
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 15px;
            transition: 0.2s;
            display: flex;
            flex-direction: column;
        }

        .file-card:hover {
            transform: translateY(-3px);
            border-color: #ED8936;
        }

        .file-icon-box {
            height: 70px;
            background: #F9FAFB;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 12px;
        }

        /* --- MODAL --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
        }

        .modal-content {
            background: white;
            width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 30px;
            border-radius: 16px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 20px;
            cursor: pointer;
            color: #9CA3AF;
        }

        .edit-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #4B5563;
            display: block;
            margin-bottom: 5px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn-save-modal {
            background: #F17C1C;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .success {
            background: #ECFDF5;
            color: #065F46;
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
                    <div style="padding: 15px 25px 30px;">
                        <h2 style="margin:10px 0 5px; font-size:20px;"><?php echo $student['full_name']; ?></h2>
                        <p style="color:#6B7280; font-size:14px; margin:0;"><?php echo $student['admission_no']; ?></p>
                        <div style="margin-top:8px; font-weight:700; color:#F17C1C; font-size:15px;"><?php echo $student['class_year']; ?></div>
                        <span class="status-pill"><?php echo $student['status']; ?></span>

                        <form method="POST" style="background:#FFF7ED; border:1px solid #FFEDD5; padding:20px; border-radius:12px; margin-top:25px; text-align:left;">
                            <span style="font-size:12px; font-weight:700; color:#C2410C; text-transform:uppercase; margin-bottom:10px; display:block;">Academic Actions</span>

                            <select name="program" class="form-select" style="margin-bottom:10px;" required>
                                <option value="">Select Program</option>
                                <?php
                                // Reset pointer just in case
                                mysqli_data_seek($programs_result, 0);
                                while ($prog_row = mysqli_fetch_assoc($programs_result)) {
                                    $pName = $prog_row['program_name'];
                                    // Simple check if current class string contains this program name
                                    $selected = (strpos($curr_class, $pName) !== false) ? 'selected' : '';
                                    echo "<option value='$pName' $selected>$pName</option>";
                                }
                                ?>
                            </select>

                            <select name="year" class="form-select" style="margin-bottom:10px;">
                                <option value="">Select Year (Optional)</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="Final Year">Final Year</option>
                                <option value="Graduated">Graduated</option>
                            </select>
                            <button type="submit" name="update_class_info" style="width:100%; background:#F17C1C; color:white; border:none; padding:10px; border-radius:6px; cursor:pointer;">Update</button>
                        </form>
                    </div>
                </div>

                <div class="tabs-container">
                    <div class="tabs-header">
                        <div class="tab-btn active" onclick="switchTab(event, 'tab-info')"><i class="fa-regular fa-id-card"></i> Info</div>
                        <div class="tab-btn" onclick="switchTab(event, 'tab-att')"><i class="fa-solid fa-chart-pie"></i> Attendance</div>
                        <div class="tab-btn" onclick="switchTab(event, 'tab-docs')"><i class="fa-regular fa-folder-open"></i> Documents</div>
                    </div>

                    <div id="tab-info" class="tab-content active">
                        <div class="info-header">
                            <span class="info-title">Basic Information</span>
                            <button class="btn-edit-profile" onclick="openEditModal()"><i class="fa-solid fa-pen"></i> Edit Details</button>
                        </div>

                        <div class="info-grid">
                            <div class="info-item"><label>Full Name</label> <span><?php echo $student['full_name']; ?></span></div>
                            <div class="info-item"><label>Admission No</label> <span><?php echo $student['admission_no']; ?></span></div>
                            <div class="info-item"><label>Gender</label> <span><?php echo !empty($student['gender']) ? $student['gender'] : '-'; ?></span></div>
                            <div class="info-item"><label>DOB / Age</label> <span><?php echo !empty($student['dob']) ? $student['dob'] . " ($age)" : '-'; ?></span></div>
                            <div class="info-item"><label>Blood Group</label> <span><?php echo !empty($student['blood_group']) ? $student['blood_group'] : '-'; ?></span></div>
                            <div class="info-item"><label>Admission Date</label> <span><?php echo $student['admission_date']; ?></span></div>
                        </div>

                        <div class="info-title" style="margin-bottom:15px; border-bottom:1px solid #F1F5F9; padding-bottom:5px;">Family & Contact</div>
                        <div class="info-grid">
                            <div class="info-item"><label>Father's Name</label> <span><?php echo $student['father_name']; ?></span></div>
                            <div class="info-item"><label>Mother's Name</label> <span><?php echo !empty($student['mother_name']) ? $student['mother_name'] : '-'; ?></span></div>
                            <div class="info-item"><label>Phone</label> <span><?php echo $student['phone']; ?></span></div>
                            <div class="info-item"><label>Emergency</label> <span style="color:#EF4444;"><?php echo !empty($student['emergency_phone']) ? $student['emergency_phone'] : '-'; ?></span></div>
                            <div class="info-item"><label>Email</label> <span><?php echo !empty($student['email']) ? $student['email'] : '-'; ?></span></div>
                            <div class="info-item"><label>Address</label> <span><?php echo $student['address']; ?></span></div>
                        </div>

                        <?php if (!empty($student['health_issues'])): ?>
                            <div class="health-box">
                                <i class="fa-solid fa-notes-medical"></i> <b>Medical Alert:</b><br>
                                <?php echo nl2br($student['health_issues']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="tab-att" class="tab-content">
                        <div style="display:flex; gap:20px; margin-bottom:20px;">
                            <div style="flex:1; background:#F8FAFC; padding:20px; border-radius:12px; text-align:center; border:1px solid #E2E8F0;">
                                <div style="color:#059669; font-size:24px; font-weight:800;"><?php echo $att_perc; ?>%</div>
                                <div style="font-size:12px; font-weight:600; color:#64748B;">ATTENDANCE RATE</div>
                            </div>
                            <div style="flex:1; background:#F8FAFC; padding:20px; border-radius:12px; text-align:center; border:1px solid #E2E8F0;">
                                <div style="font-size:24px; font-weight:800; color:#1E293B;"><?php echo $att_stats['present']; ?></div>
                                <div style="font-size:12px; font-weight:600; color:#64748B;">DAYS PRESENT</div>
                            </div>
                        </div>
                        <table style="width:100%; font-size:14px; border-collapse:collapse;">
                            <?php while ($att = mysqli_fetch_assoc($att_history)) {
                                $col = ($att['status'] == 'Present') ? '#166534' : '#991B1B';
                                echo "<tr><td style='padding:10px 0; border-bottom:1px solid #F1F5F9;'>" . date('d M Y', strtotime($att['date'])) . "</td><td style='text-align:right; border-bottom:1px solid #F1F5F9; color:$col; font-weight:700;'>" . $att['status'] . "</td></tr>";
                            } ?>
                        </table>
                    </div>

                    <div id="tab-docs" class="tab-content">
                        <form method="POST" enctype="multipart/form-data" style="background:#F8FAFC; padding:15px; border-radius:12px; border:2px dashed #CBD5E1; margin-bottom:20px; display:flex; gap:10px;">
                            <input type="text" name="doc_title" placeholder="Document Name" required style="flex:1; padding:8px; border:1px solid #CBD5E1; border-radius:6px; outline:none;">
                            <input type="file" name="doc_file" required style="padding:5px;">
                            <button type="submit" name="upload_student_doc" style="background:#F17C1C; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer;">Upload</button>
                        </form>
                        <div class="doc-grid">
                            <?php if (mysqli_num_rows($stu_docs) > 0): ?>
                                <?php while ($doc = mysqli_fetch_assoc($stu_docs)):
                                    $ext = strtolower($doc['file_type']);
                                    $icon = "fa-file";
                                    $bg = "#F1F5F9";
                                    $col = "#64748B";
                                    if (in_array($ext, ['pdf'])) {
                                        $icon = "fa-file-pdf";
                                        $bg = "#FEF2F2";
                                        $col = "#EF4444";
                                    }
                                    if (in_array($ext, ['jpg', 'png'])) {
                                        $icon = "fa-image";
                                        $bg = "#F5F3FF";
                                        $col = "#8B5CF6";
                                    }
                                ?>
                                    <div class="file-card">
                                        <div class="file-icon-box" style="background:<?php echo $bg; ?>; color:<?php echo $col; ?>;" onclick="window.open('uploads/<?php echo $doc['file_path']; ?>','_blank')">
                                            <i class="fa-regular <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="file-name" title="<?php echo $doc['title']; ?>"><?php echo $doc['title']; ?></div>
                                        <div class="file-meta"><?php echo $doc['file_size']; ?></div>
                                        <div class="file-actions">
                                            <a href="uploads/<?php echo $doc['file_path']; ?>" target="_blank" class="action-btn">View</a>
                                            <a href="uploads/<?php echo $doc['file_path']; ?>" download class="action-btn">Get</a>
                                            <a href="student_view.php?id=<?php echo $student_id; ?>&del_doc=<?php echo $doc['doc_id']; ?>" class="action-btn btn-del" onclick="return confirm('Delete?')">Del</a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p style="grid-column:1/-1; text-align:center; color:#9CA3AF;">No documents uploaded.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3 style="margin-bottom:20px;">Edit Personal Details</h3>
            <form action="" method="POST">
                <div class="edit-grid">
                    <div><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-input" value="<?php echo htmlspecialchars($student['full_name']); ?>" required></div>
                    <div><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-input" value="<?php echo $student['dob']; ?>"></div>
                </div>
                <div class="edit-grid">
                    <div><label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="Male" <?php if ($student['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if ($student['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                        </select>
                    </div>
                    <div><label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-select">
                            <option value="<?php echo $student['blood_group']; ?>" selected><?php echo $student['blood_group']; ?></option>
                            <option value="A+">A+</option>
                            <option value="O+">O+</option>
                            <option value="B+">B+</option>
                            <option value="AB+">AB+</option>
                        </select>
                    </div>
                </div>
                <div class="edit-grid">
                    <div><label class="form-label">Father's Name</label><input type="text" name="father_name" class="form-input" value="<?php echo htmlspecialchars($student['father_name']); ?>"></div>
                    <div><label class="form-label">Mother's Name</label><input type="text" name="mother_name" class="form-input" value="<?php echo htmlspecialchars($student['mother_name']); ?>"></div>
                </div>
                <div class="edit-grid">
                    <div><label class="form-label">Phone</label><input type="text" name="phone" class="form-input" value="<?php echo $student['phone']; ?>"></div>
                    <div><label class="form-label">Emergency Phone</label><input type="text" name="emergency_phone" class="form-input" value="<?php echo $student['emergency_phone']; ?>"></div>
                </div>
                <div style="margin-bottom:15px;"><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="<?php echo $student['email']; ?>"></div>
                <div style="margin-bottom:15px;"><label class="form-label">Address</label><input type="text" name="address" class="form-input" value="<?php echo htmlspecialchars($student['address']); ?>"></div>
                <div style="margin-bottom:15px;"><label class="form-label">Health Issues</label><textarea name="health_issues" class="form-input" rows="2"><?php echo htmlspecialchars($student['health_issues']); ?></textarea></div>

                <button type="submit" name="update_personal_info" class="btn-save-modal">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(evt, tabName) {
            var i, x, tablinks;
            x = document.getElementsByClassName("tab-content");
            for (i = 0; i < x.length; i++) {
                x[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        function openEditModal() {
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) closeModal();
        }
    </script>
</body>

</html>