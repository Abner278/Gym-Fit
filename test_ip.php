<?php
echo "Hostname: " . gethostname() . "\n";
echo "IP: " . gethostbyname(gethostname()) . "\n";
echo "Server Addr: " . $_SERVER['SERVER_ADDR'] . "\n";
?>