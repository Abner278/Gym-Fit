<?php
require_once 'config.php';

// 1. Create messages table
$sql_msg = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
)";
if (mysqli_query($link, $sql_msg)) {
    echo "Messages table created/checked.<br>";
} else {
    echo "Error creating messages table: " . mysqli_error($link) . "<br>";
}

// 2. Add user_id to trainers if not exists
$check_col = mysqli_query($link, "SHOW COLUMNS FROM trainers LIKE 'user_id'");
if (mysqli_num_rows($check_col) == 0) {
    if (mysqli_query($link, "ALTER TABLE trainers ADD COLUMN user_id INT DEFAULT NULL")) {
        echo "Added user_id to trainers.<br>";
    } else {
        echo "Error adding user_id: " . mysqli_error($link) . "<br>";
    }
} else {
    echo "user_id column already exists in trainers.<br>";
}

// 3. Link Trainers to Staff Users by Name (Fuzzy Match)
$trainers = mysqli_query($link, "SELECT id, name FROM trainers WHERE user_id IS NULL");
while ($t = mysqli_fetch_assoc($trainers)) {
    $t_name = trim($t['name']);
    // Simple match: full_name contains trainer name or vice versa
    $users = mysqli_query($link, "SELECT id, full_name FROM users WHERE role='staff'");
    while ($u = mysqli_fetch_assoc($users)) {
        $u_name = trim($u['full_name']);
        if (stripos($u_name, $t_name) !== false || stripos($t_name, $u_name) !== false) {
            mysqli_query($link, "UPDATE trainers SET user_id = " . $u['id'] . " WHERE id = " . $t['id']);
            echo "Linked Trainer '{$t['name']}' to User '{$u['full_name']}' (ID: {$u['id']})<br>";
            break;
        }
    }
}
?>