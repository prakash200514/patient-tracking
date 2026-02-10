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

$ai = new AIEngine();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message'])) {
    $user_message = sanitizeInput($_POST['message']);
    
    // Get AI Response
    $response = $ai->getInformation($user_message);

    // Save Chat
    $query = "INSERT INTO ai_chat_logs (patient_id, user_message, ai_response) VALUES (:pid, :msg, :resp)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':pid' => $patient_id,
        ':msg' => $user_message,
        ':resp' => $response
    ]);
}

// Fetch Chat History
$query = "SELECT * FROM ai_chat_logs WHERE patient_id = :pid ORDER BY created_at ASC";
$stmt = $conn->prepare($query);
$stmt->execute([':pid' => $patient_id]);
$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Health Assistant - HealthAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .chat-container {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 0.5rem;
            background: #f9fafb;
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
        }
        .message {
            max-width: 70%;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .user-message {
            background: var(--primary-color);
            color: white;
            align-self: flex-end;
        }
        .ai-message {
            background: #e5e7eb;
            color: var(--text-color);
            align-self: flex-start;
        }
    </style>
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
        <h1>AI Health Assistant</h1>
        <p>Ask basic health questions (e.g., "I have a fever", "Healthy diet tips").</p>

        <div class="card">
            <div class="chat-container" id="chat-box">
                <?php foreach($chats as $chat): ?>
                    <div class="message user-message"><?php echo htmlspecialchars($chat['user_message']); ?></div>
                    <div class="message ai-message"><?php echo htmlspecialchars($chat['ai_response']); ?></div>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="" style="display: flex; gap: 10px;">
                <input type="text" name="message" class="form-control" placeholder="Type your health question..." required autocomplete="off">
                <button type="submit" class="btn-primary" style="width: auto;">Send</button>
            </form>
        </div>
    </div>
    <script>
        // Auto-scroll to bottom
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>
</body>
</html>
