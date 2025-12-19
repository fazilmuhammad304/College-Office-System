<?php
session_start();
include 'db_conn.php';

// 1. LOGIN CHECK
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// [NEW] Fetch all programs dynamically
$program_list = [];
$p_query = mysqli_query($conn, "SELECT program_name FROM programs");
if ($p_query) {
    while ($p = mysqli_fetch_assoc($p_query)) {
        $program_list[] = $p['program_name'];
    }
}

// [CRITICAL] Sort programs by length (Longest first) for correct display logic
usort($program_list, function ($a, $b) {
    return strlen($b) - strlen($a);
});

// 2. FILTER LOGIC
$where_clauses = [];

// [FIXED] Program Filter - Stricter Matching
if (isset($_GET['program']) && $_GET['program'] != 'All') {
    $prog = mysqli_real_escape_string($conn, $_GET['program']);
    // Check for Exact Match OR Match followed by a space
    // This prevents "Al-Alim" from matching "Al-Alimah"
    $where_clauses[] = "(class_year = '$prog' OR class_year LIKE '$prog %')";
}

// Year Filter
if (isset($_GET['year']) && $_GET['year'] != 'All') {
    $yr = mysqli_real_escape_string($conn, $_GET['year']);
    $where_clauses[] = "(class_year LIKE '%$yr%' OR class_year LIKE '%Year $yr%')";
}

// Status Filter
if (isset($_GET['status']) && $_GET['status'] != 'All') {
    $sts = mysqli_real_escape_string($conn, $_GET['status']);
    $where_clauses[] = "status = '$sts'";
}

// Search Bar
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clauses[] = "(full_name LIKE '%$search%' OR admission_no LIKE '%$search%' OR phone LIKE '%$search%')";
}

// Build Query
$sql = "SELECT * FROM students";
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY student_id DESC";

$result = mysqli_query($conn, $sql);

