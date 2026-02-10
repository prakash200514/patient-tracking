<?php
session_start();
require_once '../classes/Auth.php';
require_once '../includes/functions.php';

checkRole(['patient']);

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get Patient ID
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
$patient_id = $patient['id'];

// Fetch Vitals History
$vitals_query = "SELECT * FROM health_metrics WHERE patient_id = :pid ORDER BY recorded_at DESC";
$stmt = $conn->prepare($vitals_query);
$stmt->execute([':pid' => $patient_id]);
$vitals_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Visits/Diagnosis History
$visits_query = "SELECT v.*, a.appointment_date, d.specialization, users.full_name as doctor_name 
                 FROM visits v 
                 JOIN appointments a ON v.appointment_id = a.id 
                 JOIN doctors d ON a.doctor_id = d.id 
                 JOIN users ON d.user_id = users.id 
                 WHERE a.patient_id = :pid 
                 ORDER BY v.visit_date DESC";
$stmt = $conn->prepare($visits_query);
$stmt->execute([':pid' => $patient_id]);
$visits_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical History - HealthAI</title>
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
        <h1>Medical History</h1>

        <div class="card">
            <h2>Vitals History</h2>
            <?php if (count($vitals_history) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>BP (mmHg)</th>
                            <th>Sugar (mg/dL)</th>
                            <th>Heart Rate</th>
                            <th>Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vitals_history as $vital): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($vital['recorded_at'])); ?></td>
                                <td><?php echo $vital['systolic_bp'] . '/' . $vital['diastolic_bp']; ?></td>
                                <td><?php echo $vital['blood_sugar']; ?></td>
                                <td><?php echo $vital['heart_rate']; ?></td>
                                <td><?php echo $vital['weight']; ?> kg</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No vitals recorded yet.</p>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top: 2rem;">
            <h2>Past Visits & Diagnoses</h2>
            <?php if (count($visits_history) > 0): ?>
                <?php foreach ($visits_history as $visit): ?>
                    <div style="border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                        <h3><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?> - Dr. <?php echo htmlspecialchars($visit['doctor_name']); ?> (<?php echo $visit['specialization']; ?>)</h3>
                        <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($visit['diagnosis']); ?></p>
                        <p><strong>Prescription:</strong> <?php echo nl2br(htmlspecialchars($visit['prescription'])); ?></p>
                        <?php if($visit['doctor_notes']): ?>
                            <p><strong>Notes:</strong> <?php echo htmlspecialchars($visit['doctor_notes']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No past visits recorded.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
