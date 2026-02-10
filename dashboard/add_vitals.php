<?php
session_start();
require_once '../classes/Auth.php';
require_once '../classes/AIEngine.php';
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

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $systolic = sanitizeInput($_POST['systolic']);
    $diastolic = sanitizeInput($_POST['diastolic']);
    $blood_sugar = sanitizeInput($_POST['blood_sugar']);
    $heart_rate = sanitizeInput($_POST['heart_rate']);
    $temp = sanitizeInput($_POST['temp']);
    $weight = sanitizeInput($_POST['weight']);

    if(empty($systolic) || empty($diastolic)) {
        $error = "Blood Pressure is required.";
    } else {
        $query = "INSERT INTO health_metrics (patient_id, systolic_bp, diastolic_bp, blood_sugar, heart_rate, temperature, weight) 
                  VALUES (:pid, :sys, :dia, :bs, :hr, :temp, :wt)";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':pid' => $patient_id,
            ':sys' => $systolic,
            ':dia' => $diastolic,
            ':bs' => $blood_sugar,
            ':hr' => $heart_rate,
            ':temp' => $temp,
            ':wt' => $weight
        ]);

        if($result) {
            // Trigger AI Analysis
            $ai = new AIEngine();
            $analysis = $ai->runAnalysis($patient_id);
            $success = "Vitals recorded! New Health Score: " . $analysis['score'];
        } else {
            $error = "Failed to record vitals.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Vitals - HealthAI</title>
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
        <h1>Record Health Metrics</h1>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="form-group">
                <label>Blood Pressure (Systolic / Diastolic)</label>
                <div style="display: flex; gap: 10px;">
                    <input type="number" name="systolic" placeholder="120" class="form-control" required>
                    <input type="number" name="diastolic" placeholder="80" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Blood Sugar (mg/dL)</label>
                <input type="number" name="blood_sugar" placeholder="100" class="form-control">
            </div>

            <div class="form-group">
                <label>Heart Rate (bpm)</label>
                <input type="number" name="heart_rate" placeholder="72" class="form-control">
            </div>

            <div class="form-group">
                <label>Temperature (°F)</label>
                <input type="number" step="0.1" name="temp" placeholder="98.6" class="form-control">
            </div>

            <div class="form-group">
                <label>Weight (kg)</label>
                <input type="number" step="0.1" name="weight" placeholder="70" class="form-control">
            </div>

            <button type="submit" class="btn-primary">Save & Analyze</button>
        </form>
    </div>
</body>
</html>
