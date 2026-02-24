<?php
require_once 'config.php';
header('Content-Type: application/json');

$sql = "SELECT p.id, p.name, p.stock_count, c.name as cat_name 
        FROM store_products p 
        JOIN store_categories c ON p.category_id = c.id";

$res = mysqli_query($link, $sql);
$inventory = [];

while ($row = mysqli_fetch_assoc($res)) {
    $inventory[] = [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'stock' => (int) $row['stock_count'],
        'category' => $row['cat_name']
    ];
}

echo json_encode(['status' => 'success', 'data' => $inventory]);
?>