CREATE DATABASE IF NOT EXISTS health_tracking_db;
USE health_tracking_db;

-- Users Table (Role-based Auth)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Patients Profile
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    contact_number VARCHAR(15),
    address TEXT,
    medical_history TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doctors Profile
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    specialization VARCHAR(100),
    license_number VARCHAR(50) UNIQUE,
    contact_number VARCHAR(15),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Appointments
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATETIME NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- Visits & Diagnosis
CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT UNIQUE NOT NULL,
    diagnosis TEXT NOT NULL,
    prescription TEXT,
    doctor_notes TEXT,
    visit_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id)
);

-- Health Metrics (Vitals)
CREATE TABLE IF NOT EXISTS health_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    systolic_bp INT,
    diastolic_bp INT,
    blood_sugar INT,
    heart_rate INT,
    temperature DECIMAL(4,1),
    weight DECIMAL(5,2),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- AI Analysis Logs
CREATE TABLE IF NOT EXISTS ai_analysis_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    health_score INT, -- 0 to 100
    risk_level ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
    adherence_score INT, -- 0 to 100
    generated_insight TEXT,
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Medication Adherence
CREATE TABLE IF NOT EXISTS medication_adherence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    medicine_name VARCHAR(100) NOT NULL,
    prescribed_dosage VARCHAR(50),
    taken_status BOOLEAN DEFAULT FALSE,
    date_taken DATE,
    FOREIGN KEY (visit_id) REFERENCES visits(id)
);

-- Insert Default Admin
INSERT IGNORE INTO users (full_name, email, password, role) 
VALUES ('System Admin', 'admin@system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password is 'password' (bcrypt hash)
