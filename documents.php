<?php
session_start();
include 'db_conn.php';

// 1. லாகின் செக்
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// --- ACTION 1: UPLOAD FILE ---
if (isset($_POST['upload_file'])) {
    // 'title' என்று மாற்றப்பட்டுள்ளது
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);

    $filename = $_FILES['file']['name'];
    $filesize = $_FILES['file']['size'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Size Calculation
    if ($filesize >= 1048576) {
        $size_text = number_format($filesize / 1048576, 2) . ' MB';
    } else {
        $size_text = number_format($filesize / 1024, 2) . ' KB';
    }

    $new_filename = uniqid() . "." . $ext;
    $target_dir = "uploads/";

    // Uploads folder உள்ளதா என சரிபார்த்தல்
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($tmp_name, $target_file)) {
        // Query-ல் 'title' பயன்படுத்தப்பட்டுள்ளது
        $sql = "INSERT INTO documents (title, category, file_path, file_type, file_size) 
                VALUES ('$title', '$category', '$new_filename', '$ext', '$size_text')";

        if (mysqli_query($conn, $sql)) {
            $message = "<div class='alert success'>File Uploaded Successfully!</div>";
        } else {
            $message = "<div class='alert error'>Database Error: " . mysqli_error($conn) . "</div>";
        }
    } else {
        $message = "<div class='alert error'>Failed to upload file. Check folder permissions.</div>";
    }
}

// --- ACTION 2: STAR / UNSTAR DOCUMENT ---
if (isset($_GET['star_id'])) {
    $id = $_GET['star_id'];
    $status = $_GET['status'];
    // 'doc_id' பயன்படுத்தப்பட்டுள்ளது
    mysqli_query($conn, "UPDATE documents SET is_starred='$status' WHERE doc_id='$id'");
    header("Location: documents.php");
}

// --- ACTION 3: DELETE DOCUMENT ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $path = $_GET['path'];
    // 'doc_id' பயன்படுத்தப்பட்டுள்ளது
    mysqli_query($conn, "DELETE FROM documents WHERE doc_id='$id'");
    if (file_exists("uploads/" . $path)) {
        unlink("uploads/" . $path);
    }
    header("Location: documents.php");
}

// --- FILTER LOGIC ---
$current_folder = isset($_GET['folder']) ? $_GET['folder'] : 'All';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$sql_docs = "SELECT * FROM documents WHERE 1=1";

if ($current_folder != 'All') {
    $sql_docs .= " AND category = '$current_folder'";
}
if ($search_query != '') {
    // 'title' பயன்படுத்தப்பட்டுள்ளது
    $sql_docs .= " AND title LIKE '%$search_query%'";
}

// Starred Documents First, then Newest First
$sql_docs .= " ORDER BY is_starred DESC, doc_id DESC";
$result_docs = mysqli_query($conn, $sql_docs);

