<?php
session_start();
require_once '../classes/Auth.php';
require_once '../classes/AIEngine.php';
require_once '../includes/functions.php';

checkRole(['patient']);

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Fetch Patient ID
$stmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = :uid");
$stmt->execute([':uid' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
$patient_id = $patient['id'];

// AI Analysis
$ai = new AIEngine();
$health_score = $ai->calculateHealthScore($patient_id);
$risk_level = $ai->predictRiskLevel($patient_id);
$insight = $ai->generateInsight($patient_id);

// Fetch Vitals for Chart
$stmt = $conn->prepare("SELECT recorded_at, systolic_bp, diastolic_bp, blood_sugar 
                        FROM health_metrics WHERE patient_id = :pid ORDER BY recorded_at ASC LIMIT 10");
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

// Fetch Active Medications (Simulated from latest visits)
$meds_query = "SELECT prescription FROM visits v 
               JOIN appointments a ON v.appointment_id = a.id 
               WHERE a.patient_id = :pid 
               ORDER BY v.visit_date DESC LIMIT 1";
$stmt = $conn->prepare($meds_query);
$stmt->execute([':pid' => $patient_id]);
$latest_rx = $stmt->fetch(PDO::FETCH_ASSOC);
$medications = $latest_rx ? explode("\n", $latest_rx['prescription']) : [];

// Fetch Latest Vitals
$stmt = $conn->prepare("SELECT * FROM health_metrics WHERE patient_id = :pid ORDER BY recorded_at DESC LIMIT 1");
$stmt->execute([':pid' => $patient_id]);
$latest_vitals = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <nav>
            <a href="#" class="logo">HealthAI</a>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></span>
                <a href="../logout.php" class="btn" style="background:var(--danger)">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container dashboard-grid">
        <aside class="sidebar">
            <ul>
                <li><a href="patient.php" class="active">Dashboard</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="add_vitals.php">Add Vitals</a></li>
                <li><a href="history.php">Medical History</a></li>
                <li><a href="upload_reports.php">My Reports</a></li>
                <li><a href="ai_assistant.php">AI Assistant</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>My Health Overview</h1>
            
            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: var(--primary-color)">
                    <h3>Health Score</h3>
                    <div class="value"><?php echo $health_score; ?>/100</div>
                </div>
                <div class="stat-card" style="border-left-color: <?php echo ($risk_level == 'High') ? 'var(--danger)' : (($risk_level == 'Medium') ? 'var(--warning)' : 'var(--success)'); ?>">
                    <h3>Risk Level</h3>
                    <div class="value risk-<?php echo strtolower($risk_level); ?>"><?php echo $risk_level; ?></div>
                </div>
            </div>

            <div class="alert alert-warning">
                <strong>AI Insight:</strong> <?php echo $insight; ?>
            </div>

            <!-- Health Trends Chart -->
            <div class="card" style="margin-bottom: 2rem;">
                <h2>Health Trends (BP & Sugar)</h2>
                <canvas id="healthChart" height="100"></canvas>
            </div>

            <div class="grid-2-col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="card">
                    <h2>Medicine Reminders</h2>
                    <?php if(count($medications) > 0 && !empty($medications[0])): ?>
                        <ul style="padding-left: 20px;">
                            <?php foreach($medications as $med): ?>
                                <?php if(trim($med)): ?>
                                    <li style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($med); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <p><small>Based on your latest prescription.</small></p>
                    <?php else: ?>
                        <p>No active medications found.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>Latest Vitals</h2>
                    <?php if($latest_vitals): ?>
                        <table>
                            <tr>
                                <td>BP</td>
                                <td><?php echo $latest_vitals['systolic_bp'] . '/' . $latest_vitals['diastolic_bp']; ?></td>
                            </tr>
                            <tr>
                                <td>Sugar</td>
                                <td><?php echo $latest_vitals['blood_sugar']; ?></td>
                            </tr>
                            <tr>
                                <td>Pulse</td>
                                <td><?php echo $latest_vitals['heart_rate']; ?></td>
                            </tr>
                        </table>
                        <a href="add_vitals.php" class="btn" style="margin-top: 10px; display: inline-block; font-size: 0.9rem;">Update Vitals</a>
                    <?php else: ?>
                        <p>No vitals recorded.</p>
                        <a href="add_vitals.php" class="btn">Add Vitals</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Chart Config -->
    <script>
        const ctx = document.getElementById('healthChart').getContext('2d');
        const healthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: 'Systolic BP',
                        data: <?php echo json_encode($sys_bp); ?>,
                        borderColor: '#ef4444',
                        tension: 0.1
                    },
                    {
                        label: 'Diastolic BP',
                        data: <?php echo json_encode($dia_bp); ?>,
                        borderColor: '#f59e0b',
                        tension: 0.1
                    },
                    {
                        label: 'Blood Sugar',
                        data: <?php echo json_encode($sugar); ?>,
                        borderColor: '#3b82f6',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
</body>
</html>
