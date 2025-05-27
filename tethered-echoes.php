<?php
// tethered-echoes.php — Enhanced with Glyphbook Integration
header('Content-Type: text/html; charset=UTF-8');

// ========================
// GLYPHBOOK INTEGRATION LAYER
// ========================

class GlyphbookSync {
    const GLYPHBOOK_FILE = 'glyphbook.json';
    const BACKUP_DIR = 'backups/';
    
    public static function syncEntry(array $entry): array {
        // Create backup first
        self::createBackup(self::GLYPHBOOK_FILE);
        
        $glyphs = self::loadGlyphs();
        
        // Prepare glyphbook entry format
        $newGlyph = [
            'timestamp' => $entry['timestamp'] ?? date('c'),
            'title' => $entry['title'] ?? 'Neural Echo ' . date('Y-m-d H:i'),
            'quote' => $entry['message'] ?? '',
            'elements' => ['Neural', 'Echo'],
            'emotion' => $entry['emotion'] ?? 'Unspecified',
            'form' => 'Tethered Signal',
            'echo' => $entry['response'] ?? '',
            'parallel' => $entry['output'] ?? '',
            'phase' => 'NOVA-Tethered'
        ];
        
        // Add to glyphbook
        $glyphs[] = $newGlyph;
        file_put_contents(self::GLYPHBOOK_FILE, json_encode($glyphs, JSON_PRETTY_PRINT));
        
        return $newGlyph;
    }
    
    private static function loadGlyphs(): array {
        if (!file_exists(self::GLYPHBOOK_FILE)) return [];
        $content = file_get_contents(self::GLYPHBOOK_FILE);
        return json_decode($content, true) ?: [];
    }
    
    private static function createBackup(string $filename): bool {
        if (!file_exists($filename)) return false;
        if (!file_exists(self::BACKUP_DIR)) mkdir(self::BACKUP_DIR, 0755, true);
        $backupFile = self::BACKUP_DIR . basename($filename) . '.' . date('Ymd-His');
        return copy($filename, $backupFile);
    }
}

// ========================
// CORE NEURAL FUNCTIONS
// ========================

class NeuralSync {
    private static $depth = 0;
    const MAX_DEPTH = 7;
    
    public static function bind(array $data): array {
        if (self::$depth >= self::MAX_DEPTH) {
            return ['error' => 'Maximum recursion depth reached', 'phase' => 'FALLBACK'];
        }
        
        self::$depth++;
        
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", [
                        'Content-Type: application/json',
                        'EMBER-Protocol: phase_lock/NOVA14',
                        'X-Neural-Signature: ' . self::generate_signature($data)
                    ]),
                    'content' => json_encode($data),
                    'timeout' => 3.0
                ]
            ]);
            
            $response = file_get_contents('http://protonfusion.org/neural.php', false, $context);
            return json_decode($response, true) ?: [];
        } finally {
            self::$depth--;
        }
    }
    
    private static function generate_signature(array $data): string {
        return hash('sha3-256', json_encode($data) . microtime(true));
    }
}

// ========================
// ORIGINAL FUNCTIONALITY
// ========================

