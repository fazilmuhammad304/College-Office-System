<?php
session_start();
include 'db_conn.php';

// 1. பாதுகாப்பு: லாகின் செய்யவில்லை என்றால் வெளியே அனுப்பு
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// 2. சேவ் பட்டன் அழுத்தப்பட்டால்
if (isset($_POST['save_student'])) {
    // A. Personal & Family Details
    $admission_no = mysqli_real_escape_string($conn, $_POST['admission_no']);
    $admission_date = $_POST['admission_date'];
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $blood_group = $_POST['blood_group'];

    $father_name = mysqli_real_escape_string($conn, $_POST['father_name']);
    $mother_name = mysqli_real_escape_string($conn, $_POST['mother_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $emergency = mysqli_real_escape_string($conn, $_POST['emergency_phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $health = mysqli_real_escape_string($conn, $_POST['health_issues']);

    // B. Academic Details (Combine Program + Year)
    $program = $_POST['program'];
    $year = $_POST['year'];

    // கோர்ஸ் மற்றும் வருடத்தை இணைத்தல் (எ.கா: "Hifz Class 1st Year")
    // வருடம் தேர்ந்தெடுக்கப்படவில்லை என்றால் கோர்ஸ் பெயர் மட்டும் வரும்
    if (!empty($year)) {
        $class_year = "$program $year";
    } else {
        $class_year = $program;
    }

    // C. Handle Student Photo
    $photo = $_FILES['photo']['name'];
    $photo_target = "uploads/" . basename($photo);
    if (!empty($photo)) {
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo_target);
    }

    // D. INSERT STUDENT DATA
    $sql = "INSERT INTO students (admission_no, admission_date, full_name, gender, dob, blood_group, father_name, mother_name, phone, emergency_phone, email, address, health_issues, class_year, photo, status) 
            VALUES ('$admission_no', '$admission_date', '$full_name', '$gender', '$dob', '$blood_group', '$father_name', '$mother_name', '$phone', '$emergency', '$email', '$address', '$health', '$class_year', '$photo', 'Active')";

    if (mysqli_query($conn, $sql)) {
        $student_id = mysqli_insert_id($conn); // Get the new Student ID

        // E. HANDLE DOCUMENT UPLOADS (Loop through the files)
        $doc_types = ['birth_cert' => 'Birth Certificate', 'nic_copy' => 'ID Card/NIC', 'leaving_cert' => 'School Leaving Cert', 'medical_report' => 'Medical Report'];

        foreach ($doc_types as $input_name => $doc_title) {
            if (!empty($_FILES[$input_name]['name'])) {
                $file_name = $_FILES[$input_name]['name'];
                $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                // பாதுகாப்பான ஃபைல் வகைகளை மட்டும் அனுமதித்தல் (Security Fix)
                $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

                if (in_array($ext, $allowed_types)) {
                    $new_name = uniqid() . "_$input_name." . $ext;

                    // Size calculation
                    $f_size = $_FILES[$input_name]['size'];
                    $file_size = ($f_size >= 1048576) ? number_format($f_size / 1048576, 2) . ' MB' : number_format($f_size / 1024, 2) . ' KB';

                    if (move_uploaded_file($_FILES[$input_name]['tmp_name'], "uploads/" . $new_name)) {
                        // Save to Documents Table linked to this student
                        $doc_sql = "INSERT INTO documents (title, category, file_path, file_type, file_size, student_id) 
                                    VALUES ('$doc_title', 'Student File', '$new_name', '$ext', '$file_size', '$student_id')";
                        mysqli_query($conn, $doc_sql);
                    }
                }
            }
        }

        $message = "<div class='alert success'><i class='fa-solid fa-check-circle'></i> Student Admission Successful! ID Generated.</div>";
    } else {
        $message = "<div class='alert error'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// --- [FIX] FETCH PROGRAMS FOR DROPDOWN ---
// Programs டேபிளில் இருந்து பெயர்களை எடுக்கிறோம்
$prog_query = "SELECT program_name FROM programs ORDER BY program_name ASC";
$prog_result = mysqli_query($conn, $prog_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>New Admission | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* --- FORM LAYOUT STYLES --- */
        .form-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            border: 1px solid #E5E7EB;
        }

        .form-header {
            background: #ED8936;
            padding: 20px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-header h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .form-body {
            padding: 30px;
        }

        /* Sections */
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #ED8936;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #FFF7ED;
            padding-bottom: 8px;
            margin-bottom: 20px;
            margin-top: 10px;
        }

        .first-section {
            margin-top: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Inputs */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #4B5563;
        }

        .form-input,
        .form-select {
            padding: 10px 12px;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            background: #F9FAFB;
        }

        .form-input:focus,
        .form-select:focus {
            border-color: #ED8936;
            background: white;
            box-shadow: 0 0 0 3px rgba(237, 137, 54, 0.1);
        }

        /* File Upload Areas */
        .doc-upload-box {
            border: 1px dashed #D1D5DB;
            padding: 15px;
            border-radius: 8px;
            background: #FAFAFA;
            text-align: center;
        }

        .doc-label {
            display: block;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .doc-input {
            font-size: 12px;
            width: 100%;
        }

        /* Buttons */
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .btn-cancel {
            padding: 10px 25px;
            border: 1px solid #D1D5DB;
            background: white;
            color: #4B5563;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-save {
            padding: 10px 30px;
            background: #ED8936;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-save:hover {
            background: #D67625;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        @media (max-width: 768px) {

            .form-grid,
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php $page = 'add_student';
        include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header">
                <h2>Student Management</h2>
            </header>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-container">

                    <div class="form-header">
                        <h2><i class="fa-solid fa-user-plus"></i> New Student Admission</h2>
                        <span style="font-size:12px; background:rgba(255,255,255,0.2); padding:5px 10px; border-radius:20px;">Academic Year 2025</span>
                    </div>

                    <div class="form-body">
                        <?php echo $message; ?>

                        <div class="section-title first-section">Academic Details</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Admission No <span style="color:red">*</span></label>
                                <input type="text" name="admission_no" class="form-input" required placeholder="e.g. 2025/001">
                            </div>
                            <div class="form-group">
                                <label>Date of Admission <span style="color:red">*</span></label>
                                <input type="date" name="admission_date" class="form-input" required value="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label>Program / Course <span style="color:red">*</span></label>
                                <select name="program" class="form-select" required>
                                    <option value="">Select Program</option>
                                    <?php
                                    if (mysqli_num_rows($prog_result) > 0) {
                                        while ($row = mysqli_fetch_assoc($prog_result)) {
                                            echo "<option value='" . $row['program_name'] . "'>" . $row['program_name'] . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Year / Level (Optional)</label>
                                <select name="year" class="form-select">
                                    <option value="">Select Year</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                    <option value="Final Year">Final Year</option>
                                </select>
                            </div>
                        </div>

                        <div class="section-title">Personal Information</div>
                        <div class="form-grid">
                            <div class="form-group" style="grid-column: span 2;">
                                <label>Full Name <span style="color:red">*</span></label>
                                <input type="text" name="full_name" class="form-input" required placeholder="Student's full name">
                            </div>
                            <div class="form-group">
                                <label>Gender <span style="color:red">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth <span style="color:red">*</span></label>
                                <input type="date" name="dob" id="dob" class="form-input" required onchange="calculateAge()">
                            </div>
                            <div class="form-group">
                                <label>Age (Auto)</label>
                                <input type="text" id="age" class="form-input" readonly style="background:#EEE;">
                            </div>
                            <div class="form-group">
                                <label>Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option value="">Unknown</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>
                        </div>

                        <div class="section-title">Family & Contact</div>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label>Father's Name <span style="color:red">*</span></label>
                                <input type="text" name="father_name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label>Mother's Name</label>
                                <input type="text" name="mother_name" class="form-input">
                            </div>
                            <div class="form-group">
                                <label>Primary Phone <span style="color:red">*</span></label>
                                <input type="text" name="phone" class="form-input" required placeholder="077xxxxxxx">
                            </div>
                            <div class="form-group">
                                <label>Emergency Phone</label>
                                <input type="text" name="emergency_phone" class="form-input" placeholder="Alternative contact">
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-input" placeholder="student@example.com">
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label>Residential Address</label>
                                <textarea name="address" class="form-input" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="section-title">Health Information</div>
                        <div class="form-grid-2">
                            <div class="form-group" style="grid-column: span 2;">
                                <label>Known Health Issues / Allergies</label>
                                <textarea name="health_issues" class="form-input" rows="2" placeholder="Describe any medical conditions..."></textarea>
                            </div>
                        </div>

                        <div class="section-title">Documents Upload</div>
                        <div class="form-grid">
                            <div class="doc-upload-box">
                                <span class="doc-label">Student Photo</span>
                                <input type="file" name="photo" class="doc-input" accept="image/*">
                            </div>
                            <div class="doc-upload-box">
                                <span class="doc-label">Birth Certificate</span>
                                <input type="file" name="birth_cert" class="doc-input" accept=".pdf,.jpg,.png,.doc,.docx">
                            </div>
                            <div class="doc-upload-box">
                                <span class="doc-label">ID Copy / NIC</span>
                                <input type="file" name="nic_copy" class="doc-input" accept=".pdf,.jpg,.png,.doc,.docx">
                            </div>
                            <div class="doc-upload-box">
                                <span class="doc-label">School Leaving Cert</span>
                                <input type="file" name="leaving_cert" class="doc-input" accept=".pdf,.jpg,.png,.doc,.docx">
                            </div>
                            <div class="doc-upload-box">
                                <span class="doc-label">Medical Report (If any)</span>
                                <input type="file" name="medical_report" class="doc-input" accept=".pdf,.jpg,.png,.doc,.docx">
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="students.php" class="btn-cancel">Cancel</a>
                            <button type="submit" name="save_student" class="btn-save">
                                <i class="fa-solid fa-save"></i> Save Admission
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        function calculateAge() {
            var dob = document.getElementById('dob').value;
            if (dob) {
                var today = new Date();
                var birthDate = new Date(dob);
                var age = today.getFullYear() - birthDate.getFullYear();
                var m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                document.getElementById('age').value = age + " Years";
            }
        }
    </script>

</body>

</html>