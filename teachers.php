<?php
session_start();
include 'db_conn.php';

// Login Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// --- ADD TEACHER ---
if (isset($_POST['add_teacher'])) {
    $name = mysqli_real_escape_string($conn, $_POST['t_name']);
    $subject = mysqli_real_escape_string($conn, $_POST['t_subject']);
    $phone = mysqli_real_escape_string($conn, $_POST['t_phone']);

    $sql = "INSERT INTO teachers (full_name, subject_specialty, phone) VALUES ('$name', '$subject', '$phone')";
    if (mysqli_query($conn, $sql)) {
        $message = "<div class='alert success'>Teacher Added Successfully!</div>";
    } else {
        $message = "<div class='alert error'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// --- DELETE TEACHER ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM teachers WHERE teacher_id='$id'");
    header("Location: teachers.php");
    exit();
}

// --- FETCH DATA ---
$result = mysqli_query($conn, "SELECT * FROM teachers ORDER BY teacher_id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Teachers Directory | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .btn-add {
            background: #ED8936;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            background: #D67625;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #F9FAFB;
            padding: 15px;
            text-align: left;
            font-size: 13px;
            color: #6B7280;
            font-weight: 700;
            border-bottom: 1px solid #E5E7EB;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #F3F4F6;
            color: #374151;
            font-size: 14px;
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

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 400px;
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            outline: none;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            color: #9CA3AF;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php $page = 'teachers';
        include 'sidebar.php'; ?>
        <main class="main-content">
            <header class="top-header">
                <h2>Teachers Management</h2>
                <button class="btn-add" onclick="document.getElementById('addModal').style.display='flex'">
                    <i class="fa-solid fa-plus"></i> Add Teacher
                </button>
            </header>

            <?php echo $message; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Subject / Specialty</th>
                            <th>Phone</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td style="font-weight:600;"><?php echo $row['full_name']; ?></td>
                                    <td><span style="background:#EFF6FF; color:#2563EB; padding:4px 10px; border-radius:15px; font-size:12px; font-weight:600;"><?php echo $row['subject_specialty']; ?></span></td>
                                    <td><?php echo $row['phone']; ?></td>
                                    <td style="text-align:right;">
                                        <a href="teachers.php?delete=<?php echo $row['teacher_id']; ?>" onclick="return confirm('Delete this teacher?')" style="color:#EF4444;"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding:30px; color:#9CA3AF;">No teachers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="addModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
            <h3 style="margin-bottom:20px;">Add New Teacher</h3>
            <form method="POST">
                <input type="text" name="t_name" placeholder="Full Name" class="form-input" required>
                <input type="text" name="t_subject" placeholder="Subject (e.g. Arabic, English)" class="form-input" required>
                <input type="text" name="t_phone" placeholder="Phone Number" class="form-input" required>
                <button type="submit" name="add_teacher" class="btn-add" style="width:100%; justify-content:center;">Save</button>
            </form>
        </div>
    </div>
</body>

</html>