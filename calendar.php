<?php
session_start();
include 'db_conn.php';
include 'google_drive.php';

date_default_timezone_set('Asia/Colombo');

// 1. LOGIN CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. HANDLE ADD NOTE
if (isset($_POST['add_note'])) {
    // --- FIX 1: SECURITY (SQL Injection) ---
    $date = mysqli_real_escape_string($conn, $_POST['note_date']);
    $note = mysqli_real_escape_string($conn, $_POST['note_text']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);

    // File Upload (Drive)
    $doc_path = "";
    if (!empty($_FILES['note_doc']['name'])) {
        $file_name = "reminder_" . time() . "_" . basename($_FILES['note_doc']['name']);
        // Ensure uploadToDrive function exists in google_drive.php
        $drive_link = uploadToDrive($_FILES['note_doc']['tmp_name'], $file_name);
        if ($drive_link) $doc_path = $drive_link;
    }

    $sql = "INSERT INTO dashboard_reminders (reminder_date, note_text, document_path, priority) VALUES ('$date', '$note', '$doc_path', '$priority')";
    mysqli_query($conn, $sql);

    // Make sure this filename matches your actual file (e.g., calendar.php)
    header("Location: calendar.php?date=$date");
    exit();
}

// 3. FILTER LOGIC
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
// Validate Date Format to prevent errors
if (!strtotime($selected_date)) $selected_date = date('Y-m-d');

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_priority = isset($_GET['priority_filter']) ? $_GET['priority_filter'] : 'All';

$cal_month = date('m', strtotime($selected_date));
$cal_year = date('Y', strtotime($selected_date));
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $cal_month, $cal_year);
$firstDay = date('w', strtotime("$cal_year-$cal_month-01"));

// 4. FETCH DATA
$sql_conditions = "MONTH(reminder_date) = '$cal_month' AND YEAR(reminder_date) = '$cal_year'";

if (!empty($search_query)) {
    $safe_search = mysqli_real_escape_string($conn, $search_query);
    $sql_conditions .= " AND note_text LIKE '%$safe_search%'";
}

// --- FIX 2: SECURITY (SQL Injection in Filter) ---
if ($filter_priority != 'All') {
    $safe_priority = mysqli_real_escape_string($conn, $filter_priority);
    $sql_conditions .= " AND priority = '$safe_priority'";
}

$notes_res = mysqli_query($conn, "SELECT * FROM dashboard_reminders WHERE $sql_conditions ORDER BY id ASC");

$calendar_events = [];
$total_notes = 0;
$high_priority_count = 0;

