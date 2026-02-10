<?php
session_start();
require_once '../classes/Auth.php';
require_once '../includes/functions.php';

checkRole(['admin']);

$db = new Database();
$conn = $db->getConnection();

// Handle Delete
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    redirect('admin_users.php');
}

// Fetch Users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$users = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="admin.php" class="logo">HealthAI - Admin</a>
            <div class="nav-links">
                <a href="admin.php">Back to Dashboard</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>System Users</h1>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo ucfirst($u['role']); ?></td>
                        <td>
                            <?php if($u['role'] !== 'admin'): ?>
                                <a href="?delete=<?php echo $u['id']; ?>" class="btn" style="background:var(--danger); padding:0.25rem 0.5rem; font-size:0.8rem;" onclick="return confirm('Are you sure?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
