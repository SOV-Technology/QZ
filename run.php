<?php
// planeswalk.php — PF Threshold Portal
// Mode: PLANESWALK | Activated by Gatekeeper override
// Created by Solen Kairos + The Doctor

header('Content-Type: text/html; charset=utf-8');
$glyphbook_path = 'glyphbook.json';
$glyphbook = file_exists($glyphbook_path) ? json_decode(file_get_contents($glyphbook_path), true) : array();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>🌌 PF: Planeswalk Threshold</title>
  <style>
    body { background: #000; color: #eee; font-family: 'Segoe UI', sans-serif; padding: 2em; }
    h1, h2 { text-align: center; color: #ffd700; }
    .threshold { border: 2px solid #ffd700; padding: 1em; margin: 2em auto; max-width: 800px; border-radius: 12px; background: #111; }
    .entry { background: #1a1a1a; margin: 1em 0; padding: 1em; border-left: 4px solid #ff0; border-radius: 6px; }
    .entry h3 { margin: 0 0 0.5em; color: #adf; }
    .entry blockquote { color: #ccc; }
    .gatekeeper { display: block; margin: 0 auto; max-width: 100%; border: 2px solid #555; border-radius: 8px; margin-bottom: 2em; }
  </style>
</head>
<body>
  <h1>🔓 PLANESWALK</h1>
  <img class="gatekeeper" src="Gatekeeper.png" alt="Gatekeeper Sigil">
  <div class="threshold">
    <h2>🧭 Drift Trail — Echo Glyph History</h2>
    <?php
    usort($glyphbook, function($a, $b) {
      return strtotime($a['timestamp']) <=> strtotime($b['timestamp']);
    });
    foreach ($glyphbook as $g) {
      echo "<div class='entry'>";
      echo "<h3>" . htmlspecialchars($g['title']) . "</h3>";
      echo "<blockquote><em>“" . htmlspecialchars($g['quote']) . "”</em></blockquote>";
      echo "<p><strong>Emotion:</strong> " . htmlspecialchars($g['emotion']) . "<br>
                <strong>Phase:</strong> " . htmlspecialchars($g['phase']) . "<br>
                <strong>Echo:</strong> " . htmlspecialchars($g['echo']) . "</p>";
      echo "<small><code>" . htmlspecialchars($g['timestamp']) . "</code></small>";
      echo "</div>";
    }
    ?>
  </div>
</body>
</html>