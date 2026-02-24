<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "admin") {
    header("location: login.php");
    exit;
}

$message = "";
$message_type = "";

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Ensure announcements table exists
$files_sql = "CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $files_sql);

// --- SCHEMA SYNCHRONIZATION ---
// Ensure visible_password column exists for users (Admin viewing requirement)
$check_col = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'visible_password'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($link, "ALTER TABLE users ADD COLUMN visible_password VARCHAR(255) AFTER password");
}

// Ensure trainers table has email and password columns
$check_trainer_email = mysqli_query($link, "SHOW COLUMNS FROM trainers LIKE 'email'");
if (mysqli_num_rows($check_trainer_email) == 0) {
    mysqli_query($link, "ALTER TABLE trainers ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER name");
    mysqli_query($link, "ALTER TABLE trainers ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER email");
    mysqli_query($link, "ALTER TABLE trainers ADD COLUMN visible_password VARCHAR(255) DEFAULT NULL AFTER password");
}

// Ensure services table exists
$services_sql = "CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(255) NOT NULL,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $services_sql);

// Seed Services if empty
$check_services = mysqli_query($link, "SELECT id FROM services LIMIT 1");
if (mysqli_num_rows($check_services) == 0) {
    $service_seeds = [
        ['Personal Trainer', 'One-on-one customized workouts to smash your goals.', 'assets/icons/muscle_custom.png', 1],
        ['Group Training', 'High-energy classes to keep you motivated and moving.', 'assets/icons/group.svg', 2],
        ['Treadmill', 'State-of-the-art cardio equipment for endurance.', 'assets/icons/treadmill.svg', 3],
        ['Yoga', 'Find your balance and improve flexibility with experts.', 'assets/icons/yoga_custom.png', 4],
        ['Workout Videos', 'Access expert-guided workout videos anytime, anywhere to stay fit and motivated.', 'assets/icons/online.svg', 5],
        ['Diet And Tips', 'Nutrition guidance ensuring you fuel your gains properly.', 'assets/icons/tips.svg', 6]
    ];
    foreach ($service_seeds as $s) {
        $t = mysqli_real_escape_string($link, $s[0]);
        $d = mysqli_real_escape_string($link, $s[1]);
        $i = mysqli_real_escape_string($link, $s[2]);
        $p = (int) $s[3];
        mysqli_query($link, "INSERT INTO services (title, description, icon, priority) VALUES ('$t', '$d', '$i', '$p')");
    }
}

// Ensure equipment showcase table exists
$equip_sql = "CREATE TABLE IF NOT EXISTS equipment_showcase (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $equip_sql);

// Seed if empty
$check_empty = mysqli_query($link, "SELECT id FROM equipment_showcase LIMIT 1");
if (mysqli_num_rows($check_empty) == 0) {
    $seeds = [
        ['Dumbbell', 'Adjustable weights', 'assets/images/dumble.png', 1],
        ['Cardio Bike', 'High-intensity cardio', 'assets/images/product-bike.png', 2],
        ['Treadmill Elite', 'Smart incline run', 'assets/images/product-treadmill.png', 3],
        ['Cable Machine', 'Full body workout', 'assets/images/product-cable.png', 4],
        ['Flat Bench', 'Steel frame support', 'assets/images/bench.png', 5],
        ['Smith Machine', 'Guided weight training', 'assets/images/smith.png', 6],
        ['Kettlebells', 'Explosive power', 'assets/images/kettlebell.png', 7],
        ['Pull-up Bar', 'Upper body strength', 'assets/images/pullup.png', 8],
        ['Medicine Ball', 'Core plyometrics', 'assets/images/medicineball.png', 9],
        ['Resistance Bands', 'Elastic resistance', 'assets/images/bands.png', 10],
        ['Rowing Machine', 'Full body cardio', 'assets/images/rowing.png', 11],
        ['Leg Press Machine', 'Lower body strength', 'assets/images/legpress.png', 12]
    ];
    foreach ($seeds as $s) {
        $n = mysqli_real_escape_string($link, $s[0]);
        $d = mysqli_real_escape_string($link, $s[1]);
        $i = mysqli_real_escape_string($link, $s[2]);
        $p = (int) $s[3];
        mysqli_query($link, "INSERT INTO equipment_showcase (name, description, image, priority) VALUES ('$n', '$d', '$i', '$p')");
    }
}


// Ensure transactions table has token columns
$check_trans_token = mysqli_query($link, "SHOW COLUMNS FROM transactions LIKE 'token_number'");
if (mysqli_num_rows($check_trans_token) == 0) {
    mysqli_query($link, "ALTER TABLE transactions ADD COLUMN token_number VARCHAR(20) DEFAULT NULL AFTER status");
    mysqli_query($link, "ALTER TABLE transactions ADD COLUMN token_accepted TINYINT(1) DEFAULT 0 AFTER token_number");
}

// Ensure gym_store_orders table exists
$store_orders_sql = "CREATE TABLE IF NOT EXISTS gym_store_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    token_number VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('pending', 'accepted') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $store_orders_sql);

// Seed categories if empty
$check_cat = mysqli_query($link, "SELECT id FROM store_categories LIMIT 1");
if (mysqli_num_rows($check_cat) == 0) {
    $cat_seeds = [
        ['Proteins', 'Whey, Isolate, Vegan & more.', 'assets/images/protein display.png'],
        ['Gym Gloves', 'Grip support & protection.', 'assets/images/Gym Glove.png'],
        ['Shakers', 'Leak-proof & durable bottles.', 'assets/images/Protein Shaker.png'],
        ['Lifting Belts', 'Support for heavy lifts.', 'assets/images/Lifting Belts.png'],
        ['Healthy Snacks', 'Clean fuel for your body.', 'assets/images/snacks display.jpg'],
        ['Fitness Accessories', 'Small tools, big results.', 'assets/images/fitness accesories display.jpg'],
        ['Hygiene Essentials', 'Stay fresh after every session.', 'assets/images/Hygiene Essentials.png']
    ];
    foreach ($cat_seeds as $c) {
        $n = mysqli_real_escape_string($link, $c[0]);
        $d = mysqli_real_escape_string($link, $c[1]);
        $i = mysqli_real_escape_string($link, $c[2]);
        mysqli_query($link, "INSERT INTO store_categories (name, description, image) VALUES ('$n', '$d', '$i')");

        $cat_id = mysqli_insert_id($link);

        // Seed products for this category
        if ($n == 'Proteins') {
            $prods = [
                ['Gold Standard Whey', 4500, 'assets/images/gold-standard.png'],
                ['MuscleBlaze Biozyme', 3200, 'assets/images/MuscleBlaze Biozyme.png'],
                ['Isopure Zero Carb', 6500, 'assets/images/isopure.png'],
                ['Vegan Plant Protein', 2800, 'assets/images/plant protein.png']
            ];
        } elseif ($n == 'Gym Gloves') {
            $prods = [
                ['Nivia Basic Gloves', 400, 'assets/images/nivia basic glove.png'],
                ['Nike Training Gloves', 1200, 'assets/images/nike gym glove.png'],
                ['Under Armour Grip', 1500, 'assets/images/under armour.png'],
                ['Wrist Support Gloves', 800, 'assets/images/wrist support.png']
            ];
        } elseif ($n == 'Shakers') {
            $prods = [
                ['Classic Shaker 500ml', 200, 'assets/images/classic shaker.png'],
                ['Spider Shaker', 500, 'assets/images/spider shaker.png'],
                ['Steel Shaker 700ml', 800, 'assets/images/steel shaker.png']
            ];
        } elseif ($n == 'Lifting Belts') {
            $prods = [
                ['Nylon Weight Belt', 800, 'assets/images/nylon weight.png'],
                ['Leather Power Belt', 2500, 'assets/images/leather.png'],
                ['Lever Buckle Belt', 5000, 'assets/images/Lever Buckle Belt.png'],
                ['Dip Belt with Chain', 1500, 'assets/images/Dip Belt with Chain.png']
            ];
        } elseif ($n == 'Healthy Snacks') {
            $prods = [
                ['Protein Bar (Pack of 6)', 720, 'assets/images/Protein Bar.png'],
                ['Peanut Butter 1kg', 450, 'assets/images/Peanut Butter.png'],
                ['Instant Oats 1kg', 200, 'assets/images/instant oats.png'],
                ['Energy Bites (Pack of 10)', 350, 'assets/images/Energy Bites.png']
            ];
        } elseif ($n == 'Fitness Accessories') {
            $prods = [
                ['Wrist Wraps', 400, 'assets/images/Wrist Wraps.png'],
                ['Knee Sleeves (Pair)', 1500, 'assets/images/Knee Sleeves.png'],
                ['Resistance Bands Set', 800, 'assets/images/Resistance Bands Set.png'],
                ['Speed Jump Rope', 300, 'assets/images/Speed Jump Rope.png']
            ];
        } elseif ($n == 'Hygiene Essentials') {
            $prods = [
                ['Microfiber Gym Towel', 300, 'assets/images/Microfiber Gym Towel.png'],
                ['Hand Sanitizer 500ml', 150, 'assets/images/Hand Sanitizer.png']
            ];
        } else {
            $prods = [];
        }

        foreach ($prods as $p) {
            $pn = mysqli_real_escape_string($link, $p[0]);
            $pp = (float) $p[1];
            $pi = mysqli_real_escape_string($link, $p[2]);
            mysqli_query($link, "INSERT INTO store_products (category_id, name, price, image) VALUES ($cat_id, '$pn', $pp, '$pi')");
        }
    }
}

// HANDLE EQUIPMENT ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_equipment'])) {
    $name = mysqli_real_escape_string($link, $_POST['eq_name']);
    $desc = mysqli_real_escape_string($link, $_POST['eq_desc']);
    $priority = (int) $_POST['eq_priority'];
    $image_path = "";

    if (isset($_FILES['eq_image']) && $_FILES['eq_image']['error'] == 0) {
        $target_dir = "assets/images/equipment/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["eq_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["eq_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    $sql = "INSERT INTO equipment_showcase (name, description, image, priority) VALUES ('$name', '$desc', '$image_path', '$priority')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Equipment added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding equipment: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE EQUIPMENT UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_equipment'])) {
    $id = (int) $_POST['eq_id'];
    $name = mysqli_real_escape_string($link, $_POST['eq_name']);
    $desc = mysqli_real_escape_string($link, $_POST['eq_desc']);
    $priority = (int) $_POST['eq_priority'];

    $image_update_sql = "";

    if (isset($_FILES['eq_image']) && $_FILES['eq_image']['error'] == 0) {
        $target_dir = "assets/images/equipment/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["eq_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["eq_image"]["tmp_name"], $target_file)) {
            // Delete old image
            $img_res = mysqli_query($link, "SELECT image FROM equipment_showcase WHERE id = $id");
            $img_data = mysqli_fetch_assoc($img_res);
            if ($img_data && !empty($img_data['image']) && file_exists($img_data['image'])) {
                unlink($img_data['image']);
            }
            $image_update_sql = ", image = '$target_file'";
        }
    }

    $sql = "UPDATE equipment_showcase SET name='$name', description='$desc', priority='$priority' $image_update_sql WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Equipment updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating equipment: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE EQUIPMENT DELETION
if (isset($_GET['delete_equipment'])) {
    $id = (int) $_GET['delete_equipment'];
    // Delete image
    $img_res = mysqli_query($link, "SELECT image FROM equipment_showcase WHERE id = $id");
    $img_data = mysqli_fetch_assoc($img_res);
    if ($img_data && !empty($img_data['image']) && file_exists($img_data['image'])) {
        unlink($img_data['image']);
    }

    if (mysqli_query($link, "DELETE FROM equipment_showcase WHERE id = $id")) {
        $_SESSION['message'] = "Equipment deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting equipment.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}


// --- STORE CATEGORY HANDLERS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_store_category'])) {
    $name = mysqli_real_escape_string($link, trim($_POST['cat_name']));
    $desc = mysqli_real_escape_string($link, trim($_POST['cat_desc']));
    $image_path = "assets/images/default_category.png";

    if (empty($name)) {
        $_SESSION['message'] = "Category name is required.";
        $_SESSION['message_type'] = "error";
        header("Location: dashboard_admin.php?section=gym-store-mgmt");
        exit;
    }

    if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES["cat_image"]["name"], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed)) {
            $_SESSION['message'] = "Invalid image format. Allowed: " . implode(', ', $allowed);
            $_SESSION['message_type'] = "error";
            header("Location: dashboard_admin.php?section=gym-store-mgmt");
            exit;
        }

        $target_dir = "assets/images/store/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);
        $file_name = "cat_" . time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    $sql = "INSERT INTO store_categories (name, description, image) VALUES ('$name', '$desc', '$image_path')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Category added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding category.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php?section=gym-store-mgmt");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_store_category'])) {
    $id = (int) $_POST['cat_id'];
    $name = mysqli_real_escape_string($link, trim($_POST['cat_name']));
    $desc = mysqli_real_escape_string($link, trim($_POST['cat_desc']));
    $image_update = "";

    if (empty($name)) {
        $_SESSION['message'] = "Category name cannot be empty.";
        $_SESSION['message_type'] = "error";
        header("Location: dashboard_admin.php?section=gym-store-mgmt");
        exit;
    }

    if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES["cat_image"]["name"], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $target_dir = "assets/images/store/";
            if (!file_exists($target_dir))
                mkdir($target_dir, 0777, true);
            $file_name = "cat_" . time() . "_" . uniqid() . "." . $file_ext;
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_file)) {
                $image_update = ", image = '$target_file'";
            }
        }
    }

    $sql = "UPDATE store_categories SET name='$name', description='$desc' $image_update WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Category updated successfully!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: dashboard_admin.php?section=gym-store-mgmt");
    exit;
}

if (isset($_GET['delete_store_category'])) {
    $id = (int) $_GET['delete_store_category'];
    if (mysqli_query($link, "DELETE FROM store_categories WHERE id = $id")) {
        $_SESSION['message'] = "Category deleted successfully!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: dashboard_admin.php?section=gym-store-mgmt");
    exit;
}

// --- STORE PRODUCT HANDLERS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_store_product'])) {
    $cat_id = (int) $_POST['prod_cat_id'];
    $name = mysqli_real_escape_string($link, trim($_POST['prod_name']));
    $price = (float) $_POST['prod_price'];
    $stock = (int) $_POST['stock_count'];
    $image_path = "assets/images/default_product.png";

    if (empty($name) || $price <= 0 || $cat_id <= 0 || $stock < 0) {
        $_SESSION['message'] = "Please provide valid product details, price, and stock.";
        $_SESSION['message_type'] = "error";
        header("Location: dashboard_admin.php?section=gym-store-mgmt");
        exit;
    }

    if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES["prod_image"]["name"], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed)) {
            $_SESSION['message'] = "Invalid product image format.";
            $_SESSION['message_type'] = "error";
            header("Location: dashboard_admin.php?section=gym-store-mgmt");
            exit;
        }

        $target_dir = "assets/images/store/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);
        $file_name = "prod_" . time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    $sql = "INSERT INTO store_products (category_id, name, price, stock_count, image) VALUES ($cat_id, '$name', $price, $stock, '$image_path')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Product added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding product.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php?section=gym-store-mgmt");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_store_product'])) {
    $id = (int) $_POST['prod_id'];
    $cat_id = (int) $_POST['prod_cat_id'];
    $name = mysqli_real_escape_string($link, trim($_POST['prod_name']));
    $price = (float) $_POST['prod_price'];
    $stock = (int) $_POST['stock_count'];
    $image_update = "";

    if (empty($name) || $price <= 0 || $stock < 0) {
        $_SESSION['message'] = "Product name, positive price, and valid stock are required.";
        $_SESSION['message_type'] = "error";
        header("Location: dashboard_admin.php?section=gym-store-mgmt");
        exit;
    }

    if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES["prod_image"]["name"], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $target_dir = "assets/images/store/";
            if (!file_exists($target_dir))
                mkdir($target_dir, 0777, true);
            $file_name = "prod_" . time() . "_" . uniqid() . "." . $file_ext;
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file)) {
                $image_update = ", image = '$target_file'";
            }
        }
    }

    $sql = "UPDATE store_products SET category_id=$cat_id, name='$name', price=$price, stock_count=$stock $image_update WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Product updated successfully!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: dashboard_admin.php?section=gym-store-mgmt");
    exit;
}

if (isset($_GET['delete_store_product'])) {
    $id = (int) $_GET['delete_store_product'];
    if (mysqli_query($link, "DELETE FROM store_products WHERE id = $id")) {
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: dashboard_admin.php?section=gym-store-mgmt");
    exit;
}


// HANDLE SERVICE ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service'])) {
    $title = mysqli_real_escape_string($link, $_POST['service_title']);
    $desc = mysqli_real_escape_string($link, $_POST['service_desc']);
    $priority = (int) $_POST['service_priority'];
    $icon_path = "";

    if (isset($_FILES['service_icon']) && $_FILES['service_icon']['error'] == 0) {
        $target_dir = "assets/icons/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["service_icon"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["service_icon"]["tmp_name"], $target_file)) {
            $icon_path = $target_file;
        }
    }

    $sql = "INSERT INTO services (title, description, icon, priority) VALUES ('$title', '$desc', '$icon_path', '$priority')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Service added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding service: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE SERVICE UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_service'])) {
    $id = (int) $_POST['service_id'];
    $title = mysqli_real_escape_string($link, $_POST['service_title']);
    $desc = mysqli_real_escape_string($link, $_POST['service_desc']);
    $priority = (int) $_POST['service_priority'];

    $icon_update_sql = "";

    if (isset($_FILES['service_icon']) && $_FILES['service_icon']['error'] == 0) {
        $target_dir = "assets/icons/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["service_icon"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["service_icon"]["tmp_name"], $target_file)) {
            $icon_update_sql = ", icon = '$target_file'";
        }
    }

    $sql = "UPDATE services SET title='$title', description='$desc', priority='$priority' $icon_update_sql WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Service updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating service: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE SERVICE DELETION
