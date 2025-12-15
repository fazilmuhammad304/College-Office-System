<?php
session_start();
include 'db_conn.php';

// 1. LOGIN & ROLE CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Staff';
$is_admin = ($user_role === 'Super Admin');

$message = "";

// --- ACTION 1: CREATE CATEGORY ---
if (isset($_POST['create_category'])) {
    $cat_name = mysqli_real_escape_string($conn, $_POST['cat_name']);
    $is_private = isset($_POST['is_private']) ? 1 : 0;

    if ($is_private == 1 && !$is_admin) {
        $message = "<div class='alert error'>Only Admins can create private folders!</div>";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM categories WHERE name = '$cat_name'");
        if (mysqli_num_rows($check) > 0) {
            $message = "<div class='alert error'>Folder already exists!</div>";
        } else {
            $sql = "INSERT INTO categories (name, is_private) VALUES ('$cat_name', '$is_private')";
            mysqli_query($conn, $sql);
            $message = "<div class='alert success'>Folder created!</div>";
        }
    }
}

// --- ACTION 2: RENAME CATEGORY ---
if (isset($_POST['rename_category'])) {
    $cat_id = $_POST['rename_cat_id'];
    $old_name = mysqli_real_escape_string($conn, $_POST['old_cat_name']);
    $new_name = mysqli_real_escape_string($conn, $_POST['new_cat_name']);

    // 1. Update Category Table
    $update_cat = "UPDATE categories SET name='$new_name' WHERE id='$cat_id'";
    if (mysqli_query($conn, $update_cat)) {
        // 2. Update All Documents in this Folder
        $update_docs = "UPDATE documents SET category='$new_name' WHERE category='$old_name'";
        mysqli_query($conn, $update_docs);
        $message = "<div class='alert success'>Folder renamed and files updated!</div>";
    } else {
        $message = "<div class='alert error'>Error updating folder.</div>";
    }
}

// --- ACTION 3: DELETE CATEGORY ---
if (isset($_GET['del_cat_id'])) {
    if (!$is_admin) {
        die("Access Denied");
    }

    $del_id = $_GET['del_cat_id'];
    $cat_name = $_GET['cat_name'];

    // Check if folder is empty
    $check_files = mysqli_query($conn, "SELECT * FROM documents WHERE category='$cat_name'");
    if (mysqli_num_rows($check_files) > 0) {
        $message = "<div class='alert error'>Cannot delete folder! It contains files.</div>";
    } else {
        mysqli_query($conn, "DELETE FROM categories WHERE id='$del_id'");
        $message = "<div class='alert success'>Folder deleted successfully.</div>";
    }
}

