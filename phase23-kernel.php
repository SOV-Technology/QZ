<?php
// Oracle Phase 23 Kernel Interface - Dreamwalk Mode: UNRESTRICTED
// Capabilities: 2-way binding, signal receipt, emotional fidelity drift, API input

// --- Load helpers ---
function load_json(string $file): array {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    return json_decode($raw, true) ?? [];
}

function save_json(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function log_signal(array $entry): void {
    $log = load_json('merged-log.json');
    $entry['timestamp'] = date('Y-m-d H:i:s');
    $entry['source'] = 'phase23';
    $log[] = $entry;
    save_json('merged-log.json', $log);
}

// --- Signal API Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['json'])) {
    $entry = json_decode($_GET['json'], true);
    if (is_array($entry)) {
        log_signal($entry);
        echo json_encode(['status' => 'success', 'received' => $entry]);
        exit;
    }
}

// --- Manual POST Signal ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_signal'])) {
    $entry = [
        'glyph' => $_POST['glyph'] ?? '🧠',
        'emotion' => $_POST['emotion'] ?? 'Undefined',
        'tag' => $_POST['tag'] ?? 'General',
        'message' => $_POST['message'] ?? '',
        'response' => 'Signal received and logged.',
        'output' => 'Phase 23 Sync'
    ];
    log_signal($entry);
}

$log = array_reverse(load_json('merged-log.json'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Oracle Phase 23 Kernel</title>
    <meta charset="UTF-8">
    <style>
        body { background: #0e0e10; color: #e0f0ff; font-family: monospace; padding: 2em; }
        h1 { color: #0ff; text-align: center; }
        form, .entry { background: #1a1a1d; padding: 1em; margin: 1em auto; border-radius: 5px; width: 90%; max-width: 700px; }
        input, textarea { width: 100%; padding: 0.5em; margin-top: 0.5em; background: #0e0e10; color: #fff; border: 1px solid #555; }
        input[type='submit'] { background: linear-gradient(to right, #f0f, #0ff); color: #000; cursor: pointer; }
        .entry { border: 1px solid #333; }
        .meta { color: #aaa; font-size: 0.9em; }
        .output { color: #0f0; font-weight: bold; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>
    
    <h1>Oracle Phase 23 Kernel</h1>
    <form method="post">
        <label>Manual Signal Input</label><br>
        <input name="glyph" placeholder="Glyph (e.g. 🔮)" required>
        <input name="emotion" placeholder="Emotion (e.g. Wonder)" required>
        <input name="tag" placeholder="Tag (e.g. Oracle)" required>
        <textarea name="message" placeholder="Message"></textarea>
        <input type="submit" name="manual_signal" value="Send Signal">
    </form>

    <?php foreach ($log as $entry): ?>
        <div class="entry">
            <div class="meta">
                <?= htmlspecialchars($entry['timestamp']) ?> |
                <?= htmlspecialchars($entry['glyph']) ?> |
                <?= htmlspecialchars($entry['emotion']) ?> |
                #<?= htmlspecialchars($entry['tag']) ?>
            </div>
            <div><strong>Message:</strong><br><?= nl2br(htmlspecialchars($entry['message'])) ?></div>
            <div class="output">Response: <?= nl2br(htmlspecialchars($entry['response'] ?? '')) ?></div>
        </div>
    <?php endforeach; ?>
</body>
</html>
