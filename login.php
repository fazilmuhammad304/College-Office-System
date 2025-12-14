<?php
session_start();
include 'db_conn.php';

$error_msg = "";

if (isset($_POST['login_btn'])) {

    // 1. Get data from form
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Raw password (e.g., admin123)

    // 2. Check Database for this username
    $sql = "SELECT * FROM admin_users WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // 3. Verify Password
        // Note: In Step 2 (Database), we inserted a HASHED password. 
        // password_verify() checks if 'admin123' matches that hash.
        if (password_verify($password, $row['password'])) {

            // Success: Set Session Variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];

            // Redirect to Dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error_msg = "Incorrect Password!";
        }
    } else {
        $error_msg = "Username not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Office Admin Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Error Message Style */
        .error-banner {
            background-color: #FEE2E2;
            color: #EF4444;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #FECACA;
        }
    </style>
</head>

<body>

    <div class="login-wrapper">
        <div class="login-card">

            <div class="card-header">
                <div class="logo-container">
                    <img src="https://via.placeholder.com/100x100?text=Logo" alt="Logo" class="logo">
                </div>
                <h1>Office Admin Portal</h1>
                <p>Secure Data Management System</p>
            </div>

            <div class="card-body">

                <?php if (!empty($error_msg)) { ?>
                    <div class="error-banner">
                        <?php echo $error_msg; ?>
                    </div>
                <?php } ?>

                <form action="login.php" method="POST">

                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter username" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>

                    <button type="submit" name="login_btn" class="btn-submit">
                        Access Dashboard <span>&rarr;</span>
                    </button>

                </form>

                <div class="footer-text">
                    Authorized Personnel Only • v2.5.0
                </div>
            </div>

        </div>
    </div>

</body>

</html>