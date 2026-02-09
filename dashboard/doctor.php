<?php
session_start();
require_once '../classes/Auth.php';
require_once '../classes/AIEngine.php';
require_once '../includes/functions.php';

checkRole(['doctor']);

$db = new Database();
$conn = $db->getConnection();

// Fetch assigned patients (For demo, fetch all patients)
$query = "SELECT p.id, u.full_name, p.gender, 
          (SELECT risk_level FROM ai_analysis_logs WHERE patient_id = p.id ORDER BY analyzed_at DESC LIMIT 1) as risk_level,
          (SELECT health_score FROM ai_analysis_logs WHERE patient_id = p.id ORDER BY analyzed_at DESC LIMIT 1) as score
          FROM patients p 
          JOIN users u ON p.user_id = u.id";
$stmt = $conn->prepare($query);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <span>Dr. <?php echo $_SESSION['full_name']; ?></span>
                <a href="../logout.php" class="btn" style="background:var(--danger)">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container dashboard-grid">
        <aside class="sidebar">
            <ul>
                <li><a href="#" class="active">Patient List</a></li>
                <li><a href="#">Appointments</a></li>
                <li><a href="#">Reports</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>Patient Overview</h1>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Health Score</th>
                        <th>Risk Level</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($patients as $p): ?>
                    <tr>
                        <td>#<?php echo $p['id']; ?></td>
                        <td><?php echo $p['full_name']; ?></td>
                        <td><?php echo $p['gender']; ?></td>
                        <td>
                            <?php if($p['score']): ?>
                                <span style="font-weight:bold; color: <?php echo $p['score'] > 75 ? 'green' : 'red'; ?>">
                                    <?php echo $p['score']; ?>
                                </span>
                            <?php else: ?>
                                <span style="color:grey">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $risk = $p['risk_level'] ?? 'Low';
                                $color = $risk == 'High' ? 'red' : ($risk == 'Medium' ? 'orange' : 'green');
                                echo "<span style='color:$color; font-weight:bold'>$risk</span>";
                            ?>
                        </td>
                        <td>
                            <a href="#" class="btn" style="padding:5px 10px; font-size:0.8rem;">View Details</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
