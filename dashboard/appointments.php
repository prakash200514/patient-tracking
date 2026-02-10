<?php
session_start();
require_once '../classes/Auth.php';
require_once '../includes/functions.php';

// Allow both patient and doctor
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['patient', 'doctor'])) {
    redirect('../login.php');
}

$db = new Database();
$conn = $db->getConnection();
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// -------------------------------------------------------------
// PATIENT LOGIC
// -------------------------------------------------------------
if ($role == 'patient') {
    // Get Patient ID
    $stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    $patient_id = $patient['id'];

    // Handle Booking
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_appointment'])) {
        $doctor_id = $_POST['doctor_id'];
        $date = $_POST['date'];
        $notes = sanitizeInput($_POST['notes']);

        // Basic validation
        if (empty($doctor_id) || empty($date)) {
            $error = "Doctor and Date are required.";
        } else {
            $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, notes) 
                      VALUES (:pid, :did, :date, 'Scheduled', :notes)";
            $stmt = $conn->prepare($query);
            if ($stmt->execute([':pid' => $patient_id, ':did' => $doctor_id, ':date' => $date, ':notes' => $notes])) {
                $success = "Appointment booked successfully!";
            } else {
                $error = "Failed to book appointment.";
            }
        }
    }

    // Fetch My Appointments
    $query = "SELECT a.*, u.full_name as doctor_name, d.specialization 
              FROM appointments a 
              JOIN doctors d ON a.doctor_id = d.id 
              JOIN users u ON d.user_id = u.id 
              WHERE a.patient_id = :pid 
              ORDER BY a.appointment_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':pid' => $patient_id]);
    $my_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Available Doctors for Dropdown
    $docs_query = "SELECT d.id, u.full_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id";
    $doctors = $conn->query($docs_query)->fetchAll(PDO::FETCH_ASSOC);
}

// -------------------------------------------------------------
// DOCTOR LOGIC
// -------------------------------------------------------------
if ($role == 'doctor') {
    // Get Doctor ID
    $stmt = $conn->prepare("SELECT id FROM doctors WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    $doctor_id = $doctor['id'];

    // Fetch My Appointments
    $query = "SELECT a.*, u.full_name as patient_name, p.gender, p.contact_number 
              FROM appointments a 
              JOIN patients p ON a.patient_id = p.id 
              JOIN users u ON p.user_id = u.id 
              WHERE a.doctor_id = :did 
              ORDER BY a.appointment_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':did' => $doctor_id]);
    $my_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$back_link = ($role == 'patient') ? 'patient.php' : 'doctor.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="<?php echo $back_link; ?>" class="logo">HealthAI</a>
            <div class="nav-links">
                <a href="<?php echo $back_link; ?>">Back to Dashboard</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1><?php echo ($role == 'patient') ? 'Book Appointment' : 'My Appointments'; ?></h1>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($role == 'patient'): ?>
            <div class="card" style="margin-bottom: 2rem;">
                <h2>Schedule New Appointment</h2>
                <form method="POST" action="">
                    <input type="hidden" name="book_appointment" value="1">
                    <div class="form-group">
                        <label>Select Doctor</label>
                        <select name="doctor_id" class="form-control" required>
                            <option value="">-- Choose Specialist --</option>
                            <?php foreach($doctors as $d): ?>
                                <option value="<?php echo $d['id']; ?>">Dr. <?php echo htmlspecialchars($d['full_name']); ?> (<?php echo htmlspecialchars($d['specialization']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date & Time</label>
                        <input type="datetime-local" name="date" class="form-control" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Reason / Notes</label>
                        <textarea name="notes" class="form-control" placeholder="Briefly describe your symptoms..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary">Book Now</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><?php echo ($role == 'patient') ? 'My Scheduled Visits' : 'Upcoming Schedule'; ?></h2>
            
            <?php if (count($my_appointments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th><?php echo ($role == 'patient') ? 'Doctor' : 'Patient'; ?></th>
                            <th>Status</th>
                            <th>Notes</th>
                            <?php if ($role == 'doctor'): ?>
                                <th>Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_appointments as $appt): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($appt['appointment_date'])); ?></td>
                                <td>
                                    <?php 
                                    if ($role == 'patient') {
                                        echo "Dr. " . htmlspecialchars($appt['doctor_name']) . " <br><small>" . htmlspecialchars($appt['specialization']) . "</small>";
                                    } else {
                                        echo htmlspecialchars($appt['patient_name']) . " <br><small>" . htmlspecialchars($appt['contact_number']) . "</small>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $status_color = ($appt['status'] == 'Scheduled') ? 'var(--primary-color)' : (($appt['status'] == 'Completed') ? 'var(--success)' : 'var(--text-color)');
                                    echo "<span style='color: $status_color; font-weight: bold;'>" . $appt['status'] . "</span>";
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($appt['notes']); ?></td>
                                <?php if ($role == 'doctor' && $appt['status'] == 'Scheduled'): ?>
                                    <td><a href="diagnosis.php?appt_id=<?php echo $appt['id']; ?>" class="btn">Start Consult</a></td>
                                <?php elseif ($role == 'doctor'): ?>
                                    <td>-</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No appointments found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
