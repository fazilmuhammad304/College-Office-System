<?php
session_start();
include 'db_conn.php';

// 1. LOGIN CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// --- ACTION: ADD NEW PROGRAM ---
if (isset($_POST['add_program'])) {
    $name = mysqli_real_escape_string($conn, $_POST['prog_name']);
    $desc = mysqli_real_escape_string($conn, $_POST['prog_desc']);

    $check = mysqli_query($conn, "SELECT * FROM programs WHERE program_name = '$name'");
    if (mysqli_num_rows($check) > 0) {
        $message = "<div class='alert error'>Program already exists!</div>";
    } else {
        $sql = "INSERT INTO programs (program_name, description) VALUES ('$name', '$desc')";
        if (mysqli_query($conn, $sql)) {
            $message = "<div class='alert success'>Program Added Successfully!</div>";
        } else {
            $message = "<div class='alert error'>Error adding program.</div>";
        }
    }
}

// --- ACTION: DELETE PROGRAM ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM programs WHERE program_id='$id'");
    header("Location: programs.php");
    exit();
}

// --- DATA FETCHING ---
// This query gets the Program Info AND counts active students in that program
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM students s WHERE s.class_year LIKE CONCAT('%', p.program_name, '%') AND s.status='Active') as student_count 
          FROM programs p 
          ORDER BY p.program_name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Programs | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* Table & Layout Styles */
        .table-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .prog-table {
            width: 100%;
            border-collapse: collapse;
        }

        .prog-table th {
            text-align: left;
            padding: 18px 25px;
            font-size: 12px;
            font-weight: 700;
            color: #6B7280;
            text-transform: uppercase;
            border-bottom: 1px solid #E5E7EB;
            background: #F8FAFC;
        }

        .prog-table td {
            padding: 18px 25px;
            border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
            color: #374151;
            font-size: 14px;
        }

        .prog-name {
            font-weight: 700;
            color: #1F2937;
            font-size: 15px;
        }

        .prog-desc {
            font-size: 13px;
            color: #6B7280;
            margin-top: 2px;
        }

        /* Student Count Badge */
        .count-badge {
            background: #EFF6FF;
            color: #2563EB;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #DBEAFE;
        }

        .count-zero {
            background: #F3F4F6;
            color: #9CA3AF;
            border-color: #E5E7EB;
        }

        /* Add Button */
        .btn-add {
            background-color: #f17c1c;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-add:hover {
            background-color: #d9650c;
        }

        /* Modal Styles */
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
            width: 400px;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            margin-bottom: 15px;
            outline: none;
        }

        .form-input:focus {
            border-color: #f17c1c;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 20px;
            color: #9CA3AF;
            cursor: pointer;
        }

        /* Action Buttons */
        .btn-del {
            color: #EF4444;
            background: #FEF2F2;
            padding: 8px;
            border-radius: 6px;
            transition: 0.2s;
        }

        .btn-del:hover {
            background: #FEE2E2;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        <?php $page = 'programs';
        include 'sidebar.php'; ?>

        <main class="main-content">

            <header class="top-header">
                <h2>Academic Programs</h2>
                <button class="btn-add" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i> Add Program
                </button>
            </header>

            <?php echo $message; ?>

            <div class="table-container">
                <table class="prog-table">
                    <thead>
                        <tr>
                            <th width="40%">Program Details</th>
                            <th width="30%">Created Date</th>
                            <th width="20%">Students Enrolled</th>
                            <th width="10%" style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)):
                                $count = $row['student_count'];
                                $badge_class = ($count > 0) ? 'count-badge' : 'count-badge count-zero';
                            ?>
                                <tr>
                                    <td>
                                        <div class="prog-name"><?php echo $row['program_name']; ?></div>
                                        <div class="prog-desc"><?php echo $row['description']; ?></div>
                                    </td>
                                    <td>
                                        <i class="fa-regular fa-calendar" style="color:#9CA3AF; margin-right:5px;"></i>
                                        <?php echo date('d M, Y', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $badge_class; ?>">
                                            <i class="fa-solid fa-user-graduate"></i> <?php echo $count; ?> Students
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="programs.php?delete_id=<?php echo $row['program_id']; ?>" class="btn-del" onclick="return confirm('Delete this program?')" title="Delete">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding:30px; color:#9CA3AF;">No programs found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <div id="progModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3 style="margin-bottom:20px; color:#1F2937;">Add New Program</h3>
            <form action="" method="POST">
                <label style="font-size:13px; font-weight:600; color:#4B5563; display:block; margin-bottom:5px;">Program Name</label>
                <input type="text" name="prog_name" class="form-input" required placeholder="e.g. Hifz Class">

                <label style="font-size:13px; font-weight:600; color:#4B5563; display:block; margin-bottom:5px;">Description (Optional)</label>
                <input type="text" name="prog_desc" class="form-input" placeholder="e.g. Quran Memorization">

                <button type="submit" name="add_program" class="btn-add" style="width:100%; justify-content:center;">
                    Create Program
                </button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('progModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('progModal').style.display = 'none';
        }
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        }
    </script>

</body>

</html>s