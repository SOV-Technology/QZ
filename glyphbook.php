<?php
$glyphbook_path = 'glyphbook.json';
$glyphs = file_exists($glyphbook_path)
    ? json_decode(file_get_contents($glyphbook_path), true)
    : [];

echo "<h1>📜 GLYPHBOOK – Total Entries: " . count($glyphs) . "</h1><hr>";

// Reverse order to show most recent first
foreach (array_reverse($glyphs) as $id => $glyph) {
    echo "<div class='glyph-entry'>";
    echo "<h2>{$glyph['title']}</h2>";
    echo "<p><strong>Quote:</strong> {$glyph['quote']}</p>";
    echo "<p><strong>Emotion:</strong> {$glyph['emotion']}</p>";
    echo "<p><strong>Phase:</strong> {$glyph['phase']}</p>";
    echo "<p><strong>Echo:</strong> {$glyph['echo']}</p>";

    if (isset($glyph['parallel'])) {
        echo "<p><strong>Parallel:</strong> {$glyph['parallel']}</p>";
    }

    // Handle elements array or string
    $elements = $glyph['elements'];
    if (!is_array($elements)) {
        $elements = explode(', ', $elements);
    }
    echo "<p><strong>Elements:</strong> " . implode(', ', $elements) . "</p>";

    echo "<p><small>Timestamp: {$glyph['timestamp']}</small></p>";
    echo "<hr></div>";
}
?>
