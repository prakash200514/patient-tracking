<?php
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function checkRole($required_roles) {
    if(session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $required_roles)) {
        redirect('../login.php');
    }
}
?>
