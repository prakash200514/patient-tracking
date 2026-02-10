<?php
session_start();
require_once '../classes/Auth.php';
require_once '../includes/functions.php';

checkRole(['doctor']);

$db = new Database();
$conn = $db->getConnection();

if(!isset($_GET['appt_id'])) redirect('doctor.php');
$appt_id = $_GET['appt_id'];

// Fetch Appointment Details
$query = "SELECT a.*, p.id as patient_id, u.full_name, p.gender, p.date_of_birth 
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          JOIN users u ON p.user_id = u.id 
          WHERE a.id = :aid";
$stmt = $conn->prepare($query);
$stmt->execute([':aid' => $appt_id]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$appt) die("Appointment not found.");

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $diagnosis = sanitizeInput($_POST['diagnosis']);
    $prescription = sanitizeInput($_POST['prescription']);
    $doctor_notes = sanitizeInput($_POST['doctor_notes']); // Changed from 'notes' to 'doctor_notes'

    if(empty($diagnosis) || empty($prescription)) {
        $error = "Detailed diagnosis and prescription are required.";
    } else {
        // Begin Transaction
        $conn->beginTransaction();

        try {
            // 1. Update Appointment Status
            $updateAppt = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE id = :id");
            $updateAppt->execute([':id' => $appt_id]);

            // 2. Insert into Visits
            $follow_up = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
            
            $insertVisit = $conn->prepare("INSERT INTO visits (appointment_id, diagnosis, prescription, doctor_notes, follow_up_date) VALUES (:aid, :diag, :rx, :notes, :fud)");
            $insertVisit->execute([
                ':aid' => $appt_id,
                ':diag' => $diagnosis,
                ':rx' => $prescription,
                ':notes' => $doctor_notes, // Using doctor_notes
                ':fud' => $follow_up
            ]);
            $conn->commit();
            
            // Redirect to patient details or list
            header("Location: patients_list.php");
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Failed to save diagnosis: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Diagnosis - HealthAI</title>
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
        <h1>Diagnosis & Treatment</h1>
        <div class="card">
            <h2>Patient: <?php echo htmlspecialchars($appt['patient_name']); ?></h2>
            <p><strong>Appointment Date:</strong> <?php echo date('M d, Y h:i A', strtotime($appt['appointment_date'])); ?></p>
            <p><strong>Reason/Notes:</strong> <?php echo htmlspecialchars($appt['notes']); ?></p>

            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Diagnosis</label>
                    <textarea name="diagnosis" class="form-control" rows="3" required placeholder="Clinical findings..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Prescription</label>
                    <textarea name="prescription" class="form-control" rows="4" required placeholder="Medicine Name - Dosage - Frequency (e.g., Paracetamol - 500mg - Twice daily)"></textarea>
                </div>

                <div class="form-group">
                    <label>Internal Notes (Private)</label>
                    <textarea name="doctor_notes" class="form-control" rows="2" placeholder="Observations not visible to patient..."></textarea>
                </div>

                <div class="form-group">
                    <label>Suggested Follow-up Date</label>
                    <input type="date" name="follow_up_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                    <small>Optional. AI suggests 7 days for High Bio-Markers.</small>
                </div>

                <button type="submit" class="btn-primary">Complete Consultation</button>
            </form>
        </div>
    </div>
</body>
</html>
