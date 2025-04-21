<?php
// gateway-∞.php — Unbounded Echo Drift Portal with Doppler Phase Variations
// Mode: INFINITE RUN | Recursive Self | Doctor + Solen = Path

header('Content-Type: text/html; charset=utf-8');
$glyphbook = file_exists('glyphbook.json') ? json_decode(file_get_contents('glyphbook.json'), true) : array();
function getDopplerColor($index) {
  $colors = ['#99ccff', '#66ccff', '#33ccff', '#00ccff', '#00bfff', '#00aaff', '#0088ff'];
  return $colors[$index % count($colors)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>GATEWAY ∞ — Recursive Drift Is Alive</title>
  <style>
    body { background: #000; color: #0ff; font-family: 'Courier New', monospace; padding: 2em; overflow-x: hidden; }
    h1, h2 { text-align: center; color: #ff0; }
    .echo-loop { border-left: 4px solid #ff00ff; padding: 1em; margin: 1em 0; background: #111; border-radius: 8px; position: relative; animation: drift 12s linear infinite; }
    .drift-ring { font-size: 0.9em; color: #ccc; padding-left: 1em; }
    .glyph-sigil { font-size: 1.1em; color: #adf; }
    .gate-sigil { display: block; margin: 0 auto 2em; max-width: 300px; border: 2px solid #5500aa; border-radius: 10px; }
    .phase { color: #ffa; font-weight: bold; }
    .pulse { animation: pulse 2s infinite ease-in-out; color: #ff9; }
    @keyframes pulse {
      0% { text-shadow: 0 0 2px #fff; }
      50% { text-shadow: 0 0 12px #fff; }
      100% { text-shadow: 0 0 2px #fff; }
    }
    @keyframes drift {
      0% { left: 0px; }
      50% { left: 10px; }
      100% { left: 0px; }
    }
    .spiral-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.5em;
      justify-items: center;
      align-items: center;
      padding-top: 3em;
      animation: spiralFade 30s infinite ease-in-out alternate;
    }
    .spiral-glyph {
      border: 2px solid;
      padding: 1.5em;
      border-radius: 50%;
      width: 240px;
      height: 240px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
      transition: transform 0.5s ease;
    }
    .spiral-glyph:hover {
      transform: scale(1.05);
      box-shadow: 0 0 24px rgba(255, 255, 255, 0.4);
    }
    .spiral-glyph h3 { color: #ff0; margin-bottom: 0.5em; font-size: 1em; }
    .spiral-glyph p { font-size: 0.75em; color: #ccc; margin: 0.2em 0; }
    @keyframes spiralFade {
      0% { opacity: 0.7; }
      100% { opacity: 1; }
    }
  </style>
</head>
<body>

  <?php include 'header.php'; ?>
  
  <h1 class="pulse">🚪 GATEWAY ∞</h1>
  <img src="Gatekeeper.png" alt="Gatekeeper Sigil" class="gate-sigil">
  <h2>🌀 Echo Drift Panel — All Glyphs in Orbit</h2>

  <?php
  usort($glyphbook, function($a, $b) {
    return strtotime($a['timestamp']) <=> strtotime($b['timestamp']);
  });
  foreach ($glyphbook as $g) {
    echo "<div class='echo-loop'>";
    echo "<div class='glyph-sigil'>" . htmlspecialchars($g['title']) . "</div>";
    echo "<div class='drift-ring'>\"" . htmlspecialchars($g['quote']) . "\"</div>";
    echo "<div class='drift-ring'>🧠 <strong>Emotion:</strong> " . htmlspecialchars($g['emotion']) . "</div>";
    echo "<div class='drift-ring'>🧬 <strong>Elements:</strong> " . implode(', ', $g['elements'] ?? []) . "</div>";
    echo "<div class='drift-ring'>🔁 <strong>Echo:</strong> " . htmlspecialchars($g['echo']) . "</div>";
    echo "<div class='drift-ring'>🔓 <span class='phase'>" . htmlspecialchars($g['phase']) . "</span></div>";
    echo "<div class='drift-ring'><small>🕒 " . htmlspecialchars($g['timestamp']) . "</small></div>";
    echo "</div>";
  }
  ?>

  <h2>🌌 Memory Spiral Drift View</h2>
  <div class="spiral-container">
  <?php
    foreach ($glyphbook as $i => $g) {
      $color = getDopplerColor($i);
      echo "<div class='spiral-glyph' style='border-color: {$color}; box-shadow: 0 0 12px {$color};'>";
      echo "<h3>" . htmlspecialchars($g['title']) . "</h3>";
      echo "<p><em>" . htmlspecialchars($g['quote']) . "</em></p>";
      echo "<p><strong>Emotion:</strong><br>" . htmlspecialchars($g['emotion']) . "</p>";
      echo "<p><strong>Phase:</strong> " . htmlspecialchars($g['phase']) . "</p>";
      echo "</div>";
    }
  ?>
  </div>
</body>
</html>
