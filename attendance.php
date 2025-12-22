<?php
session_start();
include 'db_conn.php';

// --- TIMEZONE FIX ---
date_default_timezone_set('Asia/Colombo');

// 1. LOGIN CHECK
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// 2. SAVE ATTENDANCE
if (isset($_POST['save_attendance'])) {
    $submitted_pin = $_POST['security_pin'];
    $valid_pin = "1234"; // Admin PIN

    if ($submitted_pin !== $valid_pin) {
        $message = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Incorrect PIN! Attendance not saved.</div>";
    } else {
        $date = $_POST['attendance_date'];

        if (!empty($_POST['status'])) {
            foreach ($_POST['status'] as $student_id => $status_val) {
                $student_id = intval($student_id);
                $status_val = mysqli_real_escape_string($conn, $status_val);

                $check_sql = "SELECT * FROM attendance WHERE student_id = '$student_id' AND date = '$date'";
                $check_result = mysqli_query($conn, $check_sql);

                if (mysqli_num_rows($check_result) > 0) {
                    $sql = "UPDATE attendance SET status = '$status_val' WHERE student_id = '$student_id' AND date = '$date'";
                } else {
                    $sql = "INSERT INTO attendance (student_id, date, status) VALUES ('$student_id', '$date', '$status_val')";
                }
                mysqli_query($conn, $sql);
            }
            $message = "<div class='alert success'><i class='fa-solid fa-lock'></i> Verified & Saved Successfully for <b>" . date('d M Y', strtotime($date)) . "</b></div>";
        } else {
            $message = "<div class='alert error'>No students selected!</div>";
        }
    }
}

// 3. FILTER LOGIC & QUERY BUILDING
$filter_program = isset($_GET['program']) ? $_GET['program'] : 'All';
$filter_year    = isset($_GET['year']) ? $_GET['year'] : 'All';
$filter_status  = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'All'; // NEW: Status Filter
$filter_student = isset($_GET['student']) ? $_GET['student'] : '';
$selected_date  = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// --- BUILD SQL CONDITIONS ---
// 1. Must be Active
// 2. Must be admitted ON or BEFORE the selected date (Fixes the issue of future students showing up)
$filter_conditions = "s.status='Active' AND (s.admission_date <= '$selected_date' OR s.admission_date IS NULL)";

// Filter by Program
if ($filter_program != '' && $filter_program != 'All') {
    $prog_safe = mysqli_real_escape_string($conn, $filter_program);
    $filter_conditions .= " AND (s.class_year = '$prog_safe' OR s.class_year LIKE '$prog_safe %')";
}

// Filter by Year
if ($filter_year != '' && $filter_year != 'All') {
    $year_safe = mysqli_real_escape_string($conn, $filter_year);
    $filter_conditions .= " AND s.class_year LIKE '%$year_safe%'";
}

// NEW: Filter by Attendance Status (Present / Absent)
if ($filter_status != '' && $filter_status != 'All') {
    $status_safe = mysqli_real_escape_string($conn, $filter_status);
    // Subquery to find students with the specific status on the selected date
    $filter_conditions .= " AND s.student_id IN (SELECT student_id FROM attendance WHERE date='$selected_date' AND status='$status_safe')";
}

// Filter by Student Name/ID (Search Bar)
if ($filter_student != '') {
    $stu_safe = mysqli_real_escape_string($conn, $filter_student);
    $filter_conditions .= " AND (s.full_name LIKE '%$stu_safe%' OR s.admission_no LIKE '%$stu_safe%')";
}

// --- [PERCENTAGE CALCULATIONS] ---

// A. Percentage for Selected Date (Based on current filters)
$active_count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM students s WHERE $filter_conditions");
$active_count_data = mysqli_fetch_assoc($active_count_query);
$total_filtered_students = $active_count_data['total'];

$present_count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance a 
                                            JOIN students s ON a.student_id = s.student_id 
                                            WHERE a.date='$selected_date' AND a.status='Present' AND $filter_conditions");
$present_count_data = mysqli_fetch_assoc($present_count_query);
$present_on_date = $present_count_data['total'];

$date_percentage = ($total_filtered_students > 0) ? round(($present_on_date / $total_filtered_students) * 100, 1) : 0;


