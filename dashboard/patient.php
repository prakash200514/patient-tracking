<?php
session_start();
require_once '../classes/Auth.php';
require_once '../classes/AIEngine.php';
require_once '../includes/functions.php';

checkRole(['patient']);

$auth = new Auth();
$ai = new AIEngine();
$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];

// Get Patient ID
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
$patient_id = $patient['id'];

// Handle Symptom Logging
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['log_symptoms'])) {
    $symptoms = sanitizeInput($_POST['symptoms']);
    // In a real app, store this. For now, we use it to trigger AI analysis.
    
    // Simulate updating vitals for demo purposes if provided
    if(isset($_POST['systolic'])) {
        $sys = $_POST['systolic'];
        $dia = $_POST['diastolic'];
        $bg = $_POST['sugar'];
        
        $stmt_vitals = $conn->prepare("INSERT INTO health_metrics (patient_id, systolic_bp, diastolic_bp, blood_sugar) VALUES (?, ?, ?, ?)");
        $stmt_vitals->execute([$patient_id, $sys, $dia, $bg]);
    }

    $analysis = $ai->runAnalysis($patient_id);
    $message = "Analysis Complete: " . $analysis['insight'];
}

// Get Latest AI Analysis
$stmt_ai = $conn->prepare("SELECT * FROM ai_analysis_logs WHERE patient_id = ? ORDER BY analyzed_at DESC LIMIT 1");
$stmt_ai->execute([$patient_id]);
$latest_analysis = $stmt_ai->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/charts.js"></script>
</head>
<body>
    <header>
        <nav>
            <a href="#" class="logo">HealthAI</a>
            <div class="nav-links">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?></span>
                <a href="../logout.php" class="btn" style="background:var(--danger)">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container dashboard-grid">
        <aside class="sidebar">
            <ul>
                <li><a href="#" class="active">Overview</a></li>
                <li><a href="#">My Prescriptions</a></li>
                <li><a href="#">Appointments</a></li>
                <li><a href="#">Health History</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>My Health Overview</h1>
            
            <?php if(isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: var(--primary-color)">
                    <h3>Current Health Score</h3>
                    <div class="value"><?php echo $latest_analysis['health_score'] ?? 'N/A'; ?> / 100</div>
                    <small>AI Calculated</small>
                </div>
                <div class="stat-card" style="border-left-color: <?php echo ($latest_analysis['risk_level']??'Low') == 'High' ? 'var(--danger)' : (($latest_analysis['risk_level']??'Low') == 'Medium' ? 'var(--warning)' : 'var(--success)'); ?>">
                    <h3>Risk Level</h3>
                    <div class="value"><?php echo $latest_analysis['risk_level'] ?? 'Unknown'; ?></div>
                    <small>Based on recent vitals</small>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="card">
                    <h2>Log Daily Vitals & Symptoms</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Blood Pressure (Systolic/Diastolic)</label>
                            <div style="display:flex; gap:10px;">
                                <input type="number" name="systolic" class="form-control" placeholder="120" required>
                                <input type="number" name="diastolic" class="form-control" placeholder="80" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Blood Sugar (mg/dL)</label>
                            <input type="number" name="sugar" class="form-control" placeholder="90" required>
                        </div>
                        <div class="form-group">
                            <label>Symptoms / Notes</label>
                            <textarea name="symptoms" class="form-control" rows="3"></textarea>
                        </div>
                        <input type="hidden" name="log_symptoms" value="1">
                        <button type="submit" class="btn-primary">Update Health Record</button>
                    </form>
                </div>
                
                <div class="card">
                    <h2>AI Health Assistant</h2>
                    <div style="background:#f0f9ff; padding:1rem; border-radius:0.5rem; margin-top:10px;">
                        <strong>Latest Insight:</strong>
                        <p><?php echo $latest_analysis['generated_insight'] ?? 'No data available yet. Please log your vitals.'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Health Trend Chart -->
             <div class="card" style="margin-top: 20px;">
                <h2>Health Score Trend</h2>
                <canvas id="healthChart"></canvas>
            </div>

            <?php
            // Fetch history for chart
            $stmt_hist = $conn->prepare("SELECT health_score, DATE_FORMAT(analyzed_at, '%Y-%m-%d %H:%i') as date FROM ai_analysis_logs WHERE patient_id = ? ORDER BY analyzed_at ASC LIMIT 10");
            $stmt_hist->execute([$patient_id]);
            $history = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
            
            $dates = array_column($history, 'date');
            $scores = array_column($history, 'health_score');
            ?>
            <script>
                const ctx = document.getElementById('healthChart').getContext('2d');
                renderHealthChart(ctx, <?php echo json_encode($dates); ?>, <?php echo json_encode($scores); ?>);
            </script>
            
        </main>
    </div>
</body>
</html>
