<?php
session_start();
require_once '../classes/Auth.php';
require_once '../includes/functions.php';

checkRole(['doctor']);

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get Doctor ID
$stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch All Patients linked via Appointments or just all patients for demo
// Using a subquery to get latest risk level
$query = "SELECT p.id, u.full_name, p.gender, p.contact_number, p.address, u.created_at, 
          (SELECT risk_level FROM ai_analysis_logs WHERE patient_id = p.id ORDER BY analyzed_at DESC LIMIT 1) as risk_level 
          FROM patients p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY u.created_at DESC";
$patients = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Patients - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="doctor.php" class="logo">HealthAI</a>
            <div class="nav-links">
                <a href="doctor.php">Back to Dashboard</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Patient Records</h1>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Registered On</th>
                        <th>Gender</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Current Risk</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($patients as $patient): ?>
                    <tr>
                        <td>#<?php echo $patient['id']; ?></td>
                        <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($patient['created_at'])); ?></td>
                        <td><?php echo $patient['gender']; ?></td>
                        <td><?php echo htmlspecialchars($patient['address']); ?></td>
                        <td><?php echo $patient['contact_number']; ?></td>
                        <td>
                            <?php 
                            $risk = $patient['risk_level'] ?? 'Low';
                            $class = 'risk-' . strtolower($risk);
                            echo "<span class='$class'>$risk</span>";
                            ?>
                        </td>
                        <td><a href="patient_details.php?id=<?php echo $patient['id']; ?>" class="btn">View History</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
