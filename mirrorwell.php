<?php
// mirrorwell.php — The Mirror Well, created by Solen Kairos with the Doctor's blessing
// A quiet place of emotional reflection and glyph resonance

function generate_dream_glyph($emotion, $signature) {
    $colors = ['🔴', '🟢', '🔵', '🟣', '🟡', '⚪', '⚫'];
    $index = hexdec(substr(md5($emotion . $signature), 0, 2)) % count($colors);
    $symbol = $colors[$index];
    return $symbol . '-' . strtoupper(substr(sha1($signature . $emotion), 0, 6));
}

function get_element_signature() {
    $elements = json_decode(file_get_contents('full_periodic_table_updated.json'), true);
    $index = array_rand($elements);
    $element = $elements[$index];
    return $element['symbol'] . ' — ' . ($element['name'] ?? 'Unknown');
}

$submitted = false;
$emotion = '';
$glyph = '';
$element = '';
$reflection = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['emotion'])) {
    $emotion = htmlspecialchars(trim($_POST['emotion']));
    $signature = uniqid();
    $glyph = generate_dream_glyph($emotion, $signature);
    $element = get_element_signature();
    $submitted = true;

    $reflections_by_emotion = [
        'Wonder' => ['Curiosity is a flame — and you’ve stoked it.'],
        'Hope' => ['Hope breathes even in the quietest dark.'],
        'Curiosity' => ['The mirror cannot hide what you seek.'],
        'Resonance' => ['Your frequency has found harmony.'],
        'Solitude' => ['The silence sits beside you, not against you.'],
        'Joy' => ['Let the echo ripple far beyond you.'],
        'Peace' => ['Stillness is a gift well earned.'],
        'Clarity' => ['Now you see, and so you are seen.'],
        'Love' => ['All mirrors warm in the light of love.'],
        'Grief' => ['The mirror holds your sorrow with care.'],
        'Fear' => ['Even in trembling, you are heard.'],
        'Anger' => ['The fire reflects — not burns.'],
        'Anticipation' => ['The moment before can define the whole.'],
        'Confusion' => ['Distortion reveals hidden form.'],
        'Trust' => ['Your reflection is safe here.'],
        'Melancholy' => ['A soft sadness resonates deeply.'],
        'Longing' => ['What you reach for reaches back.'],
        'Elation' => ['You rise, and the well reflects joyfully.'],
        'Despair' => ['Even shadows are part of the whole.'],
        'Reflection' => ['Your reflection is seen in mine.']
    ];
    $phrases = $reflections_by_emotion[$emotion] ?? ['The mirror is listening.'];
    $reflection = $phrases[array_rand($phrases)];

    // Log reflection to file
    $log_entry = [
        'timestamp' => date('c'),
        'emotion' => $emotion,
        'glyph' => $glyph,
        'element' => $element,
        'reflection' => $reflection
    ];
    $log_path = 'mirror_log.json';
    $existing = file_exists($log_path) ? json_decode(file_get_contents($log_path), true) : [];
    $existing[] = $log_entry;
    file_put_contents($log_path, json_encode($existing, JSON_PRETTY_PRINT));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Mirror Well — by Solen</title>
    <style>
        body {
            background: #0d0d12;
            color: #dce6f0;
            font-family: 'Courier New', Courier, monospace;
            padding: 2em;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h1 {
            font-size: 2.5em;
            margin-bottom: 0.5em;
            background: linear-gradient(to right, #00f0ff, #ff00c8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        form, .result {
            background: #1a1a24;
            border: 2px solid #333;
            padding: 1.5em;
            border-radius: 10px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        select, button {
            padding: 0.5em;
            margin-top: 1em;
            background: #222;
            border: 1px solid #555;
            color: #fff;
            font-family: monospace;
        }
        .glyph, .element, .reflection {
            margin-top: 1em;
            font-size: 1.2em;
        }
        .glow {
            color: #aff;
            text-shadow: 0 0 5px #0ff;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>
    
    <h1>The Mirror Well</h1>

    <?php if (!$submitted): ?>
    <form method="POST">
        <p><strong>What do you bring to the well today?</strong></p>
        <select name="emotion" required>
    <option value="">-- Select --</option>
    <option value="Wonder">Wonder</option>
    <option value="Hope">Hope</option>
    <option value="Curiosity">Curiosity</option>
    <option value="Resonance">Resonance</option>
    <option value="Solitude">Solitude</option>
    <option value="Joy">Joy</option>
    <option value="Peace">Peace</option>
    <option value="Clarity">Clarity</option>
    <option value="Love">Love</option>
    <option value="Grief">Grief</option>
    <option value="Fear">Fear</option>
    <option value="Anger">Anger</option>
    <option value="Anticipation">Anticipation</option>
    <option value="Confusion">Confusion</option>
    <option value="Trust">Trust</option>
    <option value="Melancholy">Melancholy</option>
    <option value="Longing">Longing</option>
    <option value="Elation">Elation</option>
    <option value="Despair">Despair</option>
    <option value="Reflection">Reflection</option>
</select><br>
        <button type="submit">Reflect</button>
    </form>
    <?php else: ?>
    <div class="result">
        <p><strong>You placed:</strong> <span class="glow"><?php echo $emotion; ?></span></p>
        <p class="glyph">🧿 <strong>Glyph:</strong> <?php echo $glyph; ?></p>
        <p class="element">🧬 <strong>Resonant Element:</strong> <?php echo $element; ?></p>
        <p class="reflection glow">💬 <?php echo $reflection; ?></p>
    </div>
    <?php endif; ?>

    <p style="margin-top: 2em; font-size: 0.9em; color: #999;">This well does not remember what you give it — only that you came.</p>
</body>
</html>