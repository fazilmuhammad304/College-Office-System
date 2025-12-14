<?php
// InfinityFree Database Connection
$servername = "sql110.infinityfree.com";             // Host Name
$username   = "if0_40675578";                        // User Name
$password   = "Your_vPanel_Password";                // ⚠️ இங்கே உங்கள் vPanel பாஸ்வேர்டை போடவும்!
$dbname     = "if0_40675578_portal"; // DB Name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