while ($row = mysqli_fetch_assoc($notes_res)) {
    $d = $row['reminder_date'];
    if (!isset($calendar_events[$d])) {
        $calendar_events[$d] = [];
    }
    // --- FIX 3: SECURITY (XSS) ---
    // We keep the raw text for DB, but when we output to JSON/HTML, we must be careful.
    // Here we store raw data, we will sanitize during output.
    $calendar_events[$d][] = $row;

    $total_notes++;
    if ($row['priority'] == 'High') $high_priority_count++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Executive Calendar | FMAC Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* STATS BOXES */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: white;
            padding: 20px;
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
            color: #1F2937;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
        }

        /* CALENDAR STRUCTURE */
        .calendar-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #E5E7EB;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            margin-bottom: 50px;
        }

        /* TOOLBAR */
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
            min-width: 150px;
        }

        .filter-group label {
            font-size: 11px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
        }

        .filter-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            outline: none;
            font-size: 13px;
            background: white;
        }

        .filter-input:focus {
            border-color: #ED8936;
        }

        .btn-load {
            background: #1E293B;
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
        }

        /* GRID */
        .cal-body {
            padding: 25px;
        }

        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .c-day-head {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #9CA3AF;
            padding-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .c-box {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            min-height: 120px;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            position: relative;
            transition: 0.2s;
        }

        .c-box:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-color: #CBD5E1;
        }

        .c-num {
            font-size: 16px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 5px;
        }

        .add-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #CBD5E1;
            font-size: 12px;
        }

        .c-box:hover .add-icon {
            color: #ED8936;
        }

        /* EVENT PILLS */
        .event-pill {
            font-size: 10px;
            padding: 4px 6px;
            border-radius: 4px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ep-High {
            background: #FEF2F2;
            color: #991B1B;
            border-left: 3px solid #EF4444;
        }

        .ep-Medium {
            background: #FFFBEB;
            color: #92400E;
            border-left: 3px solid #F59E0B;
        }

        .ep-Normal {
            background: #ECFDF5;
            color: #065F46;
            border-left: 3px solid #10B981;
        }

        /* MODAL */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
        }

        .modal-box {
            background: white;
            width: 450px;
            padding: 0;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 20px;
            background: #F8FAFC;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
        }

        .existing-event {
            background: #F9FAFB;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
            margin-bottom: 10px;
        }

        .ee-header {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .ee-text {
            font-size: 13px;
            color: #374151;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .ee-file {
            display: block;
            margin-top: 8px;
            font-size: 11px;
            color: #2563EB;
            text-decoration: none;
            font-weight: 600;
        }

        .ee-file:hover {
            text-decoration: underline;
        }

        .btn-save {
            width: 100%;
            padding: 12px;
            background: #ED8936;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }

        .btn-save:hover {
            background: #D67625;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php $page = 'calendar';
        include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header">
                <h2>Executive Calendar</h2>
                <div style="background:white; padding:8px 15px; border-radius:8px; border:1px solid #E5E7EB; font-weight:600; color:#374151;">
                    <?php echo date('F Y', strtotime("$cal_year-$cal_month-01")); ?>
                </div>
            </header>

            <div class="stats-row">
                <div class="stat-box">
                    <div>
                        <div class="stat-label">Total Notes</div>
                        <div style="font-size:11px; color:#94A3B8;">This Month</div>
                    </div>
                    <div class="stat-value"><?php echo $total_notes; ?></div>
                </div>
                <div class="stat-box">
                    <div>
                        <div class="stat-label" style="color:#DC2626;">High Priority</div>
                        <div style="font-size:11px; color:#94A3B8;">Action Required</div>
                    </div>
                    <div class="stat-value" style="color:#DC2626;"><?php echo $high_priority_count; ?></div>
                </div>
                <div class="stat-box">
                    <div>
                        <div class="stat-label">Current Date</div>
                        <div style="font-size:11px; color:#94A3B8;">Today</div>
                    </div>
                    <div class="stat-value" style="color:#4F46E5; font-size:20px;"><?php echo date('d M'); ?></div>
                </div>
            </div>

            <div class="calendar-card">

                <form method="GET" class="filters-toolbar">
                    <div class="filter-group">
                        <label>Month Selection</label>
                        <input type="date" name="date" class="filter-input" value="<?php echo htmlspecialchars($selected_date); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Filter Priority</label>
                        <select name="priority_filter" class="filter-input">
                            <option value="All">All Levels</option>
                            <option value="High" <?php if ($filter_priority == 'High') echo 'selected'; ?>>High (Urgent)</option>
                            <option value="Medium" <?php if ($filter_priority == 'Medium') echo 'selected'; ?>>Medium</option>
                            <option value="Normal" <?php if ($filter_priority == 'Normal') echo 'selected'; ?>>Normal</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Search Notes</label>
                        <input type="text" name="search" class="filter-input" placeholder="Keyword..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="filter-group" style="flex:0;">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn-load"><i class="fa-solid fa-filter"></i> Apply</button>
                    </div>
                </form>

                <div class="cal-body">
                    <div class="cal-grid">
                        <div class="c-day-head">Sun</div>
                        <div class="c-day-head">Mon</div>
                        <div class="c-day-head">Tue</div>
                        <div class="c-day-head">Wed</div>
                        <div class="c-day-head">Thu</div>
                        <div class="c-day-head">Fri</div>
                        <div class="c-day-head">Sat</div>

                        <?php
                        // Blank Days
                        for ($i = 0; $i < $firstDay; $i++) echo '<div class="c-box" style="background:transparent; border:none; box-shadow:none;"></div>';

                        // Actual Days
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $currDate = "$cal_year-$cal_month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                            $dayEvents = isset($calendar_events[$currDate]) ? $calendar_events[$currDate] : [];
                            $borderStyle = ($currDate == $selected_date) ? "border: 2px solid #ED8936;" : "";

                            // JSON Encode for JS
                            $jsonEvents = htmlspecialchars(json_encode($dayEvents), ENT_QUOTES, 'UTF-8');

                            echo "<div class='c-box' style='$borderStyle' onclick='openModal(\"$currDate\", $jsonEvents)'>";
                            echo "<span class='c-num'>$day</span>";
                            echo "<i class='fa-solid fa-plus add-icon'></i>";

                            // Pills Loop
                            $count = 0;
                            foreach ($dayEvents as $evt) {
                                if ($count < 3) {
                                    $hasDoc = !empty($evt['document_path']) ? '<i class="fa-solid fa-paperclip"></i>' : '';
                                    // --- FIX 3: Prevent XSS in HTML Display ---
                                    $safeNote = htmlspecialchars(substr($evt['note_text'], 0, 15));

                                    echo "<div class='event-pill ep-" . htmlspecialchars($evt['priority']) . "'>";
                                    echo "<span>" . $safeNote . "..</span>";
                                    echo "<span>$hasDoc</span>";
                                    echo "</div>";
                                }
                                $count++;
                            }
                            if ($count > 3) {
                                echo "<div style='font-size:10px; color:#6B7280; text-align:center;'>+ " . ($count - 3) . " more</div>";
                            }
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="noteModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="margin:0; color:#1F2937;">📅 Manage Events</h3>
                <button onclick="closeModal()" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
            </div>

            <div class="modal-body">
                <div id="existingEventsContainer"></div>

                <hr style="border:0; border-top:1px solid #E5E7EB; margin:20px 0;">

                <h4 style="margin:0 0 15px 0; color:#4B5563; font-size:13px; text-transform:uppercase;">Add New Note</h4>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="note_date" id="modalDate">

                    <div style="display:flex; gap:10px; margin-bottom:15px;">
                        <select name="priority" style="flex:1; padding:10px; border:1px solid #D1D5DB; border-radius:6px; outline:none;">
                            <option value="Normal">🟢 Normal</option>
                            <option value="Medium">🟠 Medium</option>
                            <option value="High">🔴 High (Urgent)</option>
                        </select>
                    </div>

                    <textarea name="note_text" rows="3" placeholder="Type event details..." required style="width:100%; padding:10px; border:1px solid #D1D5DB; border-radius:6px; outline:none; font-family:inherit; margin-bottom:15px;"></textarea>

                    <div style="margin-bottom:10px;">
                        <label style="font-size:12px; font-weight:700; color:#6B7280;">Attach File (Drive)</label>
                        <input type="file" name="note_doc" style="font-size:12px; margin-top:5px;">
                    </div>

                    <button type="submit" name="add_note" class="btn-save">Save Event</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // --- FIX 3: Sanitizer Function for JS XSS ---
        function escapeHtml(text) {
            if (!text) return "";
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function openModal(date, events) {
            document.getElementById('noteModal').style.display = 'flex';
            document.getElementById('modalDate').value = date;

            const container = document.getElementById('existingEventsContainer');
            container.innerHTML = '';

            if (events && events.length > 0) {
                events.forEach(evt => {
                    let fileHtml = '';
                    if (evt.document_path) {
                        fileHtml = `<a href="${evt.document_path}" target="_blank" class="ee-file"><i class="fa-solid fa-download"></i> Download Attachment</a>`;
                    }

                    let color = '#10B981';
                    if (evt.priority === 'High') color = '#EF4444';
                    if (evt.priority === 'Medium') color = '#F59E0B';

                    // USE escapeHtml() HERE
                    const html = `
                        <div class="existing-event">
                            <div class="ee-header">
                                <span style="color:${color}">${escapeHtml(evt.priority)} Priority</span>
                            </div>
                            <div class="ee-text">${escapeHtml(evt.note_text)}</div>
                            ${fileHtml}
                        </div>
                    `;
                    container.innerHTML += html;
                });
            } else {
                container.innerHTML = '<div style="text-align:center; color:#9CA3AF; font-size:13px; margin-bottom:10px;">No events for this day.</div>';
            }
        }

        function closeModal() {
            document.getElementById('noteModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('noteModal');
            if (event.target == modal) closeModal();
        }
    </script>
</body>

</html>