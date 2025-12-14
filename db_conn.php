<?php
// Localhost Database Connection (XAMPP / WAMP)
$servername = "localhost";
$username   = "root";      // XAMPP-ல் Default Username 'root'
$password   = "";          // XAMPP-ல் Default Password காலி (Empty)
$dbname     = "faizanul_madeena_portal"; // நீங்கள் phpMyAdmin-ல் உருவாக்கும் டேட்டாபேஸ் பெயர்

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
