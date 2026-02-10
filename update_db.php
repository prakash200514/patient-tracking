<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    $sql = file_get_contents('update_db.sql');
    
    // Split into individual queries
    $queries = explode(';', $sql);

    foreach ($queries as $query) {
        if (trim($query)) {
            $conn->exec($query);
        }
    }
    echo "Database updated successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
