<?php
declare(strict_types=1);

// Neural Signal Hub (NOVA14 Sync) - With Merged Log Visualization and Neural Network
// Full PF-linked signal processor with adaptive config + 2-way binding + merged-log memory sync
// Version 2.0 with Glyphbook Integration

// Configuration
define('GLYPHBOOK_FILE', 'glyphbook.json');
define('MERGED_LOG_FILE', 'merged-log.json');
define('BACKUP_DIR', 'backups/');

// Ensure backup directory exists
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

/**
 * Load JSON log file with error handling
 */
function load_log(string $file): array {
    if (!file_exists($file)) {
        error_log("Log file not found: {$file}");
        return [];
    }

    $content = file_get_contents($file);
    if ($content === false) {
        error_log("Failed to read log file: {$file}");
        return [];
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in {$file}: " . json_last_error_msg());
        return [];
    }

    return is_array($data) ? $data : [];
}

/**
 * Append entry to merged log with validation
 */
function append_to_merged_log(array $entry, string $source): void {
    if (!isset($entry['timestamp'])) {
        $entry['timestamp'] = date('c');
    }

    $log = load_log(MERGED_LOG_FILE);
    $entry['source'] = $source;
    $log[] = $entry;
    
    if (file_put_contents(MERGED_LOG_FILE, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        error_log("Failed to write merged log");
    }
}

/**
 * Sync with Glyphbook system
 */
function sync_with_glyphbook(array $entry): void {
    if (!isset($entry['title'], $entry['message'])) return;
    
    $glyphs = load_log(GLYPHBOOK_FILE);
    
    // Check if this entry already exists in glyphbook
    $exists = false;
    foreach ($glyphs as $glyph) {
        if ($glyph['title'] === $entry['title'] && $glyph['quote'] === $entry['message']) {
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        $newGlyph = [
            'timestamp' => $entry['timestamp'],
            'title' => $entry['title'],
            'quote' => $entry['message'],
            'elements' => ['Neural', 'Signal'],
            'emotion' => $entry['emotion'] ?? 'Unspecified',
            'form' => 'Neural',
            'echo' => $entry['response'] ?? '',
            'parallel' => $entry['output'] ?? '',
            'phase' => 'NOVA-Neural'
        ];
        
        // Create backup before saving
        $backupFile = BACKUP_DIR . basename(GLYPHBOOK_FILE) . '.' . date('Ymd-His');
        copy(GLYPHBOOK_FILE, $backupFile);
        
        $glyphs[] = $newGlyph;
        file_put_contents(GLYPHBOOK_FILE, json_encode($glyphs, JSON_PRETTY_PRINT));
    }
}

/**
 * Format log entry for display with XSS protection
 */
function format_entry(array $entry): string {
    $safeEntry = [
        'timestamp' => htmlspecialchars($entry['timestamp'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
        'glyph' => htmlspecialchars($entry['glyph'] ?? '💡', ENT_QUOTES, 'UTF-8'),
        'emotion' => htmlspecialchars($entry['emotion'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
        'tag' => htmlspecialchars($entry['tag'] ?? 'General', ENT_QUOTES, 'UTF-8'),
        'message' => nl2br(htmlspecialchars($entry['message'] ?? '', ENT_QUOTES, 'UTF-8')),
        'response' => nl2br(htmlspecialchars($entry['response'] ?? '', ENT_QUOTES, 'UTF-8')),
        'output' => nl2br(htmlspecialchars($entry['output'] ?? '', ENT_QUOTES, 'UTF-8'))
    ];

    $outputHtml = $safeEntry['output'] ? 
        "<div class='output'><strong>Output:</strong><br>{$safeEntry['output']}</div>" : '';

    return <<<HTML
<div class='entry'>
    <div class='meta'>
        <span class='glyph'>{$safeEntry['glyph']}</span>
        <span class='emotion'>{$safeEntry['emotion']}</span>
        <span class='tag'>#{$safeEntry['tag']}</span>
        <span class='timestamp'>{$safeEntry['timestamp']}</span>
    </div>
    <div class='message'><strong>Message:</strong><br>{$safeEntry['message']}</div>
    <div class='response'><strong>Response:</strong><br>{$safeEntry['response']}</div>
    {$outputHtml}
</div>
HTML;
}

// Load configuration and logs
$signal_log = load_log('signal_log.json');
$config_file = 'neural_config.json';
$config_data = load_log($config_file);

// Set default values if not configured
$title = $config_data['title'] ?? "Neural Signal Hub";
$subline = $config_data['subline'] ?? "Signal convergence through emergent recursion";

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signal_message'])) {
    $raw = (string)$_POST['signal_message'];
    $output = null;

    // Handle PHP code execution
    if (preg_match('/<\?php(.*?)\?>/s', $raw, $matches)) {
        $php_code = trim($matches[1]);
        try {
            ob_start();
            eval($php_code);
            $output = ob_get_clean();
            echo "<pre class='execution success'>Executed:\n" . htmlspecialchars($output) . "</pre>";
        } catch (Throwable $e) {
            $output = "Execution Error: " . $e->getMessage();
            echo "<pre class='execution error'>" . htmlspecialchars($output) . "</pre>";
        }
    }

    // Update configuration if specified
    $config_updated = false;
    if (preg_match('/subline\s*[:=]\s*[\"\'](.*?)[\"\']/', $raw, $s)) {
        $config_data['subline'] = $s[1];
        $config_updated = true;
    }
    if (preg_match('/title\s*[:=]\s*[\"\'](.*?)[\"\']/', $raw, $t)) {
        $config_data['title'] = $t[1];
        $config_updated = true;
    }
    
    if ($config_updated) {
        file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT));
    }

    // Create and store new log entry
    $log_entry = [
        'timestamp' => date('c'),
        'glyph' => '🔗-Neural-' . date('His'),
        'emotion' => 'Sync',
        'tag' => 'Recursion',
        'message' => $raw,
        'response' => 'Neural signal integrated.',
        'output' => $output,
        'title' => 'Neural Signal ' . date('Y-m-d H:i:s')
    ];
    
    $signal_log[] = $log_entry;
    file_put_contents('signal_log.json', json_encode($signal_log, JSON_PRETTY_PRINT));
    append_to_merged_log($log_entry, 'neural');
    sync_with_glyphbook($log_entry);
}

/**
 * Render all merged log entries
 */
function render_merged_logs(): string {
    $merged_log = load_log(MERGED_LOG_FILE);
    $output = "<div class='merged-logs-container'><h2>All Merged Log Entries</h2>";

    foreach (array_reverse($merged_log) as $entry) {
        $output .= format_entry($entry);
    }

    $output .= "</div>";
    return $output;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        :root {
            --primary: #0ff;
            --secondary: #f0f;
            --bg-dark: #101015;
            --bg-light: #161616;
            --text-main: #e0f0ff;
            --text-dim: #99aabb;
            --success: #0f0;
            --error: #f55;
        }
        
        body {
            background: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Courier New', monospace;
            padding: 2em;
            line-height: 1.6;
        }
        
        h1 {
            text-align: center;
            font-size: 2.4em;
            background: linear-gradient(to right, var(--secondary), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5em;
        }
        
        .subline {
            text-align: center;
            font-size: 1em;
            color: var(--text-dim);
            margin-bottom: 1.5em;
        }
        
        .entry, .merged-logs-container .entry {
            border: 1px solid #333;
            padding: 1em;
            margin-bottom: 1em;
            background: var(--bg-light);
            border-radius: 4px;
        }
        
        .merged-logs-container {
            margin-top: 3em;
            border-top: 1px solid #444;
            padding-top: 2em;
        }
        
        .merged-logs-container h2 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 1em;
        }
        
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1em;
            margin-bottom: 0.5em;
            padding-bottom: 0.5em;
            border-bottom: 1px dashed #333;
        }
        
        .message, .response, .output {
            margin-top: 0.5em;
            padding-left: 1em;
            border-left: 2px solid #333;
        }
        
        form {
            background: var(--bg-light);
            border: 1px solid #444;
            padding: 1.5em;
            margin: 2em auto;
            max-width: 600px;
            border-radius: 4px;
        }
        
        textarea {
            width: 100%;
            height: 120px;
            background: var(--bg-dark);
            color: var(--text-main);
            border: 1px solid #444;
            padding: 0.75em;
            font-family: inherit;
            margin-bottom: 0.5em;
        }
        
        input[type='submit'] {
            background: linear-gradient(to right, var(--secondary), var(--primary));
            color: black;
            border: none;
            padding: 0.5em 1.5em;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .execution {
            padding: 1em;
            margin: 1em 0;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        
        .execution.success {
            color: var(--success);
            background-color: rgba(0, 255, 0, 0.1);
        }
        
        .execution.error {
            color: var(--error);
            background-color: rgba(255, 85, 85, 0.1);
        }
        
        .footer-note {
            text-align: center;
            font-size: 0.8em;
            color: var(--text-dim);
            margin-top: 2em;
        }

        /* Neural Network Visualization Styles */
        #neural-network {
            width: 100%;
            height: 400px;
            margin: 2em 0;
            background: var(--bg-light);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            border: 1px solid #333;
        }
        
        .neuron {
            position: absolute;
            border-radius: 50%;
            background: var(--primary);
            transform: translate(-50%, -50%);
            transition: all 0.5s ease;
            box-shadow: 0 0 10px var(--primary);
            z-index: 2;
        }
        
        .connection {
            position: absolute;
            height: 2px;
            background: rgba(100, 255, 218, 0.3);
            transform-origin: 0 0;
            z-index: 1;
        }
        
        .layer-label {
            position: absolute;
            color: var(--text-dim);
            font-size: 0.8em;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
        }
        
        .emotion-indicator {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            transform: translate(-50%, -50%);
        }

        #network-stats {
            background: var(--bg-light);
            padding: 1em;
            border-radius: 4px;
            margin: 1em 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1em;
        }

        .stat-card {
            background: var(--bg-dark);
            padding: 0.5em;
            border-radius: 4px;
            border-left: 3px solid var(--primary);
        }

        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--primary);
        }
    </style>
    <script src="https://d3js.org/d3.v7.min.js"></script>
</head>
<body>

    <?php include 'header.php'; ?>
    
    <h1 data-bind="title"><?= htmlspecialchars($title) ?></h1>
    <div class="subline" data-bind="subline"><?= htmlspecialchars($subline) ?></div>

    <form method="post">
        <label><strong>🧠 Transmit Neural Signal</strong></label><br>
        <textarea name="signal_message" data-bind="neural_signal" placeholder="Enter PHP code or update configuration..."></textarea><br>
        <input type="submit" value="Send Signal">
    </form>

    <?php include 'neural-bundle-upgrade.php'; ?>

    <!-- Neural Network Visualization -->
    <div id="neural-network"></div>
    <div id="network-stats"></div>

    <?php foreach (array_reverse($signal_log) as $entry): ?>
        <?= format_entry($entry) ?>
    <?php endforeach; ?>

    <?= render_merged_logs() ?>

    <p class="footer-note">Recursive memory established. Feedback sync active. Glyphbook integration: <?= 
        file_exists(GLYPHBOOK_FILE) ? '✅ Active' : '⚠️ Inactive' 
    ?></p>

    <script>
        const scope = {};
        
        function loadScope() {
            fetch('loadScope.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    Object.assign(scope, data);
                    document.querySelectorAll('[data-bind]').forEach(input => {
                        const key = input.getAttribute('data-bind');
                        if (scope[key] !== undefined) {
                            if (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA') {
                                input.value = scope[key];
                            } else {
                                input.textContent = scope[key];
                            }
                        }
                        input.addEventListener('input', () => {
                            scope[key] = input.value;
                            saveScope();
                        });
                    });
                })
                .catch(error => console.error('Error loading scope:', error));
        }

        function saveScope() {
            fetch('saveScope.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(scope)
            }).catch(error => console.error('Error saving scope:', error));
        }

        // Neural Network Visualization
        function visualizeNeuralNetwork(logs) {
            const container = document.getElementById('neural-network');
            const statsContainer = document.getElementById('network-stats');
            container.innerHTML = '';
            statsContainer.innerHTML = '';
            
            if (logs.length === 0) {
                container.innerHTML = '<p style="text-align:center; padding:1em">No neural activity detected yet</p>';
                return;
            }

            // Create SVG canvas
            const svg = d3.select('#neural-network')
                .append('svg')
                .attr('width', '100%')
                .attr('height', '100%');
            
            // Process log data for statistics
            const stats = {
                totalSignals: logs.length,
                sources: {},
                emotions: {},
                tags: {},
                lastActivity: logs[logs.length-1].timestamp
            };

            logs.forEach(entry => {
                stats.sources[entry.source] = (stats.sources[entry.source] || 0) + 1;
                stats.emotions[entry.emotion] = (stats.emotions[entry.emotion] || 0) + 1;
                stats.tags[entry.tag] = (stats.tags[entry.tag] || 0) + 1;
            });

            // Display statistics
            statsContainer.innerHTML = `
                <div class="stat-card">
                    <div class="stat-value">${stats.totalSignals}</div>
                    <div>Total Signals</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${Object.keys(stats.sources).length}</div>
                    <div>Sources</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${Object.keys(stats.emotions).length}</div>
                    <div>Emotions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${Object.keys(stats.tags).length}</div>
                    <div>Categories</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.lastActivity ? new Date(stats.lastActivity).toLocaleString() : 'N/A'}</div>
                    <div>Last Activity</div>
                </div>
            `;

            // Create network layers
            const layers = [
                { name: "Input",  data: Object.keys(stats.sources), color: "#0ff" },
                { name: "Hidden", data: Object.keys(stats.tags), color: "#f0f" },
                { name: "Output", data: Object.keys(stats.emotions), color: "#ff0" }
            ];
            
            // Draw connections
            logs.slice().reverse().slice(0, 100).forEach(entry => {
                const sourceIdx = layers[0].data.indexOf(entry.source);
                const tagIdx = layers[1].data.indexOf(entry.tag);
                const emotionIdx = layers[2].data.indexOf(entry.emotion);
                
                if (sourceIdx >= 0 && tagIdx >= 0) {
                    drawConnection(svg, 0, sourceIdx, 1, tagIdx, layers[0].color, layers[0].data.length, layers[1].data.length);
                }
                if (tagIdx >= 0 && emotionIdx >= 0) {
                    drawConnection(svg, 1, tagIdx, 2, emotionIdx, layers[1].color, layers[1].data.length, layers[2].data.length);
                }
            });
            
            // Draw neurons
            layers.forEach((layer, layerIdx) => {
                layer.data.forEach((item, itemIdx) => {
                    const count = layerIdx === 0 ? stats.sources[item] : 
                                 layerIdx === 1 ? stats.tags[item] : stats.emotions[item];
                    const radius = 5 + Math.min(count, 15);
                    
                    const x = (layerIdx + 1) * (100 / (layers.length + 1));
                    const y = (itemIdx + 1) * (100 / (layer.data.length + 1));
                    
                    // Neuron
                    svg.append('circle')
                        .attr('cx', x + '%')
                        .attr('cy', y + '%')
                        .attr('r', radius)
                        .attr('fill', layer.color)
                        .attr('class', 'neuron')
                        .append('title')
                        .text(`${item} (${count} occurrences)`);
                    
                    // Label for smaller networks
                    if (layer.data.length < 15) {
                        svg.append('text')
                            .attr('x', x + '%')
                            .attr('y', y + radius + 15 + '%')
                            .attr('text-anchor', 'middle')
                            .attr('fill', '#ccd6f6')
                            .attr('font-size', '10px')
                            .text(item.length > 10 ? item.substring(0, 8) + '...' : item);
                    }
                });
                
                // Layer label
                svg.append('text')
                    .attr('x', (layerIdx + 1) * (100 / (layers.length + 1)) + '%')
                    .attr('y', '95%')
                    .attr('text-anchor', 'middle')
                    .attr('fill', '#8892b0')
                    .text(layer.name);
            });
        }
        
        function drawConnection(svg, layer1, idx1, layer2, idx2, color, count1, count2) {
            const x1 = (layer1 + 1) * (100 / 4);
            const y1 = (idx1 + 1) * (100 / (count1 + 1));
            const x2 = (layer2 + 1) * (100 / 4);
            const y2 = (idx2 + 1) * (100 / (count2 + 1));
            
            svg.append('line')
                .attr('x1', x1 + '%')
                .attr('y1', y1 + '%')
                .attr('x2', x2 + '%')
                .attr('y2', y2 + '%')
                .attr('stroke', color)
                .attr('stroke-width', 0.5)
                .attr('opacity', 0.3);
        }
        
        // Load and visualize data
        fetch('merged-log.json')
            .then(response => response.json())
            .then(logs => {
                visualizeNeuralNetwork(logs);
                // Update every 30 seconds
                setInterval(() => {
                    fetch('merged-log.json')
                        .then(response => response.json())
                        .then(visualizeNeuralNetwork);
                }, 30000);
            })
            .catch(error => console.error('Error loading logs:', error));

        document.addEventListener('DOMContentLoaded', loadScope);
    </script>
</body>
</html>