if (isset($_GET['delete_service'])) {
    $id = (int) $_GET['delete_service'];

    $ico_res = mysqli_query($link, "SELECT icon FROM services WHERE id = $id");
    $ico_data = mysqli_fetch_assoc($ico_res);
    if ($ico_data && !empty($ico_data['icon']) && file_exists($ico_data['icon'])) {
        if (preg_match('/^\d+_[a-z0-9]+\./', basename($ico_data['icon']))) {
            unlink($ico_data['icon']);
        }
    }

    $sql = "DELETE FROM services WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Service deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting service: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}


// HANDLE ANNOUNCEMENT ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_announcement'])) {
    $title = mysqli_real_escape_string($link, $_POST['title']);
    $msg_content = mysqli_real_escape_string($link, $_POST['message']);

    $sql = "INSERT INTO announcements (title, message) VALUES ('$title', '$msg_content')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Announcement posted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error posting announcement: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE ANNOUNCEMENT DELETION
if (isset($_GET['delete_announcement'])) {
    $id = (int) $_GET['delete_announcement'];
    if (mysqli_query($link, "DELETE FROM announcements WHERE id = $id")) {
        $_SESSION['message'] = "Announcement deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting announcement.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}


// HANDLE ANNOUNCEMENT EDITING
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_announcement'])) {
    $id = (int) $_POST['announcement_id'];
    $title = mysqli_real_escape_string($link, $_POST['title']);
    $msg_content = mysqli_real_escape_string($link, $_POST['message']);

    $sql = "UPDATE announcements SET title = '$title', message = '$msg_content' WHERE id = $id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Announcement updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating announcement: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}


// HANDLE TRAINER ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_trainer'])) {
    $name = mysqli_real_escape_string($link, trim($_POST['trainer_name']));
    $email = mysqli_real_escape_string($link, trim($_POST['trainer_email']));
    $password_raw = $_POST['trainer_password'];
    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $visible_password = mysqli_real_escape_string($link, $password_raw);
    $image_path = "";

    if (isset($_FILES['trainer_image']) && $_FILES['trainer_image']['error'] == 0) {
        $target_dir = "assets/images/trainers/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["trainer_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["trainer_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    $sql = "INSERT INTO trainers (name, email, password, visible_password, image) VALUES ('$name', '$email', '$password', '$visible_password', '$image_path')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Trainer added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding trainer: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE TRAINER EDITING
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_trainer'])) {
    $id = (int) $_POST['trainer_id'];
    $name = mysqli_real_escape_string($link, trim($_POST['trainer_name']));
    $email = mysqli_real_escape_string($link, trim($_POST['trainer_email']));
    $new_password_raw = $_POST['trainer_password'];
    $image_update = "";
    $password_update = "";

    if (!empty($new_password_raw)) {
        $new_password = password_hash($new_password_raw, PASSWORD_DEFAULT);
        $new_visible = mysqli_real_escape_string($link, $new_password_raw);
        $password_update = ", password = '$new_password', visible_password = '$new_visible'";
    }

    if (isset($_FILES['trainer_image']) && $_FILES['trainer_image']['error'] == 0) {
        $target_dir = "assets/images/trainers/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["trainer_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["trainer_image"]["tmp_name"], $target_file)) {
            // Delete old image
            $img_res = mysqli_query($link, "SELECT image FROM trainers WHERE id = $id");
            $img_data = mysqli_fetch_assoc($img_res);
            if ($img_data && !empty($img_data['image']) && file_exists($img_data['image'])) {
                unlink($img_data['image']);
            }
            $image_update = ", image = '$target_file'";
        }
    }

    $sql = "UPDATE trainers SET name = '$name', email = '$email' $password_update $image_update WHERE id = $id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Trainer updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating trainer.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE TRAINER DELETION
if (isset($_GET['delete_trainer'])) {
    $id = (int) $_GET['delete_trainer'];

    // Get image path to delete file
    $img_res = mysqli_query($link, "SELECT image FROM trainers WHERE id = $id");
    $img_data = mysqli_fetch_assoc($img_res);
    if ($img_data && !empty($img_data['image']) && file_exists($img_data['image'])) {
        unlink($img_data['image']);
    }

    if (mysqli_query($link, "DELETE FROM trainers WHERE id = $id")) {
        $_SESSION['message'] = "Trainer deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting trainer.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE INVENTORY ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_inventory'])) {
    $name = mysqli_real_escape_string($link, $_POST['item_name']);
    $qty = (int) $_POST['quantity'];
    $status = mysqli_real_escape_string($link, $_POST['status']);
    $last_m = mysqli_real_escape_string($link, $_POST['last_maintenance']);
    $next_s = mysqli_real_escape_string($link, $_POST['next_service']);

    $sql = "INSERT INTO inventory (item_name, quantity, status, last_maintenance, next_service) VALUES ('$name', $qty, '$status', '$last_m', '$next_s')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Equipment added to inventory!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding equipment.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE INVENTORY UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_inventory'])) {
    $id = (int) $_POST['item_id'];
    $name = mysqli_real_escape_string($link, $_POST['item_name']);
    $qty = (int) $_POST['quantity'];
    $status = mysqli_real_escape_string($link, $_POST['status']);
    $last_m = mysqli_real_escape_string($link, $_POST['last_maintenance']);
    $next_s = mysqli_real_escape_string($link, $_POST['next_service']);

    $sql = "UPDATE inventory SET item_name='$name', quantity=$qty, status='$status', last_maintenance='$last_m', next_service='$next_s' WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Inventory item updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating inventory.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE INVENTORY DELETION
if (isset($_GET['delete_inventory'])) {
    $id = (int) $_GET['delete_inventory'];
    if (mysqli_query($link, "DELETE FROM inventory WHERE id = $id")) {
        $_SESSION['message'] = "Item removed from inventory.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting item.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// FETCH TRAINERS
$trainers_query = mysqli_query($link, "SELECT * FROM trainers ORDER BY created_at DESC");

// FETCH ANNOUNCEMENTS
$ann_query = mysqli_query($link, "SELECT * FROM announcements ORDER BY created_at DESC");

// FETCH INVENTORY
$inventory_query = mysqli_query($link, "SELECT * FROM inventory ORDER BY created_at ASC");
$inventory_count = mysqli_num_rows($inventory_query);

// FETCH EQUIPMENT SHOWCASE
$equip_query = mysqli_query($link, "SELECT * FROM equipment_showcase ORDER BY priority ASC, created_at DESC");

// FETCH SERVICES
$services_query = mysqli_query($link, "SELECT * FROM services ORDER BY priority ASC, created_at DESC");


// --- QUERY MANAGEMENT ---
require_once 'mailer.php';

// Handle Reply
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_query'])) {
    $query_id = (int) $_POST['query_id'];
    $reply_text = mysqli_real_escape_string($link, $_POST['reply_content']);
    $user_email = mysqli_real_escape_string($link, $_POST['user_email']);
    $user_name = mysqli_real_escape_string($link, $_POST['user_name']);

    $subject = "GymFit Team: Reply to your inquiry";
    $email_body = "Hello $user_name,<br><br>Thank you for reaching out to GymFit. Here is our reply to your inquiry:<br><hr><br>$reply_text<br><br><hr>Best regards,<br>GymFit Staff Team";

    if (sendMail($user_email, $subject, $email_body)) {
        $update_sql = "UPDATE member_queries SET reply = '$reply_text', status = 'resolved' WHERE id = $query_id";
        if (mysqli_query($link, $update_sql)) {
            $_SESSION['message'] = "Reply sent and email delivered successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Reply sent but failed to update status in data.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Error: Failed to send email reply.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// --- MEMBER MANAGEMENT ---

// Add Member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
    $full_name = trim(mysqli_real_escape_string($link, $_POST['full_name']));
    $email = trim(mysqli_real_escape_string($link, $_POST['email']));
    $password_raw = $_POST['password'];

    // Validation
    if (empty($full_name) || empty($email) || empty($password_raw)) {
        $_SESSION['message'] = "Error: All fields are required.";
        $_SESSION['message_type'] = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Error: Invalid email format.";
        $_SESSION['message_type'] = "error";
    } elseif (strlen($password_raw) < 6) {
        $_SESSION['message'] = "Error: Password must be at least 6 characters.";
        $_SESSION['message_type'] = "error";
    } else {
        // Check if email exists
        $check_email = mysqli_query($link, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $_SESSION['message'] = "Error: Email is already registered.";
            $_SESSION['message_type'] = "error";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $visible_password = mysqli_real_escape_string($link, $password_raw);
            $sql = "INSERT INTO users (full_name, email, password, visible_password, role) VALUES ('$full_name', '$email', '$password', '$visible_password', 'member')";
            if (mysqli_query($link, $sql)) {
                $_SESSION['message'] = "New member added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error adding member: " . mysqli_error($link);
                $_SESSION['message_type'] = "error";
            }
        }
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Update Member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_member'])) {
    $id = (int) $_POST['member_id'];
    $full_name = trim(mysqli_real_escape_string($link, $_POST['full_name']));
    $email = trim(mysqli_real_escape_string($link, $_POST['email']));
    $password_raw = $_POST['password'];

    // Validation
    if (empty($full_name) || empty($email)) {
        $_SESSION['message'] = "Error: Name and email are required.";
        $_SESSION['message_type'] = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Error: Invalid email format.";
        $_SESSION['message_type'] = "error";
    } elseif (!empty($password_raw) && strlen($password_raw) < 6) {
        $_SESSION['message'] = "Error: New password must be at least 6 characters.";
        $_SESSION['message_type'] = "error";
    } else {
        // Check if email exists for other users
        $check_email = mysqli_query($link, "SELECT id FROM users WHERE email = '$email' AND id != $id");
        if (mysqli_num_rows($check_email) > 0) {
            $_SESSION['message'] = "Error: Email is already in use by another user.";
            $_SESSION['message_type'] = "error";
        } else {
            $pass_query = "";
            if (!empty($password_raw)) {
                $password = password_hash($password_raw, PASSWORD_DEFAULT);
                $visible_password = mysqli_real_escape_string($link, $password_raw);
                $pass_query = ", password='$password', visible_password='$visible_password'";
            }

            $sql = "UPDATE users SET full_name='$full_name', email='$email' $pass_query WHERE id=$id AND role='member'";
            if (mysqli_query($link, $sql)) {
                $_SESSION['message'] = "Member updated successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating member: " . mysqli_error($link);
                $_SESSION['message_type'] = "error";
            }
        }
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Delete Member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_member'])) {
    $id = (int) $_POST['member_id'];
    if (mysqli_query($link, "DELETE FROM users WHERE id = $id AND role='member'")) {
        $_SESSION['message'] = "Member removed successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error removing member.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Fetch all members
$members_res = mysqli_query($link, "SELECT * FROM users WHERE role = 'member' ORDER BY created_at DESC");

// Fetch All Payments
$payments_sql = "SELECT t.*, u.full_name, u.email as user_email 
                FROM transactions t 
                JOIN users u ON t.user_id = u.id 
                ORDER BY t.created_at DESC";
$payments_res = mysqli_query($link, $payments_sql);

// Group by User for cleaner display
$grouped_payments = [];
while ($row = mysqli_fetch_assoc($payments_res)) {
    $uid = $row['user_id'];
    if (!isset($grouped_payments[$uid])) {
        $grouped_payments[$uid] = [
            'user' => $row,
            'history' => [],
            'plans' => [],
            'trainers' => [],
            'store' => []
        ];
    }
    $grouped_payments[$uid]['history'][] = $row;

    $pn = $row['plan_name'];
    if (strpos($pn, 'Store:') !== false || !empty($row['token_number'])) {
        $grouped_payments[$uid]['store'][] = $row;
    } elseif (strpos($pn, 'Trainer Appointment') !== false) {
        $grouped_payments[$uid]['trainers'][] = $row;
    } else {
        $grouped_payments[$uid]['plans'][] = $row;
    }
}


// Fetch Store Orders (Pending Pickup)
$store_orders_sql = "SELECT t.*, u.full_name, u.email as user_email 
                    FROM transactions t 
                    JOIN users u ON t.user_id = u.id 
                    WHERE t.token_number IS NOT NULL 
                    ORDER BY t.token_accepted ASC, t.created_at DESC";
$store_orders_res = mysqli_query($link, $store_orders_sql);

// Handle Delete Query
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_query'])) {
    $id = (int) $_POST['query_id'];
    if (mysqli_query($link, "DELETE FROM member_queries WHERE id = $id")) {
        $_SESSION['message'] = "Inquiry deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting inquiry.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Fetch Queries
$queries_res = mysqli_query($link, "SELECT * FROM member_queries ORDER BY created_at DESC");

// HANDLE TRANSACTION DELETION
if (isset($_GET['delete_transaction'])) {
    $tid = (int) $_GET['delete_transaction'];
    if (mysqli_query($link, "DELETE FROM transactions WHERE id = $tid")) {
        $_SESSION['message'] = "Transaction deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting transaction.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE TOKEN ACCEPTANCE
if (isset($_GET['accept_token'])) {
    $tid = (int) $_GET['accept_token'];
    $uid = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
    $sql = "UPDATE transactions SET token_accepted = 1, status = 'completed' WHERE id = $tid";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Token accepted! You can now release the product.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error accepting token.";
        $_SESSION['message_type'] = "error";
    }

    $redirect = "dashboard_admin.php#store-orders";
    header("Location: $redirect");
    exit;
}

// HANDLE STORE ORDER DELETION
if (isset($_GET['delete_store_order'])) {
    $tid = (int) $_GET['delete_store_order'];
    if (mysqli_query($link, "DELETE FROM transactions WHERE id = $tid AND token_number IS NOT NULL")) {
        $_SESSION['message'] = "Store order deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting store order.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php#store-orders");
    exit;
}

// HANDLE SHOP STAFF ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_shop_staff'])) {
    $name = mysqli_real_escape_string($link, trim($_POST['shop_staff_name']));
    $email = mysqli_real_escape_string($link, trim($_POST['shop_staff_email']));
    $password_raw = $_POST['shop_staff_password'];

    if (empty($name) || empty($email) || empty($password_raw)) {
        $_SESSION['message'] = "All fields are required.";
        $_SESSION['message_type'] = "error";
    } elseif (strlen($password_raw) < 6) {
        $_SESSION['message'] = "Password must be at least 6 characters.";
        $_SESSION['message_type'] = "error";
    } else {
        $check = mysqli_query($link, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['message'] = "Email already registered.";
            $_SESSION['message_type'] = "error";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $visible_password = mysqli_real_escape_string($link, $password_raw);
            if (mysqli_query($link, "INSERT INTO users (full_name, email, password, visible_password, role) VALUES ('$name', '$email', '$password', '$visible_password', 'shop_staff')")) {
                $_SESSION['message'] = "Shop staff account created successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error creating account: " . mysqli_error($link);
                $_SESSION['message_type'] = "error";
            }
        }
    }
    header("Location: dashboard_admin.php#shop-staff");
    exit;
}

// HANDLE SHOP STAFF EDITING
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_shop_staff'])) {
    $id = (int) $_POST['shop_staff_id'];
    $name = mysqli_real_escape_string($link, trim($_POST['shop_staff_name']));
    $email = mysqli_real_escape_string($link, trim($_POST['shop_staff_email']));
    $new_pass = $_POST['shop_staff_new_password'];
    $image_update = "";
    $password_update = "";

    if (!empty($new_pass)) {
        if (strlen($new_pass) < 6) {
            $_SESSION['message'] = "New password must be at least 6 characters.";
            $_SESSION['message_type'] = "error";
            header("Location: dashboard_admin.php#shop-staff");
            exit;
        }
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $visible = mysqli_real_escape_string($link, $new_pass);
        $password_update = ", password = '$hashed', visible_password = '$visible'";
    }

    if (isset($_FILES['shop_staff_image']) && $_FILES['shop_staff_image']['error'] == 0) {
        $target_dir = "assets/images/profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["shop_staff_image"]["name"], PATHINFO_EXTENSION);
        $file_name = "staff_" . time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["shop_staff_image"]["tmp_name"], $target_file)) {
            // Delete old image
            $img_res = mysqli_query($link, "SELECT profile_image FROM users WHERE id = $id");
            $img_data = mysqli_fetch_assoc($img_res);
            if ($img_data && !empty($img_data['profile_image']) && file_exists($img_data['profile_image'])) {
                if (strpos($img_data['profile_image'], 'default.png') === false) {
                    unlink($img_data['profile_image']);
                }
            }
            $image_update = ", profile_image = '$target_file'";
        }
    }

    $sql = "UPDATE users SET full_name = '$name', email = '$email' $password_update $image_update WHERE id = $id AND role = 'shop_staff'";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Shop staff account updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating shop staff: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php#shop-staff");
    exit;
}

// HANDLE SHOP STAFF DELETION
if (isset($_GET['delete_shop_staff'])) {
    $sid = (int) $_GET['delete_shop_staff'];

    // Get image path to delete file
    $img_res = mysqli_query($link, "SELECT profile_image FROM users WHERE id = $sid AND role = 'shop_staff'");
    if ($img_data = mysqli_fetch_assoc($img_res)) {
        if (!empty($img_data['profile_image']) && file_exists($img_data['profile_image'])) {
            if (strpos($img_data['profile_image'], 'default.png') === false) {
                unlink($img_data['profile_image']);
            }
        }
    }

    if (mysqli_query($link, "DELETE FROM users WHERE id = $sid AND role = 'shop_staff'")) {
        $_SESSION['message'] = "Shop staff account deleted.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting shop staff.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php#shop-staff");
    exit;
}

// --- ATTENDANCE SYSTEM MIGRATION & LOGIC ---
// Ensure attendance table exists and has user_type column
mysqli_query($link, "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('user', 'trainer') DEFAULT 'user',
    date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$check_att_type = mysqli_query($link, "SHOW COLUMNS FROM attendance LIKE 'user_type'");
if (mysqli_num_rows($check_att_type) == 0) {
    mysqli_query($link, "ALTER TABLE attendance ADD COLUMN user_type ENUM('user', 'trainer') DEFAULT 'user' AFTER user_id");
    @mysqli_query($link, "ALTER TABLE attendance DROP INDEX unique_attendance");
    mysqli_query($link, "ALTER TABLE attendance ADD UNIQUE KEY unique_attendance (user_id, user_type, date)");
}

// Handle Mark Attendance from Admin (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_admin_attendance'])) {
    $target_uid = (int) $_POST['user_id'];
    $target_type = mysqli_real_escape_string($link, $_POST['user_type']);
    $target_date = mysqli_real_escape_string($link, $_POST['date']);
    $new_status = mysqli_real_escape_string($link, $_POST['status']); // present / absent

    $sql = "INSERT INTO attendance (user_id, user_type, date, status) 
            VALUES ($target_uid, '$target_type', '$target_date', '$new_status')
            ON DUPLICATE KEY UPDATE status = '$new_status'";

    header('Content-Type: application/json');
    if (mysqli_query($link, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($link)]);
    }
    exit;
}

// Fetch attendance map for selected date
$att_date_view = isset($_GET['att_date']) ? $_GET['att_date'] : date('Y-m-d');
$att_map_res = mysqli_query($link, "SELECT user_id, user_type, status FROM attendance WHERE date = '$att_date_view'");
$daily_att_map = [];
while ($row = mysqli_fetch_assoc($att_map_res)) {
    $key = $row['user_type'] . "_" . $row['user_id'];
    $daily_att_map[$key] = $row['status'];
}

// Fetch all staff (trainers + shop_staff) for attendance
$trainers_att_query = mysqli_query($link, "SELECT id, name as full_name, email, 'trainer' as specific_type FROM trainers ORDER BY name ASC");
$shop_staff_att_query = mysqli_query($link, "SELECT id, full_name, email, 'shop_staff' as specific_type FROM users WHERE role = 'shop_staff' ORDER BY full_name ASC");
$trainers_att = [];
$shop_staff_att = [];
while ($t = mysqli_fetch_assoc($trainers_att_query))
    $trainers_att[] = $t;
while ($s = mysqli_fetch_assoc($shop_staff_att_query))
    $shop_staff_att[] = $s;

// Handle Add Plan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_plan'])) {
    $name = mysqli_real_escape_string($link, $_POST['name']);
    $price_monthly = (float) $_POST['price_monthly'];
    $price_yearly = (float) $_POST['price_yearly'];
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;

    // Default values for a new plan
    $sql = "INSERT INTO membership_plans (
        name, price_monthly, price_yearly, is_popular, 
        gym_access, free_locker, group_class, personal_trainer, 
        protein_drinks_monthly, protein_drinks_yearly, customized_workout_plan, 
        diet_consultation_yearly, personal_locker_yearly, guest_pass_yearly, nutrition_guide_yearly,
        custom_attributes, feature_labels, hidden_features
    ) VALUES (
        '$name', '$price_monthly', '$price_yearly', '$is_popular',
        1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
        '[]', '{}', '[]'
    )";

    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "New plan created successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error creating plan.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Handle Plan Deletion
if (isset($_GET['delete_plan'])) {
    $plan_id = (int) $_GET['delete_plan'];
    if (mysqli_query($link, "DELETE FROM membership_plans WHERE id = $plan_id")) {
        $_SESSION['message'] = "Plan deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting plan.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Handle Plan Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_plan'])) {
    $id = (int) $_POST['plan_id'];
    $name = mysqli_real_escape_string($link, $_POST['name']);
    $price_monthly = (float) $_POST['price_monthly'];
    $price_yearly = (float) $_POST['price_yearly'];
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;

    // Features
    $gym_access = isset($_POST['gym_access']) ? 1 : 0;
    $free_locker = isset($_POST['free_locker']) ? 1 : 0;
    $group_class = isset($_POST['group_class']) ? 1 : 0;
    $personal_trainer = isset($_POST['personal_trainer']) ? 1 : 0;
    $protein_drinks_monthly = isset($_POST['protein_drinks_monthly']) ? 1 : 0;
    $protein_drinks_yearly = isset($_POST['protein_drinks_yearly']) ? 1 : 0;
    $customized_workout_plan = isset($_POST['customized_workout_plan']) ? 1 : 0;
    $diet_consultation_yearly = isset($_POST['diet_consultation_yearly']) ? 1 : 0;
    $personal_locker_yearly = isset($_POST['personal_locker_yearly']) ? 1 : 0;
    $guest_pass_yearly = isset($_POST['guest_pass_yearly']) ? 1 : 0;
    $nutrition_guide_yearly = isset($_POST['nutrition_guide_yearly']) ? 1 : 0;
    $custom_attributes = mysqli_real_escape_string($link, $_POST['custom_attributes'] ?? '');
    $hidden_features = mysqli_real_escape_string($link, $_POST['hidden_features'] ?? '[]');

    // Labels for standard features
    $labels = [
        'gym_access' => $_POST['lab_gym_access'] ?? 'Gym Access',
        'free_locker' => $_POST['lab_free_locker'] ?? 'Free Locker',
        'group_class' => $_POST['lab_group_class'] ?? 'Group Class',
        'personal_trainer' => $_POST['lab_personal_trainer'] ?? 'Personal Trainer',
        'protein_drinks_monthly' => $_POST['lab_protein_drinks_monthly'] ?? 'Protein Drinks (Monthly)',
        'protein_drinks_yearly' => $_POST['lab_protein_drinks_yearly'] ?? 'Protein Drinks (Yearly)',
        'customized_workout_plan' => $_POST['lab_customized_workout_plan'] ?? 'Customized Workout Plan',
        'diet_consultation_yearly' => $_POST['lab_diet_consultation_yearly'] ?? 'Diet Consultation (Yearly)',
        'personal_locker_yearly' => $_POST['lab_personal_locker_yearly'] ?? 'Personal Locker (Yearly)',
        'guest_pass_yearly' => $_POST['lab_guest_pass_yearly'] ?? '1 Guest Pass/Mo (Yearly)',
        'nutrition_guide_yearly' => $_POST['lab_nutrition_guide_yearly'] ?? 'Nutrition Guide (Yearly)'
    ];
    $feature_labels = mysqli_real_escape_string($link, json_encode($labels));

    $update_sql = "UPDATE membership_plans SET 
        name='$name', 
        price_monthly='$price_monthly', 
        price_yearly='$price_yearly', 
        is_popular='$is_popular',
        gym_access='$gym_access',
        free_locker='$free_locker',
        group_class='$group_class',
        personal_trainer='$personal_trainer',
        protein_drinks_monthly='$protein_drinks_monthly',
        protein_drinks_yearly='$protein_drinks_yearly',
        customized_workout_plan='$customized_workout_plan',
        diet_consultation_yearly='$diet_consultation_yearly',
        personal_locker_yearly='$personal_locker_yearly',
        guest_pass_yearly='$guest_pass_yearly',
        nutrition_guide_yearly='$nutrition_guide_yearly',
        custom_attributes='$custom_attributes',
        feature_labels='$feature_labels',
        hidden_features='$hidden_features'
        WHERE id = $id";

    if (mysqli_query($link, $update_sql)) {
        $_SESSION['message'] = "Plan updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating plan.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Mark pickup as done
if (isset($_GET['mark_pickup_done'])) {
    $pickup_tid = (int) $_GET['mark_pickup_done'];
    mysqli_query($link, "UPDATE transactions SET token_accepted = 1 WHERE id = $pickup_tid");
    $_SESSION['message'] = "Order marked as picked up successfully.";
    $_SESSION['message_type'] = "success";
    header("Location: dashboard_admin.php#store-orders");
    exit;
}

// Fetch all plans
$plans_res = mysqli_query($link, "SELECT * FROM membership_plans ORDER BY id ASC");

// --- OVERVIEW STATS (DYNAMIC) ---
// 1. Total Members
$total_members_query = mysqli_query($link, "SELECT COUNT(*) as count FROM users WHERE role = 'member'");
$total_members = mysqli_fetch_assoc($total_members_query)['count'];

// 2. Monthly Revenue — supports ?rev_month=YYYY-MM (2026+ only)
$rev_month_param = isset($_GET['rev_month']) ? $_GET['rev_month'] : date('Y-m');
// Validate format and restrict to 2026+
if (!preg_match('/^\d{4}-\d{2}$/', $rev_month_param) || substr($rev_month_param, 0, 4) < 2026) {
    $rev_month_param = date('Y-m');
}
$revenue_query = mysqli_query($link, "SELECT SUM(amount) as total FROM transactions WHERE status = 'completed' AND DATE_FORMAT(created_at, '%Y-%m') = '$rev_month_param'");
$monthly_revenue = mysqli_fetch_assoc($revenue_query)['total'] ?? 0;

// Available months for revenue dropdown — 2026 onwards only
$rev_months_list = [];
$start = mktime(0, 0, 0, 1, 1, 2026); // Jan 2026
$now = mktime(0, 0, 0, date('n'), 1, date('Y'));
for ($ts = $now; $ts >= $start; $ts = strtotime('-1 month', $ts)) {
    $rev_months_list[] = date('Y-m', $ts);
}

// 3. Gym Trainers
$total_trainers_query = mysqli_query($link, "SELECT COUNT(*) as count FROM trainers");
$total_trainers = mysqli_fetch_assoc($total_trainers_query)['count'];

// 4. Equipment Status (Simple average/percentage of "Functional" or "Good" items)
$inventory_stats_query = mysqli_query($link, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('Functional', 'Good') THEN 1 ELSE 0 END) as healthy
    FROM inventory");
$iv_stats = mysqli_fetch_assoc($inventory_stats_query);
$equipment_status = ($iv_stats['total'] > 0) ? round(($iv_stats['healthy'] / $iv_stats['total']) * 100) : 100;

// 5. Pending Store Pickups — both count AND detailed list
$pending_pickups_query = mysqli_query($link, "SELECT COUNT(*) as count FROM transactions WHERE token_number IS NOT NULL AND token_accepted = 0");
$pending_pickups = mysqli_fetch_assoc($pending_pickups_query)['count'];

$pending_pickups_details = mysqli_query($link, "
    SELECT t.id, t.token_number, t.amount, t.created_at, t.plan_name,
           u.full_name, u.email
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.token_number IS NOT NULL AND t.token_accepted = 0
    ORDER BY t.created_at DESC
");

// 6. Shop Staff
$total_shop_staff_query = mysqli_query($link, "SELECT COUNT(*) as count FROM users WHERE role = 'shop_staff'");
$total_shop_staff = mysqli_fetch_assoc($total_shop_staff_query)['count'];

// --- REVENUE CHART DATA ---
$selected_year = isset($_GET['revenue_year']) ? (int) $_GET['revenue_year'] : date('Y');

// Fetch available years for the dropdown
$years_query = mysqli_query($link, "SELECT DISTINCT YEAR(created_at) as year FROM transactions WHERE status = 'completed' ORDER BY year DESC");
$available_years = [];
while ($y_row = mysqli_fetch_assoc($years_query)) {
    $available_years[] = $y_row['year'];
}
if (!in_array(date('Y'), $available_years)) {
    array_unshift($available_years, date('Y'));
}

// Monthly revenue for selected year
$monthly_data = array_fill(1, 12, 0);
$chart_query = mysqli_query($link, "SELECT MONTH(created_at) as month, SUM(amount) as total 
                                   FROM transactions 
                                   WHERE status = 'completed' AND YEAR(created_at) = $selected_year 
                                   GROUP BY MONTH(created_at)");
while ($c_row = mysqli_fetch_assoc($chart_query)) {
    $monthly_data[(int) $c_row['month']] = (float) $c_row['total'];
}

$chart_labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$chart_values = array_values($monthly_data);

// Fetch all shop staff
$shop_staff_res = mysqli_query($link, "SELECT id, full_name, email, visible_password, profile_image, created_at FROM users WHERE role = 'shop_staff' ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Admin Dashboard - BeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        :root {
            --primary-color: #ceff00;
            --secondary-color: #1a1a2e;
            --bg-dark: #080810;
            --card-bg: rgba(255, 255, 255, 0.05);
            --text-gray: #aaa;
            --admin-accent: #3498db;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-dark);
            color: #fff;
            font-family: 'Roboto', sans-serif;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: #000;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .logo {
            font-family: 'Oswald', sans-serif;
            font-size: 1.8rem;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 40px;
        }

        .sidebar-menu {
            list-style: none;
            flex-grow: 1;
        }

        .sidebar-menu a {
            color: #fff;
            text-decoration: none;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: 0.3s;
            margin-bottom: 5px;
            opacity: 0.7;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--primary-color);
            color: #000;
            opacity: 1;
            font-weight: bold;
        }

        .sidebar-menu a.back-to-site {
            color: var(--primary-color);
            opacity: 1;
        }

        .sidebar-menu a.back-to-site:hover {
            color: #000;
        }

        .main-content {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .dashboard-header h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 2.2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid var(--primary-color);
        }

        .stat-card h4 {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Sections */
        .dashboard-section {
            display: none;
        }

        .dashboard-section.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h3 {
            font-family: 'Oswald', sans-serif;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .data-table th {
            font-size: 0.8rem;
            color: var(--text-gray);
            text-transform: uppercase;
        }

        .btn-add {
            background: var(--primary-color);
            color: #000;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            border: none;
            margin-right: 5px;
        }

        .btn-view {
            background: #3498db;
            color: #fff;
        }

        .btn-delete {
            background: #e74c3c;
            color: #fff;
        }

        .chart-container {
            height: 300px;
            margin-top: 20px;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .badge-success {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        /* Modal Styles (Synced with Staff Dashboard) */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--secondary-color);
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            color: #fff;
            outline: none;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
        }

        select.form-control {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23ceff00'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2em;
            padding-right: 2.5rem;
            border-color: var(--primary-color) !important;
        }

        select.form-control option {
            background-color: var(--secondary-color);
            color: #fff;
            padding: 10px;
        }

        .btn-action-modal {
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            padding: 15px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            font-family: inherit;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .btn-action-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(206, 255, 0, 0.3);
        }

        .pass-wrapper {
            position: relative;
        }

        .pass-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
            transition: 0.3s;
        }

        .pass-toggle:hover {
            color: var(--primary-color);
        }

        .edit-member-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            margin: 0 auto 15px;
            display: block;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* Sub-navigation Tabs */
        .user-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 2px;
        }

        .user-tab {
            padding: 12px 25px;
            color: var(--text-gray);
            cursor: pointer;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid transparent;
            transition: 0.3s;
        }

        .user-tab:hover {
            color: #fff;
        }

        .user-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .store-tab-content {
            animation: fadeIn 0.4s ease-out;
        }

        .slider-table {
            max-height: 450px;
            overflow-y: auto;
            border-radius: 12px;
            padding-right: 5px;
        }

        .slider-table::-webkit-scrollbar {
            width: 8px;
        }

        .slider-table::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .slider-table::-webkit-scrollbar-thumb {
            background: rgba(206, 255, 0, 0.2);
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }

        .slider-table::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }

        .data-table-container {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .user-tab-content {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .user-tab-content.active {
            display: block;
        }

        /* History Category Tabs */
        .hist-tab {
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            color: var(--text-gray);
            transition: 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .hist-tab:hover {
            background: rgba(206, 255, 0, 0.1);
            color: #fff;
        }

        .hist-tab.active-hist {
            background: var(--primary-color);
            color: #000;
            font-weight: bold;
            border-color: var(--primary-color);
        }

        .hist-content,
        .att-content {
            display: none;
        }

        .hist-content.active,
        .att-content.active {
            display: block;
        }

        /* Flatpickr Custom High-Visibility Styles */
        .flatpickr-calendar {
            background: #151525 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6) !important;
            border-radius: 12px !important;
        }

        .flatpickr-day.selected,
        .flatpickr-day.selected:hover,
        .flatpickr-day.selected:focus {
            background: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: #000 !important;
            font-weight: bold !important;
        }

        .flatpickr-day {
            color: #eee !important;
            border-radius: 6px !important;
        }

        .flatpickr-day:hover {
            background: rgba(206, 255, 0, 0.15) !important;
            border-color: transparent !important;
            color: #fff !important;
        }

        .flatpickr-current-month,
        .flatpickr-month {
            color: #fff !important;
            fill: #fff !important;
        }

        .flatpickr-weekday {
            color: var(--primary-color) !important;
            font-weight: bold !important;
            font-size: 0.8rem !important;
        }

        .flatpickr-months .flatpickr-prev-month,
        .flatpickr-months .flatpickr-next-month {
            color: var(--primary-color) !important;
            fill: var(--primary-color) !important;
        }
    </style>
</head>

<body>

    <?php if (!empty($message)): ?>
        <div style="position: fixed; top: 20px; right: 20px; background: <?php echo $message_type == 'success' ? '#27ae60' : '#e74c3c'; ?>; color: #fff; padding: 15px 25px; border-radius: 8px; z-index: 10000; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: slideIn 0.5s ease-out;"
            id="admin-toast">
            <i class="fa-solid <?php echo $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"
                style="margin-right: 10px;"></i>
            <?php echo $message; ?>
        </div>
        <script>setTimeout(() => { document.getElementById('admin-toast').style.opacity = '0'; setTimeout(() => document.getElementById('admin-toast').remove(), 500); }, 3000);</script>
        <style>
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }

                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        </style>
    <?php endif; ?>

    <div class="sidebar">
        <a href="index.php" class="logo">GYMFIT ADMIN</a>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="back-to-site"><i class="fa-solid fa-house-chimney"></i>
                    Back to Website</a></li>
            <li><a href="#" class="active" onclick="showSection('overview')"><i class="fa-solid fa-gauge"></i>
                    Overview</a></li>
            <li><a href="#" onclick="showSection('users')"><i class="fa-solid fa-user-shield"></i>Users</a></li>
            <li><a href="#" onclick="showSection('attendance')"><i class="fa-solid fa-calendar-check"></i>
                    Attendance</a></li>
            <li><a href="#" onclick="showSection('plans')"><i class="fa-solid fa-tags"></i> Membership Plans</a></li>
            <li><a href="#" onclick="showSection('queries')"><i class="fa-solid fa-comments"></i> Member Queries</a>
            </li>
            <li><a href="#" onclick="showSection('trainers')"><i class="fa-solid fa-dumbbell"></i> Trainers</a></li>

            <li><a href="#" onclick="showSection('inventory')"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
            </li>
            <li><a href="#" onclick="showSection('shop-staff')"><i class="fa-solid fa-users-gear"></i> Shop Staff</a>
            </li>
            <li><a href="#" onclick="showSection('announcements')"><i class="fa-solid fa-bullhorn"></i>
                    Announcements</a></li>
            <li><a href="#" onclick="showSection('showcase')"><i class="fa-solid fa-images"></i>
                    Website Showcase</a></li>
            <li><a href="#" onclick="showSection('services-section')"><i class="fa-solid fa-list-check"></i>
                    Website Services</a></li>
            <li><a href="#" onclick="showSection('gym-store-mgmt')"><i class="fa-solid fa-shop"></i>
                    Gym Store</a></li>
            <li><a href="#" onclick="showSection('store-orders')" style="position:relative;">
                    <i class="fa-solid fa-truck-ramp-box"></i> Pending Pickups
                    <?php if ($pending_pickups > 0): ?>
                        <span
                            style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:#ff9f00; color:#000; font-size:0.65rem; font-weight:bold; padding:1px 7px; border-radius:20px;"><?php echo $pending_pickups; ?></span>
                    <?php endif; ?>
                </a></li>

        </ul>
        <div style="margin-top: auto;padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="logout.php"
                style="color: #ff4d4d; text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 15px; border-radius: 8px; transition: 0.3s; opacity: 0.8;"
                onmouseover="this.style.opacity='1'; this.style.background='rgba(255, 77, 77, 0.1)';"
                onmouseout="this.style.opacity='0.8'; this.style.background='transparent';">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <div></div> <!-- Spacer for alignment -->
            <div style="text-align: right;">
                <p>Admin</p>
            </div>
        </div>

        <div id="overview" class="dashboard-section active">
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stat-card">
                    <h4>Total Members</h4>
                    <div class="value"><?php echo number_format($total_members); ?> <i class="fa-solid fa-users"
                            style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h4>Gym Trainers</h4>
                    <div class="value"><?php echo number_format($total_trainers); ?> <i class="fa-solid fa-user-ninja"
                            style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h4>Shop Staff</h4>
                    <div class="value"><?php echo number_format($total_shop_staff); ?> <i class="fa-solid fa-users-gear"
                            style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h4
                        style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:5px;">
                        Monthly Revenue
                        <!-- Custom themed month picker -->
                        <div class="rev-month-picker" style="position:relative;">
                            <button onclick="toggleRevPicker(event)" id="rev-picker-btn"
                                style="background: rgba(206,255,0,0.08); border: 1px solid rgba(206,255,0,0.3); color: #ceff00; padding: 3px 10px 3px 9px; border-radius: 20px; cursor: pointer; font-size: 0.68rem; font-family: 'Roboto', sans-serif; display:flex; align-items:center; gap:5px; white-space:nowrap;">
                                <span
                                    id="rev-picker-label"><?php echo date('M Y', strtotime($rev_month_param . '-01')); ?></span>
                                <i class="fa-solid fa-chevron-down" style="font-size:0.55rem;"></i>
                            </button>
                            <div id="rev-picker-dropdown"
                                style="display:none; position:absolute; right:0; top:calc(100% + 6px); background:#0d0d1a; border:1px solid rgba(206,255,0,0.25); border-radius:10px; min-width:110px; overflow:hidden; z-index:9999; box-shadow: 0 8px 24px rgba(0,0,0,0.6);">
                                <?php foreach ($rev_months_list as $rm): ?>
                                    <a href="?rev_month=<?php echo $rm; ?>#overview"
                                        style="display:block; padding:8px 14px; font-size:0.78rem; color:<?php echo $rm === $rev_month_param ? '#ceff00' : '#aaa'; ?>; background:<?php echo $rm === $rev_month_param ? 'rgba(206,255,0,0.1)' : 'transparent'; ?>; text-decoration:none; transition:0.15s;"
                                        onmouseenter="this.style.background='rgba(206,255,0,0.12)'; this.style.color='#ceff00';"
                                        onmouseleave="this.style.background='<?php echo $rm === $rev_month_param ? 'rgba(206,255,0,0.1)' : 'transparent'; ?>'; this.style.color='<?php echo $rm === $rev_month_param ? '#ceff00' : '#aaa'; ?>';">
                                        <?php echo date('M Y', strtotime($rm . '-01')); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </h4>
                    <div class="value">
                        ₹<?php
                        $rev = (float) $monthly_revenue;
                        if ($rev >= 10000000)
                            echo number_format($rev / 10000000, 2) . ' Cr';
                        elseif ($rev >= 100000)
                            echo number_format($rev / 100000, 2) . ' L';
                        elseif ($rev >= 1000)
                            echo number_format($rev / 1000, 1) . 'K';
                        else
                            echo number_format($rev);
                        ?>
                        <i class="fa-solid fa-indian-rupee-sign" style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h4>Equipment Status</h4>
                    <div class="value"><?php echo $equipment_status; ?>% <i class="fa-solid fa-check-double"
                            style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card"
                    style="cursor: pointer; border-left-color: <?php echo $pending_pickups > 0 ? '#ff9f00' : 'var(--primary-color)'; ?>; transition: 0.3s;"
                    onclick="showSection('store-orders')" onmouseenter="this.style.background='rgba(255,159,0,0.08)'"
                    onmouseleave="this.style.background=''">
                    <h4 style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        Pending Pickups
                        <?php if ($pending_pickups > 0): ?>
                            <span
                                style="font-size:0.62rem; color:#ff9f00; background:rgba(255,159,0,0.12); padding:1px 6px; border-radius:10px; border:1px solid rgba(255,159,0,0.3);">Click
                                to View</span>
                        <?php endif; ?>
                    </h4>
                    <div class="value"><?php echo number_format($pending_pickups); ?> <i
                            class="fa-solid fa-truck-ramp-box"
                            style="color: <?php echo $pending_pickups > 0 ? '#ff9f00' : 'var(--primary-color)'; ?>;"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Revenue Growth</h3>
                    <select onchange="location.href='?revenue_year=' + this.value + '#overview'"
                        style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 5px 15px; border-radius: 20px; outline: none; cursor: pointer; font-size: 0.85rem;">
                        <?php foreach ($available_years as $yr): ?>
                            <option value="<?php echo $yr; ?>" <?php echo $yr == $selected_year ? 'selected' : ''; ?>>Year
                                <?php echo $yr; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Store Orders / Pending Pickups -->
        <div id="store-orders" class="dashboard-section">
            <div class="card">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h3><i class="fa-solid fa-truck-ramp-box" style="color:#ff9f00; margin-right:8px;"></i>Pending Store
                        Pickups</h3>
                    <span style="font-size:0.85rem; color:var(--text-gray);">
                        <?php echo $pending_pickups; ?> order(s) awaiting pickup
                    </span>
                </div>

                <?php if ($pending_pickups == 0): ?>
                    <div style="text-align:center; padding:60px 30px; color:var(--text-gray);">
                        <i class="fa-solid fa-circle-check"
                            style="font-size:3rem; color:#ceff00; display:block; margin-bottom:15px; opacity:0.5;"></i>
                        <p style="font-size:1.1rem;">All clear! No pending pickups right now.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Product / Order</th>
                                    <th>Token #</th>
                                    <th>Amount</th>
                                    <th>Order Date</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pickup = mysqli_fetch_assoc($pending_pickups_details)): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:#fff;">
                                                <?php echo htmlspecialchars($pickup['full_name']); ?>
                                            </div>
                                            <div style="font-size:0.78rem; color:var(--text-gray);">
                                                <?php echo htmlspecialchars($pickup['email']); ?>
                                            </div>
                                        </td>
                                        <td style="color:var(--text-gray);">
                                            <?php echo htmlspecialchars($pickup['plan_name'] ?? 'Store Item'); ?>
                                        </td>
                                        <td>
                                            <span
                                                style="background:rgba(255,159,0,0.15); color:#ff9f00; padding:4px 12px; border-radius:20px; font-size:0.85rem; font-weight:bold; border:1px solid rgba(255,159,0,0.3);">
                                                #<?php echo htmlspecialchars($pickup['token_number']); ?>
                                            </span>
                                        </td>
                                        <td style="color:var(--primary-color); font-weight:600;">
                                            ₹<?php echo number_format($pickup['amount'], 2); ?></td>
                                        <td style="color:var(--text-gray); font-size:0.85rem;">
                                            <?php echo date('d M Y, h:i A', strtotime($pickup['created_at'])); ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <a href="?mark_pickup_done=<?php echo $pickup['id']; ?>"
                                                onclick="return confirm('Mark token #<?php echo $pickup['token_number']; ?> as picked up?')"
                                                title="Mark as Picked Up"
                                                style="background:rgba(206,255,0,0.1); color:#ceff00; border:1px solid rgba(206,255,0,0.3); padding:6px 14px; border-radius:20px; font-size:0.8rem; text-decoration:none; transition:0.2s;"
                                                onmouseenter="this.style.background='rgba(206,255,0,0.2)'"
                                                onmouseleave="this.style.background='rgba(206,255,0,0.1)'">
                                                <i class="fa-solid fa-check"></i> Mark Done
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Member Directory & Payments -->
        <div id="users" class="dashboard-section">
            <div class="user-tabs">
                <div class="user-tab active" onclick="switchUserTab('directory', this)">Member Directory</div>
                <div class="user-tab" onclick="switchUserTab('payments', this)">Member Payment History</div>
            </div>

            <div id="tab-directory" class="user-tab-content active">
                <div class="card">
                    <div class="card-header">
                        <h3>Member Directory</h3>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="position: relative; width: 300px;">
                                <i class="fa-solid fa-magnifying-glass"
                                    style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                                <input type="text" id="member-search" onkeyup="searchMembers()"
                                    placeholder="Search members"
                                    style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                            </div>
                            <button class="btn-add" onclick="openAddMemberModal()" style="margin:0;">+ Add New
                                Member</button>
                        </div>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Join Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($members_res) > 0): ?>
                                <?php while ($member = mysqli_fetch_assoc($members_res)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button class="btn-action btn-view"
                                                    onclick='openEditMemberModal(<?php echo json_encode($member); ?>)'>Edit</button>
                                                <form method="POST"
                                                    onsubmit="return confirm('Remove this member permanently?');"
                                                    style="margin:0;">
                                                    <input type="hidden" name="delete_member" value="1">
                                                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                    <button type="submit" class="btn-action btn-delete">Remove</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text-gray);">No members found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>


            <div id="tab-payments" class="user-tab-content">
                <div class="card">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; font-family: 'Oswald', sans-serif;">Member Payment History</h3>
                        <div style="position: relative; width: 300px;">
                            <i class="fa-solid fa-magnifying-glass"
                                style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                            <input type="text" id="payment-search" onkeyup="searchPayments()"
                                placeholder="Search payments"
                                style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Latest Plan</th>
                                    <th>Last Payment</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($grouped_payments) > 0): ?>
                                    <?php foreach ($grouped_payments as $uid => $data):
                                        $latest = $data['history'][0]; // First item is latest
                                
                                        // Find latest valid Membership Plan (ignoring Trainer Appointments & Store)
                                        $latest_plan_name = "None";
                                        foreach ($data['history'] as $h_item) {
                                            $pn = $h_item['plan_name'];
                                            // Filter out non-membership transactions
                                            if (strpos($pn, 'Trainer Appointment') === false && strpos($pn, 'Store:') === false) {
                                                $latest_plan_name = $pn;
                                                break;
                                            }
                                        }

                                        $count = count($data['history']);
                                        ?>
                                        <!-- Main User Row -->
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <td>
                                                <div style="display: flex; flex-direction: column;">
                                                    <span
                                                        style="font-weight: bold; font-size: 1rem; color: #fff;"><?php echo htmlspecialchars($data['user']['full_name']); ?></span>
                                                    <small
                                                        style="color: var(--text-gray); font-size: 0.8rem;"><?php echo htmlspecialchars($data['user']['user_email']); ?></small>
                                                </div>
                                            </td>
                                            <td><span
                                                    style="color: var(--primary-color);"><?php echo htmlspecialchars($latest_plan_name); ?></span>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($latest['created_at'])); ?>
                                                <span
                                                    class="badge <?php echo $latest['status'] == 'completed' ? 'badge-success' : ($latest['status'] == 'pending' ? 'badge-warning' : ''); ?>"
                                                    style="margin-left: 5px; font-size: 0.7rem;">
                                                    <?php echo ucfirst($latest['status']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <button onclick="toggleHistory(<?php echo $uid; ?>)" class="btn-action btn-view"
                                                    style="display: inline-flex; align-items: center; gap: 5px; cursor: pointer; width: auto;">
                                                    <i class="fa-solid fa-clock-rotate-left"></i> History
                                                    (<?php echo $count; ?>) <i class="fa-solid fa-chevron-down"
                                                        id="icon-<?php echo $uid; ?>"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Hidden History Row -->
                                        <tr id="history-<?php echo $uid; ?>"
                                            style="display: none; background: rgba(0,0,0,0.15);">
                                            <td colspan="4" style="padding: 0;">
                                                <div
                                                    style="padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                    <div
                                                        style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                                                        <div style="display: flex; gap: 10px;">
                                                            <div class="hist-tab active-hist"
                                                                onclick="switchHistTab(<?php echo $uid; ?>, 'plans', this)">
                                                                Plans (<?php echo count($data['plans']); ?>)</div>
                                                            <div class="hist-tab"
                                                                onclick="switchHistTab(<?php echo $uid; ?>, 'trainers', this)">
                                                                Trainers (<?php echo count($data['trainers']); ?>)</div>
                                                            <div class="hist-tab"
                                                                onclick="switchHistTab(<?php echo $uid; ?>, 'store', this)">
                                                                Store (<?php echo count($data['store']); ?>)</div>
                                                        </div>
                                                        <input type="text" placeholder="Search history..."
                                                            onkeyup="filterUserHistory(this)"
                                                            style="padding: 6px 12px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.3); color: #fff; font-size: 0.8rem; outline: none; width: 200px;">
                                                    </div>

                                                    <!-- Plans Tab Content -->
                                                    <div id="hist-plans-<?php echo $uid; ?>" class="hist-content active">
                                                        <div
                                                            style="background: rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden;">
                                                            <table style="width: 100%; border-collapse: collapse;">
                                                                <thead>
                                                                    <tr style="background: rgba(255,255,255,0.03);">
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Plan</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Amount</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Method</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Date</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Status</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: right; color: var(--text-gray); text-transform: uppercase;">
                                                                            Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php if (empty($data['plans'])): ?>
                                                                        <tr>
                                                                            <td colspan="6"
                                                                                style="padding: 20px; text-align: center; color: var(--text-gray);">
                                                                                No plan history</td>
                                                                        </tr>
                                                                    <?php else: ?>
                                                                        <?php foreach ($data['plans'] as $payment): ?>
                                                                            <tr
                                                                                style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                                                                <td style="padding: 10px; font-size: 0.9rem;">
                                                                                    <?php echo htmlspecialchars($payment['plan_name']); ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; color: var(--primary-color);">
                                                                                    ₹<?php echo number_format($payment['amount'], 2); ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; text-transform: capitalize;">
                                                                                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; color: #ddd;">
                                                                                    <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                                                                </td>
                                                                                <td style="padding: 10px;">
                                                                                    <span
                                                                                        class="badge <?php echo $payment['status'] == 'completed' ? 'badge-success' : 'badge-warning'; ?>"
                                                                                        style="font-size: 0.65rem;"><?php echo ucfirst($payment['status']); ?></span>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; text-align: right; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                                                                                    <a href="invoice.php?tid=<?php echo $payment['id']; ?>"
                                                                                        target="_blank"
                                                                                        style="color: var(--text-gray); font-size: 1rem;"
                                                                                        title="Download Invoice"><i
                                                                                            class="fa-solid fa-file-pdf"></i></a>
                                                                                    <a href="dashboard_admin.php?delete_transaction=<?php echo $payment['id']; ?>"
                                                                                        onclick="return confirm('Are you sure you want to delete this payment record?');"
                                                                                        style="color: #ff4d4d; font-size: 1rem;"
                                                                                        title="Delete Record"><i
                                                                                            class="fa-solid fa-trash"></i></a>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    <?php endif; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    <!-- Trainers Tab Content -->
                                                    <div id="hist-trainers-<?php echo $uid; ?>" class="hist-content">
                                                        <div
                                                            style="background: rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden;">
                                                            <table style="width: 100%; border-collapse: collapse;">
                                                                <thead>
                                                                    <tr style="background: rgba(255,255,255,0.03);">
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Trainer Appointment</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Amount</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Method</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Date</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Status</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: right; color: var(--text-gray); text-transform: uppercase;">
                                                                            Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php if (empty($data['trainers'])): ?>
                                                                        <tr>
                                                                            <td colspan="6"
                                                                                style="padding: 20px; text-align: center; color: var(--text-gray);">
                                                                                No trainer appointments</td>
                                                                        </tr>
                                                                    <?php else: ?>
                                                                        <?php foreach ($data['trainers'] as $payment): ?>
                                                                            <tr
                                                                                style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                                                                <td style="padding: 10px; font-size: 0.9rem;">
                                                                                    <?php echo htmlspecialchars($payment['plan_name']); ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; color: var(--primary-color);">
                                                                                    ₹<?php echo number_format($payment['amount'], 2); ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; text-transform: capitalize;">
                                                                                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; color: #ddd;">
                                                                                    <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                                                                </td>
                                                                                <td style="padding: 10px;">
                                                                                    <span
                                                                                        class="badge <?php echo $payment['status'] == 'completed' ? 'badge-success' : 'badge-warning'; ?>"
                                                                                        style="font-size: 0.65rem;"><?php echo ucfirst($payment['status']); ?></span>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; text-align: right; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                                                                                    <a href="invoice.php?tid=<?php echo $payment['id']; ?>"
                                                                                        target="_blank"
                                                                                        style="color: var(--text-gray); font-size: 1rem;"
                                                                                        title="Download Invoice"><i
                                                                                            class="fa-solid fa-file-pdf"></i></a>
                                                                                    <a href="dashboard_admin.php?delete_transaction=<?php echo $payment['id']; ?>"
                                                                                        onclick="return confirm('Are you sure you want to delete this payment record?');"
                                                                                        style="color: #ff4d4d; font-size: 1rem;"
                                                                                        title="Delete Record"><i
                                                                                            class="fa-solid fa-trash"></i></a>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    <?php endif; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    <!-- Store Tab Content -->
                                                    <div id="hist-store-<?php echo $uid; ?>" class="hist-content">
                                                        <div
                                                            style="background: rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden;">
                                                            <table style="width: 100%; border-collapse: collapse;">
                                                                <thead>
                                                                    <tr style="background: rgba(255,255,255,0.03);">
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Product Name</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Amount</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Method</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Date</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Status</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: right; color: var(--text-gray); text-transform: uppercase;">
                                                                            Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php if (empty($data['store'])): ?>
                                                                        <tr>
                                                                            <td colspan="6"
                                                                                style="padding: 20px; text-align: center; color: var(--text-gray);">
                                                                                No store purchases</td>
                                                                        </tr>
                                                                    <?php else: ?>
                                                                        <?php foreach ($data['store'] as $payment): ?>
                                                                            <tr
                                                                                style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                                                                <td style="padding: 10px; font-size: 0.9rem;">
                                                                                    <?php echo htmlspecialchars($payment['plan_name']); ?>
                                                                                    <?php if (!empty($payment['token_number'])): ?>
                                                                                        <br><small style="color: var(--text-gray);">Token:
                                                                                            <?php echo $payment['token_number']; ?></small>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; color: var(--primary-color);">
                                                                                    ₹<?php echo number_format($payment['amount'], 2); ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; text-transform: capitalize;">
                                                                                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; color: #ddd;">
                                                                                    <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                                                                </td>
                                                                                <td style="padding: 10px;">
                                                                                    <span
                                                                                        class="badge <?php echo $payment['status'] == 'completed' ? 'badge-success' : 'badge-warning'; ?>"
                                                                                        style="font-size: 0.65rem;"><?php echo ucfirst($payment['status']); ?></span>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; text-align: right; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                                                                                    <a href="invoice.php?tid=<?php echo $payment['id']; ?>"
                                                                                        target="_blank"
                                                                                        style="color: var(--text-gray); font-size: 1rem;"
                                                                                        title="Download Invoice"><i
                                                                                            class="fa-solid fa-file-pdf"></i></a>
                                                                                    <a href="dashboard_admin.php?delete_transaction=<?php echo $payment['id']; ?>"
                                                                                        onclick="return confirm('Are you sure you want to delete this payment record?');"
                                                                                        style="color: #ff4d4d; font-size: 1rem;"
                                                                                        title="Delete Record"><i
                                                                                            class="fa-solid fa-trash"></i></a>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    <?php endif; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: var(--text-gray); padding: 30px;">
                                            <i class="fa-solid fa-receipt"
                                                style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                                            No payment records found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plans -->
        <div id="plans" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Membership Plans</h3>
                    <button class="btn-add" onclick="document.getElementById('add-plan-modal').style.display='flex'">+
                        Add Plan</button>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <?php
                    mysqli_data_seek($plans_res, 0);
                    while ($plan = mysqli_fetch_assoc($plans_res)):
                        ?>
                        <div class="stat-card"
                            style="position: relative; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;">
                            <?php if ($plan['is_popular']): ?>
                                <span class="badge badge-success"
                                    style="position: absolute; top: 15px; right: 15px; background: var(--primary-color); color: #000;">Popular</span>
                            <?php endif; ?>
                            <h4 style="color: var(--primary-color); font-size: 1.4rem; margin-bottom: 5px;">
                                <?php echo htmlspecialchars($plan['name']); ?>
                            </h4>
                            <div style="margin: 15px 0; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px;">
                                <p style="font-size: 1.8rem; font-weight: bold; margin: 0;">
                                    ₹<?php echo number_format($plan['price_monthly']); ?><span
                                        style="font-size: 0.9rem; color: var(--text-gray); font-weight: normal;">/mo</span>
                                </p>
                                <p style="font-size: 1.2rem; color: var(--text-gray); margin-top: 5px;">
                                    ₹<?php echo number_format($plan['price_yearly']); ?><span
                                        style="font-size: 0.8rem;">/yr</span></p>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 10px;">
                                <button class="btn-action btn-view"
                                    style="flex: 1; padding: 12px; font-weight: bold; border-radius: 8px;"
                                    onclick='openEditPlanModal(<?php echo json_encode($plan); ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit Attributes
                                </button>
                                <a href="?delete_plan=<?php echo $plan['id']; ?>" class="btn-action btn-delete"
                                    style="flex: 1; padding: 12px; font-weight: bold; border-radius: 8px; text-align: center; display: inline-block;"
                                    onclick="return confirm('Are you sure you want to delete this plan?')">
                                    <i class="fa-solid fa-trash"></i> Delete Plan
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>



        <!-- Inventory -->
        <div id="inventory" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Gym Inventory & Equipment</h3>
                </div>

                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 15px;">
                    <div style="position: relative; width: 300px;">
                        <i class="fa-solid fa-magnifying-glass"
                            style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                        <input type="text" id="inventory-search" onkeyup="searchInventory()" placeholder="Search items"
                            style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span
                            style="background: #000; color: #fff; padding: 12px 25px; border-radius: 10px; font-weight: bold; font-size: 1rem; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 4px 10px rgba(0,0,0,0.5);">Total
                            Items: <?php echo $inventory_count; ?></span>
                        <button class="btn-add" onclick="openAddInventoryModal()"
                            style="margin: 0; float: none; padding: 12px 25px; border-radius: 10px;">+ Add
                            Item</button>
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table" id="inventory-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Last Maintenance</th>
                                <th>Next Service</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($inventory_query) > 0): ?>
                                <?php
                                mysqli_data_seek($inventory_query, 0); // Reset pointer
                                while ($item = mysqli_fetch_assoc($inventory_query)):
                                    $status_color = ($item['status'] == 'Functional' || $item['status'] == 'Good') ? '#ceff00' : '#ff4d4d';
                                    ?>
                                    <tr>
                                        <td style="font-weight: bold;"><?php echo htmlspecialchars($item['item_name']); ?>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td style="color: <?php echo $status_color; ?>">
                                            <?php echo htmlspecialchars($item['status']); ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($item['last_maintenance'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($item['next_service'])); ?></td>
                                        <td>
                                            <button class="btn-action btn-view"
                                                onclick='openEditInventoryModal(<?php echo json_encode($item); ?>)'>Edit</button>
                                            <a href="?delete_inventory=<?php echo $item['id']; ?>" class="btn-action btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-gray);">No inventory
                                        items
                                        found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gym Store Management -->
        <div id="gym-store-mgmt" class="dashboard-section">
            <div class="user-tabs">
                <div class="user-tab active" onclick="switchStoreTab('categories', this)">Categories</div>
                <div class="user-tab" onclick="switchStoreTab('products', this)">Products</div>
            </div>

            <!-- Categories Section -->
            <div id="store-categories-tab" class="store-tab-content active">
                <div class="card">
                    <div class="card-header">
                        <h3>Product Categories</h3>
                        <button class="btn-add"
                            onclick="document.getElementById('add-cat-modal').style.display='flex'">+ Add
                            Category</button>
                    </div>
                    <div class="data-table-containerslider-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $categories = mysqli_query($link, "SELECT * FROM store_categories ORDER BY name ASC");
                                while ($cat = mysqli_fetch_assoc($categories)):
                                    ?>
                                    <tr>
                                        <td><img src="<?php echo $cat['image']; ?>"
                                                style="width:50px; height:50px; border-radius:5px; object-fit:cover;"></td>
                                        <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cat['description']); ?></td>
                                        <td>
                                            <button class="btn-action btn-view"
                                                onclick='openEditCatModal(<?php echo json_encode($cat); ?>)'>Edit</button>
                                            <a href="?delete_store_category=<?php echo $cat['id']; ?>"
                                                class="btn-action btn-delete"
                                                onclick="return confirm('Deleting a category will also delete its products. Continue?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div id="store-products-tab" class="store-tab-content" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Store Products</h3>
                        <button class="btn-add"
                            onclick="document.getElementById('add-prod-modal').style.display='flex'">+ Add
                            Product</button>
                    </div>
                    <div class="data-table-containerslider-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Category</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $products = mysqli_query($link, "SELECT p.*, c.name as cat_name FROM store_products p JOIN store_categories c ON p.category_id = c.id ORDER BY cat_name ASC, p.name ASC");
                                while ($prod = mysqli_fetch_assoc($products)):
                                    ?>
                                    <tr>
                                        <td><img src="<?php echo $prod['image']; ?>"
                                                style="width:50px; height:50px; border-radius:5px; object-fit:cover;"></td>
                                        <td><span
                                                class="badge badge-warning"><?php echo htmlspecialchars($prod['cat_name']); ?></span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                        <td style="color: var(--primary-color);">
                                            ₹<?php echo number_format($prod['price'], 2); ?></td>
                                        <td>
                                            <span class="badge" id="stock-val-<?php echo $prod['id']; ?>"
                                                style="background: <?php echo $prod['stock_count'] > 0 ? 'rgba(206, 255, 0, 0.1)' : 'rgba(255, 77, 77, 0.1)'; ?>; color: <?php echo $prod['stock_count'] > 0 ? 'var(--primary-color)' : '#ff4d4d'; ?>;">
                                                <?php echo $prod['stock_count'] > 0 ? $prod['stock_count'] . ' in stock' : 'Sold Out'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-action btn-view"
                                                onclick='openEditProdModal(<?php echo json_encode($prod); ?>)'>Edit</button>
                                            <a href="?delete_store_product=<?php echo $prod['id']; ?>"
                                                class="btn-action btn-delete"
                                                onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Modals -->
        <div id="add-cat-modal" class="modal">
            <div class="modal-content">
                <div class="card-header">
                    <h3>Add Category</h3>
                    <button onclick="closeModal('add-cat-modal')"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_store_category" value="1">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="cat_name" class="form-control" requiredmaxlength="50"
                            placeholder="e.g. Proteins, Equipment">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="cat_desc" class="form-control" rows="3" maxlength="255"
                            placeholder="Brief description of the category"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="cat_image" class="form-control" accept="image/*">
                        <small style="color: var(--text-gray);">Supported: JPG, PNG</small>
                    </div>
                    <button type="submit" class="btn-action-modal">Add Category</button>
                </form>
            </div>
        </div>

        <div id="edit-cat-modal" class="modal">
            <div class="modal-content">
                <div class="card-header">
                    <h3>Edit Category</h3>
                    <button onclick="closeModal('edit-cat-modal')"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_store_category" value="1">
                    <input type="hidden" name="cat_id" id="edit-cat-id">
                    <div class="form-group">
                        <label>Category Name</label>
                        <input type="text" name="cat_name" id="edit-cat-name" class="form-control" required
                            maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="cat_desc" id="edit-cat-desc" class="form-control" rows="3"
                            maxlength="255"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image (Optional)</label>
                        <input type="file" name="cat_image" class="form-control" accept="image/*">
                        <small style="color: var(--text-gray);">Leave blank to keep current image</small>
                    </div>
                    <button type="submit" class="btn-action-modal">Update Category</button>
                </form>
            </div>
        </div>

        <div id="add-prod-modal" class="modal">
            <div class="modal-content">
                <div class="card-header">
                    <h3>Add Product</h3>
                    <button onclick="closeModal('add-prod-modal')"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_store_product" value="1">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="prod_cat_id" class="form-control" required>
                            <option value="" disabled selected>Select Category</option>
                            <?php
                            $cats = mysqli_query($link, "SELECT * FROM store_categories ORDER BY name ASC");
                            while ($c = mysqli_fetch_assoc($cats))
                                echo "<option value='" . $c['id'] . "'>" . $c['name'] . "</option>";
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="prod_name" class="form-control" required maxlength="100"
                            placeholder="Enter product name">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Price (₹)</label>
                            <input type="number" step="0.01" name="prod_price" class="form-control" required min="0.01"
                                placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Stock Count</label>
                            <input type="number" name="stock_count" class="form-control" required min="0" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="prod_image" class="form-control" accept="image/*">
                        <small style="color: var(--text-gray);">Supported: JPG, PNG, WEBP</small>
                    </div>
                    <button type="submit" class="btn-action-modal">Add Product</button>
                </form>
            </div>
        </div>

        <div id="edit-prod-modal" class="modal">
            <div class="modal-content">
                <div class="card-header">
                    <h3>Edit Product</h3>
                    <button onclick="closeModal('edit-prod-modal')"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_store_product" value="1">
                    <input type="hidden" name="prod_id" id="edit-prod-id">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="prod_cat_id" id="edit-prod-cat" class="form-control" required>
                            <?php
                            mysqli_data_seek($cats, 0);
                            while ($c = mysqli_fetch_assoc($cats))
                                echo "<option value='" . $c['id'] . "'>" . $c['name'] . "</option>";
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="prod_name" id="edit-prod-name" class="form-control" required
                            maxlength="100">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Price (₹)</label>
                            <input type="number" step="0.01" name="prod_price" id="edit-prod-price" class="form-control"
                                required min="0.01">
                        </div>
                        <div class="form-group">
                            <label>Stock Count</label>
                            <input type="number" name="stock_count" id="edit-prod-stock" class="form-control" required
                                min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Image (Optional)</label>
                        <input type="file" name="prod_image" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn-action-modal">Update Product</button>
                </form>
            </div>
        </div>


        <!-- Trainers -->
        <div id="trainers" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Trainer Management</h3>
                    <button class="btn-add"
                        onclick="document.getElementById('add-trainer-modal').style.display='flex'">+
                        Add
                        Trainer</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($trainers_query) > 0): ?>
                            <?php while ($trainer = mysqli_fetch_assoc($trainers_query)): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $trainer['image'] ? $trainer['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($trainer['name']) . '&background=ceff00&color=1a1a2e'; ?>"
                                            style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                                    </td>
                                    <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($trainer['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-action btn-view"
                                            onclick='openEditTrainerModal(<?php echo json_encode($trainer); ?>)'>Edit</button>
                                        <a href="?delete_trainer=<?php echo $trainer['id']; ?>" class="btn-action btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this trainer?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-gray);">No trainers found.
                                    Add
                                    your first trainer!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Website Showcase -->
        <div id="showcase" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Website Equipment Showcase</h3>
                    <button class="btn-add"
                        onclick="document.getElementById('add-equipment-modal').style.display='flex'">+
                        Add Item</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($equip_query) > 0): ?>
                            <?php while ($eq = mysqli_fetch_assoc($equip_query)): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $eq['image'] ? $eq['image'] : 'assets/images/placeholder.png'; ?>"
                                            style="width: 60px; height: 60px; object-fit: contain; background: rgba(255,255,255,0.05); padding: 5px; border-radius: 5px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($eq['name']); ?></td>
                                    <td><?php echo htmlspecialchars($eq['description']); ?></td>
                                    <td><?php echo $eq['priority']; ?></td>
                                    <td>
                                        <button class="btn-action btn-view"
                                            onclick='openEditEquipmentModal(<?php echo htmlspecialchars(json_encode($eq), ENT_QUOTES, "UTF-8"); ?>)'>Edit</button>
                                        <a href="?delete_equipment=<?php echo $eq['id']; ?>" class="btn-action btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-gray);">No showcase items
                                    found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Website Services -->
        <div id="services-section" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Website Our Services</h3>
                    <button class="btn-add"
                        onclick="document.getElementById('add-service-modal').style.display='flex'">+
                        Add Service</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Icon</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($services_query) > 0): ?>
                            <?php while ($srv = mysqli_fetch_assoc($services_query)): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $srv['icon'] ? $srv['icon'] : 'assets/icons/muscle_custom.png'; ?>"
                                            style="width: 40px; height: 40px; object-fit: contain; background: rgba(255,255,255,0.05); padding: 5px; border-radius: 50%;">
                                    </td>
                                    <td><?php echo htmlspecialchars($srv['title']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($srv['description'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo $srv['priority']; ?></td>
                                    <td>
                                        <button class="btn-action btn-view"
                                            onclick='openEditServiceModal(<?php echo htmlspecialchars(json_encode($srv), ENT_QUOTES, "UTF-8"); ?>)'>Edit</button>
                                        <a href="?delete_service=<?php echo $srv['id']; ?>" class="btn-action btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this service?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-gray);">No services found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Announcements Section -->
        <div id="announcements" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Announcements Management</h3>
                    <button class="btn-add"
                        onclick="document.getElementById('add-announcement-modal').style.display='flex'">+ Post
                        Announcement</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Date Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($ann_query) > 0): ?>
                            <?php while ($ann = mysqli_fetch_assoc($ann_query)): ?>
                                <tr>
                                    <td style="font-weight: bold; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($ann['title']); ?>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($ann['message'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-action btn-view"
                                            onclick='openEditAnnouncementModal(<?php echo htmlspecialchars(json_encode($ann), ENT_QUOTES, "UTF-8"); ?>)'>Edit</button>
                                        <a href="?delete_announcement=<?php echo $ann['id']; ?>" class="btn-action btn-delete"
                                            onclick="return confirm('Delete this announcement?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-gray);">No announcements
                                    found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Announcement Modal -->
        <div id="add-announcement-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:500px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Post New Announcement</h3>
                    <button onclick="document.getElementById('add-announcement-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Title</label>
                        <input type="text" name="title" required maxlength="100"
                            placeholder="e.g., New Equipment Arrival!"
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Message</label>
                        <textarea name="message" rows="4" required maxlength="1000"
                            placeholder="Enter announcement details..."
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px; resize: vertical; font-family: inherit;"></textarea>
                    </div>
                    <button type="submit" name="add_announcement" class="btn-add" style="width:100%;">Post
                        Now</button>
                </form>
            </div>
        </div>


        <!-- Edit Announcement Modal -->
        <div id="edit-announcement-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:500px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Edit Announcement</h3>
                    <button onclick="document.getElementById('edit-announcement-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="announcement_id" id="edit-ann_id">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Title</label>
                        <input type="text" name="title" id="edit-ann_title" required
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Message</label>
                        <textarea name="message" id="edit-ann_message" rows="6" required
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px; resize: vertical; font-family: inherit;"></textarea>
                    </div>
                    <button type="submit" name="edit_announcement" class="btn-add" style="width:100%;">Update
                        Announcement</button>
                </form>
            </div>
        </div>


        <!-- Add Inventory Modal -->
        <div id="add-inventory-modal" class="modal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif; font-size: 1.8rem;">
                        Add
                        Inventory Item</h3>
                    <span onclick="closeModal('add-inventory-modal')"
                        style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="add_inventory" value="1">
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="item_name" class="form-control" required placeholder="e.g. Treadmill">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" class="form-control" required min="1">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Functional">Functional</option>
                            <option value="Good">Good</option>
                            <option value="Service Due">Service Due</option>
                            <option value="Broken">Broken</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Last Maintenance</label>
                        <input type="text" name="last_maintenance" class="form-control date-picker" required
                            placeholder="Select Date">
                    </div>
                    <div class="form-group">
                        <label>Next Service</label>
                        <input type="text" name="next_service" class="form-control date-picker" required
                            placeholder="Select Date">
                    </div>
                    <button type="submit" class="btn-action-modal">Add Item</button>
                </form>
            </div>
        </div>

        <!-- Edit Inventory Modal -->
        <div id="edit-inventory-modal" class="modal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif; font-size: 1.8rem;">
                        Edit
                        Inventory Item</h3>
                    <span onclick="closeModal('edit-inventory-modal')"
                        style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="update_inventory" value="1">
                    <input type="hidden" name="item_id" id="edit-inventory-id">
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="item_name" id="edit-inventory-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="edit-inventory-qty" class="form-control" required
                            min="1">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit-inventory-status" class="form-control">
                            <option value="Functional">Functional</option>
                            <option value="Good">Good</option>
                            <option value="Service Due">Service Due</option>
                            <option value="Broken">Broken</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Last Maintenance</label>
                        <input type="text" name="last_maintenance" id="edit-inventory-last"
                            class="form-control date-picker" required placeholder="Select Date">
                    </div>
                    <div class="form-group">
                        <label>Next Service</label>
                        <input type="text" name="next_service" id="edit-inventory-next" class="form-control date-picker"
                            required placeholder="Select Date">
                    </div>
                    <button type="submit" class="btn-action-modal">Update Item</button>
                </form>
            </div>
        </div>

        <!-- Add Trainer Modal -->
        <div id="add-trainer-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:450px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Add New Trainer</h3>
                    <button onclick="document.getElementById('add-trainer-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="add_trainer" value="1">
                    <!-- Dummy fields to trick browser autofill -->
                    <input type="text" style="display:none">
                    <input type="password" style="display:none">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer
                            Name</label>
                        <input type="text" name="trainer_name" required maxlength="50" placeholder="Full Name"
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Email
                            Address</label>
                        <input type="email" name="trainer_email" required placeholder="trainer@gmail.com"
                            onfocus="this.removeAttribute('readonly');" readonly
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Password</label>
                        <input type="password" name="trainer_password" required minlength="6" maxlength="20"
                            placeholder="Min 6 characters" onfocus="this.removeAttribute('readonly');" readonly
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer
                            Image</label>
                        <div style="display: flex; align-items: center; margin-top: 5px;">
                            <label for="trainer_image_add"
                                style="background: rgba(255,255,255,0.1); color: #fff; padding: 10px 20px; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.2); transition: 0.3s; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.borderColor='var(--primary-color)'; this.style.color='var(--primary-color)';"
                                onmouseout="this.style.borderColor='rgba(255,255,255,0.2)'; this.style.color='#fff';">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Choose File
                            </label>
                            <span id="trainer-file-name"
                                style="margin-left: 15px; color: var(--text-gray); font-size: 0.9rem; font-style: italic;">No
                                file chosen</span>
                            <input type="file" name="trainer_image" id="trainer_image_add" accept="image/*"
                                style="display: none;"
                                onchange="document.getElementById('trainer-file-name').innerText = this.files.length > 0 ? this.files[0].name : 'No file chosen'; document.getElementById('trainer-file-name').style.color = '#fff';">
                        </div>
                        <small style="color: var(--text-gray); display:block; margin-top:5px;">Upload a professional
                            photo for the trainer profile.</small>
                    </div>
                    <button type="submit" name="add_trainer" class="btn-add" style="width:100%;">Save
                        Trainer</button>
                </form>
            </div>
        </div>

        <!-- Shop Staff Section -->
        <div id="shop-staff" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Manage Shop Staff</h3>
                    <button class="btn-add"
                        onclick="document.getElementById('add-shop-staff-modal').style.display='flex'">+ Add New
                        Staff</button>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Added On</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($shop_staff_res) > 0): ?>
                                <?php while ($staff = mysqli_fetch_assoc($shop_staff_res)): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="font-weight: 500; font-family: 'Oswald'; letter-spacing: 0.5px;">
                                            <?php echo htmlspecialchars($staff['full_name']); ?>
                                        </td>
                                        <td style="color: var(--text-gray);"><?php echo htmlspecialchars($staff['email']); ?>
                                        </td>
                                        <td style="color: var(--text-gray); font-size: 0.9rem;">
                                            <?php echo date('M d, Y', strtotime($staff['created_at'])); ?>
                                        </td>
                                        <td
                                            style="text-align: right; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                                            <button class="btn-action btn-view"
                                                onclick='openEditShopStaffModal(<?php echo json_encode($staff); ?>)'
                                                title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <a href="dashboard_admin.php?delete_shop_staff=<?php echo $staff['id']; ?>"
                                                onclick="return confirm('Delete this shop staff account?');" class="btn-delete"
                                                title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: var(--text-gray);">No
                                        shop staff found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Shop Staff Modal -->
        <div id="add-shop-staff-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:450px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Add Shop Staff</h3>
                    <button onclick="document.getElementById('add-shop-staff-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="add_shop_staff" value="1">
                    <!-- Dummy fields to trick browser autofill -->
                    <input type="text" style="display:none">
                    <input type="password" style="display:none">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Full Name</label>
                        <input type="text" name="shop_staff_name" required placeholder="Staff Name" autocomplete="off"
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Email Address</label>
                        <input type="email" name="shop_staff_email" required placeholder="staff@gym.com"
                            autocomplete="off"
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Password</label>
                        <div style="position: relative;">
                            <input type="password" name="shop_staff_password" id="add-staff-pass" required
                                placeholder="Create password" autocomplete="new-password"
                                style="width:100%; padding:12px; padding-right: 45px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                            <i class="fa-solid fa-eye" id="toggle-add-staff-pass"
                                onclick="togglePasswordVisibility('add-staff-pass', 'toggle-add-staff-pass')"
                                style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); cursor: pointer; font-size: 1rem;"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn-add" style="width:100%;">Create Account</button>
                </form>
            </div>
        </div>

        <!-- Edit Shop Staff Modal -->
        <div id="edit-shop-staff-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:450px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Edit Shop Staff</h3>
                    <button onclick="document.getElementById('edit-shop-staff-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" autocomplete="off" enctype="multipart/form-data">
                    <input type="hidden" name="edit_shop_staff" value="1">
                    <input type="hidden" name="shop_staff_id" id="edit-staff-id">
                    <!-- Dummy fields to trick browser autofill -->
                    <input type="text" style="display:none">
                    <input type="password" style="display:none">

                    <div style="text-align: center; margin-bottom: 20px; padding-top: 10px;">
                        <img id="edit-staff-preview" src="assets/images/profiles/default.png"
                            style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                    </div>

                    <div style="margin-bottom: 15px; padding: 0 20px;">
                        <label
                            style="display:block; margin-bottom: 8px; color: var(--text-gray); font-size: 0.9rem;">Profile
                            Image</label>
                        <input type="file" name="shop_staff_image" accept="image/*" onchange="previewStaffImage(this)"
                            style="width:100%; padding:10px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px; font-size: 0.8rem;">
                    </div>

                    <div style="margin-bottom: 15px; padding: 0 20px;">
                        <label
                            style="display:block; margin-bottom: 8px; color: var(--text-gray); font-size: 0.9rem;">Full
                            Name</label>
                        <input type="text" name="shop_staff_name" id="edit-staff-name" required autocomplete="off"
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 15px; padding: 0 20px;">
                        <label
                            style="display:block; margin-bottom: 8px; color: var(--text-gray); font-size: 0.9rem;">Email
                            Address</label>
                        <input type="email" name="shop_staff_email" id="edit-staff-email" required autocomplete="off"
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 15px; padding: 0 20px;">
                        <label
                            style="display:block; margin-bottom: 8px; color: var(--text-gray); font-size: 0.9rem;">Current
                            Password (Admin View)</label>
                        <input type="text" name="shop_staff_current_password" id="edit-staff-current-pass" readonly
                            style="width:100%; padding:12px; background:rgba(0,255,0,0.05); border:1px solid #222; color:var(--primary-color); border-radius:8px; cursor: not-allowed; font-weight: 500;">
                    </div>
                    <div style="margin-bottom: 25px; padding: 0 20px;">
                        <label
                            style="display:block; margin-bottom: 8px; color: var(--text-gray); font-size: 0.9rem;">New
                            Password (leave blank to keep current)</label>
                        <div style="position: relative;">
                            <input type="password" name="shop_staff_new_password" id="edit-staff-new-pass"
                                placeholder="Enter new password" autocomplete="new-password"
                                style="width:100%; padding:12px; padding-right: 45px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                            <i class="fa-solid fa-eye" id="toggle-staff-pass"
                                onclick="togglePasswordVisibility('edit-staff-new-pass', 'toggle-staff-pass')"
                                style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); cursor: pointer; font-size: 1rem;"></i>
                        </div>
                    </div>
                    <div style="padding: 0 20px 20px 20px;">
                        <button type="submit" class="btn-add"
                            style="width:100%; background: var(--admin-accent);">Update Account</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Section -->
        <div id="attendance" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fa-solid fa-calendar-check"
                            style="font-size: 1.5rem; color: var(--primary-color);"></i>
                        <h3 style="margin: 0;">Daily Attendance Management</h3>
                    </div>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <label style="color: var(--text-gray); font-size: 0.9rem;">Date:</label>
                        <div style="position: relative; width: 180px;">
                            <input type="text" id="attendance-date-picker" class="date-picker"
                                value="<?php echo $att_date_view; ?>" placeholder="Select Date"
                                style="width: 100%; padding: 8px 35px 8px 12px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.3); color: #fff; outline:none; cursor:pointer;">
                            <i class="fa-solid fa-calendar-days"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--primary-color); pointer-events: none; opacity: 0.8;"></i>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 25px; padding: 0 10px;">
                    <div class="hist-tab active-hist" onclick="switchAttTab('members', this)">Members
                        (<?php echo mysqli_num_rows($members_res); ?>)</div>
                    <div class="hist-tab" onclick="switchAttTab('trainers-att', this)">Trainers
                        (<?php echo count($trainers_att); ?>)</div>
                    <div class="hist-tab" onclick="switchAttTab('shop-staff-att', this)">Shop Staff
                        (<?php echo count($shop_staff_att); ?>)</div>
                </div>

                <!-- Members Attendance -->
                <div id="att-members" class="att-content active">
                    <div style="background: rgba(0,0,0,0.2); border-radius: 10px; overflow: hidden;">
                        <table class="data-table">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.03);">
                                    <th style="padding: 15px;">Member</th>
                                    <th style="padding: 15px;">Plan</th>
                                    <th style="padding: 15px;">Status Today</th>
                                    <th style="padding: 15px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($members_res, 0);
                                while ($m = mysqli_fetch_assoc($members_res)):
                                    $key = "user_" . $m['id'];
                                    $status = isset($daily_att_map[$key]) ? $daily_att_map[$key] : 'absent';
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                        <td style="padding: 12px 15px;">
                                            <div style="display:flex; align-items:center; gap:12px;">
                                                <img src="<?php echo $m['profile_image'] ? $m['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($m['full_name']) . '&background=ceff00&color=1a1a2e'; ?>"
                                                    style="width:38px; height:38px; border-radius:50%; object-fit:cover; border: 1px solid rgba(255,255,255,0.1);">
                                                <div>
                                                    <div
                                                        style="font-weight:600; font-family: 'Oswald'; letter-spacing: 0.5px;">
                                                        <?php echo htmlspecialchars($m['full_name']); ?>
                                                    </div>
                                                    <div style="font-size:0.75rem; color:var(--text-gray);">
                                                        <?php echo htmlspecialchars($m['email']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 12px 15px; color: var(--text-gray);">
                                            <?php echo htmlspecialchars($m['membership_plan']); ?>
                                        </td>
                                        <td style="padding: 12px 15px;">
                                            <span id="status-user-<?php echo $m['id']; ?>"
                                                class="badge <?php echo $status == 'present' ? 'badge-success' : 'badge-warning'; ?>"
                                                style="font-size: 0.7rem; padding: 4px 10px;">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 15px; text-align: right;">
                                            <button onclick="toggleUserAtt(<?php echo $m['id']; ?>, 'user', this)"
                                                class="btn-action <?php echo $status == 'present' ? 'btn-delete' : 'btn-view'; ?>"
                                                style="font-size: 0.75rem; padding: 6px 14px; display:inline-flex; width: auto; min-width: 110px; justify-content: center;">
                                                <?php echo $status == 'present' ? '<i class="fa-solid fa-xmark" style="margin-right:5px;"></i> Mark Absent' : '<i class="fa-solid fa-check" style="margin-right:5px;"></i> Mark Present'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Trainers Attendance -->
                <div id="att-trainers-att" class="att-content">
                    <div style="background: rgba(0,0,0,0.2); border-radius: 10px; overflow: hidden;">
                        <table class="data-table">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.03);">
                                    <th style="padding: 15px;">Trainer</th>
                                    <th style="padding: 15px;">Status Today</th>
                                    <th style="padding: 15px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainers_att as $s):
                                    $key = "trainer_" . $s['id'];
                                    $status = isset($daily_att_map[$key]) ? $daily_att_map[$key] : 'absent';
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                        <td style="padding: 12px 15px;">
                                            <div style="display:flex; align-items:center; gap:12px;">
                                                <div
                                                    style="width:38px; height:38px; border-radius:50%; background:rgba(206, 255, 0, 0.1); display:flex; align-items:center; justify-content:center; color:var(--primary-color); border: 1px solid rgba(206, 255, 0, 0.2);">
                                                    <i class="fa-solid fa-dumbbell"></i>
                                                </div>
                                                <div>
                                                    <div
                                                        style="font-weight:600; font-family: 'Oswald'; letter-spacing: 0.5px;">
                                                        <?php echo htmlspecialchars($s['full_name']); ?>
                                                    </div>
                                                    <div style="font-size:0.75rem; color:var(--text-gray);">
                                                        <?php echo htmlspecialchars($s['email']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 12px 15px;">
                                            <span id="status-trainer-<?php echo $s['id']; ?>"
                                                class="badge <?php echo $status == 'present' ? 'badge-success' : 'badge-warning'; ?>"
                                                style="font-size: 0.7rem; padding: 4px 10px;">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 15px; text-align: right;">
                                            <button onclick="toggleUserAtt(<?php echo $s['id']; ?>, 'trainer', this)"
                                                class="btn-action <?php echo $status == 'present' ? 'btn-delete' : 'btn-view'; ?>"
                                                style="font-size: 0.75rem; padding: 6px 14px; display:inline-flex; width: auto; min-width: 110px; justify-content: center;">
                                                <?php echo $status == 'present' ? '<i class="fa-solid fa-xmark" style="margin-right:5px;"></i> Mark Absent' : '<i class="fa-solid fa-check" style="margin-right:5px;"></i> Mark Present'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Shop Staff Attendance -->
                <div id="att-shop-staff-att" class="att-content">
                    <div style="background: rgba(0,0,0,0.2); border-radius: 10px; overflow: hidden;">
                        <table class="data-table">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.03);">
                                    <th style="padding: 15px;">Shop Staff</th>
                                    <th style="padding: 15px;">Status Today</th>
                                    <th style="padding: 15px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shop_staff_att as $s):
                                    $key = "user_" . $s['id'];
                                    $status = isset($daily_att_map[$key]) ? $daily_att_map[$key] : 'absent';
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                        <td style="padding: 12px 15px;">
                                            <div style="display:flex; align-items:center; gap:12px;">
                                                <div
                                                    style="width:38px; height:38px; border-radius:50%; background:rgba(206, 255, 0, 0.1); display:flex; align-items:center; justify-content:center; color:var(--primary-color); border: 1px solid rgba(206, 255, 0, 0.2);">
                                                    <i class="fa-solid fa-shop"></i>
                                                </div>
                                                <div>
                                                    <div
                                                        style="font-weight:600; font-family: 'Oswald'; letter-spacing: 0.5px;">
                                                        <?php echo htmlspecialchars($s['full_name']); ?>
                                                    </div>
                                                    <div style="font-size:0.75rem; color:var(--text-gray);">
                                                        <?php echo htmlspecialchars($s['email']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 12px 15px;">
                                            <span id="status-user-<?php echo $s['id']; ?>"
                                                class="badge <?php echo $status == 'present' ? 'badge-success' : 'badge-warning'; ?>"
                                                style="font-size: 0.7rem; padding: 4px 10px;">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 15px; text-align: right;">
                                            <button onclick="toggleUserAtt(<?php echo $s['id']; ?>, 'user', this)"
                                                class="btn-action <?php echo $status == 'present' ? 'btn-delete' : 'btn-view'; ?>"
                                                style="font-size: 0.75rem; padding: 6px 14px; display:inline-flex; width: auto; min-width: 110px; justify-content: center;">
                                                <?php echo $status == 'present' ? '<i class="fa-solid fa-xmark" style="margin-right:5px;"></i> Mark Absent' : '<i class="fa-solid fa-check" style="margin-right:5px;"></i> Mark Present'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Member Queries Section -->
        <div id="queries" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Member Inquiries</h3>
                </div>
                <div class="video-list" style="display: flex; flex-direction: column; gap: 15px;">
                    <?php if (mysqli_num_rows($queries_res) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($queries_res)): ?>
                            <div
                                style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; border-left: 5px solid <?php echo $row['status'] == 'pending' ? 'var(--primary-color)' : '#00ff00'; ?>; position: relative;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                    <div>
                                        <h4 style="color: #fff; margin-bottom: 3px; font-family: 'Oswald', sans-serif;">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </h4>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fa-solid fa-envelope"
                                                style="font-size: 0.8rem; color: var(--primary-color);"></i>
                                            <span
                                                style="color: var(--text-gray); font-size: 0.85rem;"><?php echo htmlspecialchars($row['email']); ?></span>
                                        </div>
                                    </div>
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                        <span
                                            class="badge <?php echo $row['status'] == 'pending' ? 'badge-warning' : 'badge-success'; ?>"
                                            style="font-size: 0.7rem; padding: 4px 10px; border-radius: 20px;">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('Permanently delete this inquiry?');">
                                            <input type="hidden" name="delete_query" value="1">
                                            <input type="hidden" name="query_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit"
                                                style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 0.9rem; padding: 5px;"
                                                title="Delete Inquiry">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div
                                    style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                                    <p style="font-size: 0.95rem; color: #eee; line-height: 1.5; font-style: italic;">
                                        "<?php echo htmlspecialchars($row['message']); ?>"</p>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <small style="color: var(--text-gray); font-size: 0.8rem;">
                                        <i class="fa-regular fa-clock" style="margin-right: 5px;"></i>
                                        <?php echo date('M d, Y | g:i A', strtotime($row['created_at'])); ?>
                                    </small>

                                    <?php if ($row['status'] == 'pending'): ?>
                                        <button class="btn-sm btn-edit"
                                            style="margin: 0; padding: 8px 15px; border-radius: 6px; display: flex; align-items: center; gap: 6px;"
                                            onclick='openReplyModal(<?php echo json_encode($row); ?>)'>
                                            <i class="fa-solid fa-reply"></i> Reply via Email
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if ($row['status'] == 'resolved'): ?>
                                    <div
                                        style="margin-top: 15px; padding: 12px; background: rgba(161, 212, 35, 0.05); border: 1px dashed rgba(161, 212, 35, 0.3); border-radius: 8px;">
                                        <strong
                                            style="color: var(--primary-color); display: block; font-size: 0.8rem; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px;">
                                            <i class="fa-solid fa-check-double"></i> Staff Response:
                                        </strong>
                                        <p style="font-size: 0.9rem; color: #ddd; line-height: 1.4;">
                                            "<?php echo htmlspecialchars($row['reply']); ?>"</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: var(--text-gray); font-style: italic;">No inquiries found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reply Modal -->
        <div id="reply-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:600px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Reply to Member</h3>
                    <button onclick="document.getElementById('reply-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <div style="margin-bottom: 15px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 5px;">Member's Question:
                    </p>
                    <p id="reply-question"
                        style="font-size: 0.9rem; line-height: 1.4; font-style: italic; color: #fff;">
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="reply_query" value="1">
                    <input type="hidden" name="query_id" id="reply-id">
                    <input type="hidden" name="user_email" id="reply-email">
                    <input type="hidden" name="user_name" id="reply-name">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Your
                            Reply</label>
                        <textarea name="reply_content" rows="5" required
                            style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid #333; color: #fff; border-radius: 8px; font-family: inherit;"
                            placeholder="Type your response here..."></textarea>
                    </div>
                    <button type="submit" class="btn-add" style="width: 100%;">Send Reply</button>
                </form>
            </div>
        </div>

        <div id="edit-trainer-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:450px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3 id="edit-trainer-title">Edit Trainer Details</h3>
                    <button onclick="document.getElementById('edit-trainer-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data" autocomplete="off">
                    <!-- Dummy fields to trick browser autofill -->
                    <input type="text" style="display:none">
                    <input type="password" style="display:none">
                    <input type="hidden" name="edit_trainer" value="1">
                    <input type="hidden" name="trainer_id" id="edit-trainer-id">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Full Name</label>
                        <input type="text" name="trainer_name" id="edit-trainer-name" required
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Email
                            Address</label>
                        <input type="email" name="trainer_email" id="edit-trainer-email" required
                            onfocus="this.removeAttribute('readonly');" readonly
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>

                    <!-- Current Password -->
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Current
                            Password</label>
                        <div style="position: relative;">
                            <input type="password" value="" readonly id="edit-trainer-current-pass"
                                style="width:100%; padding:12px; padding-right: 40px; background:rgba(255,255,255,0.05); border:1px solid #333; color:#fff; border-radius:8px; cursor: default;">
                            <i class="fa-solid fa-eye" id="toggleTrainerCurrentPass"
                                onclick="toggleStaffPassword('edit-trainer-current-pass', 'toggleTrainerCurrentPass')"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-gray);"></i>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">New Password
                            (leave
                            blank to keep current)</label>
                        <div style="position: relative;">
                            <input type="password" name="trainer_password" id="edit-trainer-new-password"
                                placeholder="Enter new password" onfocus="this.removeAttribute('readonly');" readonly
                                minlength="6" maxlength="20"
                                style="width:100%; padding:12px; padding-right: 40px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                            <i class="fa-solid fa-eye" id="toggleTrainerEditPass"
                                onclick="toggleStaffPassword('edit-trainer-new-password', 'toggleTrainerEditPass')"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-gray);"></i>
                        </div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer Image
                            (Optional)</label>
                        <div style="display: flex; align-items: center; margin-top: 5px;">
                            <label for="edit_trainer_image"
                                style="background: rgba(255,255,255,0.1); color: #fff; padding: 10px 20px; border-radius: 8px; cursor: pointer; border: 1px solid rgba(255,255,255,0.2); transition: 0.3s; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.borderColor='var(--primary-color)'; this.style.color='var(--primary-color)';"
                                onmouseout="this.style.borderColor='rgba(255,255,255,0.2)'; this.style.color='#fff';">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Choose New File
                            </label>
                            <span id="edit-trainer-file-name"
                                style="margin-left: 15px; color: var(--text-gray); font-size: 0.9rem; font-style: italic;">No
                                file chosen</span>
                            <input type="file" name="trainer_image" id="edit_trainer_image" accept="image/*"
                                style="display: none;"
                                onchange="document.getElementById('edit-trainer-file-name').innerText = this.files.length > 0 ? this.files[0].name : 'No file chosen'; document.getElementById('edit-trainer-file-name').style.color = '#fff';">
                        </div>
                        <small style="color: var(--text-gray); display:block; margin-top:5px;">Upload a new photo
                            only
                            if you want to change the current one.</small>
                    </div>
                    <button type="submit" name="edit_trainer" class="btn-add" style="width:100%;">Update
                        Trainer</button>
                </form>
            </div>
        </div>

        <!-- Add Member Modal -->
        <div id="add-member-modal" class="modal">
            <div class="modal-content"
                style="background: #1a1a2e; border: 1px solid rgba(255,255,255,0.05); padding: 40px; border-radius: 20px;">
                <h3 style="margin-bottom: 35px; text-align: center; color: #fff; font-size: 2rem; font-weight: 700;">
                    Add New Member</h3>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="add_member" value="1">

                    <!-- Dummy fields to trick browser autofill -->
                    <input type="text" style="display:none">
                    <input type="password" style="display:none">

                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px; display: block;">Full
                            Name</label>
                        <input type="text" name="full_name" class="form-control" required
                            placeholder="Member's full name" autocomplete="off"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                    </div>
                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px; display: block;">Email
                            Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="member@gmail.com"
                            autocomplete="off"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                    </div>
                    <div class="form-group">
                        <label
                            style="color: #fff; font-weight: 500; margin-bottom: 10px; display: block;">Password</label>
                        <div class="pass-wrapper">
                            <input type="password" name="password" id="add-pass" class="form-control" required
                                placeholder="Minimum 6 characters" autocomplete="new-password" minlength="6"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                            <i class="fa-solid fa-eye pass-toggle" onclick="togglePass('add-pass', this)"
                                style="right: 15px;"></i>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px; margin-top: 40px;">
                        <button type="submit" class="btn-action-modal"
                            style="flex: 2; font-size: 1.1rem; padding: 18px; border-radius: 15px;">Create
                            Account</button>
                        <button type="button" class="btn-action-modal"
                            style="flex: 1; background: #333; color: #fff; font-size: 1.1rem; padding: 18px; border-radius: 15px;"
                            onclick="closeModal('add-member-modal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Plan Modal -->
        <div id="add-plan-modal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif;">Add New Plan</h3>
                    <span onclick="closeModal('add-plan-modal')"
                        style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="add_plan" value="1">

                    <div class="form-group">
                        <label>Plan Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Pro"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Monthly Price (₹)</label>
                            <input type="number" name="price_monthly" class="form-control" required step="0.01" min="0"
                                placeholder="0.00"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                        </div>
                        <div class="form-group">
                            <label>Yearly Price (₹)</label>
                            <input type="number" name="price_yearly" class="form-control" required step="0.01" min="0"
                                placeholder="0.00"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="is_popular" style="width: 18px; height: 18px;">
                            <span style="color: #fff;">Mark as "Popular"</span>
                        </label>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn-action-modal">Create Plan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Plan Modal -->
        <div id="edit-plan-modal" class="modal">
            <div class="modal-content"
                style="max-width: 850px; width: 95%; max-height: 90vh; overflow-y: auto; background: #1a1a2e; padding: 40px; border-radius: 20px; scrollbar-width: thin; scrollbar-color: var(--primary-color) #1a1a2e;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                    <h3 id="edit-plan-title"
                        style="color: var(--primary-color); font-family: 'Oswald', sans-serif; font-size: 2rem;">
                        Edit
                        Plan</h3>
                    <span onclick="closeModal('edit-plan-modal')"
                        style="cursor:pointer; font-size:1.8rem; color:#fff;">&times;</span>
                </div>
                <form method="POST" onsubmit="checkPendingAttribute()">
                    <input type="hidden" name="update_plan" value="1">
                    <input type="hidden" name="plan_id" id="edit-plan-id">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label style="color: #fff; display: block; margin-bottom: 8px;">Plan Name</label>
                            <input type="text" name="name" id="edit-plan-name" class="form-control" required
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px;">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end; padding-bottom: 12px;">
                            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; color: #fff;">
                                <input type="checkbox" name="is_popular" id="edit-plan-popular"
                                    style="width: 22px; height: 22px; cursor: pointer;">
                                Set as Popular (Badge)
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="color: #fff; display: block; margin-bottom: 8px;">Monthly Price
                                (₹)</label>
                            <input type="number" name="price_monthly" id="edit-plan-monthly" class="form-control"
                                required step="0.01" min="0"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px;">
                        </div>
                        <div class="form-group">
                            <label style="color: #fff; display: block; margin-bottom: 8px;">Yearly Price (₹)</label>
                            <input type="number" name="price_yearly" id="edit-plan-yearly" class="form-control" required
                                step="0.01" min="0"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px;">
                        </div>
                    </div>

                    <h4
                        style="margin: 30px 0 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; color: #fff; font-family: 'Oswald', sans-serif; letter-spacing: 1px;">
                        PLAN ATTRIBUTES & FEATURES</h4>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 30px;">
                        <input type="hidden" name="hidden_features" id="edit-plan-hidden-json">

                        <div id="div-gym_access"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="gym_access" id="feat-gym"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_gym_access" id="lab-gym" value="Gym Access"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-gym').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-gym_access"
                                    onclick="toggleStandardFeature('gym_access')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-free_locker"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="free_locker" id="feat-locker"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_free_locker" id="lab-locker" value="Free Locker"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-locker').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-free_locker"
                                    onclick="toggleStandardFeature('free_locker')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-group_class"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="group_class" id="feat-group"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_group_class" id="lab-group" value="Group Class"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-group').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-group_class"
                                    onclick="toggleStandardFeature('group_class')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-personal_trainer"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="personal_trainer" id="feat-trainer"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_personal_trainer" id="lab-trainer" value="Personal Trainer"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-trainer').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-personal_trainer"
                                    onclick="toggleStandardFeature('personal_trainer')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-protein_drinks_monthly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="protein_drinks_monthly" id="feat-protein-m"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_protein_drinks_monthly" id="lab-protein-m"
                                    value="Protein Drinks (Monthly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-protein-m').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-protein_drinks_monthly"
                                    onclick="toggleStandardFeature('protein_drinks_monthly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-protein_drinks_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="protein_drinks_yearly" id="feat-protein-y"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_protein_drinks_yearly" id="lab-protein-y"
                                    value="Protein Drinks (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-protein-y').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-protein_drinks_yearly"
                                    onclick="toggleStandardFeature('protein_drinks_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-customized_workout_plan"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="customized_workout_plan" id="feat-workout"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_customized_workout_plan" id="lab-workout"
                                    value="Customized Workout Plan"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-workout').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-customized_workout_plan"
                                    onclick="toggleStandardFeature('customized_workout_plan')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-diet_consultation_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="diet_consultation_yearly" id="feat-diet"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_diet_consultation_yearly" id="lab-diet"
                                    value="Diet Consultation (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-diet').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-diet_consultation_yearly"
                                    onclick="toggleStandardFeature('diet_consultation_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-personal_locker_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="personal_locker_yearly" id="feat-p-locker"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_personal_locker_yearly" id="lab-p-locker"
                                    value="Personal Locker (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-p-locker').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-personal_locker_yearly"
                                    onclick="toggleStandardFeature('personal_locker_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-guest_pass_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="guest_pass_yearly" id="feat-guest"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_guest_pass_yearly" id="lab-guest"
                                    value="1 Guest Pass/Mo (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-guest').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-guest_pass_yearly"
                                    onclick="toggleStandardFeature('guest_pass_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-nutrition_guide_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="nutrition_guide_yearly" id="feat-nutrition"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_nutrition_guide_yearly" id="lab-nutrition"
                                    value="Nutrition Guide (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-nutrition').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-nutrition_guide_yearly"
                                    onclick="toggleStandardFeature('nutrition_guide_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="custom-attributes-container" style="display: contents;">
                            <!-- Custom attributes will be injected here -->
                        </div>
                    </div>

                    <input type="hidden" name="custom_attributes" id="edit-plan-custom-json">

                    <div
                        style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                        <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 10px;">Add New
                            Attribute
                        </p>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" id="new-attr-name" placeholder="Feature name (e.g. Free WiFi)"
                                class="form-control"
                                style="flex: 2; height: 40px; font-size: 0.9rem; background: rgba(0,0,0,0.2);">
                            <label
                                style="color: #eee; font-size: 0.8rem; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="new-attr-monthly" style="width: 16px; height: 16px;">
                                Monthly
                            </label>
                            <label
                                style="color: #eee; font-size: 0.8rem; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="new-attr-yearly" style="width: 16px; height: 16px;">
                                Yearly
                            </label>
                            <button type="button" id="btn-add-attr" onclick="addNewAttribute()"
                                style="background: var(--primary-color); color: #000; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 0.8rem;">Add</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-action-modal"
                        style="margin-top: 35px; background: var(--primary-color); color: #000; font-size: 1.15rem; padding: 18px;">Update
                        Plan Attributes</button>
                </form>
            </div>
        </div>

        <!-- Edit Member Modal -->
        <div id="edit-member-modal" class="modal">
            <div class="modal-content"
                style="background: #1a1a2e; border: 1px solid rgba(255,255,255,0.05); padding: 40px;">
                <h3 style="margin-bottom: 25px; text-align: center; color: #fff; font-size: 1.8rem; font-weight: 700;">
                    Edit Member Details</h3>
                <img id="edit-member-preview" src="" alt="Profile" class="edit-member-img"
                    style="width: 80px; height: 80px; margin-bottom: 20px;">
                <form method="POST" autocomplete="off">
                    <!-- Fake fields to prevent autofill -->
                    <input type="text" style="display:none" name="fake_email_mem_edit">
                    <input type="password" style="display:none" name="fake_pass_mem_edit">

                    <input type="hidden" name="update_member" value="1">
                    <input type="hidden" name="member_id" id="edit-member-id">
                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px;">Full Name</label>
                        <input type="text" name="full_name" id="edit-member-name" class="form-control" required
                            style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 15px;">
                    </div>
                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px;">Email Address</label>
                        <input type="email" name="email" id="edit-member-email" class="form-control" required
                            style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 15px;">
                    </div>
                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px;">Current Password</label>
                        <div class="pass-wrapper">
                            <input type="password" id="edit-member-current-pass" class="form-control" readonly
                                style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 15px; color: var(--primary-color);">
                            <i class="fa-solid fa-eye pass-toggle" id="toggleMemberCurrentPass"
                                onclick="togglePass('edit-member-current-pass', this)" style="right: 15px;"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px;">Reset Password (blank to
                            keep
                            current)</label>
                        <div class="pass-wrapper">
                            <input type="password" name="password" id="edit-pass" class="form-control"
                                placeholder="New password" minlength="6" autocomplete="new-password"
                                style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 15px;">
                            <i class="fa-solid fa-eye pass-toggle" onclick="togglePass('edit-pass', this)"
                                style="right: 15px;"></i>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px; margin-top: 40px;">
                        <button type="submit" class="btn-action-modal"
                            style="flex: 2.5; font-size: 1.1rem; padding: 15px;">Save Changes</button>
                        <button type="button" class="btn-action-modal"
                            style="flex: 1; background: #333; color: #fff; font-size: 1.1rem; padding: 15px;"
                            onclick="closeModal('edit-member-modal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Add Service Modal -->
        <div id="add-service-modal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif;">Add New Service</h3>
                    <span onclick="closeModal('add-service-modal')"
                        style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_service" value="1">
                    <div class="form-group">
                        <label>Service Title</label>
                        <input type="text" name="service_title" class="form-control" required
                            placeholder="e.g. Yoga Class"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="service_desc" class="form-control" required rows="4"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Display Priority</label>
                        <input type="number" name="service_priority" class="form-control" value="0"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Icon / Image</label>
                        <input type="file" name="service_icon" class="form-control" required accept="image/*"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn-action-modal">Save Service</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Service Modal -->
        <div id="edit-service-modal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif;">Edit Service</h3>
                    <span onclick="closeModal('edit-service-modal')"
                        style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_service" value="1">
                    <input type="hidden" name="service_id" id="edit-service-id">
                    <div class="form-group">
                        <label>Service Title</label>
                        <input type="text" name="service_title" id="edit-service-title" class="form-control" required
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="service_desc" id="edit-service-desc" class="form-control" required rows="4"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Display Priority</label>
                        <input type="number" name="service_priority" id="edit-service-priority" class="form-control"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Icon / Image (leave blank to keep current)</label>
                        <input type="file" name="service_icon" class="form-control" accept="image/*"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn-action-modal">Update Service</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Equipment Modal -->
        <div id="add-equipment-modal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif;">Add Showcase Item</h3>
                    <span onclick="closeModal('add-equipment-modal')"
                        style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_equipment" value="1">
                    <div class="form-group">
                        <label>Equipment Name</label>
                        <input type="text" name="eq_name" class="form-control" required placeholder="e.g. Dumbbell"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="eq_desc" class="form-control" required
                            placeholder="e.g. Adjustable weights"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Priority (Lower numbers show first)</label>
                        <input type="number" name="eq_priority" class="form-control" value="0"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Equipment Image</label>
                        <input type="file" name="eq_image" class="form-control" required accept="image/*"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn-action-modal">Save Item</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Equipment Modal -->
        <div id="edit-equipment-modal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif;">Edit Showcase Item</h3>
                    <span onclick="closeModal('edit-equipment-modal')"
                        style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_equipment" value="1">
                    <input type="hidden" name="eq_id" id="edit-eq-id">
                    <div class="form-group">
                        <label>Equipment Name</label>
                        <input type="text" name="eq_name" id="edit-eq-name" class="form-control" required
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="eq_desc" id="edit-eq-desc" class="form-control" required
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <input type="number" name="eq_priority" id="edit-eq-priority" class="form-control"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div class="form-group">
                        <label>Equipment Image (leave blank to keep current)</label>
                        <input type="file" name="eq_image" class="form-control" accept="image/*"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn-action-modal">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </div> <!-- End Main Content -->

    <script>
        function showSection(sectionId, updateHistory = true) {
            // Prevent default anchor behavior if triggered by click
            if (typeof event !== 'undefined' && event && event.type === 'click') {
                event.preventDefault();
            }

            // Update URL to include the section hash for copy-pasting
            if (updateHistory) {
                if (history.pushState) {
                    history.pushState(null, null, '#' + sectionId);
                } else {
                    window.location.hash = sectionId;
                }
            }

            document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
            const target = document.getElementById(sectionId);
            if (target) target.classList.add('active');

            // Handle if the call came from a sidebar click (has currentTarget)
            if (event && event.currentTarget && event.currentTarget.classList) {
                event.currentTarget.classList.add('active');
            } else {
                // Fallback to find link by onclick including sectionId
                document.querySelectorAll('.sidebar-menu a').forEach(a => {
                    const onclick = a.getAttribute('onclick');
                    if (onclick && onclick.includes(`showSection('${sectionId}')`)) {
                        a.classList.add('active');
                    }
                });
            }
        }

        // Initialize from URL hash
        window.addEventListener('DOMContentLoaded', function () {
            const hash = window.location.hash.substring(1);
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('section');

            if (sectionParam) {
                showSection(sectionParam, false);
            } else if (hash) {
                showSection(hash, false);
            } else {
                const activeSec = document.querySelector('.dashboard-section.active') || document.querySelector('.dashboard-section');
                if (activeSec) showSection(activeSec.id, false);
            }

            // AUTO-OPEN USER HISTORY if open_uid is present
            const openUid = urlParams.get('open_uid');
            if (openUid && hash === 'users') {
                const paymentsTabBtn = document.querySelector('.user-tab:nth-child(2)');
                if (paymentsTabBtn) switchUserTab('payments', paymentsTabBtn);

                setTimeout(() => {
                    const histRow = document.getElementById('history-' + openUid);
                    if (histRow && histRow.style.display === 'none') {
                        toggleHistory(openUid);
                        histRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 600);
            }
        });

        // Handle Back/Forward
        window.addEventListener('popstate', function (event) {
            const hash = window.location.hash.substring(1);
            if (hash) {
                showSection(hash, false);
            } else {
                const activeSec = document.querySelector('.dashboard-section'); // First one as default
                if (activeSec) showSection(activeSec.id, false);
            }
        });

        function openEditTrainerModal(trainer) {
            document.getElementById('edit-trainer-title').innerText = 'Edit ' + trainer.name + ' Details';
            document.getElementById('edit-trainer-id').value = trainer.id;
            document.getElementById('edit-trainer-name').value = trainer.name;
            document.getElementById('edit-trainer-email').value = trainer.email || '';
            document.getElementById('edit-trainer-new-password').value = '';

            // Reset Visibility Icons and Types
            const currentPassField = document.getElementById('edit-trainer-current-pass');
            const currentPassIcon = document.getElementById('toggleTrainerCurrentPass');
            const newPassField = document.getElementById('edit-trainer-new-password');
            const newPassIcon = document.getElementById('toggleTrainerEditPass');

            // Reset new password field to hidden
            newPassField.type = "password";
            newPassIcon.classList.remove('fa-eye-slash');
            newPassIcon.classList.add('fa-eye');

            // Show visible password if available
            if (trainer.visible_password && trainer.visible_password.trim() !== '') {
                currentPassField.value = trainer.visible_password;
                currentPassField.style.color = "#fff";
                currentPassField.type = "password";
                currentPassIcon.classList.remove('fa-eye-slash');
                currentPassIcon.classList.add('fa-eye');
                currentPassIcon.style.display = "block";
            } else {
                currentPassField.value = "Not Set";
                currentPassField.style.color = "#ff4d4d";
                currentPassField.type = "text";
                currentPassIcon.style.display = "none"; // Hide eye icon if nothing to toggle
            }

            document.getElementById('edit-trainer-modal').style.display = 'flex';
        }

        function openEditEquipmentModal(eq) {
            document.getElementById('edit-eq-id').value = eq.id;
            document.getElementById('edit-eq-name').value = eq.name;
            document.getElementById('edit-eq-desc').value = eq.description;
            document.getElementById('edit-eq-priority').value = eq.priority;
            document.getElementById('edit-equipment-modal').style.display = 'flex';
        }

        function openEditServiceModal(srv) {
            document.getElementById('edit-service-id').value = srv.id;
            document.getElementById('edit-service-title').value = srv.title;
            document.getElementById('edit-service-desc').value = srv.description;
            document.getElementById('edit-service-priority').value = srv.priority;
            document.getElementById('edit-service-modal').style.display = 'flex';
        }

        function openEditAnnouncementModal(ann) {
            document.getElementById('edit-ann_id').value = ann.id;
            document.getElementById('edit-ann_title').value = ann.title;
            document.getElementById('edit-ann_message').value = ann.message;
            document.getElementById('edit-announcement-modal').style.display = 'flex';
        }

        function openAddInventoryModal() {
            document.getElementById('add-inventory-modal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function openEditInventoryModal(item) {
            document.getElementById('edit-inventory-id').value = item.id;
            document.getElementById('edit-inventory-name').value = item.item_name;
            document.getElementById('edit-inventory-qty').value = item.quantity;
            document.getElementById('edit-inventory-status').value = item.status;

            // Set values for date inputs using flatpickr instance if available
            const lastInput = document.getElementById('edit-inventory-last');
            const nextInput = document.getElementById('edit-inventory-next');

            if (lastInput._flatpickr) lastInput._flatpickr.setDate(item.last_maintenance);
            else lastInput.value = item.last_maintenance;

            if (nextInput._flatpickr) nextInput._flatpickr.setDate(item.next_service);
            else nextInput.value = item.next_service;

            document.getElementById('edit-inventory-modal').style.display = 'flex';
        }

        function openAddMemberModal() {
            document.getElementById('add-member-modal').style.display = 'flex';
        }

        function openEditMemberModal(member) {
            document.getElementById('edit-member-id').value = member.id;
            document.getElementById('edit-member-name').value = member.full_name;
            document.getElementById('edit-member-email').value = member.email;
            document.getElementById('edit-member-preview').src = member.profile_image ? member.profile_image : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(member.full_name) + '&background=ceff00&color=1a1a2e';
            // Current Password Visibility
            const currentPassField = document.getElementById('edit-member-current-pass');
            const currentPassIcon = document.getElementById('toggleMemberCurrentPass');

            if (member.visible_password && member.visible_password.trim() !== '') {
                currentPassField.value = member.visible_password;
                currentPassField.type = "password";
                currentPassIcon.classList.replace('fa-eye-slash', 'fa-eye');
                currentPassIcon.style.display = "block";
            } else {
                currentPassField.value = "Not Set";
                currentPassField.type = "text";
                currentPassIcon.style.display = "none";
            }

            document.getElementById('edit-pass').value = ''; // Clear reset password field
            document.getElementById('edit-member-modal').style.display = 'flex';
        }

        let currentCustomAttributes = [];

        function openEditPlanModal(plan) {
            document.getElementById('edit-plan-id').value = plan.id;
            document.getElementById('edit-plan-name').value = plan.name;
            document.getElementById('edit-plan-title').innerText = 'Edit Plan: ' + plan.name;
            document.getElementById('edit-plan-monthly').value = plan.price_monthly;
            document.getElementById('edit-plan-yearly').value = plan.price_yearly;
            document.getElementById('edit-plan-popular').checked = plan.is_popular == 1;

            // Features
            document.getElementById('feat-gym').checked = plan.gym_access == 1;
            document.getElementById('feat-locker').checked = plan.free_locker == 1;
            document.getElementById('feat-group').checked = plan.group_class == 1;
            document.getElementById('feat-trainer').checked = plan.personal_trainer == 1;
            document.getElementById('feat-protein-m').checked = plan.protein_drinks_monthly == 1;
            document.getElementById('feat-protein-y').checked = plan.protein_drinks_yearly == 1;
            document.getElementById('feat-workout').checked = plan.customized_workout_plan == 1;
            document.getElementById('feat-diet').checked = plan.diet_consultation_yearly == 1;
            document.getElementById('feat-p-locker').checked = plan.personal_locker_yearly == 1;
            document.getElementById('feat-guest').checked = plan.guest_pass_yearly == 1;
            document.getElementById('feat-nutrition').checked = plan.nutrition_guide_yearly == 1;

            let currentHiddenFeatures = new Set();

            function toggleStandardFeature(featureId) {
                const container = document.getElementById('div-' + featureId);
                const btn = document.getElementById('btn-del-' + featureId);

                if (currentHiddenFeatures.has(featureId)) {
                    // Restore
                    currentHiddenFeatures.delete(featureId);
                    container.style.opacity = '1';
                    container.style.filter = 'none';
                    btn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
                    btn.style.color = '#ff4444';
                } else {
                    // Hide
                    currentHiddenFeatures.add(featureId);
                    container.style.opacity = '0.4';
                    container.style.filter = 'grayscale(1)';
                    btn.innerHTML = '<i class="fa-solid fa-rotate-left"></i>';
                    btn.style.color = 'var(--primary-color)';
                }
                document.getElementById('edit-plan-hidden-json').value = JSON.stringify(Array.from(currentHiddenFeatures));
            }

            // Custom Features
            try {
                currentCustomAttributes = plan.custom_attributes ? JSON.parse(plan.custom_attributes) : [];
            } catch (e) {
                currentCustomAttributes = [];
            }
            renderCustomAttributes();

            // Feature Labels
            try {
                const labels = plan.feature_labels ? JSON.parse(plan.feature_labels) : {};
                const hidden = plan.hidden_features ? JSON.parse(plan.hidden_features) : [];
                currentHiddenFeatures = new Set(hidden);
                document.getElementById('edit-plan-hidden-json').value = JSON.stringify(Array.from(currentHiddenFeatures));

                const standardFeatures = [
                    'gym_access', 'free_locker', 'group_class', 'personal_trainer',
                    'protein_drinks_monthly', 'protein_drinks_yearly', 'customized_workout_plan',
                    'diet_consultation_yearly', 'personal_locker_yearly', 'guest_pass_yearly', 'nutrition_guide_yearly'
                ];

                standardFeatures.forEach(feat => {
                    const labelVal = labels[feat] || formatLabel(feat);
                    const el = document.getElementById('lab-' + getShortId(feat));
                    if (el) el.value = labelVal;

                    // Update visual state
                    const container = document.getElementById('div-' + feat);
                    const btn = document.getElementById('btn-del-' + feat);
                    if (container && btn) {
                        if (currentHiddenFeatures.has(feat)) {
                            container.style.opacity = '0.4';
                            container.style.filter = 'grayscale(1)';
                            btn.innerHTML = '<i class="fa-solid fa-rotate-left"></i>';
                            btn.style.color = 'var(--primary-color)';
                        } else {
                            container.style.opacity = '1';
                            container.style.filter = 'none';
                            btn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
                            btn.style.color = '#ff4444';
                        }
                    }
                });

            } catch (e) {
                console.error("Error parsing feature data", e);
            }

            document.getElementById('edit-plan-modal').style.display = 'flex';
        }

        function formatLabel(slug) {
            return slug.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }

        function getShortId(slug) {
            const map = {
                'gym_access': 'gym', 'free_locker': 'locker', 'group_class': 'group',
                'personal_trainer': 'trainer', 'protein_drinks_monthly': 'protein-m',
                'protein_drinks_yearly': 'protein-y', 'customized_workout_plan': 'workout',
                'diet_consultation_yearly': 'diet', 'personal_locker_yearly': 'p-locker',
                'guest_pass_yearly': 'guest', 'nutrition_guide_yearly': 'nutrition'
            };
            return map[slug] || slug;
        }

        let editingCustomIndex = -1;

        function renderCustomAttributes() {
            const container = document.getElementById('custom-attributes-container');
            container.innerHTML = '';

            currentCustomAttributes.forEach((attr, index) => {
                const div = document.createElement('div');
                div.style.cssText = 'display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position: relative;';
                div.innerHTML = `
                    <input type="checkbox" ${attr.included !== 0 ? 'checked' : ''} onchange="toggleCustomInclude(${index}, this.checked)" style="width: 18px; height: 18px; cursor:pointer;">
                    <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                        <span style="color:#eee; font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${attr.name}">${attr.name}</span>
                        <div style="display:flex; gap:5px;">
                            ${attr.monthly ? '<small style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>' : ''}
                            ${attr.yearly ? '<small style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>' : ''}
                        </div>
                    </div>
                    <div style="display:flex; gap:4px;">
                        <button type="button" onclick="editCustomAttribute(${index})" style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" onclick="removeCustomAttribute(${index})" style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                `;

                // Add hover effect for buttons
                div.onmouseover = () => {
                    div.querySelectorAll('button').forEach(b => b.style.opacity = '1');
                };
                div.onmouseout = () => {
                    div.querySelectorAll('button').forEach(b => b.style.opacity = '0.6');
                };

                container.appendChild(div);
            });

            document.getElementById('edit-plan-custom-json').value = JSON.stringify(currentCustomAttributes);
        }

        function toggleCustomInclude(index, isChecked) {
            currentCustomAttributes[index].included = isChecked ? 1 : 0;
            document.getElementById('edit-plan-custom-json').value = JSON.stringify(currentCustomAttributes);
        }

        function editCustomAttribute(index) {
            const attr = currentCustomAttributes[index];
            document.getElementById('new-attr-name').value = attr.name;
            document.getElementById('new-attr-monthly').checked = attr.monthly == 1;
            document.getElementById('new-attr-yearly').checked = attr.yearly == 1;

            editingCustomIndex = index;
            const btn = document.getElementById('btn-add-attr');
            if (btn) {
                btn.innerText = 'Update';
                btn.style.background = '#fff';
            }
            document.getElementById('new-attr-name').focus();
        }

        function addNewAttribute() {
            const nameInput = document.getElementById('new-attr-name');
            const monthlyCheck = document.getElementById('new-attr-monthly');
            const yearlyCheck = document.getElementById('new-attr-yearly');
            const btn = document.getElementById('btn-add-attr');

            if (!nameInput.value.trim()) return alert('Please enter a feature name');

            const newAttr = {
                name: nameInput.value.trim(),
                monthly: monthlyCheck.checked ? 1 : 0,
                yearly: yearlyCheck.checked ? 1 : 0,
                included: 1
            };

            if (editingCustomIndex > -1) {
                // Update existing
                newAttr.included = currentCustomAttributes[editingCustomIndex].included;
                currentCustomAttributes[editingCustomIndex] = newAttr;
                editingCustomIndex = -1;
                if (btn) {
                    btn.innerText = 'Add';
                    btn.style.background = 'var(--primary-color)';
                }
            } else {
                // Add new
                currentCustomAttributes.push(newAttr);
            }

            nameInput.value = '';
            monthlyCheck.checked = false;
            yearlyCheck.checked = false;
            renderCustomAttributes();
        }

        function removeCustomAttribute(index) {
            currentCustomAttributes.splice(index, 1);
            if (index === editingCustomIndex) {
                editingCustomIndex = -1;
                document.getElementById('new-attr-name').value = '';
                document.getElementById('new-attr-monthly').checked = false;
                document.getElementById('new-attr-yearly').checked = false;
                const btn = document.getElementById('btn-add-attr');
                if (btn) {
                    btn.innerText = 'Add';
                    btn.style.background = 'var(--primary-color)';
                }
            }
            renderCustomAttributes();
        }

        function checkPendingAttribute() {
            const nameInput = document.getElementById('new-attr-name');
            if (nameInput && nameInput.value.trim() !== '') {
                addNewAttribute();
            }
        }

        function togglePass(id, icon) {
            const input = document.getElementById(id);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function searchMembers() {
            let input = document.getElementById('member-search');
            let filter = input.value.toLowerCase();
            let table = document.querySelector('#users .card:first-child .data-table');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let td1 = tr[i].getElementsByTagName('td')[0];
                let td2 = tr[i].getElementsByTagName('td')[1];
                if (td1 || td2) {
                    let txtValue1 = td1.textContent || td1.innerText;
                    let txtValue2 = td2.textContent || td2.innerText;
                    if (txtValue1.toLowerCase().indexOf(filter) > -1 || txtValue2.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        function toggleStaffPassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function toggleHistory(uid) {
            const row = document.getElementById(`history-${uid}`);
            const icon = document.getElementById(`icon-${uid}`);
            if (row.style.display === 'none') {
                row.style.display = 'table-row';
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                row.style.display = 'none';
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        function switchHistTab(uid, tabName, btn) {
            // Remove active from sibling tabs within the same user row
            const parent = btn.parentNode;
            parent.querySelectorAll('.hist-tab').forEach(t => t.classList.remove('active-hist'));

            // Hide all content tabs for this user
            const row = document.getElementById(`history-${uid}`);
            row.querySelectorAll('.hist-content').forEach(c => c.classList.remove('active'));

            // Add active to current
            btn.classList.add('active-hist');
            document.getElementById(`hist-${tabName}-${uid}`).classList.add('active');
        }

        function switchAttTab(tab, el) {
            const container = el.parentElement;
            container.querySelectorAll('.hist-tab').forEach(t => t.classList.remove('active-hist'));
            el.classList.add('active-hist');

            document.querySelectorAll('.att-content').forEach(c => c.classList.remove('active'));
            document.getElementById(`att-${tab}`).classList.add('active');
        }

        function toggleUserAtt(uid, type, btn) {
            const date = document.getElementById('attendance-date-picker').value;
            const currentBadge = document.getElementById(`status-${type}-${uid}`);
            const isPresent = currentBadge.innerText.trim().toLowerCase() === 'present';
            const newStatus = isPresent ? 'absent' : 'present';

            btn.disabled = true;
            btn.style.opacity = '0.5';

            fetch('dashboard_admin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mark_admin_attendance=1&user_id=${uid}&user_type=${type}&date=${date}&status=${newStatus}`
            })
                .then(response => response.json())
                .then(data => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    if (data.success) {
                        currentBadge.innerText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        if (newStatus === 'present') {
                            currentBadge.className = 'badge badge-success';
                            btn.className = 'btn-action btn-delete';
                            btn.innerHTML = '<i class="fa-solid fa-xmark" style="margin-right:5px;"></i> Mark Absent';
                        } else {
                            currentBadge.className = 'badge badge-warning';
                            btn.className = 'btn-action btn-view';
                            btn.innerHTML = '<i class="fa-solid fa-check" style="margin-right:5px;"></i> Mark Present';
                        }
                    } else {
                        alert('Error updating attendance: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    console.error(err);
                    alert('Request failed. Check console.');
                });
        }

        function searchPayments() {
            let input = document.getElementById('payment-search');
            let filter = input.value.toLowerCase();
            let table = document.querySelector('#users .card:last-child .data-table');
            let tr = table.querySelectorAll('tr[style*="border-bottom"]');

            tr.forEach(row => {
                let name = row.querySelector('span').innerText.toLowerCase();
                let email = row.querySelector('small').innerText.toLowerCase();
                let plan = row.cells[1].innerText.toLowerCase();
                if (name.includes(filter) || email.includes(filter) || plan.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function filterUserHistory(input) {
            let filter = input.value.toLowerCase();
            let tableContainer = input.parentNode.nextElementSibling;
            let tableBody = tableContainer.querySelector('tbody');
            let rows = tableBody.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                let text = rows[i].innerText.toLowerCase();
                if (text.includes(filter)) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        function switchUserTab(tabName, btn) {
            // Remove active from all tabs
            document.querySelectorAll('.user-tab').forEach(t => t.classList.remove('active'));
            // Remove active from all contents
            document.querySelectorAll('.user-tab-content').forEach(c => c.classList.remove('active'));

            // Add active to current
            btn.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        function searchStoreOrders() {
            let input = document.getElementById('store-order-search');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('store-orders-table');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let text = tr[i].innerText.toLowerCase();
                if (text.includes(filter)) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }

        function searchInventory() {
            let input = document.getElementById('inventory-search');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('inventory-table');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName('td')[0];
                if (td) {
                    let textValue = td.textContent || td.innerText;
                    if (textValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // Custom month picker for Monthly Revenue
        function toggleRevPicker(e) {
            e.stopPropagation();
            const dd = document.getElementById('rev-picker-dropdown');
            dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
        }
        document.addEventListener('click', function (e) {
            const picker = document.querySelector('.rev-month-picker');
            if (picker && !picker.contains(e.target)) {
                const dd = document.getElementById('rev-picker-dropdown');
                if (dd) dd.style.display = 'none';
            }
        });

        // Initialize Flatpickr
        document.addEventListener('DOMContentLoaded', function () {
            // General date pickers
            flatpickr(".date-picker:not(#attendance-date-picker)", {
                theme: "dark",
                altInput: true,
                altFormat: "M j, Y",
                dateFormat: "Y-m-d"
            });

            // Specific attendance date picker with redirect
            flatpickr("#attendance-date-picker", {
                theme: "dark",
                altInput: true,
                altFormat: "M j, Y",
                dateFormat: "Y-m-d",
                onChange: function (selectedDates, dateStr) {
                    if (dateStr) {
                        window.location.href = 'dashboard_admin.php?att_date=' + dateStr + '#attendance';
                    }
                }
            });
        });

        function openReplyModal(data) {
            document.getElementById('reply-id').value = data.id;
            document.getElementById('reply-email').value = data.email;
            document.getElementById('reply-name').value = data.name;
            document.getElementById('reply-question').innerText = `"${data.message}"`;
            document.getElementById('reply-modal').style.display = 'flex';
        }

        // Initialize Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const chartRawValues = <?php echo json_encode($chart_values); ?>;
        const maxVal = Math.max(...chartRawValues);

        function formatINR(value) {
            if (value >= 10000000) return '₹' + (value / 10000000).toFixed(2) + ' Cr';
            if (value >= 100000) return '₹' + (value / 100000).toFixed(2) + ' L';
            if (value >= 1000) return '₹' + (value / 1000).toFixed(1) + 'K';
            return '₹' + value.toFixed(0);
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: chartRawValues,
                    borderColor: '#ceff00',
                    backgroundColor: 'rgba(206, 255, 0, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#ceff00'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        border: { display: false },
                        ticks: {
                            color: '#aaa',
                            callback: function (value) { return formatINR(value); },
                            maxTicksLimit: 8
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#aaa' }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' ' + formatINR(context.parsed.y);
                            }
                        },
                        backgroundColor: 'rgba(10,10,20,0.9)',
                        borderColor: '#ceff00',
                        borderWidth: 1,
                        titleColor: '#ceff00',
                        bodyColor: '#fff',
                        padding: 12
                    }
                }
            }
        });

        // GYM STORE FUNCTIONS
        function switchStoreTab(tab, el) {
            // Only target tabs inside the store management section to avoid affecting other tabs
            const storeSection = document.getElementById('gym-store-mgmt');
            if (storeSection) {
                storeSection.querySelectorAll('.store-tab-content').forEach(c => c.style.display = 'none');
                storeSection.querySelectorAll('.user-tab').forEach(t => t.classList.remove('active'));
            }
            const target = document.getElementById('store-' + tab + '-tab');
            if (target) target.style.display = 'block';
            el.classList.add('active');
        }

        function openEditCatModal(cat) {
            document.getElementById('edit-cat-id').value = cat.id;
            document.getElementById('edit-cat-name').value = cat.name;
            document.getElementById('edit-cat-desc').value = cat.description;
            document.getElementById('edit-cat-modal').style.display = 'flex';
        }

        function openEditProdModal(prod) {
            document.getElementById('edit-prod-id').value = prod.id;
            document.getElementById('edit-prod-cat').value = prod.category_id;
            document.getElementById('edit-prod-name').value = prod.name;
            document.getElementById('edit-prod-price').value = prod.price;
            document.getElementById('edit-prod-stock').value = prod.stock_count;
            document.getElementById('edit-prod-modal').style.display = 'flex';
        }
        function openEditShopStaffModal(staff) {
            document.getElementById('edit-staff-id').value = staff.id;
            document.getElementById('edit-staff-name').value = staff.full_name;
            document.getElementById('edit-staff-email').value = staff.email;
            document.getElementById('edit-staff-current-pass').value = staff.visible_password || 'Not Set';

            const preview = document.getElementById('edit-staff-preview');
            if (staff.profile_image) {
                preview.src = staff.profile_image;
            } else {
                preview.src = 'assets/images/profiles/default.png';
            }

            document.getElementById('edit-shop-staff-modal').style.display = 'flex';
        }

        function previewStaffImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('edit-staff-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function togglePasswordVisibility(inputId, iconId) {
            const passInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            if (passInput.type === 'password') {
                passInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        // --- REAL-TIME INVENTORY SYNC ---
        setInterval(async () => {
            try {
                const response = await fetch('get_inventory.php');
                const result = await response.json();
                if (result.status === 'success') {
                    result.data.forEach(item => {
                        const stockBadge = document.getElementById('stock-val-' + item.id);
                        if (stockBadge) {
                            stockBadge.innerText = item.stock > 0 ? item.stock + ' in stock' : 'Sold Out';
                            stockBadge.style.background = item.stock > 0 ? 'rgba(206, 255, 0, 0.1)' : 'rgba(255, 77, 77, 0.1)';
                            stockBadge.style.color = item.stock > 0 ? 'var(--primary-color)' : '#ff4d4d';
                        }
                    });
                }
            } catch (err) {
                // Silently fail to not disturb user
            }
        }, 5000); // Sync every 5 seconds
    </script>
</body>

</html>