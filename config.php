<?php
define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gym_management');
define('GOOGLE_CLIENT_ID', '410622149418-c4qiqdkfk5n192tc1lcsqu73ktpul6i8.apps.googleusercontent.com');

/* Attempt to connect to MySQL database */
try {
    $link = @mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

    if ($link === false) {
        throw new Exception(mysqli_connect_error());
        // Create user_progress_photos table
        $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
        mysqli_query($link, $photos_sql);
    }
} catch (Exception $e) {
    die("<div style='font-family:sans-serif; padding:40px; text-align:center; background-color:#0f0f1a; color:#fff; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>
            <h2 style='color:#ceff00;'>Database Connection Error</h2>
            <p style='font-size:1.2rem;'>Please ensure that <strong>MySQL</strong> is started in your <strong>XAMPP Control Panel</strong>.</p>
            <p style='color:#ff4444; font-size:0.9rem; margin-top:20px; background: rgba(255,0,0,0.1); padding: 10px; border-radius: 5px;'><strong>Debug Error:</strong> " . $e->getMessage() . "</p>
            <div style='margin-top:30px; border:1px dashed #555; padding:15px; border-radius:10px;'>
                <p>1. Open XAMPP Control Panel</p>
                <p>2. Click 'Start' next to MySQL</p>
            </div>
         </div>");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($link, $sql)) {
    mysqli_select_db($link, DB_NAME);
} else {
    die("ERROR: Could not create database. " . mysqli_error($link));
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

// Create users table with profile_image
$table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('member', 'staff', 'admin') DEFAULT 'member',
    profile_image TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

// Update role ENUM to include shop_staff if not present
$check_role = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'role'");
if ($check_role) {
    $role_row = mysqli_fetch_assoc($check_role);
    if (strpos($role_row['Type'], "'shop_staff'") === false) {
        mysqli_query($link, "ALTER TABLE users MODIFY COLUMN role ENUM('member', 'staff', 'admin', 'shop_staff') DEFAULT 'member'");
    }
}

if (!mysqli_query($link, $table_sql)) {
    die("ERROR: Could not create table. " . mysqli_error($link));
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

// Check if profile_image column exists, if not add it (for existing databases)
$check_col = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'profile_image'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($link, "ALTER TABLE users ADD profile_image TEXT DEFAULT NULL AFTER role");
} else {
    // Ensure it's TEXT type (fix for short VARCHAR truncation)
    mysqli_query($link, "ALTER TABLE users MODIFY profile_image TEXT DEFAULT NULL");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

// Check membership columns for users table (membership_plan, membership_status, membership_expiry)
$check_plan_col = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'membership_plan'");
if (mysqli_num_rows($check_plan_col) == 0) {
    mysqli_query($link, "ALTER TABLE users ADD membership_plan VARCHAR(100) DEFAULT 'Standard'");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

$check_status_col = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'membership_status'");
if (mysqli_num_rows($check_status_col) == 0) {
    mysqli_query($link, "ALTER TABLE users ADD membership_status VARCHAR(50) DEFAULT 'Active'");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

$check_expiry_col = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'membership_expiry'");
if (mysqli_num_rows($check_expiry_col) == 0) {
    mysqli_query($link, "ALTER TABLE users ADD membership_expiry DATE DEFAULT NULL");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
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
    status ENUM('completed', 'failed', 'pending') DEFAULT 'completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($link, $trans_sql);

// Add status column if it doesn't exist
$check_trans_status = mysqli_query($link, "SHOW COLUMNS FROM transactions LIKE 'status'");
if (mysqli_num_rows($check_trans_status) == 0) {
    mysqli_query($link, "ALTER TABLE transactions ADD status ENUM('completed', 'failed', 'pending') DEFAULT 'completed' AFTER payment_method");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

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

// Create trainers table
$trainers_sql = "CREATE TABLE IF NOT EXISTS trainers (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $trainers_sql);

// Create member_queries table
$queries_sql = "CREATE TABLE IF NOT EXISTS member_queries (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    gender VARCHAR(10) DEFAULT NULL,
    message TEXT NOT NULL,
    reply TEXT DEFAULT NULL,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $queries_sql);

// Create announcements table
$ann_sql = "CREATE TABLE IF NOT EXISTS announcements (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $ann_sql);

// Create daily_workouts table
$daily_sql = "CREATE TABLE IF NOT EXISTS daily_workouts (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    video_url VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $daily_sql);

// Create inventory table
$inventory_sql = "CREATE TABLE IF NOT EXISTS inventory (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    last_maintenance DATE NULL,
    next_service DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $inventory_sql);

// Populate default inventory if table is empty or has very few items (fresh install)
$check_inv = mysqli_query($link, "SELECT count(*) as count FROM inventory");
$inv_count = mysqli_fetch_assoc($check_inv);
if ($inv_count['count'] < 5) {
    $inv_insert = "INSERT INTO inventory (item_name, quantity, status, last_maintenance, next_service) VALUES 
    ('Elliptical Trainer 2000', 3, 'Functional', '2026-01-10', '2026-04-10'),
    ('Stationary Bike Pro', 4, 'Functional', '2026-01-15', '2026-04-15'),
    ('Rowing Machine RM-5', 2, 'Maintenance', '2025-12-20', '2026-01-25'),
    ('Bench Press Station', 3, 'Functional', '2026-01-05', '2026-07-05'),
    ('Squat Rack Heavy', 2, 'Functional', '2026-01-08', '2026-07-08'),
    ('Dumbbell Set (5-50kg)', 2, 'Functional', '2026-01-01', '2026-04-01'),
    ('Kettlebell Collection', 1, 'Functional', '2026-01-01', '2026-04-01'),
    ('Leg Press Machine', 1, 'Functional', '2026-01-12', '2026-04-12'),
    ('Lat Pulldown Machine', 1, 'Service Due', '2025-10-15', '2026-01-15'),
    ('Cable Crossover', 1, 'Functional', '2026-01-10', '2026-04-10'),
    ('Smith Machine', 1, 'Functional', '2026-01-08', '2026-07-08'),
    ('Yoga Mats Premium', 20, 'Functional', '2026-01-18', '2026-02-18'),
    ('Medicine Balls Set', 5, 'Functional', '2026-01-05', '2026-03-05'),
    ('Swiss Balls', 5, 'Functional', '2026-01-05', '2026-03-05'),
    ('Speed Jump Ropes', 10, 'Functional', '2026-01-20', '2026-03-20'),
    ('Foam Rollers', 8, 'Functional', '2026-01-20', '2026-03-20'),
    ('Battle Ropes', 2, 'Damaged', '2025-11-20', '2026-01-22'),
    ('Pull-up Bar Station', 2, 'Functional', '2026-01-01', '2026-12-01'),
    ('Dip Station', 1, 'Functional', '2026-01-01', '2026-12-01'),
    ('Ab Roller Wheels', 5, 'Functional', '2026-01-15', '2026-04-15'),
    ('TRX Suspension Kit', 3, 'Functional', '2026-01-10', '2026-03-10'),
    ('Heavy Boxing Bag', 2, 'Functional', '2026-01-05', '2026-04-05'),
    ('EZ Curl Bar', 3, 'Functional', '2026-01-08', '2026-07-08'),
    ('Adjustable Bench', 4, 'Functional', '2026-01-12', '2026-07-12')";

    if (!mysqli_query($link, $inv_insert)) {
        // Silently fail or log, but don't stop execution if duplicate
        // Create user_progress_photos table
        $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
        mysqli_query($link, $photos_sql);
    }
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

// Create membership_plans table
$plans_sql = "CREATE TABLE IF NOT EXISTS membership_plans (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    price_monthly DECIMAL(10,2) NOT NULL,
    price_yearly DECIMAL(10,2) NOT NULL,
    is_popular TINYINT(1) DEFAULT 0,
    gym_access TINYINT(1) DEFAULT 1,
    free_locker TINYINT(1) DEFAULT 1,
    group_class TINYINT(1) DEFAULT 1,
    personal_trainer TINYINT(1) DEFAULT 0,
    protein_drinks_monthly TINYINT(1) DEFAULT 0,
    protein_drinks_yearly TINYINT(1) DEFAULT 0,
    customized_workout_plan TINYINT(1) DEFAULT 0,
    diet_consultation_yearly TINYINT(1) DEFAULT 0,
    personal_locker_yearly TINYINT(1) DEFAULT 0,
    guest_pass_yearly TINYINT(1) DEFAULT 0,
    nutrition_guide_yearly TINYINT(1) DEFAULT 0
)";
mysqli_query($link, $plans_sql);

// Insert default plans if they don't exist
$check_plans = "SELECT * FROM membership_plans LIMIT 1";
$res_plans = mysqli_query($link, $check_plans);
if ($res_plans && mysqli_num_rows($res_plans) == 0) {
    mysqli_query($link, "INSERT INTO membership_plans (name, price_monthly, price_yearly, is_popular, gym_access, free_locker, group_class, personal_trainer, protein_drinks_monthly, protein_drinks_yearly, customized_workout_plan, diet_consultation_yearly, personal_locker_yearly, guest_pass_yearly, nutrition_guide_yearly) VALUES 
    ('Basic', 399, 2999, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 1, 0),
    ('Standard', 899, 5999, 1, 1, 1, 1, 1, 0, 1, 0, 0, 0, 0, 1),
    ('Premium', 999, 10999, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0)");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

// Add columns for dynamic features and labels
$check_custom = mysqli_query($link, "SHOW COLUMNS FROM membership_plans LIKE 'custom_attributes'");
if (mysqli_num_rows($check_custom) == 0) {
    mysqli_query($link, "ALTER TABLE membership_plans ADD custom_attributes TEXT DEFAULT NULL");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}
$check_labels = mysqli_query($link, "SHOW COLUMNS FROM membership_plans LIKE 'feature_labels'");
if (mysqli_num_rows($check_labels) == 0) {
    mysqli_query($link, "ALTER TABLE membership_plans ADD feature_labels TEXT DEFAULT NULL");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}
$check_hidden = mysqli_query($link, "SHOW COLUMNS FROM membership_plans LIKE 'hidden_features'");
if (mysqli_num_rows($check_hidden) == 0) {
    mysqli_query($link, "ALTER TABLE membership_plans ADD hidden_features TEXT DEFAULT NULL");
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}

// Create some default users for testing if they don't exist
$check_admin = "SELECT * FROM users WHERE role='admin' LIMIT 1";
$result = mysqli_query($link, $check_admin);
if ($result && mysqli_num_rows($result) == 0) {
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $staff_pass = password_hash('staff123', PASSWORD_DEFAULT);
    $member_pass = password_hash('member123', PASSWORD_DEFAULT);

    mysqli_query($link, "INSERT INTO users (full_name, email, password, role) VALUES ('Admin User', 'admin@gym.com', '$admin_pass', 'admin')");
    mysqli_query($link, "INSERT INTO users (full_name, email, password, role) VALUES ('Staff User', 'staff@gym.com', '$staff_pass', 'staff')");
    mysqli_query($link, "INSERT INTO users (full_name, email, password, role) VALUES ('Member User', 'member@gym.com', '$member_pass', 'member')");

    // Add default tasks for the member user
    $member_id = mysqli_insert_id($link);
    if ($member_id) {
        mysqli_query($link, "INSERT INTO tasks (user_id, task_name, is_done) VALUES 
            ($member_id, 'Morning Cardio (30 mins)', 1),
            ($member_id, 'Drink 4L Water', 0),
            ($member_id, 'Take Supplements', 1)");
        // Create user_progress_photos table
        $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
        mysqli_query($link, $photos_sql);
    }
    // Create user_progress_photos table
    $photos_sql = "CREATE TABLE IF NOT EXISTS user_progress_photos (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        photo_path VARCHAR(255) NOT NULL,
        photo_type ENUM('before', 'after', 'progress') DEFAULT 'progress',
        date_taken DATE NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($link, $photos_sql);
}
