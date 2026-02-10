<?php
session_start();
require_once '../classes/Auth.php';
require_once '../includes/functions.php';

checkRole(['patient']);

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Fetch Patient Details
$query = "SELECT u.full_name, u.email, p.* FROM users u 
          JOIN patients p ON u.id = p.user_id 
          WHERE u.id = :uid";
$stmt = $conn->prepare($query);
$stmt->execute([':uid' => $user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact = sanitizeInput($_POST['contact']);
    $address = sanitizeInput($_POST['address']);
    $history = sanitizeInput($_POST['history']);

    $updateQuery = "UPDATE patients SET 
                    date_of_birth = :dob, 
                    gender = :gender, 
                    contact_number = :contact, 
                    address = :address, 
                    medical_history = :history 
                    WHERE user_id = :uid";
    
    $stmt = $conn->prepare($updateQuery);
    $result = $stmt->execute([
        ':dob' => $dob,
        ':gender' => $gender,
        ':contact' => $contact,
        ':address' => $address,
        ':history' => $history,
        ':uid' => $user_id
    ]);

    if($result) {
        $success = "Profile updated successfully!";
        // Refresh data
        $stmt = $conn->prepare($query);
        $stmt->execute([':uid' => $user_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="patient.php" class="logo">HealthAI</a>
            <div class="nav-links">
                <a href="patient.php">Back to Dashboard</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>My Profile</h1>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="card">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" value="<?php echo htmlspecialchars($profile['full_name']); ?>" disabled class="form-control">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled class="form-control">
            </div>

            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="dob" value="<?php echo $profile['date_of_birth']; ?>" class="form-control">
            </div>

            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="Male" <?php echo ($profile['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($profile['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($profile['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact" value="<?php echo htmlspecialchars($profile['contact_number']); ?>" class="form-control">
            </div>

            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control"><?php echo htmlspecialchars($profile['address']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Medical History (Allergies, Past Surgeries, etc.)</label>
                <textarea name="history" class="form-control" rows="4"><?php echo htmlspecialchars($profile['medical_history']); ?></textarea>
            </div>

            <button type="submit" class="btn-primary">Update Profile</button>
        </form>
    </div>
</body>
</html>
