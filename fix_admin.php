<?php
// 1. Connect to Database
include 'db_conn.php';

echo "<h3>Fixing Admin Account...</h3>";

// 2. பாஸ்வேர்ட் கால அளவு குறைவாக இருந்தால் அதை 255 ஆக மாற்றும் (பாதுகாப்பிற்காக)
$sql_alter = "ALTER TABLE admin_users MODIFY password VARCHAR(255)";
mysqli_query($conn, $sql_alter);

// 3. பழைய 'admin' கணக்கை அழித்தல்
$sql_delete = "DELETE FROM admin_users WHERE username = 'admin'";
if (mysqli_query($conn, $sql_delete)) {
    echo "✅ Old admin deleted.<br>";
}

// 4. புதிய 'admin' கணக்கை உருவாக்குதல் (சரியான Hash உடன்)
// Username: admin
// Password: 123
$new_pass = password_hash("123", PASSWORD_DEFAULT); // '123' பாஸ்வேர்டுக்கான Hash

$sql_insert = "INSERT INTO admin_users (username, password, full_name, role) 
               VALUES ('admin', '$new_pass', 'System Admin', 'Super Admin')";

if (mysqli_query($conn, $sql_insert)) {
    echo "✅ New Admin Created Successfully!<br>";
    echo "<hr>";
    echo "<h3>Login Details:</h3>";
    echo "Username: <b>admin</b><br>";
    echo "Password: <b>123</b><br>";
    echo "<br><a href='login.php' style='font-size:20px;'>Go to Login Page</a>";
} else {
    echo "❌ Error: " . mysqli_error($conn);
}
