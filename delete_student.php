<?php
session_start();
include 'db_conn.php';

// லாகின் செக் (Login Check)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // ---------------------------------------------------------
    // 1. மாணவரின் போட்டோவை நீக்குதல் (Local File ஆக இருந்தால்)
    // ---------------------------------------------------------
    $s_query = mysqli_query($conn, "SELECT photo FROM students WHERE student_id = '$id'");
    $student = mysqli_fetch_assoc($s_query);

    if ($student && !empty($student['photo'])) {
        $local_photo_path = "uploads/" . $student['photo'];
        // ஃபைல் லோக்கல் ஃபோல்டரில் இருந்தால் மட்டும் அழிக்கும்
        if (file_exists($local_photo_path)) {
            unlink($local_photo_path);
        }
    }

    // ---------------------------------------------------------
    // 2. மாணவரின் ஆவணங்களை (Documents) நீக்குதல்
    // ---------------------------------------------------------

    // முதலில் ஃபைல்களை லோக்கலில் இருந்து நீக்குவோம்
    $d_query = mysqli_query($conn, "SELECT file_path FROM documents WHERE student_id = '$id'");
    while ($doc = mysqli_fetch_assoc($d_query)) {
        $local_doc_path = "uploads/" . $doc['file_path'];
        if (!empty($doc['file_path']) && file_exists($local_doc_path)) {
            unlink($local_doc_path);
        }
    }

    // பிறகு டேட்டாபேஸில் இருந்து பதிவை நீக்குவோம்
    mysqli_query($conn, "DELETE FROM documents WHERE student_id = '$id'");

    // ---------------------------------------------------------
    // 3. வருகைப் பதிவை (Attendance) நீக்குதல்
    // ---------------------------------------------------------
    mysqli_query($conn, "DELETE FROM attendance WHERE student_id = '$id'");

    // ---------------------------------------------------------
    // 4. கடைசியாக மாணவரை (Student) நீக்குதல்
    // ---------------------------------------------------------
    $sql = "DELETE FROM students WHERE student_id = '$id'";

    if (mysqli_query($conn, $sql)) {
        // எல்லாம் முடிந்ததும் students பக்கத்திற்குச் செல்லவும்
        header("Location: students.php?msg=deleted");
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
} else {
    header("Location: students.php");
}
