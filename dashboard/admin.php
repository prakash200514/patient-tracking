<?php
session_start();
require_once '../classes/Auth.php';
require_once '../includes/functions.php';

checkRole(['admin']);

$db = new Database();
$conn = $db->getConnection();

// System Stats
$stats = [];
$stats['users'] = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['patients'] = $conn->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$stats['doctors'] = $conn->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$stats['high_risk'] = $conn->query("SELECT COUNT(DISTINCT patient_id) FROM ai_analysis_logs WHERE risk_level = 'High'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="#" class="logo">HealthAI - Admin</a>
            <div class="nav-links">
                <span>Admin Panel</span>
                <a href="../logout.php" class="btn" style="background:var(--danger)">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container dashboard-grid">
        <aside class="sidebar">
            <ul>
                <li><a href="#" class="active">Dashboard</a></li>
                <li><a href="#">Manage Users</a></li>
                <li><a href="#">Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1>System Overview</h1>
            
            <div class="stats-grid">
                <div class="stat-card" style="border-left-color: var(--primary-color)">
                    <h3>Total Users</h3>
                    <div class="value"><?php echo $stats['users']; ?></div>
                </div>
                <div class="stat-card" style="border-left-color: var(--success)">
                    <h3>Active Doctors</h3>
                    <div class="value"><?php echo $stats['doctors']; ?></div>
                </div>
                <div class="stat-card" style="border-left-color: var(--warning)">
                    <h3>Registered Patients</h3>
                    <div class="value"><?php echo $stats['patients']; ?></div>
                </div>
                <div class="stat-card" style="border-left-color: var(--danger)">
                    <h3>High Risk Cases</h3>
                    <div class="value"><?php echo $stats['high_risk']; ?></div>
                </div>
            </div>

            <div class="card">
                <h2>Recent Activity Log</h2>
                <p>System logs would appear here...</p>
            </div>
        </main>
    </div>
</body>
</html>