// B. Average Percentage (Range: Selected Date -> Today)
$range_percentage = 0;
if ($selected_date <= date('Y-m-d')) {
    $range_present_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance a 
                                                JOIN students s ON a.student_id = s.student_id 
                                                WHERE a.date >= '$selected_date' AND a.date <= CURDATE() 
                                                AND a.status='Present' AND $filter_conditions");
    $range_present_data = mysqli_fetch_assoc($range_present_query);
    $total_range_present = $range_present_data['total'];

    $range_total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance a 
                                              JOIN students s ON a.student_id = s.student_id 
                                              WHERE a.date >= '$selected_date' AND a.date <= CURDATE() 
                                              AND $filter_conditions");
    $range_total_data = mysqli_fetch_assoc($range_total_query);
    $total_range_records = $range_total_data['total'];

    $range_percentage = ($total_range_records > 0) ? round(($total_range_present / $total_range_records) * 100, 1) : 0;
}

// 4. FETCH STUDENTS LIST
$query = "SELECT * FROM students s WHERE $filter_conditions ORDER BY s.admission_no ASC";
$students = mysqli_query($conn, $query);

$check_date_sql = "SELECT * FROM attendance WHERE date = '$selected_date' LIMIT 1";
$is_existing = (mysqli_num_rows(mysqli_query($conn, $check_date_sql)) > 0);
$btn_text = $is_existing ? "Update Attendance" : "Save Attendance";
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
        .attendance-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            border: 1px solid #E5E7EB;
            margin-bottom: 100px;
        }

        .filters-toolbar {
            background: #F8FAFC;
            padding: 20px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            min-width: 140px;
        }

        .filter-group label {
            font-size: 11px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
        }

        .filter-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            outline: none;
            color: #1E293B;
            font-size: 13px;
            font-weight: 500;
            background-color: white;
            transition: 0.2s;
        }

        .filter-input:focus {
            border-color: #ED8936;
            box-shadow: 0 0 0 2px rgba(237, 137, 54, 0.1);
        }

        .btn-load {
            background-color: #1E293B;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            height: 38px;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-load:hover {
            background-color: #0F172A;
        }

        .actions-bar {
            padding: 15px 20px;
            background: white;
            border-bottom: 1px solid #F1F5F9;
            display: flex;
            gap: 10px;
        }

        .btn-quick {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #E2E8F0;
            background: white;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-quick:hover {
            background: #F8FAFC;
            color: #0F172A;
            border-color: #CBD5E1;
        }

        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 800;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            text-align: right;
        }

        .att-table {
            width: 100%;
            border-collapse: collapse;
        }

        .att-table th {
            padding: 12px 20px;
            background: #F1F5F9;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
        }

        .att-table td {
            padding: 12px 20px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }

        .st-avatar {
            width: 32px;
            height: 32px;
            background: #E2E8F0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #64748B;
            font-size: 12px;
        }

        .status-options {
            display: flex;
            gap: 5px;
        }

        .status-radio {
            display: none;
        }

        .status-label {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            border: 1px solid #E2E8F0;
            color: #94A3B8;
            background: white;
            transition: 0.1s;
        }

        .status-radio[value="Present"]:checked+.status-label {
            background: #DCFCE7;
            color: #15803D;
            border-color: #15803D;
        }

        .status-radio[value="Absent"]:checked+.status-label {
            background: #FEE2E2;
            color: #B91C1C;
            border-color: #B91C1C;
        }

        .status-radio[value="Holiday"]:checked+.status-label {
            background: #E0E7FF;
            color: #4338CA;
            border-color: #4338CA;
        }

        .save-bar {
            position: fixed;
            bottom: 0;
            right: 0;
            left: 260px;
            background: white;
            padding: 15px 30px;
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
            padding: 10px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            background-color: #D67625;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-box {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 350px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
        }

        .pass-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 18px;
            text-align: center;
            margin: 20px 0;
            outline: none;
            letter-spacing: 5px;
        }

        .pass-input:focus {
            border-color: #ED8936;
        }

        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 10px;
            text-align: center;
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
                <div style="background:white; padding:8px 15px; border-radius:8px; border:1px solid #E5E7EB; font-weight:600; color:#374151;">
                    <?php echo date('F d, Y', strtotime($selected_date)); ?>
                </div>
            </header>

            <?php echo $message; ?>

            <div class="stats-row">
                <div class="stat-box">
                    <div>
                        <div class="stat-label" style="text-align:left;">Date Attendance</div>
                        <div style="font-size:11px; color:#94A3B8;">On Selected Date</div>
                    </div>
                    <div class="stat-value" style="color: #059669;"><?php echo $date_percentage; ?>%</div>
                </div>
                <div class="stat-box">
                    <div>
                        <div class="stat-label" style="text-align:left;">Average</div>
                        <div style="font-size:11px; color:#94A3B8;">Till Date (Filtered)</div>
                    </div>
                    <div class="stat-value" style="color: #4F46E5;"><?php echo $range_percentage; ?>%</div>
                </div>
            </div>

            <div class="attendance-card">

                <form method="GET" class="filters-toolbar">
                    <div class="filter-group">
                        <label>Date</label>
                        <input type="date" name="date" class="filter-input" value="<?php echo $selected_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Program</label>
                        <select name="program" class="filter-input">
                            <option value="All">All Programs</option>
                            <?php
                            $prog_res = mysqli_query($conn, "SELECT program_name FROM programs");
                            while ($prog = mysqli_fetch_assoc($prog_res)) {
                                $pName = $prog['program_name'];
                                $sel = ($filter_program == $pName) ? 'selected' : '';
                                echo "<option value='$pName' $sel>$pName</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Year</label>
                        <select name="year" class="filter-input">
                            <option value="All" <?php if ($filter_year == 'All') echo 'selected'; ?>>All Years</option>
                            <option value="1st Year" <?php if ($filter_year == '1st Year') echo 'selected'; ?>>1st Year</option>
                            <option value="2nd Year" <?php if ($filter_year == '2nd Year') echo 'selected'; ?>>2nd Year</option>
                            <option value="3rd Year" <?php if ($filter_year == '3rd Year') echo 'selected'; ?>>3rd Year</option>
                            <option value="4th Year" <?php if ($filter_year == '4th Year') echo 'selected'; ?>>4th Year</option>
                            <option value="5th Year" <?php if ($filter_year == '5th Year') echo 'selected'; ?>>5th Year</option>
                            <option value="6th Year" <?php if ($filter_year == '6th Year') echo 'selected'; ?>>6th Year</option>
                            <option value="Final Year" <?php if ($filter_year == 'Final Year') echo 'selected'; ?>>Final Year</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status_filter" class="filter-input">
                            <option value="All" <?php if ($filter_status == 'All') echo 'selected'; ?>>All Status</option>
                            <option value="Present" <?php if ($filter_status == 'Present') echo 'selected'; ?>>Present</option>
                            <option value="Absent" <?php if ($filter_status == 'Absent') echo 'selected'; ?>>Absent</option>
                            <option value="Holiday" <?php if ($filter_status == 'Holiday') echo 'selected'; ?>>Holiday</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Student Search</label>
                        <input type="text" name="student" class="filter-input" placeholder="Name or ID..." value="<?php echo htmlspecialchars($filter_student); ?>">
                    </div>
                    <button type="submit" class="btn-load">
                        <i class="fa-solid fa-rotate"></i> Load Data
                    </button>
                </form>

                <div class="actions-bar">
                    <button type="button" class="btn-quick" onclick="markAll('Present')"><i class="fa-solid fa-check"></i> All Present</button>
                    <button type="button" class="btn-quick" onclick="markAll('Absent')"><i class="fa-solid fa-xmark"></i> All Absent</button>
                    <button type="button" class="btn-quick" onclick="markAll('Holiday')"><i class="fa-solid fa-umbrella-beach"></i> All Holiday</button>
                </div>

                <form method="POST" id="attForm">
                    <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                    <input type="hidden" name="security_pin" id="hiddenPin" value="">

                    <?php if (mysqli_num_rows($students) > 0) { ?>
                        <table class="att-table">
                            <thead>
                                <tr>
                                    <th style="width: 45%;">Student</th>
                                    <th style="width: 25%;">Class Info</th>
                                    <th style="width: 30%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($students)) {
                                    $sid = $row['student_id'];
                                    $initial = substr($row['full_name'], 0, 1);

                                    // Fetch specific attendance
                                    $res = mysqli_query($conn, "SELECT status FROM attendance WHERE student_id='$sid' AND date='$selected_date'");
                                    $existing = mysqli_fetch_assoc($res);
                                    $status = $existing ? $existing['status'] : 'Present';
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:12px;">
                                                <div class="st-avatar"><?php echo $initial; ?></div>
                                                <div>
                                                    <div style="font-weight:600; font-size:13px; color:#1E293B;"><?php echo $row['full_name']; ?></div>
                                                    <div style="font-size:11px; color:#94A3B8;"><?php echo $row['admission_no']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size:11px; color:#475569; background:#F1F5F9; padding:4px 8px; border-radius:4px; border:1px solid #E2E8F0;">
                                                <?php echo $row['class_year']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="status-options">
                                                <label title="Present"><input type="radio" name="status[<?php echo $sid; ?>]" value="Present" class="status-radio" <?php if ($status == 'Present') echo 'checked'; ?>>
                                                    <div class="status-label">P</div>
                                                </label>
                                                <label title="Absent"><input type="radio" name="status[<?php echo $sid; ?>]" value="Absent" class="status-radio" <?php if ($status == 'Absent') echo 'checked'; ?>>
                                                    <div class="status-label">A</div>
                                                </label>
                                                <label title="Holiday"><input type="radio" name="status[<?php echo $sid; ?>]" value="Holiday" class="status-radio" <?php if ($status == 'Holiday') echo 'checked'; ?>>
                                                    <div class="status-label"><i class="fa-solid fa-umbrella-beach"></i></div>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                        <div class="save-bar">
                            <div style="font-size:13px; color:#64748B;">
                                Records: <b><?php echo mysqli_num_rows($students); ?></b>
                            </div>
                            <button type="button" onclick="openSecurityModal()" class="btn-save">
                                <i class="fa-solid fa-lock"></i> <?php echo $btn_text; ?>
                            </button>
                            <button type="submit" name="save_attendance" id="realSubmitBtn" style="display:none;"></button>
                        </div>

                    <?php } else { ?>
                        <div style="text-align:center; padding:60px 20px; color:#94A3B8;">
                            <i class="fa-regular fa-folder-open" style="font-size:30px; margin-bottom:10px; opacity:0.5;"></i><br>
                            No students found matching your filters.
                        </div>
                    <?php } ?>
                </form>
            </div>

        </main>
    </div>

    <div id="securityModal" class="modal-overlay">
        <div class="modal-box">
            <i class="fa-solid fa-shield-halved" style="font-size:40px; color:#ED8936; margin-bottom:15px;"></i>
            <h3 style="color:#1F2937; margin-bottom:5px;">Security Check</h3>
            <p style="color:#6B7280; font-size:13px; margin-bottom:20px;">Enter Admin PIN to update records</p>
            <input type="password" id="adminPin" class="pass-input" placeholder="****" maxlength="4">
            <div style="display:flex; gap:10px;">
                <button onclick="closeModal()" style="background:#F3F4F6; color:#4B5563; border:none; padding:10px; width:100%; border-radius:8px; cursor:pointer;">Cancel</button>
                <button onclick="verifyPin()" style="background:#ED8936; color:white; border:none; padding:10px; width:100%; border-radius:8px; cursor:pointer; font-weight:600;">Confirm</button>
            </div>
            <p id="errorMsg" style="color:red; font-size:12px; margin-top:10px; display:none;">Please enter a PIN</p>
        </div>
    </div>

    <script>
        function markAll(statusValue) {
            const radios = document.querySelectorAll(`input[type="radio"][value="${statusValue}"]`);
            radios.forEach(radio => radio.checked = true);
        }

        const modal = document.getElementById('securityModal');
        const pinInput = document.getElementById('adminPin');
        const hiddenPinInput = document.getElementById('hiddenPin');
        const errorMsg = document.getElementById('errorMsg');

        function openSecurityModal() {
            modal.style.display = 'flex';
            pinInput.value = '';
            pinInput.focus();
            errorMsg.style.display = 'none';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        function verifyPin() {
            if (pinInput.value.trim() === "") {
                errorMsg.style.display = 'block';
                return;
            }
            hiddenPinInput.value = pinInput.value;
            document.getElementById('realSubmitBtn').click();
        }
    </script>

</body>

</html>