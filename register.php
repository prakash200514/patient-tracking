<?php
session_start();
require_once 'classes/Auth.php';
require_once 'includes/functions.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $auth = new Auth();
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $user_id = $auth->register($full_name, $email, $password, $role);
        if ($user_id) {
            // Also create the specific profile entry
            $db = new Database();
            $conn = $db->getConnection();
            
            if($role == 'patient') {
                $stmt = $conn->prepare("INSERT INTO patients (user_id) VALUES (:user_id)");
                $stmt->execute([':user_id' => $user_id]);
            } elseif($role == 'doctor') {
                $stmt = $conn->prepare("INSERT INTO doctors (user_id) VALUES (:user_id)");
                $stmt->execute([':user_id' => $user_id]);
            }

            $success = "Registration successful! You can now login.";
        } else {
            $error = "Registration failed. Email might already be taken.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HealthAI</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">HealthAI</a>
        </nav>
    </header>

    <div class="auth-container">
        <h2>Create Account</h2>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="auth-footer"><a href="login.php">Proceed to Login</a></div>
        <?php else: ?>
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="role">I am a:</label>
                    <select id="role" name="role" class="form-control">
                        <option value="patient">Patient</option>
                        <option value="doctor">Doctor</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Register</button>
            </form>
            <div class="auth-footer">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
