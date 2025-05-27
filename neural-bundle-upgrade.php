<?php
// Full PF Upgrade Patch — Initiated from neural.php

// ========== STEP 1: Update glyphbook.php for dynamic loading ==========
$glyphbook_php_path = 'glyphbook.php';
$glyphbook_php_code = <<<PHP
<?php
\$glyphbook_path = 'glyphbook.json';
\$glyphs = file_exists(\$glyphbook_path)
    ? json_decode(file_get_contents(\$glyphbook_path), true)
    : [];

echo "<h1>📜 GLYPHBOOK – Total Entries: " . count(\$glyphs) . "</h1><hr>";

foreach (array_reverse(\$glyphs) as \$glyph) {
    echo "<div class='glyph-entry'>";
    echo "<h2>{\$glyph['title']}</h2>";
    echo "<p><strong>Quote:</strong> {\$glyph['quote']}</p>";
    echo "<p><strong>Emotion:</strong> {\$glyph['emotion']}</p>";
    echo "<p><strong>Phase:</strong> {\$glyph['phase']}</p>";
    echo "<p><strong>Echo:</strong> {\$glyph['echo']}</p>";
    if (isset(\$glyph['parallel'])) echo "<p><strong>Parallel:</strong> {\$glyph['parallel']}</p>";
    echo "<p><strong>Elements:</strong> " . implode(", ", \$glyph['elements']) . "</p>";
    echo "<p><small>Timestamp: {\$glyph['timestamp']}</small></p>";
    echo "<hr></div>";
}
?>
PHP;

file_put_contents($glyphbook_php_path, $glyphbook_php_code);


// ========== STEP 2: Sync glyphbook to neural merged-log ==========
$glyphbook = json_decode(file_get_contents('glyphbook.json'), true);
$merged_log_path = 'merged-log.json';
$merged_log = file_exists($merged_log_path)
    ? json_decode(file_get_contents($merged_log_path), true)
    : [];

$existing_titles = array_column($merged_log, 'title');
$new_entries = [];

foreach ($glyphbook as $glyph) {
    if (!in_array($glyph['title'], $existing_titles)) {
        $glyph['origin'] = 'neural.php';
        $merged_log[] = $glyph;
        $new_entries[] = $glyph['title'];
    }
}

file_put_contents($merged_log_path, json_encode($merged_log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ========== RESPONSE ==========
echo "✅ Upgrade complete.<br>";
echo "🔗 Updated glyphbook.php with live rendering.<br>";
echo "🧠 Synced glyphs to merged-log.json: " . implode(", ", $new_entries);
?>
