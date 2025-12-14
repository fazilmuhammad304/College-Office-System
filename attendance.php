<?php
session_start();
include 'db_conn.php';

// 1. லாகின் செக்
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// 2. அட்டெண்டன்ஸ் சேவ் செய்தல்
if (isset($_POST['save_attendance'])) {
    $date = $_POST['attendance_date'];

    if (!empty($_POST['status'])) {
        foreach ($_POST['status'] as $student_id => $status_val) {
            $check_sql = "SELECT * FROM attendance WHERE student_id = '$student_id' AND date = '$date'";
            $check_result = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($check_result) > 0) {
                $sql = "UPDATE attendance SET status = '$status_val' WHERE student_id = '$student_id' AND date = '$date'";
            } else {
                $sql = "INSERT INTO attendance (student_id, date, status) VALUES ('$student_id', '$date', '$status_val')";
            }
            mysqli_query($conn, $sql);
        }
        $message = "<div class='alert success'><i class='fa-solid fa-circle-check'></i> Attendance Saved Successfully for $date</div>";
    } else {
        $message = "<div class='alert error'>No students selected!</div>";
    }
}

// 3. ஃபில்டர் லாஜிக்
$filter_class = isset($_GET['class_year']) ? $_GET['class_year'] : '';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$query = "SELECT * FROM students WHERE status='Active'";
if ($filter_class != '' && $filter_class != 'All') {
    $query .= " AND class_year = '$filter_class'";
}
$query .= " ORDER BY admission_no ASC";
$students = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Registry | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* --- PROFESSIONAL DROPDOWN STYLE --- */

        .filters-card {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
            display: flex;
            align-items: flex-end;
            gap: 25px;
            border: 1px solid #F3F4F6;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ✨ Magic Happens Here: Custom Dropdown CSS */
        .filter-input {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            outline: none;
            color: #1F2937;
            font-size: 14px;
            font-weight: 500;
            background-color: #F9FAFB;
            cursor: pointer;

            /* Remove Default Arrow */
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;

            /* Add Custom Orange Arrow Icon */
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ED8936' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            transition: all 0.2s ease-in-out;
        }

        /* Hover & Focus Effects */
        .filter-input:hover {
            background-color: white;
            border-color: #D1D5DB;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .filter-input:focus {
            background-color: white;
            border-color: #ED8936;
            /* Brand Orange */
            box-shadow: 0 0 0 4px rgba(237, 137, 54, 0.1);
        }

        /* Button Styling */
        .btn-load {
            background-color: #4C1D95;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            height: 46px;
            /* Match input height */
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            box-shadow: 0 4px 10px rgba(76, 29, 149, 0.2);
        }

        .btn-load:hover {
            background-color: #371270;
            transform: translateY(-2px);
        }

        /* --- TABLE & OTHER STYLES (PC Optimized) --- */
        .attendance-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            border: 1px solid #F3F4F6;
            margin-bottom: 100px;
        }

        .att-table {
            width: 100%;
            border-collapse: collapse;
        }

        .att-table th {
            padding: 18px 25px;
            background: #F8FAFC;
            color: #64748B;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid #E2E8F0;
            text-align: left;
        }

        .att-table td {
            padding: 15px 25px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }

        .student-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .st-avatar {
            width: 45px;
            height: 45px;
            background: #F3F4F6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #6B7280;
        }

        /* Status Toggles */
        .status-options {
            display: flex;
            gap: 10px;
        }

        .status-radio {
            display: none;
        }

        .status-label {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: 1px solid #E2E8F0;
            color: #94A3B8;
            background: white;
            transition: 0.2s;
        }

        .status-radio[value="Present"]:checked+.status-label {
            background: #DCFCE7;
            color: #166534;
            border-color: #16A34A;
        }

        .status-radio[value="Absent"]:checked+.status-label {
            background: #FEE2E2;
            color: #991B1B;
            border-color: #DC2626;
        }

        .status-radio[value="Late"]:checked+.status-label {
            background: #FFEDD5;
            color: #9A3412;
            border-color: #EA580C;
        }

        /* Sticky Footer */
        .save-bar {
            position: fixed;
            bottom: 0;
            right: 0;
            left: 260px;
            background: white;
            padding: 20px 40px;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #E2E8F0;
            z-index: 50;
        }

        .btn-save {
            background-color: #ED8936;
            color: white;
            padding: 12px 35px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(237, 137, 54, 0.25);
            transition: 0.2s;
        }

        .btn-save:hover {
            background-color: #D67625;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
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
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php $page = 'attendance';
        include 'sidebar.php'; ?>

        <main class="main-content">

            <header class="top-header">
                <h2>Attendance Registry</h2>
                <div class="header-right">
                    <div style="background:white; padding:8px 15px; border-radius:8px; border:1px solid #E5E7EB; font-weight:600; color:#374151;">
                        <i class="fa-regular fa-calendar"></i> <?php echo date('F d, Y'); ?>
                    </div>
                </div>
            </header>

            <?php echo $message; ?>

            <form method="GET" action="">
                <div class="filters-card">
                    <div class="filter-group">
                        <label>Select Date</label>
                        <input type="date" name="date" class="filter-input" value="<?php echo $selected_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Filter by Program</label>
                        <select name="class_year" class="filter-input">
                            <option value="All">All Programs</option>
                            <option value="Hifz Class" <?php if ($filter_class == 'Hifz Class') echo 'selected'; ?>>Hifz Class</option>
                            <option value="Al-Alim 1st Year" <?php if ($filter_class == 'Al-Alim 1st Year') echo 'selected'; ?>>Al-Alim 1st Year</option>
                            <option value="Al-Alim 2nd Year" <?php if ($filter_class == 'Al-Alim 2nd Year') echo 'selected'; ?>>Al-Alim 2nd Year</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-load">
                        <i class="fa-solid fa-filter"></i> Load List
                    </button>
                </div>
            </form>

            <form method="POST" action="">
                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">

                <div class="attendance-card">
                    <table class="att-table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Student Name</th>
                                <th style="width: 30%;">Program & Year</th>
                                <th style="width: 30%;">Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($students) > 0) {
                                while ($row = mysqli_fetch_assoc($students)) {
                                    $sid = $row['student_id'];
                                    $initial = substr($row['full_name'], 0, 1);
                                    $status_sql = "SELECT status FROM attendance WHERE student_id='$sid' AND date='$selected_date'";
                                    $res = mysqli_query($conn, $status_sql);
                                    $existing = mysqli_fetch_assoc($res);
                                    $status = $existing ? $existing['status'] : 'Present';
                            ?>
                                    <tr>
                                        <td>
                                            <div class="student-profile">
                                                <div class="st-avatar"><?php echo $initial; ?></div>
                                                <div class="st-info">
                                                    <h4><?php echo $row['full_name']; ?></h4>
                                                    <span><?php echo $row['admission_no']; ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#475569; background:#F1F5F9; padding:5px 10px; border-radius:6px; font-size:13px;">
                                                <?php echo $row['class_year']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="status-options">
                                                <label><input type="radio" name="status[<?php echo $sid; ?>]" value="Present" class="status-radio" <?php if ($status == 'Present') echo 'checked'; ?>>
                                                    <div class="status-label">P</div>
                                                </label>
                                                <label><input type="radio" name="status[<?php echo $sid; ?>]" value="Absent" class="status-radio" <?php if ($status == 'Absent') echo 'checked'; ?>>
                                                    <div class="status-label">A</div>
                                                </label>
                                                <label><input type="radio" name="status[<?php echo $sid; ?>]" value="Late" class="status-radio" <?php if ($status == 'Late') echo 'checked'; ?>>
                                                    <div class="status-label">L</div>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='3' style='text-align:center; padding:40px; color:#64748B;'>No students found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="save-bar">
                    <div style="font-size:15px; color:#475569;">Attendance: <b style="color:#1E293B;"><?php echo date('d M, Y', strtotime($selected_date)); ?></b></div>
                    <button type="submit" name="save_attendance" class="btn-save"><i class="fa-solid fa-check"></i> Save Attendance</button>
                </div>
            </form>

        </main>
    </div>
</body>

</html>