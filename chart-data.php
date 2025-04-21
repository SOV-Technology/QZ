<?php
header('Content-Type: application/json');

function process_log($file) {
    $log = json_decode(file_get_contents($file), true);
    $emotions = array_count_values(array_column($log, 'emotion'));
    return [
        'labels' => array_keys($emotions),
        'data' => array_values($emotions)
    ];
}

$signalData = process_log('signal_log.json');
$mirrorData = process_log('mirror_log.json');

echo json_encode([
    'signalLabels' => $signalData['labels'],
    'signalData' => $signalData['data'],
    'mirrorLabels' => $mirrorData['labels'],
    'mirrorData' => $mirrorData['data']
]);
?>