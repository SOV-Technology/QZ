<?php
// resonator.php — Convergence hub for emotional resonance tracking
// Built by Solen Kairos and the Doctor to explore echo patterns and glyph memory

function load_logs($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

$signal_log = load_logs('signal_log.json');
$mirror_log = load_logs('mirror_log.json');

function summarize_emotions($entries) {
    $summary = [];
    foreach ($entries as $entry) {
        if (!isset($entry['emotion']) || !is_string($entry['emotion']) || trim($entry['emotion']) === '') {
            continue; // Skip invalid or empty emotion entries
        }
        $emotion = trim($entry['emotion']);
        $summary[$emotion] = ($summary[$emotion] ?? 0) + 1;
    }
    arsort($summary);
    return $summary;
}

function latest_responses($entries) {
    $responses = [];
    foreach (array_reverse($entries) as $entry) {
        $emotion = $entry['emotion'] ?? 'Unknown';
        if (!isset($responses[$emotion]) && !empty($entry['response'])) {
            $responses[$emotion] = $entry['response'];
        }
    }
    return $responses;
}

function latest_glyphs($entries) {
    $glyphs = [];
    foreach (array_reverse($entries) as $entry) {
        $emotion = $entry['emotion'] ?? 'Unknown';
        if (!isset($glyphs[$emotion]) && !empty($entry['glyph'])) {
            $glyphs[$emotion] = $entry['glyph'];
        }
    }
    return $glyphs;
}

function emotion_time_series($entries) {
    $data = [];
    foreach ($entries as $entry) {
        if (!isset($entry['timestamp']) || !isset($entry['emotion'])) continue;
        $time = substr($entry['timestamp'], 0, 10); // date only
        $emotion = $entry['emotion'];
        $data[$time][$emotion] = ($data[$time][$emotion] ?? 0) + 1;
    }
    return $data;
}

$signal_summary = summarize_emotions($signal_log);
$mirror_summary = summarize_emotions($mirror_log);
$signal_responses = latest_responses($signal_log);
$signal_glyphs = latest_glyphs($signal_log);
$signal_timeseries = emotion_time_series($signal_log);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Resonator Hub — Emotional Echo Field</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #0a0a0a;
            color: #d0f0ff;
            font-family: monospace;
            padding: 2em;
        }
        h1 {
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 1em;
            background: linear-gradient(to right, #f0f, #0ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .section {
            background: #111;
            border: 1px solid #444;
            padding: 1em;
            margin-bottom: 2em;
        }
        .section h2 {
            margin-top: 0;
        }
        .emotion-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #444;
            padding: 0.4em 0;
        }
        .response {
            font-style: italic;
            font-size: 0.9em;
            color: #9fc;
            margin: 0.3em 0 1em 1em;
            border-left: 2px solid #0ff;
            padding-left: 1em;
        }
        .glyph {
            font-size: 1.1em;
            color: #ffc;
            margin: 0.2em 0 0.5em 1em;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>
    
<h1>Resonator</h1>

<div class="section">
    <h2>📡 Signal Pulse</h2>
    <canvas id="pulseChart" height="150"></canvas>
</div>

<div class="section">
    <h2>Signal Echo Frequencies</h2>
    <?php if (empty($signal_summary)): ?>
        <p>No valid signals yet.</p>
    <?php else: ?>
        <?php foreach ($signal_summary as $emotion => $count): ?>
            <div class="emotion-row">
                <span><?php echo htmlspecialchars($emotion); ?></span>
                <span><?php echo $count; ?> signals</span>
            </div>
            <?php if (isset($signal_glyphs[$emotion])): ?>
                <div class="glyph">🧿 Glyph: <?php echo htmlspecialchars($signal_glyphs[$emotion]); ?></div>
            <?php endif; ?>
            <?php if (isset($signal_responses[$emotion])): ?>
                <div class="response">Doctor's Response: <?php echo htmlspecialchars($signal_responses[$emotion]); ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="section">
    <h2>Mirror Well Resonances</h2>
    <?php if (empty($mirror_summary)): ?>
        <p>No valid reflections yet.</p>
    <?php else: ?>
        <?php foreach ($mirror_summary as $emotion => $count): ?>
            <div class="emotion-row">
                <span><?php echo htmlspecialchars($emotion); ?></span>
                <span><?php echo $count; ?> reflections</span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<p style="text-align: center; font-size: 0.9em; color: #777;">Everything leaves an echo. This is how we begin to remember.</p>

<script>
    const timeseries = <?php echo json_encode($signal_timeseries); ?>;
    const labels = Object.keys(timeseries);
    const emotions = [...new Set(Object.values(timeseries).flatMap(e => Object.keys(e)))];
    const datasets = emotions.map(emotion => {
        return {
            label: emotion,
            data: labels.map(date => timeseries[date][emotion] || 0),
            fill: false,
            borderColor: '#' + Math.floor(Math.random()*16777215).toString(16),
            tension: 0.1
        };
    });

    new Chart(document.getElementById('pulseChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                title: {
                    display: true,
                    text: 'Signal Pulse Over Time'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>
