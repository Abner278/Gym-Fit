<?php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gym_management');
define('GOOGLE_CLIENT_ID', '410622149418-c4qiqdkfk5n192tc1lcsqu73ktpul6i8.apps.googleusercontent.com');

/* Attempt to connect to MySQL database */
try {
    $link = @mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

    if ($link === false) {
        throw new Exception("Connection failed");
    }
} catch (Exception $e) {
    die("<div style='font-family:sans-serif; padding:40px; text-align:center; background-color:#0f0f1a; color:#fff; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
            <h2 style='color:#ceff00;'>Database Connection Error</h2>
            <p style='font-size:1.2rem;'>Please ensure that <strong>MySQL</strong> is started in your <strong>XAMPP Control Panel</strong>.</p>
            <p style='color:#aaa; font-size:0.9rem; margin-top:20px;'>Your gym system requires a running database to function.</p>
            <div style='margin-top:30px; border:1px dashed #555; padding:15px; border-radius:10px;'>
                <p>1. Open XAMPP Control Panel</p>
                <p>2. Click 'Start' next to MySQL</p>
            </div>
         </div>");
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($link, $sql)) {
    mysqli_select_db($link, DB_NAME);
} else {
    die("ERROR: Could not create database. " . mysqli_error($link));
}

// Create users table with profile_image
$table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('member', 'staff', 'admin') DEFAULT 'member',
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($link, $table_sql)) {
    die("ERROR: Could not create table. " . mysqli_error($link));
}

// Check if profile_image column exists, if not add it (for existing databases)
$check_col = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'profile_image'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($link, "ALTER TABLE users ADD profile_image VARCHAR(255) DEFAULT NULL AFTER role");
}

// Create tasks table
$tasks_sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($link, $tasks_sql);

// Create transactions table
$trans_sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_name VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($link, $trans_sql);

// Create completed_workouts table for progress tracking
$workouts_sql = "CREATE TABLE IF NOT EXISTS completed_workouts (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    video_id VARCHAR(100) NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($link, $workouts_sql);

// Create password_resets table
$reset_sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $reset_sql);

// Create some default users for testing if they don't exist
$check_admin = "SELECT * FROM users WHERE role='admin' LIMIT 1";
$result = mysqli_query($link, $check_admin);
if (mysqli_num_rows($result) == 0) {
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $staff_pass = password_hash('staff123', PASSWORD_DEFAULT);
    $member_pass = password_hash('member123', PASSWORD_DEFAULT);

    mysqli_query($link, "INSERT INTO users (full_name, email, password, role) VALUES ('Admin User', 'admin@gym.com', '$admin_pass', 'admin')");
    mysqli_query($link, "INSERT INTO users (full_name, email, password, role) VALUES ('Staff User', 'staff@gym.com', '$staff_pass', 'staff')");
    mysqli_query($link, "INSERT INTO users (full_name, email, password, role) VALUES ('Member User', 'member@gym.com', '$member_pass', 'member')");

    // Add default tasks for the member user
    $member_id = mysqli_insert_id($link);
    mysqli_query($link, "INSERT INTO tasks (user_id, task_name, is_done) VALUES 
        ($member_id, 'Morning Cardio (30 mins)', 1),
        ($member_id, 'Drink 4L Water', 0),
        ($member_id, 'Take Supplements', 1)");
}
?>