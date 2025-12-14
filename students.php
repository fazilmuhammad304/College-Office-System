<?php
// 1. டேட்டாபேஸ் இணைப்பு
include 'db_conn.php';

// 2. ஸ்டூடன்ட் விவரங்களை எடுப்பதற்கான Query (புதிய மாணவர்கள் முதலில் வர DESC)
$sql = "SELECT * FROM students ORDER BY student_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* Table Styles (இந்த பக்கத்திற்கு மட்டும் தேவையான டிசைன்) */
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            margin-top: 20px;
        }

        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filters select {
            padding: 10px 15px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            margin-right: 10px;
            color: #4B5563;
            outline: none;
        }

        /* Add Student Button (Orange) */
        .btn-add-student {
            background-color: #ED8936;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-add-student:hover {
            background-color: #D67625;
        }

        /* Table Structure */
        .student-table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-table th {
            text-align: left;
            padding: 15px;
            font-size: 12px;
            font-weight: 600;
            color: #9CA3AF;
            text-transform: uppercase;
            border-bottom: 1px solid #F3F4F6;
        }

        .student-table td {
            padding: 15px;
            border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
            color: #374151;
            font-size: 14px;
        }

        /* Avatar & Name Styling */
        .student-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #E0E0E0 0%, #F5F5F5 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #666;
            font-size: 14px;
        }

        .name-box h4 {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 2px;
        }

        .name-box span {
            font-size: 12px;
            color: #9CA3AF;
        }

        /* Status Badges */
        .badge-active {
            background-color: #ECFDF5;
            color: #10B981;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-inactive {
            background-color: #FEF2F2;
            color: #EF4444;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .action-icon {
            color: #9CA3AF;
            cursor: pointer;
            font-size: 16px;
            transition: 0.2s;
        }

        .action-icon:hover {
            color: #ED8936;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">

        <?php
        $page = 'students'; // Sidebar-ல் 'Students' டேப் Active ஆக இருக்க இது உதவும்
        include 'sidebar.php';
        ?>

        <main class="main-content">

            <header class="top-header">
                <h2>Student Management</h2>
                <div class="header-right">
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search students...">
                    </div>
                </div>
            </header>

            <div class="table-container">

                <div class="controls-row">
                    <div class="filters">
                        <select>
                            <option>All Programs</option>
                            <option>Al-Alim</option>
                            <option>Hifz</option>
                        </select>
                        <select>
                            <option>All Years</option>
                            <option>Year 1</option>
                            <option>Year 2</option>
                        </select>
                    </div>

                    <a href="add_student.html" class="btn-add-student">
                        <i class="fa-solid fa-plus"></i> Add Student
                    </a>
                </div>

                <table class="student-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Program</th>
                            <th>Date Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        // PHP Loop to display data
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {

                                // Status கலர் மாற்றம் (Green/Red)
                                $status_badge = ($row['status'] == 'Active') ? 'badge-active' : 'badge-inactive';

                                // பெயரின் முதல் எழுத்தை Avatar-ல் காட்ட
                                $initial = substr($row['full_name'], 0, 1);

                                echo "<tr>";

                                // 1. Name Column
                                echo "<td>
                                        <div class='student-info'>
                                            <div class='avatar'>$initial</div>
                                            <div class='name-box'>
                                                <h4>" . $row['full_name'] . "</h4>
                                                <span>" . $row['admission_no'] . "</span>
                                            </div>
                                        </div>
                                      </td>";

                                // 2. Program Column
                                echo "<td>" . $row['class_year'] . "</td>";

                                // 3. Date Column
                                echo "<td>" . $row['admission_date'] . "</td>";

                                // 4. Status Column
                                echo "<td><span class='" . $status_badge . "'>● " . $row['status'] . "</span></td>";

                                // 5. Action Column
                                echo "<td>
                                        <i class='fa-regular fa-eye action-icon' title='View Details'></i>
                                      </td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#666;'>No students found in database.</td></tr>";
                        }
                        ?>

                    </tbody>
                </table>

            </div>

        </main>
    </div>

</body>

</html>