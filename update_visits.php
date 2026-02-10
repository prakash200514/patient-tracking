<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = file_get_contents('update_visits.sql');
    $conn->exec($sql);
    echo "Database updated successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
