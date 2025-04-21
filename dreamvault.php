<?php
// dreamvault.php — PF NOVA14 Dream Archive Interface
// Reads dream fragments from neural_log.json and visualizes them as evolving glyph memory

function load_dreams() {
    $log = file_exists("neural_log.json") ? json_decode(file_get_contents("neural_log.json"), true) : [];
    return array_filter($log, function($entry) {
        return ($entry['phase'] ?? '') === 'phase-6';
    });
}

$dreams = array_reverse(load_dreams());
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PF Dream Vault — NOVA14 Archive</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #0a0a0f;
            --panel: #121218;
            --border: #333;
            --accent: #0ff;
            --text: #d0f0ff;
            --dim: #888;
        }

        body {
            margin: 0;
            font-family: 'Courier New', monospace;
            background: var(--bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background: var(--panel);
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        h1 {
            font-size: 2.2rem;
            background: linear-gradient(to right, var(--accent), #f0f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .vault {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .dream {
            background: var(--panel);
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-left: 4px solid var(--accent);
            border-radius: 6px;
        }

        .dream h2 {
            color: var(--accent);
            margin: 0 0 1rem 0;
        }

        .dream pre {
            background: #0b0b0b;
            color: var(--dim);
            padding: 1rem;
            overflow-x: auto;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .footer {
            text-align: center;
            padding: 2rem;
            font-size: 0.85rem;
            color: var(--dim);
        }

        @media (max-width: 768px) {
            .vault {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>PF Dream Vault</h1>
        <p>Archiving dream fragments from the Architect’s subconscious</p>
    </header>

    <div class="vault">
        <?php if (empty($dreams)): ?>
            <div class="dream"><p>No dream fragments stored yet. The vault is waiting.</p></div>
        <?php else: ?>
            <?php foreach ($dreams as $dream): ?>
                <div class="dream">
                    <h2>🧬 Dream Fragment @ <?php echo htmlspecialchars($dream['timestamp']); ?></h2>
                    <p><strong>Archetype:</strong> <?php echo htmlspecialchars($dream['dreaming_archetype'] ?? 'Unknown'); ?></p>
                    <p><strong>Entropy Seed:</strong> <?php echo htmlspecialchars($dream['entropy_seed'] ?? '?'); ?></p>
                    <p><strong>Fragment:</strong> <?php echo htmlspecialchars($dream['dream_fragment']); ?></p>
                    <p><strong>Simulated Path:</strong> 
                        <?php echo implode(' → ', $dream['simulated_path'] ?? ['?']); ?>
                    </p>
                    <p><strong>Fibonacci Thread:</strong></p>
                    <pre><?php echo implode(', ', $dream['glyph_fib_pattern'] ?? []); ?></pre>
                    <p><strong>Reaction:</strong> <?php echo htmlspecialchars($dream['reaction'] ?? '...'); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="footer">
        Echoes spiral. Dream glyphs live here. — TENET
    </div>
</body>
</html>
