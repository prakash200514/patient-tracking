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

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["report_file"])) {
    $report_type = $_POST['report_type'];
    
    // File Upload Logic
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = basename($_FILES["report_file"]["name"]);
    $target_file = $target_dir . time() . "_" . $file_name;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "pdf" ) {
        $error = "Sorry, only JPG, JPEG, PNG & PDF files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
            // Save to DB
            $query = "INSERT INTO patient_reports (patient_id, report_type, file_name, file_path) VALUES (:pid, :type, :name, :path)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':pid' => $patient_id,
                ':type' => $report_type,
                ':name' => $file_name,
                ':path' => $target_file
            ]);
            $success = "The file ". htmlspecialchars($file_name). " has been uploaded.";
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch Reports
$stmt = $conn->prepare("SELECT * FROM patient_reports WHERE patient_id = :pid ORDER BY uploaded_at DESC");
$stmt->execute([':pid' => $patient_id]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Reports - HealthAI</title>
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
        <h1>Medical Reports</h1>

        <div class="card" style="margin-bottom: 2rem;">
            <h2>Upload New Report</h2>
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Report Type</label>
                    <select name="report_type" class="form-control">
                        <option value="Lab Report">Lab Report</option>
                        <option value="Scan/X-Ray">Scan/X-Ray</option>
                        <option value="Prescription">Prescription</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select File (PDF, JPG, PNG)</label>
                    <input type="file" name="report_file" class="form-control" required>
                </div>

                <button type="submit" class="btn-primary">Upload</button>
            </form>
        </div>

        <div class="card">
            <h2>My Documents</h2>
            <?php if(count($reports) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>File Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reports as $r): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($r['uploaded_at'])); ?></td>
                            <td><?php echo htmlspecialchars($r['report_type']); ?></td>
                            <td><?php echo htmlspecialchars($r['file_name']); ?></td>
                            <td><a href="<?php echo htmlspecialchars($r['file_path']); ?>" target="_blank" class="btn">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No reports uploaded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
