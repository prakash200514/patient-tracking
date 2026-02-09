<?php
require_once __DIR__ . '/../config/database.php';

class AIEngine {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // 1. Calculate Health Score (0-100)
    // Based on vitals stability and medication adherence
    public function calculateHealthScore($patient_id) {
        $score = 80; // Base score

        // Fetch latest vitals
        $query = "SELECT * FROM health_metrics WHERE patient_id = :pid ORDER BY recorded_at DESC LIMIT 5";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':pid' => $patient_id]);
        $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($metrics) {
            $latest = $metrics[0];
            
            // Penalty for High BP
            if ($latest['systolic_bp'] > 140 || $latest['diastolic_bp'] > 90) {
                $score -= 10;
            }
            // Penalty for High Sugar
            if ($latest['blood_sugar'] > 140) {
                $score -= 10;
            }
            // Reward for normal range
            if ($latest['systolic_bp'] < 120 && $latest['blood_sugar'] < 100) {
                $score += 5;
            }
        }

        // Adherence Factor (Mock calculation for now)
        $adherence = $this->calculateAdherence($patient_id);
        if ($adherence < 50) $score -= 15;
        elseif ($adherence > 90) $score += 5;

        return max(0, min(100, $score));
    }

    // 2. Predict Risk Level (Low/Medium/High)
    public function predictRiskLevel($patient_id) {
        $score = $this->calculateHealthScore($patient_id);
        
        if ($score < 50) return 'High';
        if ($score < 75) return 'Medium';
        return 'Low';
    }

    // 3. Adherence Analysis
    public function calculateAdherence($patient_id) {
        // Get total prescribed vs taken
        // This is a simplified logic. In real system, would join with visits and prescriptions
        $query = "SELECT COUNT(*) as total, SUM(taken_status) as taken FROM medication_adherence ma 
                  JOIN visits v ON ma.visit_id = v.id 
                  JOIN appointments a ON v.appointment_id = a.id 
                  WHERE a.patient_id = :pid";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':pid' => $patient_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data['total'] == 0) return 100; // No meds prescribed, assume perfect adherence
        return ($data['taken'] / $data['total']) * 100;
    }

    // 4. Generate Insight
    public function generateInsight($patient_id) {
        $risk = $this->predictRiskLevel($patient_id);
        $score = $this->calculateHealthScore($patient_id);

        $insight = "Your health score is $score. ";
        
        if ($risk == 'High') {
            $insight .= "CRITICAL: Immediate medical attention recommended. Vitals are unstable.";
        } elseif ($risk == 'Medium') {
            $insight .= "WARNING: Improve medication adherence and monitor diet.";
        } else {
            $insight .= "Great job! Keep maintaining your current lifestyle.";
        }

        return $insight;
    }

    // 5. Save Analysis
    public function runAnalysis($patient_id) {
        $score = $this->calculateHealthScore($patient_id);
        $risk = $this->predictRiskLevel($patient_id);
        $adherence = $this->calculateAdherence($patient_id);
        $insight = $this->generateInsight($patient_id);

        $query = "INSERT INTO ai_analysis_logs (patient_id, health_score, risk_level, adherence_score, generated_insight) 
                  VALUES (:pid, :score, :risk, :adh, :insight)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':pid' => $patient_id,
            ':score' => $score,
            ':risk' => $risk,
            ':adh' => $adherence,
            ':insight' => $insight
        ]);
        
        return ['score' => $score, 'risk' => $risk, 'insight' => $insight];
    }
}
?>
