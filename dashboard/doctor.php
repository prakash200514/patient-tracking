<?php
session_start();
require_once '../classes/Auth.php';
require_once '../classes/AIEngine.php';
require_once '../includes/functions.php';

checkRole(['doctor']);

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get Doctor ID
$stmt = $conn->prepare("SELECT d.id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.id = :uid");
$stmt->execute([':uid' => $user_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch High Risk Patients (Mocking "assigned" as all for now/demo, or linked via appointments)
// For demonstration, we show all patients with High Risk
$query = "SELECT p.id, u.full_name, a.risk_level, a.health_score 
          FROM patients p 
          JOIN users u ON p.user_id = u.id 
          JOIN ai_analysis_logs a ON p.id = a.patient_id 
          WHERE a.id = (SELECT MAX(id) FROM ai_analysis_logs WHERE patient_id = p.id) 
          AND a.risk_level = 'High'";
$high_risk_patients = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Recent Appointments
$appt_query = "SELECT a.*, u.full_name as patient_name 
               FROM appointments a 
               JOIN patients p ON a.patient_id = p.id 
               JOIN users u ON p.user_id = u.id 
               WHERE a.doctor_id = :did AND a.status = 'Scheduled' 
               ORDER BY a.appointment_date ASC LIMIT 5";
$stmt = $conn->prepare($appt_query);
$stmt->execute([':did' => $doctor['id']]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="#" class="logo">HealthAI - Doctor Portal</a>
            <div class="nav-links">
                <span>Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></span>
                <a href="../logout.php" class="btn" style="background:var(--danger)">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container dashboard-grid">
        <aside class="sidebar">
            <ul>
                <li><a href="#" class="active">Dashboard</a></li>
                <li><a href="patients_list.php">My Patients</a></li>
                <li><a href="appointments.php">Appointments</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Dashboard Overview</h1>
            
            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: var(--danger)">
                    <h3>Critical Patients</h3>
                    <div class="value"><?php echo count($high_risk_patients); ?></div>
                </div>
                <div class="stat-card" style="border-left-color: var(--primary-color)">
                    <h3>Pending Appointments</h3>
                    <div class="value"><?php echo count($appointments); ?></div>
                </div>
            </div>

            <?php if(count($high_risk_patients) > 0): ?>
            <div class="alert alert-danger">
                <strong>Attention Needed:</strong> There are <?php echo count($high_risk_patients); ?> patients with High Risk status!
            </div>
            <?php endif; ?>

            <div class="card">
                <h2>Upcoming Appointments</h2>
                <?php if($appointments): ?>
                    <table>
                        <tr>
                            <th>Patient</th>
                            <th>Date & Time</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach($appointments as $appt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($appt['appointment_date'])); ?></td>
                            <td><a href="diagnosis.php?appt_id=<?php echo $appt['id']; ?>" class="btn">Start Visit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>No upcoming appointments.</p>
                <?php endif; ?>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>High Risk Patients</h2>
                    <a href="patients_list.php" class="btn">View All Patients</a>
                </div>
                
                <?php if(count($high_risk_patients) > 0): ?>
                    <table>
                        <tr>
                            <th>Name</th>
                            <th>Health Score</th>
                            <th>Risk Level</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach($high_risk_patients as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                            <td><?php echo $p['health_score']; ?></td>
                            <td><span class="risk-high">High</span></td>
                            <td><a href="patient_details.php?id=<?php echo $p['id']; ?>" class="btn">View Details</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p>No high risk patients found. <a href="patients_list.php">Browse your full patient list</a> to see charts and details.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
