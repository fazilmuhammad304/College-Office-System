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
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $admission_no = mysqli_real_escape_string($conn, $_POST['admission_no']);
    $class_year = mysqli_real_escape_string($conn, $_POST['class_year']);
    $admission_date = $_POST['admission_date'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // போட்டோ அப்லோட்
    $photo = $_FILES['photo']['name'];
    $target = "uploads/" . basename($photo);

    $sql = "INSERT INTO students (full_name, admission_no, class_year, admission_date, phone, address, photo, status) 
            VALUES ('$full_name', '$admission_no', '$class_year', '$admission_date', '$phone', '$address', '$photo', 'Active')";

    if (mysqli_query($conn, $sql)) {
        if (!empty($photo)) {
            move_uploaded_file($_FILES['photo']['tmp_name'], $target);
        }
        $message = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Student Added Successfully!</div>";
    } else {
        $message = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Error: " . mysqli_error($conn) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* --- DESKTOP OPTIMIZED FORM --- */
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            /* அதிக பேடிங் */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            max-width: 900px;
            /* PC திரைக்கு ஏற்ற அகலம் */
            margin: 0 auto;
            /* மையப்படுத்த */
        }

        .form-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #F3F4F6;
            padding-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1F2937;
        }

        /* 2-Column Grid Layout */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* சரிபாதி இரண்டு கட்டங்கள் */
            gap: 30px;
            /* கட்டங்களுக்கு இடையே இடைவெளி */
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #4B5563;
        }

        /* Input Fields Design */
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 14px 18px;
            /* பெரிய உள்ளீடு பெட்டி */
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            font-size: 15px;
            color: #1F2937;
            outline: none;
            background-color: #F9FAFB;
            transition: 0.3s;
        }

        /* Focus Effect (Orange Theme) */
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #ED8936;
            background-color: white;
            box-shadow: 0 0 0 4px rgba(237, 137, 54, 0.1);
        }

        /* Full Width for Address & Photo */
        .full-width {
            grid-column: span 2;
            /* இரண்டு காலம்களையும் ஆக்கிரமிக்கும் */
        }

        /* Submit Button Area */
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            /* வலது ஓரத்தில் பட்டன்கள் */
        }

        .btn-submit {
            background-color: #ED8936;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-submit:hover {
            background-color: #D67625;
            transform: translateY(-1px);
        }

        .btn-cancel {
            background-color: white;
            color: #6B7280;
            border: 1px solid #E5E7EB;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: 0.2s;
        }

        .btn-cancel:hover {
            background-color: #F3F4F6;
            color: #374151;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success {
            background-color: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .error {
            background-color: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">

        <?php
        $page = 'add_student';
        include 'sidebar.php';
        ?>

        <main class="main-content">

            <header class="top-header">
                <h2>Student Management</h2>
                <div class="header-right">
                    <div class="notification-btn"><i class="fa-regular fa-bell"></i></div>
                </div>
            </header>

            <div class="form-container">

                <div class="form-header">
                    <h3>New Student Registration</h3>
                    <span style="font-size: 13px; color: #6B7280;">* Indicates required fields</span>
                </div>

                <?php echo $message; ?>

                <form action="" method="POST" enctype="multipart/form-data">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Admission Number *</label>
                            <input type="text" name="admission_no" placeholder="Ex: 2024/001" required>
                        </div>

                        <div class="form-group">
                            <label>Date of Admission *</label>
                            <input type="date" name="admission_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" placeholder="Enter student's full name" required>
                        </div>

                        <div class="form-group">
                            <label>Program / Class *</label>
                            <select name="class_year" required>
                                <option value="">Select Program</option>
                                <option value="Hifz Class">Hifz Class</option>
                                <option value="Al-Alim 1st Year">Al-Alim 1st Year</option>
                                <option value="Al-Alim 2nd Year">Al-Alim 2nd Year</option>
                                <option value="Al-Alim 3rd Year">Al-Alim 3rd Year</option>
                                <option value="Al-Alim Final Year">Al-Alim Final Year</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" placeholder="Ex: 077 123 4567">
                        </div>

                        <div class="form-group full-width">
                            <label>Address / Place</label>
                            <textarea name="address" rows="3" placeholder="Enter full address..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label>Student Photo</label>
                            <input type="file" name="photo" style="padding: 10px; background: white;">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="students.php" class="btn-cancel">Cancel</a>
                        <button type="submit" name="save_student" class="btn-submit">
                            <i class="fa-solid fa-check"></i> Save Student
                        </button>
                    </div>

                </form>
            </div>

        </main>
    </div>

</body>

</html>