// Get Folders List
$sql_folders = "SELECT DISTINCT category FROM documents";
$result_folders = mysqli_query($conn, $sql_folders);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Repository | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        /* --- STYLES (Same as before) --- */
        .doc-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 25px;
            height: calc(100vh - 100px);
        }

        .doc-sidebar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .btn-upload {
            background-color: #059669;
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.2s;
            border: none;
            width: 100%;
        }

        .btn-upload:hover {
            background-color: #047857;
        }

        .folder-list {
            list-style: none;
            padding: 0;
        }

        .folder-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            color: #4B5563;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.2s;
            font-size: 14px;
        }

        .folder-item a:hover,
        .folder-item a.active {
            background-color: #ECFDF5;
            color: #059669;
            font-weight: 600;
        }

        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            overflow-y: auto;
            padding-right: 10px;
            align-content: start;
        }

        .file-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #F3F4F6;
            position: relative;
            transition: 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 180px;
        }

        .file-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-color: #ED8936;
            transform: translateY(-3px);
        }

        .star-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #D1D5DB;
            cursor: pointer;
            font-size: 16px;
            transition: 0.2s;
        }

        .star-icon.active {
            color: #F59E0B;
        }

        .star-icon:hover {
            transform: scale(1.2);
        }

        .file-icon-box {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .type-pdf {
            background: #FEF2F2;
            color: #EF4444;
        }

        .type-img {
            background: #F3E8FF;
            color: #9333EA;
        }

        .type-doc {
            background: #EFF6FF;
            color: #3B82F6;
        }

        .type-zip {
            background: #FFF7ED;
            color: #EA580C;
        }

        .file-name {
            font-size: 14px;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-meta {
            font-size: 12px;
            color: #9CA3AF;
            display: flex;
            justify-content: space-between;
        }

        .file-actions {
            margin-top: 15px;
            border-top: 1px solid #F3F4F6;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
        }

        .action-btn {
            color: #6B7280;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }

        .action-btn:hover {
            color: #ED8936;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .preview-content {
            width: 800px;
            height: 85vh;
            display: flex;
            flex-direction: column;
        }

        .preview-frame {
            flex: 1;
            border: none;
            background: #F3F4F6;
            border-radius: 8px;
            margin-top: 15px;
        }

        .preview-img {
            max-width: 100%;
            max-height: 70vh;
            margin: auto;
            display: block;
            border-radius: 8px;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 20px;
            color: #6B7280;
            cursor: pointer;
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
            border-color: #ED8936;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
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
        <?php $page = 'documents';
        include 'sidebar.php'; ?>

        <main class="main-content">

            <header class="top-header">
                <h2>Central Document Repository</h2>
                <div class="header-right">
                    <form action="" method="GET">
                        <div class="search-bar">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" placeholder="Search files..." value="<?php echo $search_query; ?>">
                        </div>
                    </form>
                </div>
            </header>

            <?php echo $message; ?>

            <div class="doc-layout">
                <aside class="doc-sidebar">
                    <button class="btn-upload" onclick="openUploadModal()">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload File
                    </button>
                    <div>
                        <p style="font-size:12px; font-weight:700; color:#9CA3AF; margin-bottom:10px; text-transform:uppercase;">Folders</p>
                        <ul class="folder-list">
                            <li class="folder-item">
                                <a href="documents.php" class="<?php echo ($current_folder == 'All') ? 'active' : ''; ?>">
                                    <i class="fa-regular fa-folder-open"></i> All Documents
                                </a>
                            </li>
                            <?php
                            while ($folder = mysqli_fetch_assoc($result_folders)) {
                                if (!empty($folder['category'])) {
                                    $active = ($current_folder == $folder['category']) ? 'active' : '';
                                    echo "<li class='folder-item'>
                                        <a href='documents.php?folder={$folder['category']}' class='$active'>
                                            <i class='fa-regular fa-folder'></i> {$folder['category']}
                                        </a>
                                      </li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </aside>

                <div class="doc-grid">
                    <?php
                    if (mysqli_num_rows($result_docs) > 0) {
                        while ($doc = mysqli_fetch_assoc($result_docs)) {
                            $ext = $doc['file_type'];
                            $icon_class = "fa-file";
                            $color_class = "type-doc";
                            if (in_array($ext, ['pdf'])) {
                                $icon_class = "fa-file-pdf";
                                $color_class = "type-pdf";
                            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                $icon_class = "fa-image";
                                $color_class = "type-img";
                            } elseif (in_array($ext, ['zip', 'rar'])) {
                                $icon_class = "fa-file-zipper";
                                $color_class = "type-zip";
                            }

                            $star_class = ($doc['is_starred'] == 1) ? 'active' : '';
                            $star_status = ($doc['is_starred'] == 1) ? 0 : 1;

                            // doc_id மற்றும் title பயன்படுத்தப்பட்டுள்ளது
                            $doc_id = $doc['doc_id'];
                            $doc_title = $doc['title'];
                    ?>
                            <div class="file-card">
                                <a href="documents.php?star_id=<?php echo $doc_id; ?>&status=<?php echo $star_status; ?>"
                                    class="star-icon <?php echo $star_class; ?>" title="Pin/Unpin"><i class="fa-solid fa-star"></i></a>

                                <div class="file-info-area" onclick="openPreview('<?php echo $doc_title; ?>', 'uploads/<?php echo $doc['file_path']; ?>', '<?php echo $ext; ?>')">
                                    <div class="file-icon-box <?php echo $color_class; ?>"><i class="fa-regular <?php echo $icon_class; ?>"></i></div>
                                    <h4 class="file-name" title="<?php echo $doc_title; ?>"><?php echo $doc_title; ?></h4>
                                    <div class="file-meta">
                                        <span><?php echo $doc['file_size']; ?></span>
                                        <span><?php echo $doc['category']; ?></span>
                                    </div>
                                </div>

                                <div class="file-actions">
                                    <span class="action-btn" onclick="openPreview('<?php echo $doc_title; ?>', 'uploads/<?php echo $doc['file_path']; ?>', '<?php echo $ext; ?>')"><i class="fa-regular fa-eye"></i> View</span>
                                    <a href="uploads/<?php echo $doc['file_path']; ?>" download class="action-btn"><i class="fa-solid fa-download"></i></a>
                                    <a href="documents.php?delete_id=<?php echo $doc_id; ?>&path=<?php echo $doc['file_path']; ?>"
                                        class="action-btn" style="color:#EF4444;" onclick="return confirm('Delete this file?')"><i class="fa-regular fa-trash-can"></i></a>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo "<p style='color:#6B7280; padding:20px;'>No documents found.</p>";
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <div id="uploadModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('uploadModal')">&times;</span>
            <h3 style="margin-bottom:20px;">Upload New Document</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <label style="font-size:12px; font-weight:bold; color:#6B7280;">Document Title</label>
                <input type="text" name="title" class="form-input" placeholder="Ex: Academic Calendar" required>

                <label style="font-size:12px; font-weight:bold; color:#6B7280;">Category</label>
                <input type="text" name="category" class="form-input" list="cat_list" placeholder="Select or Type New" required>
                <datalist id="cat_list">
                    <option value="Circulars">
                    <option value="Legal">
                    <option value="Forms">
                </datalist>

                <label style="font-size:12px; font-weight:bold; color:#6B7280;">Select File</label>
                <input type="file" name="file" class="form-input" required>

                <button type="submit" name="upload_file" class="btn-upload" style="width:100%; margin-top:10px;">Upload Now</button>
            </form>
        </div>
    </div>

    <div id="previewModal" class="modal-overlay">
        <div class="modal-content preview-content">
            <span class="close-modal" onclick="closeModal('previewModal')">&times;</span>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 id="previewTitle">Preview</h3>
                <div>
                    <button onclick="printDoc()" style="padding:8px 15px; border:1px solid #ccc; background:white; cursor:pointer; border-radius:5px;"><i class="fa-solid fa-print"></i> Print</button>
                    <a id="downloadBtn" href="#" download style="padding:8px 15px; background:#ED8936; color:white; text-decoration:none; border-radius:5px; margin-left:10px;"><i class="fa-solid fa-download"></i> Download</a>
                </div>
            </div>
            <div id="previewBody" style="flex:1; margin-top:15px; overflow:hidden; border-radius:8px; background:#f9f9f9; display:flex; align-items:center; justify-content:center;"></div>
        </div>
    </div>

    <script>
        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
            if (id === 'previewModal') document.getElementById('previewBody').innerHTML = '';
        }

        function openPreview(name, path, ext) {
            document.getElementById('previewModal').style.display = 'flex';
            document.getElementById('previewTitle').innerText = name;
            document.getElementById('downloadBtn').href = path;
            const container = document.getElementById('previewBody');
            container.innerHTML = '';
            if (['jpg', 'jpeg', 'png', 'gif'].includes(ext.toLowerCase())) {
                container.innerHTML = `<img src="${path}" class="preview-img">`;
            } else if (ext.toLowerCase() === 'pdf') {
                container.innerHTML = `<iframe src="${path}" class="preview-frame" width="100%" height="100%"></iframe>`;
            } else {
                container.innerHTML = `<div style="text-align:center; color:#666;">No preview available.<br>Please download.</div>`;
            }
        }

        function printDoc() {
            const frame = document.querySelector('.preview-frame');
            if (frame) {
                frame.contentWindow.print();
            } else {
                window.print();
            }
        }
    </script>

</body>

</html>