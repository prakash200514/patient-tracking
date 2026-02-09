<?php
session_start();
require_once 'classes/Auth.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $auth = new Auth();
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($auth->login($email, $password)) {
        // Redirect based on role
        switch ($_SESSION['role']) {
            case 'patient':
                header("Location: dashboard/patient.php");
                break;
            case 'doctor':
                header("Location: dashboard/doctor.php");
                break;
            case 'admin':
                header("Location: dashboard/admin.php");
                break;
            default:
                header("Location: login.php");
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HealthAI</title>
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
        <h2>Welcome Back</h2>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-primary">Login</button>
        </form>
        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>
