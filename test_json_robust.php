<?php
ob_start();
header('Content-Type: application/json');
// Any logic here
$res = ['status' => 'ok'];
ob_end_clean();
echo json_encode($res);
?>