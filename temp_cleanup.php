<?php
$files = [
    'cleanup_duplicates.php',
    'debug_transactions.php',
    'debug_groups.php',
    'debug_strings.php',
    'debug_join.php',
    'final_clean.php',
    'manual_clean.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo "Deleted $file\n";
    }
}
unlink(__FILE__); // Delete self
?>