<?php
header('Content-Type: application/json');
$last = $_GET['last'] ?? 0;
$log = json_decode(file_get_contents('signal_log.json'), true);

$newEntries = array_filter($log, function($entry) use ($last) {
    return strtotime($entry['timestamp']) > $last;
});

echo json_encode([
    'newEntries' => count($newEntries),
    'lastUpdate' => time()
]);
?>