// --- ACTION 4: UPLOAD FILE ---
if (isset($_POST['upload_file'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $filename = $_FILES['file']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $filesize = $_FILES['file']['size'];
    $size_text = ($filesize >= 1048576) ? number_format($filesize / 1048576, 2) . ' MB' : number_format($filesize / 1024, 2) . ' KB';
    $new_filename = uniqid() . "." . $ext;

    if (move_uploaded_file($_FILES['file']['tmp_name'], "uploads/" . $new_filename)) {
        $sql = "INSERT INTO documents (title, category, file_path, file_type, file_size, is_starred) 
                VALUES ('$title', '$category', '$new_filename', '$ext', '$size_text', 0)";
        mysqli_query($conn, $sql);
        $message = "<div class='alert success'>File Uploaded!</div>";
    }
}

// --- ACTION 5: EDIT FILE ---
if (isset($_POST['edit_file'])) {
    $doc_id = $_POST['edit_doc_id'];
    $new_title = mysqli_real_escape_string($conn, $_POST['edit_title']);
    $new_cat = mysqli_real_escape_string($conn, $_POST['edit_category']);
    mysqli_query($conn, "UPDATE documents SET title='$new_title', category='$new_cat' WHERE doc_id='$doc_id'");
    $message = "<div class='alert success'>Document Updated!</div>";
}

// --- ACTION 6: DELETE FILE ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $path = $_GET['path'];
    mysqli_query($conn, "DELETE FROM documents WHERE doc_id='$id'");
    if (file_exists("uploads/" . $path)) {
        unlink("uploads/" . $path);
    }
    header("Location: documents.php");
    exit();
}

// --- DATA FETCHING ---
$current_folder = isset($_GET['folder']) ? $_GET['folder'] : 'All';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch Folders
$folder_sql = "SELECT * FROM categories";
if (!$is_admin) {
    $folder_sql .= " WHERE is_private = 0";
}
$folder_sql .= " ORDER BY name ASC";
$result_folders = mysqli_query($conn, $folder_sql);

// Fetch Docs
$sql_docs = "SELECT * FROM documents WHERE 1=1";
if ($current_folder != 'All') {
    $sql_docs .= " AND category = '$current_folder'";
}
if ($search_query != '') {
    $sql_docs .= " AND title LIKE '%$search_query%'";
}
$sql_docs .= " ORDER BY is_starred DESC, doc_id DESC";
$result_docs = mysqli_query($conn, $sql_docs);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Documents | College Office</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        .doc-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 25px;
            height: calc(100vh - 140px);
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
            background: #ED8936;
            color: white;
            padding: 12px;
            border-radius: 8px;
            border: none;
            width: 100%;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .btn-upload:hover {
            background: #D67625;
        }

        .btn-new-folder {
            background: white;
            border: 1px solid #E5E7EB;
            color: #374151;
            padding: 10px;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .btn-new-folder:hover {
            background: #F9FAFB;
        }

        .folder-list {
            list-style: none;
            padding: 0;
            overflow-y: auto;
            flex: 1;
        }

        .folder-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            margin-bottom: 2px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .folder-item:hover {
            background: #FFF7ED;
        }

        .folder-item.active-item {
            background: #ED8936;
        }

        .folder-item.active-item a,
        .folder-item.active-item i {
            color: white !important;
        }

        .folder-link {
            text-decoration: none;
            color: #4B5563;
            font-size: 14px;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .folder-actions {
            display: none;
            gap: 5px;
        }

        .folder-item:hover .folder-actions {
            display: flex;
        }

        .f-btn {
            font-size: 11px;
            padding: 4px;
            color: #9CA3AF;
            cursor: pointer;
        }

        .f-btn:hover {
            color: #ED8936;
        }

        .f-btn.del:hover {
            color: #EF4444;
        }

        .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            overflow-y: auto;
            align-content: start;
        }

        .file-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #E5E7EB;
            position: relative;
            transition: 0.2s;
            display: flex;
            flex-direction: column;
        }

        .file-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border-color: #ED8936;
        }

        .file-icon-box {
            height: 90px;
            background: #F9FAFB;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 15px;
            cursor: pointer;
        }

        .type-pdf {
            color: #EF4444;
            background: #FEF2F2;
        }

        .type-img {
            color: #8B5CF6;
            background: #F5F3FF;
        }

        .type-doc {
            color: #3B82F6;
            background: #EFF6FF;
        }

        .file-name {
            font-weight: 600;
            font-size: 14px;
            color: #1F2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-meta {
            font-size: 11px;
            color: #9CA3AF;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .file-actions {
            display: flex;
            gap: 5px;
            border-top: 1px solid #F3F4F6;
            padding-top: 10px;
            justify-content: space-between;
        }

        /* --- MENU & LIST STYLES (New) --- */
        .menu-container {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        .menu-btn {
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6B7280;
            transition: 0.2s;
        }

        .menu-btn:hover {
            color: #1F2937;
            background: #F3F4F6;
        }

        .dropdown-menu {
            position: absolute;
            top: 35px;
            right: 0;
            background: white;
            border: 1px solid #E5E7EB;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            width: 140px;
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 20;
        }

        .dropdown-menu.show {
            display: flex;
        }

        .dropdown-item {
            padding: 8px 12px;
            font-size: 13px;
            color: #4B5563;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .dropdown-item:hover {
            background: #FFF7ED;
            color: #FB923C;
        }

        .dropdown-item.del:hover {
            background: #FEF2F2;
            color: #EF4444;
        }

        /* Modals */
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
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            width: 450px;
            padding: 25px;
            border-radius: 12px;
            position: relative;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1);
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            color: #9CA3AF;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            margin-bottom: 15px;
            outline: none;
        }

        .form-input:focus {
            border-color: #ED8936;
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
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
        <?php $page = 'documents';
        include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header">
                <h2>Document Center</h2>
                <div class="search-bar" style="background:white; padding:8px 15px; border-radius:8px; border:1px solid #E5E7EB; display:flex; align-items:center; width:300px;">
                    <i class="fa-solid fa-magnifying-glass" style="color:#9CA3AF; margin-right:10px;"></i>
                    <form action="" method="GET" style="flex:1;">
                        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>" style="border:none; outline:none; width:100%;">
                    </form>
                </div>
            </header>

            <?php echo $message; ?>

            <div class="doc-layout">
                <aside class="doc-sidebar">
                    <button class="btn-upload" onclick="openModal('uploadModal')"><i class="fa-solid fa-cloud-arrow-up"></i> Upload</button>
                    <button class="btn-new-folder" onclick="openModal('catModal')"><i class="fa-solid fa-folder-plus"></i> New Folder</button>

                    <div>
                        <p style="font-size:11px; font-weight:700; color:#9CA3AF; margin-bottom:10px; text-transform:uppercase;">Folders</p>
                        <ul class="folder-list">
                            <li class="folder-item <?php echo ($current_folder == 'All') ? 'active-item' : ''; ?>">
                                <a href="documents.php" class="folder-link"><i class="fa-solid fa-layer-group"></i> All Files</a>
                            </li>
                            <?php
                            if (mysqli_num_rows($result_folders) > 0) {
                                mysqli_data_seek($result_folders, 0);
                                while ($cat = mysqli_fetch_assoc($result_folders)) {
                                    $cName = $cat['name'];
                                    $cId = $cat['id'];
                                    $isPriv = $cat['is_private'];
                                    $isActive = ($current_folder == $cName) ? 'active-item' : '';
                                    $lock = ($isPriv == 1) ? '<i class="fa-solid fa-lock" style="font-size:10px; opacity:0.5;"></i>' : '';

                                    echo "<li class='folder-item $isActive'>
                                            <a href='documents.php?folder=$cName' class='folder-link'>
                                                <i class='fa-regular fa-folder'></i> $cName $lock
                                            </a>";

                                    if ($is_admin) {
                                        echo "<div class='folder-actions'>
                                                <i class='fa-solid fa-pen f-btn' title='Rename' onclick=\"openRenameModal('$cId', '$cName')\"></i>
                                                <a href='documents.php?del_cat_id=$cId&cat_name=$cName' onclick=\"return confirm('Delete folder: $cName? (Must be empty)')\">
                                                    <i class='fa-solid fa-trash f-btn del' title='Delete'></i>
                                                </a>
                                              </div>";
                                    }
                                    echo "</li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </aside>

                <div class="doc-grid">
                    <?php if (mysqli_num_rows($result_docs) > 0): ?>
                        <?php while ($doc = mysqli_fetch_assoc($result_docs)):
                            $ext = strtolower($doc['file_type']);
                            $bg = "type-doc";
                            $icon = "fa-file";

                            if ($ext == 'pdf') {
                                $bg = "type-pdf"; // Red
                                $icon = "fa-file-pdf";
                            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                $bg = "type-img"; // Purple
                                $icon = "fa-image";
                            } elseif (in_array($ext, ['mp4', 'webm', 'ogg'])) {
                                $bg = "type-pdf"; // Reuse Red
                                $icon = "fa-file-video";
                            } elseif (in_array($ext, ['mp3', 'wav'])) {
                                $bg = "type-img"; // Reuse Purple
                                $icon = "fa-file-audio";
                            } elseif ($ext == 'txt') {
                                $bg = "type-doc"; // Blue
                                $icon = "fa-file-lines";
                            }
                        ?>
                            <div class="file-card">
                                <div class="menu-container">
                                    <div class="menu-btn" onclick="toggleMenu('menu-<?php echo $doc['doc_id']; ?>', event)">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </div>
                                    <div class="dropdown-menu" id="menu-<?php echo $doc['doc_id']; ?>">
                                        <span class="dropdown-item" onclick="openPreview('<?php echo htmlspecialchars($doc['title'], ENT_QUOTES); ?>', 'uploads/<?php echo $doc['file_path']; ?>', '<?php echo $ext; ?>')">
                                            <i class="fa-regular fa-eye"></i> View
                                        </span>
                                        <span class="dropdown-item" onclick="openEditModal('<?php echo $doc['doc_id']; ?>', '<?php echo addslashes($doc['title']); ?>', '<?php echo $doc['category']; ?>')">
                                            <i class="fa-solid fa-pen"></i> Edit
                                        </span>
                                        <a href="uploads/<?php echo $doc['file_path']; ?>" download class="dropdown-item">
                                            <i class="fa-solid fa-download"></i> Download
                                        </a>
                                        <a href="documents.php?delete_id=<?php echo $doc['doc_id']; ?>&path=<?php echo $doc['file_path']; ?>" class="dropdown-item del" onclick="return confirm('Delete?')">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>

                                <div class="file-icon-box <?php echo $bg; ?>" onclick="openPreview('<?php echo htmlspecialchars($doc['title'], ENT_QUOTES); ?>', 'uploads/<?php echo $doc['file_path']; ?>', '<?php echo $ext; ?>')">
                                    <i class="fa-regular <?php echo $icon; ?>"></i>
                                </div>
                                <div class="file-name" title="<?php echo $doc['title']; ?>"><?php echo $doc['title']; ?></div>
                                <div class="file-meta"><span><?php echo $doc['file_size']; ?></span><span><?php echo $doc['category']; ?></span></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column:1/-1; text-align:center; padding:50px; color:#9CA3AF;">No files found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="uploadModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('uploadModal')">&times;</span>
            <h3 style="margin-bottom:20px;">Upload File</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="text" name="title" class="form-input" required placeholder="File Title">
                <select name="category" class="form-input" required>
                    <?php mysqli_data_seek($result_folders, 0);
                    while ($c = mysqli_fetch_assoc($result_folders)) {
                        echo "<option value='" . $c['name'] . "'>" . $c['name'] . "</option>";
                    } ?>
                </select>
                <input type="file" name="file" class="form-input" required>
                <button type="submit" name="upload_file" class="btn-upload">Upload</button>
            </form>
        </div>
    </div>

    <div id="catModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('catModal')">&times;</span>
            <h3 style="margin-bottom:20px;">New Folder</h3>
            <form action="" method="POST">
                <input type="text" name="cat_name" class="form-input" required placeholder="Folder Name">
                <?php if ($is_admin): ?><label><input type="checkbox" name="is_private"> Private (Admin Only)</label><br><br><?php endif; ?>
                <button type="submit" name="create_category" class="btn-upload">Create</button>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('editModal')">&times;</span>
            <h3 style="margin-bottom:20px;">Edit File</h3>
            <form action="" method="POST">
                <input type="hidden" name="edit_doc_id" id="edit_doc_id">
                <label style="font-size:12px;">Title</label>
                <input type="text" name="edit_title" id="edit_title" class="form-input" required>
                <label style="font-size:12px;">Move to Folder</label>
                <select name="edit_category" id="edit_category" class="form-input" required>
                    <?php mysqli_data_seek($result_folders, 0);
                    while ($c = mysqli_fetch_assoc($result_folders)) {
                        echo "<option value='" . $c['name'] . "'>" . $c['name'] . "</option>";
                    } ?>
                </select>
                <button type="submit" name="edit_file" class="btn-upload">Save</button>
            </form>
        </div>
    </div>

    <div id="renameModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('renameModal')">&times;</span>
            <h3 style="margin-bottom:20px;">Rename Folder</h3>
            <form action="" method="POST">
                <input type="hidden" name="rename_cat_id" id="rename_cat_id">
                <input type="hidden" name="old_cat_name" id="old_cat_name">
                <label style="font-size:12px;">New Name</label>
                <input type="text" name="new_cat_name" id="new_cat_name" class="form-input" required>
                <div style="font-size:12px; color:#EF4444; margin-bottom:10px;">Warning: This will update all files inside this folder to the new name.</div>
                <button type="submit" name="rename_category" class="btn-upload">Update Name</button>
            </form>
        </div>
    </div>

    <div id="previewModal" class="modal-overlay">
        <div class="modal-content" style="width:800px; height:85vh; display:flex; flex-direction:column; padding:0; background:transparent; box-shadow:none;">
            <div style="background:white; padding:15px 20px; border-radius:12px 12px 0 0; display:flex; justify-content:space-between; align-items:center;">
                <h3 id="previewTitle" style="color:#1F293B; margin:0; font-size:18px;">Preview</h3>
                <div style="display:flex; gap:10px;">
                    <a id="downloadBtn" href="#" download style="padding:8px 15px; background:#F17C1C; color:white; text-decoration:none; border-radius:6px; font-size:13px; font-weight:600;"><i class="fa-solid fa-download"></i> Download</a>
                    <i class="fa-solid fa-xmark" onclick="closeModal('previewModal')" style="font-size:24px; color:#64748B; cursor:pointer; display:flex; align-items:center;"></i>
                </div>
            </div>
            <div id="previewBody" style="flex:1; background:#F1F5F9; border-radius:0 0 12px 12px; overflow:hidden; display:flex; align-items:center; justify-content:center; position:relative;"></div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function openEditModal(id, title, cat) {
            document.getElementById('edit_doc_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_category').value = cat;
            openModal('editModal');
        }

        function openRenameModal(id, name) {
            document.getElementById('rename_cat_id').value = id;
            document.getElementById('old_cat_name').value = name;
            document.getElementById('new_cat_name').value = name;
            openModal('renameModal');
        }

        function openPreview(name, path, ext) {
            document.getElementById('previewModal').style.display = 'flex';
            document.getElementById('previewTitle').innerText = name;
            document.getElementById('downloadBtn').href = path;
            const container = document.getElementById('previewBody');
            container.innerHTML = '';

            var extLc = ext.toLowerCase();

            if (['jpg', 'jpeg', 'png', 'gif'].includes(extLc)) {
                container.innerHTML = `<img src="${path}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
            } else if (extLc === 'pdf') {
                container.innerHTML = `<iframe src="${path}" style="width:100%; height:100%; border:none;"></iframe>`;
            } else if (['mp4', 'webm', 'ogg'].includes(extLc)) {
                container.innerHTML = `<video src="${path}" controls style="max-width:100%; max-height:100%; outline:none; box-shadow:0 4px 10px rgba(0,0,0,0.1); border-radius:8px;"></video>`;
            } else if (['mp3', 'wav'].includes(extLc)) {
                container.innerHTML = `<audio src="${path}" controls style="width:80%; outline:none;"></audio>`;
            } else if (extLc === 'txt') {
                container.innerHTML = `<iframe src="${path}" style="width:100%; height:100%; border:none; background:white;"></iframe>`;
            } else {
                container.innerHTML = `<div style="text-align:center; color:#64748B;">No preview available for this file type.<br>Please download to view.</div>`;
            }
        }

        function toggleMenu(id, event) {
            event.stopPropagation();
            var menu = document.getElementById(id);
            var isVisible = menu.classList.contains('show');

            // Hide all other menus
            var allMenus = document.querySelectorAll('.dropdown-menu');
            allMenus.forEach(m => m.classList.remove('show'));

            if (!isVisible) {
                menu.classList.add('show');
            }
        }

        window.onclick = function(e) {
            // Close Modals
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = "none";
            }
            // Close Menus
            if (!e.target.closest('.menu-container')) {
                var allMenus = document.querySelectorAll('.dropdown-menu');
                allMenus.forEach(m => m.classList.remove('show'));
            }
        }
    </script>
</body>

</html>