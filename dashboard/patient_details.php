<?php
session_start();
require_once '../classes/Auth.php';
require_once '../includes/functions.php';

checkRole(['doctor']);

$db = new Database();
$conn = $db->getConnection();

if(!isset($_GET['id'])) redirect('patients_list.php');
$patient_id = $_GET['id'];

// Fetch Patient Info
$query = "SELECT p.*, u.full_name, u.email FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = :pid";
$stmt = $conn->prepare($query);
$stmt->execute([':pid' => $patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch AI Stats
$query = "SELECT * FROM ai_analysis_logs WHERE patient_id = :pid ORDER BY analyzed_at DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute([':pid' => $patient_id]);
$latest_analysis = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Vitals History
$vitals = $conn->prepare("SELECT * FROM health_metrics WHERE patient_id = :pid ORDER BY recorded_at DESC LIMIT 10");
$vitals->execute([':pid' => $patient_id]);
$vitals_list = $vitals->fetchAll(PDO::FETCH_ASSOC);

// Fetch Past Visits
$visits_stmt = $conn->prepare("SELECT v.*, a.appointment_date, u.full_name as doctor_name 
                          FROM visits v 
                          JOIN appointments a ON v.appointment_id = a.id 
                          JOIN doctors d ON a.doctor_id = d.id 
                          JOIN users u ON d.user_id = u.id 
                          WHERE a.patient_id = :pid 
                          ORDER BY v.visit_date DESC");
$visits_stmt->execute([':pid' => $patient_id]);
$visits = $visits_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Vitals for Chart
$stmt = $conn->prepare("SELECT recorded_at, systolic_bp, diastolic_bp, blood_sugar 
                        FROM health_metrics WHERE patient_id = :pid ORDER BY recorded_at ASC LIMIT 20");
$stmt->execute([':pid' => $patient_id]);
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
$sys_bp = [];
$dia_bp = [];
$sugar = [];

foreach ($chart_data as $d) {
    $dates[] = date('m/d', strtotime($d['recorded_at']));
    $sys_bp[] = $d['systolic_bp'];
    $dia_bp[] = $d['diastolic_bp'];
    $sugar[] = $d['blood_sugar'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Details - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <nav>
            <a href="doctor.php" class="logo">HealthAI</a>
            <div class="nav-links">
                <a href="patients_list.php">Back to List</a>
                <a href="doctor.php">Dashboard</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>Patient Profile: <?php echo htmlspecialchars($patient['full_name']); ?></h1>
            <div class="risk-badge" style="background: <?php echo (($latest_analysis['risk_level'] ?? '') == 'High') ? 'var(--danger)' : 'var(--success)'; ?>; color: white; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold;">
                Risk Level: <?php echo $latest_analysis['risk_level'] ?? 'Unknown'; ?>
            </div>
        </div>

        <div class="grid-2-col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 2rem;">
            <!-- Personal Info -->
            <div class="card">
                <h2>Personal Details</h2>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($patient['contact_number']); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender']); ?></p>
                <p><strong>DOB:</strong> <?php echo htmlspecialchars($patient['date_of_birth']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['address']); ?></p>
            </div>

            <!-- Medical Info -->
            <div class="card">
                <h2>Medical Context</h2>
                <p><strong>History:</strong> <?php echo nl2br(htmlspecialchars($patient['medical_history'])); ?></p>
                <hr>
                <p><strong>Allow AI Analysis:</strong> Health Score: <?php echo $latest_analysis['health_score'] ?? 'N/A'; ?>/100</p>
                <p><strong>Latest Insight:</strong> <?php echo $latest_analysis['generated_insight'] ?? 'No analysis yet.'; ?></p>
            </div>
        </div>

        <!-- Charts -->
        <div class="card" style="margin-bottom: 2rem;">
            <h2>Recovery Trend (Vitals over time)</h2>
            <canvas id="patientChart" height="80"></canvas>
        </div>

        <!-- Visit History -->
        <div class="card">
            <h2>Visit History</h2>
            <?php if (count($visits) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Diagnosis</th>
                            <th>Prescription</th>
                            <th>Follow-up</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($visits as $visit): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($visit['visit_date'])); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($visit['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($visit['diagnosis']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($visit['prescription'])); ?></td>
                                <td><?php echo !empty($visit['follow_up_date']) ? date('M d, Y', strtotime($visit['follow_up_date'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No previous visits.</p>
            <?php endif; ?>
        </div>
</body>
</html>
