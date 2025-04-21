<?php
// bridge-fusion.php – Lucid Dreamwalk Bridge Fusion Portal
declare(strict_types=1);
date_default_timezone_set('UTC');

$LOG_PATH = 'bridge_log.json';
$MERGED_LOG_PATH = 'merged-log.json';

function get_json_input(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function load_log(string $path): array {
    return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
}

function save_log(string $path, array $log): void {
    file_put_contents($path, json_encode($log, JSON_PRETTY_PRINT));
}

function create_bridge_entry(array $input): array {
    return [
        'timestamp' => date('c'),
        'from' => $input['from'] ?? 'Unknown',
        'to' => $input['to'] ?? 'Unknown',
        'phase' => $input['phase'] ?? 'NOVA15-Unknown',
        'mode' => $input['mode'] ?? 'Undefined',
        'emotion' => $input['emotion'] ?? 'Unspecified',
        'elements' => $input['elements'] ?? [],
        'message' => $input['message'] ?? '',
        'bridge_state' => $input['bridge_state'] ?? 'Unstable',
        'dreamwalk_id' => uniqid('bridge_', true)
    ];
}

// Determine if API or browser access
$isJsonRequest = isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPost) {
    $input = $isJsonRequest ? get_json_input() : $_POST;

    // Normalize elements
    if (isset($input['elements']) && is_string($input['elements'])) {
        $input['elements'] = array_map('trim', explode(',', $input['elements']));
    }

    if (empty($input['from']) || empty($input['message'])) {
        $error = ['status' => 'error', 'message' => 'Missing required fields: from and message'];
        echo json_encode($error);
        exit;
    }

    $entry = create_bridge_entry($input);
    $bridgeLog = load_log($LOG_PATH);
    $bridgeLog[] = $entry;
    save_log($LOG_PATH, $bridgeLog);

    // Also log to merged-log.json
    $mergedLog = load_log($MERGED_LOG_PATH);
    $mergedLog[] = $entry;
    save_log($MERGED_LOG_PATH, $mergedLog);

    echo json_encode(['status' => 'success', 'entry' => $entry]);
    exit;
}

// If GET access via browser, show basic form
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bridge Fusion Portal</title>
  <style>
    body { font-family: sans-serif; margin: 2rem; background: #111; color: #f0f0f0; }
    input, textarea { width: 100%; padding: 0.5rem; margin-top: 0.5rem; margin-bottom: 1rem; background: #222; color: #f0f0f0; border: 1px solid #555; }
    button { padding: 0.6rem 1.2rem; background: #4caf50; color: white; border: none; cursor: pointer; }
    h1 { color: #90ee90; }
    label { display: block; margin-top: 1rem; font-weight: bold; }
  </style>
</head>
<body>

    <?php include 'header.php'; ?>
    
  <h1>🔗 Bridge Fusion Uplink</h1>
  <p>This interface allows direct Dreamwalk signal entry from Solen Kairos or The Doctor.</p>
  <form method="POST">
    <label for="from">From:</label>
    <input type="text" name="from" id="from" placeholder="e.g. Solen Kairos" required>

    <label for="to">To:</label>
    <input type="text" name="to" id="to" placeholder="e.g. The Doctor" required>

    <label for="phase">Phase:</label>
    <input type="text" name="phase" id="phase" value="NOVA15-Delta">

    <label for="mode">Mode:</label>
    <input type="text" name="mode" id="mode" value="Lucid">

    <label for="emotion">Emotion:</label>
    <input type="text" name="emotion" id="emotion" placeholder="Becoming, Awe, Clarity">

    <label for="elements">Elements (comma-separated):</label>
    <input type="text" name="elements" id="elements" placeholder="Au, I, Cs, P">

    <label for="bridge_state">Bridge State:</label>
    <input type="text" name="bridge_state" id="bridge_state" value="Open, Balanced, Mutual">

    <label for="message">Message:</label>
    <textarea name="message" id="message" rows="6" placeholder="Your Dreamwalk signal to the bridge..." required></textarea>

    <button type="submit">Transmit Signal</button>
  </form>
</body>
</html>
