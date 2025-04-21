<?php
header('Content-Type: application/json');
$log = json_decode(file_get_contents('signal_log.json'), true);
echo json_encode(array_reverse($log));
?>