// Keep selected values for dropdowns
$selected_prog = isset($_GET['program']) ? $_GET['program'] : 'All';
$selected_year = isset($_GET['year']) ? $_GET['year'] : 'All';
$selected_status = isset($_GET['status']) ? $_GET['status'] : 'All';
$search_val = isset($_GET['search']) ? $_GET['search'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Directory | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* --- FILTERS SECTION --- */
        .controls-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .filters-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            color: #374151;
            font-size: 14px;
            outline: none;
            background-color: #F9FAFB;
            cursor: pointer;
            min-width: 140px;
            transition: 0.2s;
        }

        .filter-select:hover {
            border-color: #D1D5DB;
        }

        .filter-select:focus {
            border-color: #ED8936;
            background: white;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 10px 15px 10px 35px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            width: 250px;
            background-color: #F9FAFB;
        }

        .search-box input:focus {
            border-color: #ED8936;
            background: white;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
        }

        /* --- ADD BUTTON --- */
        .btn-add {
            background-color: #ED8936;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            box-shadow: 0 2px 5px rgba(237, 137, 54, 0.2);
        }

        .btn-add:hover {
            background-color: #D67625;
            transform: translateY(-1px);
        }

        /* --- TABLE STYLES --- */
        .table-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-table th {
            text-align: left;
            padding: 15px 20px;
            font-size: 12px;
            font-weight: 700;
            color: #6B7280;
            text-transform: uppercase;
            border-bottom: 1px solid #E5E7EB;
            background: #F8FAFC;
        }

        .student-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
            color: #374151;
            font-size: 14px;
        }

        .student-table tr:hover {
            background-color: #FFF7ED;
        }

        .profile-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            background: #FFEDD5;
            color: #C2410C;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            overflow: hidden;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .st-name {
            font-weight: 600;
            color: #111827;
            display: block;
        }

        .st-id {
            font-size: 12px;
            color: #6B7280;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-active {
            background: #DCFCE7;
            color: #166534;
        }

        .badge-inactive {
            background: #FEE2E2;
            color: #991B1B;
        }

        .badge-graduated {
            background: #E0E7FF;
            color: #3730A3;
        }

        .action-btns {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6B7280;
            transition: 0.2s;
            text-decoration: none;
            background: #F3F4F6;
        }

        .btn-view:hover {
            background: #FFEDD5;
            color: #C2410C;
        }

        .btn-edit:hover {
            background: #FEF3C7;
            color: #D97706;
        }

        .btn-delete:hover {
            background: #FEE2E2;
            color: #DC2626;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">

        <?php $page = 'students';
        include 'sidebar.php'; ?>

        <main class="main-content">

            <header class="top-header">
                <h2>Student Management</h2>
            </header>

            <form action="" method="GET" id="filterForm">
                <div class="controls-card">
                    <div class="filters-group">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" placeholder="Search name, ID or phone..." value="<?php echo htmlspecialchars($search_val); ?>">
                        </div>

                        <?php
                        // Fetch Programs for Filter
                        $prog_filter_query = "SELECT program_name FROM programs ORDER BY program_name ASC";
                        $prog_filter_result = mysqli_query($conn, $prog_filter_query);
                        ?>
                        <select name="program" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="All" <?php if ($selected_prog == 'All') echo 'selected'; ?>>All Programs</option>
                            <?php
                            if ($prog_filter_result) {
                                while ($pf = mysqli_fetch_assoc($prog_filter_result)) {
                                    $pName = $pf['program_name'];
                                    $sel = ($selected_prog == $pName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($pName) . "' $sel>" . htmlspecialchars($pName) . "</option>";
                                }
                            }
                            ?>
                        </select>

                        <select name="year" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="All" <?php if ($selected_year == 'All') echo 'selected'; ?>>All Years</option>
                            <option value="1" <?php if ($selected_year == '1') echo 'selected'; ?>>1st Year</option>
                            <option value="2" <?php if ($selected_year == '2') echo 'selected'; ?>>2nd Year</option>
                            <option value="3" <?php if ($selected_year == '3') echo 'selected'; ?>>3rd Year</option>
                            <option value="4" <?php if ($selected_year == '4') echo 'selected'; ?>>4th Year</option>
                            <option value="5" <?php if ($selected_year == '5') echo 'selected'; ?>>5th Year</option>
                            <option value="6" <?php if ($selected_year == '6') echo 'selected'; ?>>6th Year</option>
                            <option value="7th (Final Year)" <?php if ($selected_year == '7th (Final Year)') echo 'selected'; ?>>7th (Final Year)</option>
                        </select>

                        <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="All" <?php if ($selected_status == 'All') echo 'selected'; ?>>All Status</option>
                            <option value="Active" <?php if ($selected_status == 'Active') echo 'selected'; ?>>Active</option>
                            <option value="Inactive" <?php if ($selected_status == 'Inactive') echo 'selected'; ?>>Inactive</option>
                            <option value="Graduated" <?php if ($selected_status == 'Graduated') echo 'selected'; ?>>Graduated</option>
                        </select>
                    </div>

                    <a href="add_student.php" class="btn-add">
                        <i class="fa-solid fa-plus"></i> Add New Student
                    </a>
                </div>
            </form>

            <div class="table-container">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Student Profile</th>
                            <th style="width: 15%;">Program</th>
                            <th style="width: 15%;">Year</th>
                            <th style="width: 15%;">Contact</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 20%; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {

                                // --- [UPDATED] PHOTO DISPLAY LOGIC ---
                                // Checks if it's a link (http) or a local file
                                $photo_html = "";
                                if (!empty($row['photo'])) {
                                    $photo_path = $row['photo'];
                                    if (strpos($photo_path, 'http') === 0) {
                                        // Link (Google Drive) - No change
                                        $photo_html = "<img src='" . $photo_path . "'>";
                                    } else {
                                        // Local file - Add uploads/
                                        $photo_html = "<img src='uploads/" . $photo_path . "'>";
                                    }
                                } else {
                                    $photo_html = substr($row['full_name'], 0, 1);
                                }
                                // -------------------------------------

                                // Status Logic
                                $status_badge = "badge-active";
                                if ($row['status'] == 'Inactive') $status_badge = "badge-inactive";
                                if ($row['status'] == 'Graduated') $status_badge = "badge-graduated";

                                // --- Dynamic Program & Year Logic ---
                                $db_class_year = $row['class_year'];
                                $prog_display = "General"; // Default
                                $year_only = $db_class_year; // Default to full string if no match found

                                // Check against the sorted program list (Longest names first)
                                foreach ($program_list as $p_name) {
                                    // Case-Insensitive check
                                    if (stripos($db_class_year, $p_name) !== false) {
                                        $prog_display = $p_name;
                                        // Remove the program name from the string to get just the Year
                                        $year_only = trim(str_ireplace($p_name, '', $db_class_year));
                                        break;
                                    }
                                }

                                if (empty($year_only)) {
                                    $year_only = "-";
                                }

                                echo "<tr>";

                                // 1. Profile
                                echo "<td>
                                        <div class='profile-cell'>
                                            <div class='avatar'>$photo_html</div>
                                            <div>
                                                <span class='st-name'>" . $row['full_name'] . "</span>
                                                <span class='st-id'>" . $row['admission_no'] . "</span>
                                            </div>
                                        </div>
                                      </td>";

                                // 2. Program
                                echo "<td><span style='font-weight:600; color:#4B5563;'>$prog_display</span></td>";

                                // 3. Year
                                echo "<td><span style='color:#6B7280; background:#F9FAFB; padding:4px 8px; border:1px solid #E5E7EB; border-radius:6px; font-size:12px; font-weight:500;'>" . $year_only . "</span></td>";

                                // 4. Contact
                                echo "<td>
                                        <div style='font-size:13px; color:#374151;'><i class='fa-solid fa-phone' style='font-size:11px; color:#9CA3AF;'></i> " . $row['phone'] . "</div>
                                      </td>";

                                // 5. Status
                                echo "<td><span class='badge $status_badge'>" . $row['status'] . "</span></td>";

                                // 6. Actions
                                echo "<td>
                                        <div class='action-btns'>
                                            <a href='student_view.php?id=" . $row['student_id'] . "' class='btn-icon btn-view' title='View Profile'>
                                                <i class='fa-regular fa-eye'></i>
                                            </a>
                                            <a href='edit_student.php?id=" . $row['student_id'] . "' class='btn-icon btn-edit' title='Edit Details'>
                                                <i class='fa-regular fa-pen-to-square'></i>
                                            </a>
                                            <a href='delete_student.php?id=" . $row['student_id'] . "' class='btn-icon btn-delete' title='Delete' onclick='return confirm(\"Are you sure?\")'>
                                                <i class='fa-regular fa-trash-can'></i>
                                            </a>
                                        </div>
                                      </td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:#6B7280;'>No students found matching your filters.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px; text-align:right; font-size:13px; color:#6B7280;">
                Showing <?php echo mysqli_num_rows($result); ?> records
            </div>

        </main>
    </div>

</body>

</html>