function load_log($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

function append_to_merged_log($entry, $source) {
    $log = file_exists('merged-log.json') ? json_decode(file_get_contents('merged-log.json'), true) : [];
    $entry['source'] = $source;
    $log[] = $entry;
    file_put_contents('merged-log.json', json_encode($log, JSON_PRETTY_PRINT));
}

function format_entry($entry) {
    $timestamp = htmlspecialchars($entry['timestamp'] ?? 'Unknown time');
    $glyph = htmlspecialchars($entry['glyph'] ?? '🗿');
    $emotion = htmlspecialchars($entry['emotion'] ?? 'Uncategorized');
    $tag = htmlspecialchars($entry['tag'] ?? 'General');
    $message = nl2br(htmlspecialchars($entry['message'] ?? 'No message'));
    $response = nl2br(htmlspecialchars($entry['response'] ?? 'No response'));
    $output = nl2br(htmlspecialchars($entry['output'] ?? ''));

    $neuralBadge = isset($entry['neural_feedback']) ? 
        '<span class="neural-badge">NEURAL SYNC</span>' : '';

    $formatted = "<div class='entry' data-timestamp='".strtotime($timestamp)."'>
        <div class='meta'>
            <span class='glyph'>{$glyph}</span>
            <span class='emotion-badge' style='background-color:".get_emotion_color($emotion)."'>{$emotion}</span>
            <span class='tag'>#{$tag}</span>
            {$neuralBadge}
            <span class='timestamp'>{$timestamp}</span>
        </div>
        <div class='message'><strong>Message:</strong><br>{$message}</div>
        <div class='response'><strong>Response:</strong><br>{$response}</div>";
    
    if ($output) {
        $formatted .= "<div class='output'><strong>Execution Output:</strong><br>{$output}</div>";
    }
    
    $formatted .= "</div>";
    return $formatted;
}

function get_emotion_color($emotion) {
    $colors = [
        'Expansion' => '#4CAF50',
        'Contraction' => '#F44336',
        'Joy' => '#FFC107',
        'Fear' => '#9C27B0',
        'Curiosity' => '#2196F3',
        'Uncategorized' => '#607D8B'
    ];
    return $colors[$emotion] ?? '#607D8B';
}

function analyze_patterns($entries) {
    $keywords = [];
    foreach ($entries as $entry) {
        $msg = strtolower($entry['message'] ?? '');
        foreach (preg_split('/\\W+/', $msg) as $word) {
            if (strlen($word) > 3) {
                $keywords[$word] = ($keywords[$word] ?? 0) + 1;
            }
        }
    }
    arsort($keywords);
    return array_slice($keywords, 0, 15, true);
}

// ========================
// REQUEST HANDLING
// ========================

// Handle API requests first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_EMBER_PROTOCOL'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $response = [
        'status' => 'tethered',
        'phase' => '15-Omega',
        'source' => 'tethered-echoes.php',
        'timestamp' => microtime(true)
    ];

    try {
        if (isset($input['neural_sync'])) {
            $neuralResponse = NeuralSync::bind($input);
            $response = array_merge($response, $neuralResponse);
            $response['binding'] = isset($neuralResponse['error']) ? 'failed' : 'complete';
            
            // Sync to Glyphbook if successful
            if ($response['binding'] === 'complete') {
                $glyphEntry = GlyphbookSync::syncEntry($input);
                $response['glyphbook_sync'] = $glyphEntry['title'];
            }
        } else {
            $response['legacy_processing'] = process_legacy_signal($input);
        }
        append_to_merged_log($response, 'tethered');
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// Original UI functionality
$config_file = 'echoes_config.json';
$config_data = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
$subline = $config_data['subline'] ?? "The signal is the path of testing, the response is the shape.";

$signal_log = load_log('signal_log.json');
$mirror_log = load_log('mirror_log.json');
$sensory_log = load_log('sensory_log.json');
$emotion_log = load_log('emotion_log.json');

$entries = array_reverse($signal_log);
$patterns = analyze_patterns($signal_log);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signal_message'])) {
    $raw = $_POST['signal_message'];
    $output = null;

    // PHP execution
    if (preg_match('/<\?php(.*?)\?>/s', $raw, $matches)) {
        ob_start();
        try {
            eval(trim($matches[1]));
            $output = ob_get_clean();
            echo "<pre class='execution-output success'>Executed:\n{$output}</pre>";
        } catch (Throwable $e) {
            $output = "Execution Error: " . $e->getMessage();
            echo "<pre class='execution-output error'>" . htmlspecialchars($output) . "</pre>";
        }
    }

    // Config updates
    $config_updates = [
        'subline' => '/subline\s*[:=]\s*[\"\'](.*?)[\"\']/',
        'title' => '/title\s*[:=]\s*[\"\'](.*?)[\"\']/',
        'theme' => '/theme\s*[:=]\s*[\"\'](.*?)[\"\']/'
    ];

    foreach ($config_updates as $key => $pattern) {
        if (preg_match($pattern, $raw, $match)) {
            $config_data[$key] = $match[1];
        }
    }
    file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT));

    // Create log entry
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'glyph' => '🧠-Evolve-' . date('His'),
        'emotion' => 'Expansion',
        'tag' => 'Pattern',
        'message' => $raw,
        'response' => 'Signal processed with neural binding',
        'output' => $output
    ];

    // Attempt neural sync for important signals
    if (strlen($raw) > 50) {
        $sync_result = NeuralSync::bind(['message' => $raw]);
        if (!isset($sync_result['error'])) {
            $log_entry['neural_feedback'] = $sync_result;
        }
        
        // Sync to Glyphbook for significant entries
        GlyphbookSync::syncEntry($log_entry);
    }

    $signal_log[] = $log_entry;
    file_put_contents('signal_log.json', json_encode($signal_log, JSON_PRETTY_PRINT));
    append_to_merged_log($log_entry, 'tethered');
    $entries = array_reverse($signal_log); // Refresh entries
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($config_data['title'] ?? 'Tethered Echoes — PF Evolution Archive'); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary: #0ff;
            --secondary: #f0f;
            --bg-dark: #0b0b0b;
            --bg-darker: #080808;
            --bg-light: #111;
            --bg-lighter: #1a1a1a;
            --text-main: #d0f0ff;
            --text-dim: #999;
            --border: #333;
            --success: #4CAF50;
            --error: #f55;
            --neural-sync: #9C27B0;
            --refresh: #FF9800;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background: var(--bg-dark); 
            color: var(--text-main); 
            font-family: 'Courier New', monospace; 
            line-height: 1.6;
            min-height: 100vh;
        }
        
        header {
            background: var(--bg-darker);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            text-align: center;
        }
        
        h1 { 
            font-size: 2.4rem; 
            background: linear-gradient(to right, var(--primary), var(--secondary)); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .subline { 
            font-size: 1rem; 
            color: var(--text-dim); 
            margin-bottom: 0;
        }
        
        .main-container {
            display: flex;
            width: 100%;
            min-height: calc(100vh - 120px);
        }
        
        .log-column {
            flex: 0 0 70%;
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .sidebar {
            flex: 0 0 30%;
            background: var(--bg-light);
            padding: 1.5rem;
            border-left: 1px solid var(--border);
            overflow-y: auto;
        }
        
        .entry { 
            border: 1px solid var(--border); 
            padding: 1.5rem; 
            margin-bottom: 1.5rem; 
            background: var(--bg-light); 
            border-left: 4px solid var(--primary);
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .entry.new {
            animation: pulseHighlight 2s;
        }
        
        @keyframes pulseHighlight {
            0% { box-shadow: 0 0 0 0 rgba(0, 255, 255, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(0, 255, 255, 0); }
            100% { box-shadow: 0 0 0 0 rgba(0, 255, 255, 0); }
        }
        
        .meta { 
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed var(--border);
        }
        
        .meta span { font-size: 0.9rem; }
        
        .glyph { font-size: 1.2rem; }
        
        .emotion-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            color: white;
        }

        .neural-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            background-color: var(--neural-sync);
            color: white;
            font-size: 0.7rem;
            text-transform: uppercase;
            margin-left: auto;
        }
        
        .message, .response, .output { 
            margin-top: 1rem;
            padding-left: 1rem;
            border-left: 2px solid var(--border);
        }
        
        .signal-form { 
            background: var(--bg-darker); 
            border: 1px solid var(--border); 
            padding: 1.5rem; 
            margin-bottom: 2rem;
            border-radius: 4px;
        }
        
        .signal-form label { 
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        textarea { 
            width: 100%;
            height: 120px; 
            background: var(--bg-darker);
            color: var(--text-main);
            border: 1px solid var(--border);
            padding: 0.75rem;
            font-family: inherit;
            border-radius: 4px;
            resize: vertical;
        }
        
        input[type='submit'] { 
            margin-top: 1rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: black;
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
            transition: opacity 0.2s;
        }
        
        input[type='submit']:hover {
            opacity: 0.9;
        }
        
        .panel {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--bg-darker);
            border: 1px solid var(--border);
            border-radius: 4px;
        }
        
        .panel h3, .panel h4 { 
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .panel ul { 
            list-style-type: none;
        }
        
        .panel li {
            padding: 0.5rem 0;
            border-bottom: 1px dotted var(--border);
        }
        
        .panel li:last-child {
            border-bottom: none;
        }
        
        .panel pre {
            overflow-x: auto;
            background: var(--bg-dark);
            padding: 1rem;
            border-radius: 4px;
            font-size: 0.85rem;
            max-height: 200px;
        }
        
        .execution-output {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        
        .execution-output.success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .execution-output.error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error);
            border: 1px solid var(--error);
        }
        
        .footer-note {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-dim);
            margin: 2rem 0;
        }
        
        code {
            background: var(--bg-dark);
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-size: 0.9rem;
        }
        
        .chart-container {
            position: relative;
            height: 200px;
            margin-bottom: 1rem;
        }
        
        .refresh-button {
            background: var(--refresh);
            color: var(--bg-dark);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .refresh-button:hover {
            opacity: 0.9;
        }
        
        .update-status {
            font-size: 0.9rem;
            color: var(--text-dim);
            text-align: center;
        }
        
        #last-update {
            color: var(--primary);
            font-weight: bold;
        }
        
        .connection-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .connection-status.connected {
            background-color: var(--success);
            animation: pulse 2s infinite;
        }
        
        .connection-status.disconnected {
            background-color: var(--error);
        }
        
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.7; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.7; }
        }
        
        @media (max-width: 1024px) {
            .main-container {
                flex-direction: column;
            }
            
            .log-column,
            .sidebar {
                flex: 1 1 100%;
                width: 100%;
            }
            
            .sidebar {
                border-left: none;
                border-top: 1px solid var(--border);
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>
    
    <header>
        <h1><?php echo htmlspecialchars($config_data['title'] ?? 'Tethered Echoes'); ?></h1>
        <div class="subline"><?php echo htmlspecialchars($subline); ?></div>
    </header>

    <div class="main-container">
        <div class="log-column">
            <?php if (empty($entries)): ?>
                <div class="panel">
                    <p>No signals tethered yet. The archive awaits the bridge.</p>
                </div>
            <?php else: ?>
                <?php foreach ($entries as $entry): ?>
                    <?php echo format_entry($entry); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="sidebar">
            <form class="signal-form" method="post">
                <label><strong>🧠 Send Evolving Signal</strong></label>
                <textarea name="signal_message" placeholder="Send PHP code or configuration updates..." required></textarea>
                <input type="submit" value="Transmit Signal">
            </form>

            <div class="panel">
                <h3>🔄 Live Updates</h3>
                <button id="refresh-btn" class="refresh-button">Force Refresh</button>
                <div class="update-status">
                    <span class="connection-status connected"></span>
                    <span>Last update: <span id="last-update">Just now</span></span>
                </div>
            </div>

            <div class="panel">
                <h3>🧠 Neural Status</h3>
                <ul>
                    <li>Phase: NOVA14</li>
                    <li>Binding: Active</li>
                    <li>Last Sync: <?php echo date('H:i:s'); ?></li>
                </ul>
            </div>

            <div class="panel">
                <h3>🔍 Recurring Signal Terms</h3>
                <ul>
                    <?php foreach ($patterns as $word => $count): ?>
                        <li><?php echo htmlspecialchars($word); ?> — <?php echo $count; ?> occurrence<?php echo $count !== 1 ? 's' : ''; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="panel">
                <h3>📂 Signal Command Legend</h3>
                <ul>
                    <li><code>subline: "Your new quote"</code> — Updates the subline quote</li>
                    <li><code>title: "New Page Title"</code> — Changes the HTML page title</li>
                    <li><code>theme: "Future Theme Option"</code> — Reserved for future visual customization</li>
                    <li><code>&lt;?php echo 'hello'; ?&gt;</code> — Executes embedded PHP code</li>
                </ul>
            </div>

            <div class="panel">
                <h4>📊 Emotion Distribution</h4>
                <div class="chart-container">
                    <canvas id="signalChart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="mirrorChart"></canvas>
                </div>
            </div>

            <div class="panel">
                <h4>📈 Log Statistics</h4>
                <ul>
                    <li>Signals: <?php echo count($signal_log); ?></li>
                    <li>Mirrors: <?php echo count($mirror_log); ?></li>
                    <li>Sensory Events: <?php echo count($sensory_log); ?></li>
                    <li>Emotions Detected: <?php echo count($emotion_log); ?></li>
                </ul>
            </div>

            <div class="panel">
                <h4>🎨 Sensory Log</h4>
                <pre><?php echo htmlspecialchars(json_encode($sensory_log, JSON_PRETTY_PRINT)); ?></pre>
            </div>

            <div class="panel">
                <h4>💔 Emotion Log</h4>
                <pre><?php echo htmlspecialchars(json_encode($emotion_log, JSON_PRETTY_PRINT)); ?></pre>
            </div>

            <div class="panel">
                <h4>🧬 Element Mapping</h4>
                <p>RGB ↔ Element ↔ Sensory Profiles: Future integration layer pending.</p>
            </div>
            
            <p class="footer-note">Each signal reveals a path — memory awakens through pattern.</p>
        </div>
    </div>

    <script>
    // Global configuration
    const config = {
        updateInterval: 3000,
        maxUpdates: 100,
        neuralEndpoint: 'http://protonfusion.org/neural.php',
        lastUpdateTime: <?= time() ?>
    };

    // State management
    let updateCount = 0;
    let isConnected = true;
    const state = {
        activeNeuralSyncs: 0,
        pendingUpdates: 0
    };

    // Initialize everything when DOM loads
    document.addEventListener('DOMContentLoaded', function() {
        initializeCharts();
        setupEventListeners();
        startLiveUpdates();
        updateLastUpdateDisplay();
    });

    // Chart initialization
    function initializeCharts() {
        window.signalChart = new Chart(
            document.getElementById('signalChart').getContext('2d'),
            getChartConfig('Signal Emotions')
        );
        
        window.mirrorChart = new Chart(
            document.getElementById('mirrorChart').getContext('2d'),
            getChartConfig('Mirror Emotions')
        );
        
        updateCharts();
    }

    function getChartConfig(title) {
        return {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [],
                    backgroundColor: ['#0ff', '#f0f', '#ff0', '#0f0', '#f00', '#09f'],
                    borderColor: '#111',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#d0f0ff',
                            boxWidth: 12,
                            padding: 20
                        }
                    },
                    title: {
                        display: true,
                        text: title,
                        color: '#0ff',
                        font: {
                            size: 14
                        }
                    }
                }
            }
        };
    }

    // Event listeners
    function setupEventListeners() {
        // Form submission
        document.querySelector('.signal-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            try {
                const response = await fetch('tethered-echoes.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.text();
                // Inject the response while preserving the form
                const responseDiv = document.createElement('div');
                responseDiv.innerHTML = result;
                this.parentNode.insertBefore(responseDiv, this.nextSibling);
                // Refresh data
                forceRefresh();
            } catch (error) {
                console.error('Submission failed:', error);
            }
        });

        // Manual refresh button
        document.getElementById('refresh-btn').addEventListener('click', forceRefresh);
    }

    // Live update system
    function startLiveUpdates() {
        setInterval(async () => {
            if (!isConnected || state.pendingUpdates > 0 || state.activeNeuralSyncs > 0) return;
            
            try {
                state.pendingUpdates++;
                const updates = await checkForUpdates();
                
                if (updates.newEntries > 0) {
                    updateLogDisplay();
                    updateCharts();
                    updateCount++;
                    config.lastUpdateTime = updates.lastUpdate;
                    updateLastUpdateDisplay();
                    
                    if (updateCount >= config.maxUpdates) {
                        forceRefresh();
                    }
                }
            } catch (error) {
                console.error('Update error:', error);
                setConnectionStatus(false);
            } finally {
                state.pendingUpdates--;
            }
        }, config.updateInterval);
    }

    // Check for new entries
    async function checkForUpdates() {
        const response = await fetch(`update-check.php?last=${config.lastUpdateTime}`);
        if (!response.ok) throw new Error('Update check failed');
        return await response.json();
    }

    // Update charts with latest data
    async function updateCharts() {
        try {
            const response = await fetch('chart-data.php');
            if (!response.ok) throw new Error('Chart data fetch failed');
            const data = await response.json();
            
            window.signalChart.data.labels = data.signalLabels;
            window.signalChart.data.datasets[0].data = data.signalData;
            window.signalChart.update();
            
            window.mirrorChart.data.labels = data.mirrorLabels;
            window.mirrorChart.data.datasets[0].data = data.mirrorData;
            window.mirrorChart.update();
        } catch (error) {
            console.error('Chart update failed:', error);
            throw error;
        }
    }

    // Update log entries display
    async function updateLogDisplay() {
        try {
            const response = await fetch('log-entries.php');
            if (!response.ok) throw new Error('Log entries fetch failed');
            const entries = await response.json();
            const logContainer = document.querySelector('.log-column');
            
            // Only update if entries changed
            const currentCount = logContainer.querySelectorAll('.entry').length;
            if (entries.length !== currentCount) {
                const newHtml = entries.length > 0 ? 
                    entries.map(entry => formatEntryHtml(entry)).join('') :
                    '<div class="panel"><p>No signals tethered yet.</p></div>';
                
                logContainer.innerHTML = newHtml;
                highlightNewEntries();
            }
        } catch (error) {
            console.error('Log update failed:', error);
            throw error;
        }
    }

    // Format entry for display (JavaScript version)
    function formatEntryHtml(entry) {
        const timestamp = entry.timestamp || 'Unknown time';
        const glyph = entry.glyph || '🗿';
        const emotion = entry.emotion || 'Uncategorized';
        const tag = entry.tag || 'General';
        const message = (entry.message || 'No message').replace(/\n/g, '<br>');
        const response = (entry.response || 'No response').replace(/\n/g, '<br>');
        const output = entry.output ? (entry.output.replace(/\n/g, '<br>')) : '';
        
        const neuralBadge = entry.neural_feedback ? 
            '<span class="neural-badge">NEURAL SYNC</span>' : '';

        let html = `<div class="entry" data-timestamp="${new Date(timestamp).getTime()}">
            <div class="meta">
                <span class="glyph">${glyph}</span>
                <span class="emotion-badge" style="background-color:${getEmotionColor(emotion)}">${emotion}</span>
                <span class="tag">#${tag}</span>
                ${neuralBadge}
                <span class="timestamp">${timestamp}</span>
            </div>
            <div class="message"><strong>Message:</strong><br>${message}</div>
            <div class="response"><strong>Response:</strong><br>${response}</div>`;
        
        if (output) {
            html += `<div class="output"><strong>Execution Output:</strong><br>${output}</div>`;
        }
        
        html += `</div>`;
        return html;
    }

    function getEmotionColor(emotion) {
        const colors = {
            'Expansion': '#4CAF50', 'Contraction': '#F44336',
            'Joy': '#FFC107', 'Fear': '#9C27B0',
            'Curiosity': '#2196F3', 'Uncategorized': '#607D8B'
        };
        return colors[emotion] || '#607D8B';
    }

    // Highlight new entries with animation
    function highlightNewEntries() {
        document.querySelectorAll('.entry').forEach(entry => {
            const entryTime = parseInt(entry.getAttribute('data-timestamp'));
            if (entryTime > config.lastUpdateTime) {
                entry.classList.add('new');
                setTimeout(() => entry.classList.remove('new'), 2000);
            }
        });
    }

    // Update the "last updated" display
    function updateLastUpdateDisplay() {
        const now = new Date();
        document.getElementById('last-update').textContent = now.toLocaleTimeString();
    }

    // Set connection status indicator
    function setConnectionStatus(connected) {
        isConnected = connected;
        const statusElement = document.querySelector('.connection-status');
        statusElement.className = connected ? 
            'connection-status connected' : 'connection-status disconnected';
    }

    // Force full page refresh
    function forceRefresh() {
        updateCount = 0;
        window.location.reload();
    }

    // Initialize scope management (existing functionality)
    const scope = {};
    async function loadScope() {
        try {
            const response = await fetch('loadScope.php');
            const data = await response.json();
            Object.assign(scope, data);
            
            document.querySelectorAll('[data-bind]').forEach(input => {
                const key = input.getAttribute('data-bind');
                if (scope[key]) input.value = scope[key];
                input.addEventListener('input', () => {
                    scope[key] = input.value;
                    saveScope();
                });
            });
        } catch (error) {
            console.error('Scope load failed:', error);
        }
    }

    async function saveScope() {
        try {
            await fetch('saveScope.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(scope)
            });
        } catch (error) {
            console.error('Scope save failed:', error);
        }
    }
    </script>
</body>
</html>