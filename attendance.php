<?php
session_start();
include 'db_conn.php';

// 1. லாகின் செக்
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// 2. அட்டெண்டன்ஸ் சேவ் செய்தல் (SERVER-SIDE PIN CHECK)
if (isset($_POST['save_attendance'])) {

    // --- [SECURITY FIX START] ---
    $submitted_pin = $_POST['security_pin']; // மோடலில் இருந்து வந்த PIN
    $valid_pin = "1234"; // இங்கு பாதுகாப்பான PIN-ஐ மாற்றிக்கொள்ளவும்

    if ($submitted_pin !== $valid_pin) {
        $message = "<div class='alert error'><i class='fa-solid fa-triangle-exclamation'></i> Incorrect PIN! Attendance not saved.</div>";
    } else {
        // PIN சரியாக இருந்தால் மட்டுமே சேவ் செய்யப்படும்
        $date = $_POST['attendance_date'];

        if (!empty($_POST['status'])) {
            foreach ($_POST['status'] as $student_id => $status_val) {
                $student_id = intval($student_id); // Security: Force Integer
                $status_val = mysqli_real_escape_string($conn, $status_val); // Security: Escape String

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
    // --- [SECURITY FIX END] ---
}

// 3. ஃபில்டர் லாஜிக்
$filter_class = isset($_GET['class_year']) ? $_GET['class_year'] : 'All';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$query = "SELECT * FROM students WHERE status='Active'";
if ($filter_class != '' && $filter_class != 'All') {
    $class_safe = mysqli_real_escape_string($conn, $filter_class);
    $query .= " AND class_year = '$class_safe'";
}
$query .= " ORDER BY admission_no ASC";
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
        /* --- STYLES --- */
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
        }

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
        }

        .btn-load {
            background-color: #4C1D95;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            height: 46px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .btn-quick {
            padding: 8px 15px;
            border-radius: 6px;
            border: 1px solid #E5E7EB;
            background: white;
            color: #4B5563;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-quick:hover {
            background: #F3F4F6;
            color: #111827;
        }

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
            padding: 15px 25px;
            background: #F8FAFC;
            color: #64748B;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid #E2E8F0;
            text-align: left;
        }

        .att-table td {
            padding: 12px 25px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }

        .student-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .st-avatar {
            width: 40px;
            height: 40px;
            background: #F3F4F6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #6B7280;
        }

        .status-options {
            display: flex;
            gap: 8px;
        }

        .status-radio {
            display: none;
        }

        .status-label {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
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

        .status-radio[value="Holiday"]:checked+.status-label {
            background: #E0E7FF;
            color: #4338CA;
            border-color: #4F46E5;
        }

        .save-bar {
            position: fixed;
            bottom: 0;
            right: 0;
            left: 260px;
            background: white;
            padding: 15px 40px;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #E2E8F0;
            z-index: 50;
        }

        /* 🔥 SECURITY MODAL CSS 🔥 */
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

        .btn-confirm {
            background: #ED8936;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-confirm:hover {
            background: #C05621;
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
        }

        .btn-save:hover {
            background-color: #D67625;
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
                        Selected: <span style="color:#2563EB;"><?php echo date('d M, Y', strtotime($selected_date)); ?></span>
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
                            <?php
                            // Dynamic Program Fetching for Filter
                            $prog_res = mysqli_query($conn, "SELECT program_name FROM programs");
                            while ($prog = mysqli_fetch_assoc($prog_res)) {
                                $pName = $prog['program_name'];
                                $sel = ($filter_class == $pName) ? 'selected' : '';
                                echo "<option value='$pName' $sel>$pName</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-load">
                        <i class="fa-solid fa-filter"></i> Load List
                    </button>
                </div>
            </form>

            <form method="POST" action="" id="attForm">
                <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">

                <input type="hidden" name="security_pin" id="hiddenPin" value="">

                <?php if (mysqli_num_rows($students) > 0) { ?>

                    <div class="quick-actions">
                        <button type="button" class="btn-quick" onclick="markAll('Present')"><i class="fa-solid fa-check"></i> Mark All Present</button>
                        <button type="button" class="btn-quick" onclick="markAll('Holiday')"><i class="fa-solid fa-umbrella-beach"></i> Mark All Holiday</button>
                        <button type="button" class="btn-quick" onclick="markAll('Absent')"><i class="fa-solid fa-xmark"></i> Mark All Absent</button>
                    </div>

                    <div class="attendance-card">
                        <table class="att-table">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Student Name</th>
                                    <th style="width: 25%;">Class</th>
                                    <th style="width: 35%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
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
                                                    <h4 style="margin:0; font-size:14px;"><?php echo $row['full_name']; ?></h4>
                                                    <span style="font-size:12px; color:#9CA3AF;"><?php echo $row['admission_no']; ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#475569; background:#F1F5F9; padding:5px 10px; border-radius:6px; font-size:12px;">
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
                                                <label title="Late"><input type="radio" name="status[<?php echo $sid; ?>]" value="Late" class="status-radio" <?php if ($status == 'Late') echo 'checked'; ?>>
                                                    <div class="status-label">L</div>
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
                    </div>

                    <div class="save-bar">
                        <div style="font-size:14px; color:#475569;">
                            Editing: <b style="color:#1E293B;"><?php echo date('d M, Y', strtotime($selected_date)); ?></b>
                        </div>
                        <button type="button" onclick="openSecurityModal()" class="btn-save">
                            <i class="fa-solid fa-lock"></i> <?php echo $btn_text; ?>
                        </button>
                        <button type="submit" name="save_attendance" id="realSubmitBtn" style="display:none;"></button>
                    </div>

                <?php } else { ?>
                    <div style="text-align:center; padding:50px; background:white; border-radius:12px; border:1px dashed #E5E7EB; color:#6B7280;">
                        No active students found.
                    </div>
                <?php } ?>
            </form>

        </main>
    </div>

    <div id="securityModal" class="modal-overlay">
        <div class="modal-box">
            <i class="fa-solid fa-shield-halved" style="font-size:40px; color:#ED8936; margin-bottom:15px;"></i>
            <h3 style="color:#1F2937; margin-bottom:5px;">Security Check</h3>
            <p style="color:#6B7280; font-size:13px; margin-bottom:20px;">Enter Admin PIN to update records</p>

            <input type="password" id="adminPin" class="pass-input" placeholder="****" maxlength="4">

            <div style="display:flex; gap:10px;">
                <button onclick="closeModal()" style="background:#F3F4F6; color:#4B5563; border:none; padding:10px; flex:1; border-radius:8px; cursor:pointer;">Cancel</button>
                <button onclick="verifyPin()" class="btn-confirm">Verify & Save</button>
            </div>
            <p id="errorMsg" style="color:red; font-size:12px; margin-top:10px; display:none;">Please enter PIN</p>
        </div>
    </div>

    <script>
        function markAll(statusValue) {
            const radios = document.querySelectorAll(`input[type="radio"][value="${statusValue}"]`);
            radios.forEach(radio => {
                radio.checked = true;
            });
        }

        // --- SECURITY MODAL LOGIC (UPDATED) ---
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
                errorMsg.innerText = "Please enter a PIN";
                errorMsg.style.display = 'block';
                return;
            }

            // JavaScript-ல் சரிபார்க்காமல், PHP-க்கு அனுப்புகிறோம்
            hiddenPinInput.value = pinInput.value;
            document.getElementById('realSubmitBtn').click(); // Submit Form
        }
    </script>

</body>

</html>