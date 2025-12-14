<?php
session_start();
include 'db_conn.php';

// 1. லாகின் செக்
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 2. ID உள்ளதா என பார்த்தல்
if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$message = "";

// 3. மாணவர் தகவல்களை எடுத்தல்
$sql = "SELECT * FROM students WHERE student_id = '$id'";
$result = mysqli_query($conn, $sql);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    echo "Student not found!";
    exit();
}

// 4. அப்டேட் செய்தல்
if (isset($_POST['update_student'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $admission_no = mysqli_real_escape_string($conn, $_POST['admission_no']);
    $class_year = mysqli_real_escape_string($conn, $_POST['class_year']);
    $father_name = mysqli_real_escape_string($conn, $_POST['father_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $status = $_POST['status'];

    // போட்டோ மாற்றம்
    $photo_name = $student['photo'];

    if (!empty($_FILES['photo']['name'])) {
        $new_photo = $_FILES['photo']['name'];
        $target = "uploads/" . basename($new_photo);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $photo_name = $new_photo;
        }
    }

    $update_sql = "UPDATE students SET 
                   full_name='$full_name', 
                   admission_no='$admission_no', 
                   class_year='$class_year', 
                   father_name='$father_name', 
                   phone='$phone', 
                   address='$address', 
                   status='$status',
                   photo='$photo_name' 
                   WHERE student_id='$id'";

    if (mysqli_query($conn, $update_sql)) {
        $message = "<div class='alert success'>Student Details Updated Successfully!</div>";
        // Refresh Data
        $student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE student_id = '$id'"));
    } else {
        $message = "<div class='alert error'>Error: " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #4B5563;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            outline: none;
            background: #F9FAFB;
        }

        .form-group input:focus {
            border-color: #2563EB;
            background: white;
        }

        .btn-update {
            background: #2563EB;
            color: white;
            padding: 12px;
            border-radius: 8px;
            border: none;
            width: 100%;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            margin-top: 20px;
        }

        .btn-update:hover {
            background: #1D4ED8;
        }

        .current-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #E5E7EB;
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
                    <h2>Edit Student Details</h2>
                </div>
            </header>

            <?php echo $message; ?>

            <div class="form-container">
                <form action="" method="POST" enctype="multipart/form-data">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Admission No</label>
                            <input type="text" name="admission_no" value="<?php echo htmlspecialchars($student['admission_no']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Program / Class</label>
                            <select name="class_year" required>
                                <option value="<?php echo htmlspecialchars($student['class_year']); ?>" selected><?php echo htmlspecialchars($student['class_year']); ?> (Current)</option>
                                <option value="Hifz Class">Hifz Class</option>
                                <option value="Al-Alim 1st Year">Al-Alim 1st Year</option>
                                <option value="Al-Alim 2nd Year">Al-Alim 2nd Year</option>
                                <option value="Al-Alim 3rd Year">Al-Alim 3rd Year</option>
                                <option value="Al-Alim Final Year">Al-Alim Final Year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" value="<?php echo htmlspecialchars($student['father_name']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="Active" <?php if ($student['status'] == 'Active') echo 'selected'; ?>>Active</option>
                                <option value="Inactive" <?php if ($student['status'] == 'Inactive') echo 'selected'; ?>>Inactive</option>
                                <option value="Graduated" <?php if ($student['status'] == 'Graduated') echo 'selected'; ?>>Graduated</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                    </div>

                    <div class="form-row" style="align-items:center;">
                        <div class="form-group">
                            <label>Change Photo</label>
                            <input type="file" name="photo">
                        </div>
                        <?php if (!empty($student['photo'])) { ?>
                            <div style="text-align:center;">
                                <label style="display:block; font-size:12px; margin-bottom:5px;">Current Photo</label>
                                <img src="uploads/<?php echo htmlspecialchars($student['photo']); ?>" class="current-photo">
                            </div>
                        <?php } ?>
                    </div>

                    <button type="submit" name="update_student" class="btn-update">Update Student Details</button>

                </form>
            </div>

        </main>
    </div>

</body>

</html>