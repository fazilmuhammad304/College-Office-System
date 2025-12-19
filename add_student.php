<?php
session_start();
include 'db_conn.php';
include 'google_drive.php'; // Drive Function

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if (isset($_POST['add_student'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $admission_no = mysqli_real_escape_string($conn, $_POST['admission_no']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $phone = $_POST['phone'];
    $father = mysqli_real_escape_string($conn, $_POST['father_name']);
    $program = $_POST['program'];
    $year = $_POST['year'];

    $class_year = ($year == "Graduated") ? "Graduated" : "$program $year";
    $status = "Active";

    // --- GOOGLE DRIVE UPLOAD ---
    $photo_link = "";
    if (!empty($_FILES['photo']['name'])) {
        $fileName = basename($_FILES['photo']['name']);
        $tempPath = $_FILES['photo']['tmp_name'];
        $uploadedUrl = uploadToDrive($tempPath, $fileName);
        if ($uploadedUrl) {
            $photo_link = $uploadedUrl;
        }
    }

    $sql = "INSERT INTO students (full_name, admission_no, gender, dob, phone, father_name, class_year, status, photo, admission_date) 
            VALUES ('$full_name', '$admission_no', '$gender', '$dob', '$phone', '$father', '$class_year', '$status', '$photo_link', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Student Added Successfully!'); window.location.href='students.php';</script>";
    } else {
        $message = "<div class='alert error'>Error: " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .form-container {
            background: white;
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #4B5563;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
        }

        .btn-submit {
            background: #ED8936;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php $page = 'students';
        include 'sidebar.php'; ?>
        <main class="main-content">
            <header class="top-header">
                <a href="students.php" style="font-size:20px; color:#6B7280; margin-right:10px;"><i class="fa-solid fa-arrow-left"></i></a>
                <h2>Add New Student</h2>
            </header>

            <?php echo $message; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                    <div class="form-group"><label>Admission No</label><input type="text" name="admission_no" required></div>
                    <div class="form-group"><label>Gender</label><select name="gender">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select></div>
                    <div class="form-group"><label>Date of Birth</label><input type="date" name="dob"></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
                    <div class="form-group"><label>Father Name</label><input type="text" name="father_name"></div>

                    <div class="form-group"><label>Program</label>
                        <select name="program" required>
                            <?php
                            $res = mysqli_query($conn, "SELECT program_name FROM programs");
                            while ($r = mysqli_fetch_assoc($res)) echo "<option value='{$r['program_name']}'>{$r['program_name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Year</label>
                        <select name="year">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                        </select>
                    </div>

                    <div class="form-group"><label>Student Photo</label><input type="file" name="photo"></div>

                    <button type="submit" name="add_student" class="btn-submit">Add Student</button>
                </form>
            </div>
        </main>
    </div>
</body>

</html>