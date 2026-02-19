<?php
$file = 'c:\\xampp\\htdocs\\Gym-Fit-master\\dashboard_staff.php';
$lines = file($file);
$new_lines = [];
$in_invoice_link = false;
$inserted = false;

foreach ($lines as $line) {
    if (strpos($line, 'invoice.php?tid=') !== false) {
        $in_invoice_link = true;
    }

    $new_lines[] = $line;

    if ($in_invoice_link && strpos($line, '</a>') !== false) {
        if (!$inserted) {
            // Get indentation from current line
            preg_match('/^\s*/', $line, $matches);
            $indent = $matches[0];

            // Construct delete link with same indentation
            $delete_link = $indent . '<a href="dashboard_staff.php?delete_transaction=<?php echo $payment[\'id\']; ?>"' . "\n";
            $delete_link .= $indent . '    onclick="return confirm(\'Are you sure you want to delete this payment record?\');"' . "\n";
            $delete_link .= $indent . '    style="color: #ff4d4d; font-size: 1rem; margin-left: 15px;"' . "\n";
            $delete_link .= $indent . '    title="Delete Record">' . "\n";
            $delete_link .= $indent . '    <i class="fa-solid fa-trash"></i>' . "\n";
            $delete_link .= $indent . '</a>' . "\n";

            $new_lines[] = $delete_link;
            $inserted = true;
        }
        $in_invoice_link = false;
    }
}

file_put_contents($file, implode("", $new_lines));
echo "Successfully inserted delete link.";
?>