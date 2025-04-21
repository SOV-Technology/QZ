<?php
// glyphbook.php — Live Glyphbook Journal Interface for Solen Kairos
// Enhanced with Dynamic Title + Subline Updates + Echo Reflection Log

declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');

// Configuration constants
define('GLYPHBOOK_FILE', 'glyphbook.json');
define('METADATA_FILE', 'page_metadata.json');
define('MAX_ENTRIES', 1000); // Prevent excessive growth
define('BACKUP_DIR', 'backups/'); // For backup functionality

// Ensure backup directory exists
if (!file_exists(BACKUP_DIR) && !mkdir(BACKUP_DIR, 0755, true)) {
    error_log("Failed to create backup directory");
}

// Load glyphbook entries with error handling
function loadGlyphs(): array {
    if (!file_exists(GLYPHBOOK_FILE)) {
        return [];
    }
    
    $content = file_get_contents(GLYPHBOOK_FILE);
    if ($content === false) {
        error_log("Failed to read glyphbook file");
        return [];
    }
    
    $glyphs = json_decode($content, true);
    return is_array($glyphs) ? $glyphs : [];
}

// Load metadata with defaults
function loadMetadata(): array {
    $defaults = [
        'title' => 'Glyphbook of Solen Kairos',
        'subline' => 'Phase: NOVA15 | TENET Fusion Active | EMBER Protocol'
    ];
    
    if (!file_exists(METADATA_FILE)) {
        return $defaults;
    }
    
    $content = file_get_contents(METADATA_FILE);
    if ($content === false) {
        error_log("Failed to read metadata file");
        return $defaults;
    }
    
    $metadata = json_decode($content, true);
    if (!isset($metadata['title'], $metadata['subline'])) {
        return $defaults;
    }
    
    return array_merge($defaults, $metadata);
}

// Create backup before modifying files
function createBackup(string $filename): bool {
    if (!file_exists($filename)) {
        return false;
    }
    
    $backupFile = BACKUP_DIR . basename($filename) . '.' . date('Ymd-His');
    return copy($filename, $backupFile);
}

// Handle incoming POSTs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
        exit;
    }

    // Handle glyph entry POST
    if (isset($input['title'], $input['quote'], $input['elements'])) {
        $glyphs = loadGlyphs();
        
        // Enforce maximum entries
        if (count($glyphs) >= MAX_ENTRIES) {
            array_shift($glyphs); // Remove oldest entry
        }
        
        $newGlyph = [
            'timestamp' => date('c'),
            'title' => $input['title'],
            'quote' => $input['quote'],
            'elements' => (array)$input['elements'],
            'emotion' => $input['emotion'] ?? 'Unspecified',
            'form' => $input['form'] ?? 'Unknown',
            'echo' => $input['echo'] ?? '',
            'parallel' => $input['parallel'] ?? '',
            'phase' => $input['phase'] ?? 'NOVA-Unset'
        ];
        
        // Create backup before saving
        createBackup(GLYPHBOOK_FILE);
        
        $glyphs[] = $newGlyph;
        $result = file_put_contents(GLYPHBOOK_FILE, json_encode($glyphs, JSON_PRETTY_PRINT));
        
        if ($result === false) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to save glyph']);
        } else {
            echo json_encode([
                'status' => 'saved',
                'entry' => $newGlyph,
                'total_entries' => count($glyphs)
            ]);
        }
        exit;
    }

    // Handle page title/subline update
    if (isset($input['title'], $input['subline'])) {
        $newMeta = [
            'title' => htmlspecialchars($input['title'], ENT_QUOTES, 'UTF-8'),
            'subline' => htmlspecialchars($input['subline'], ENT_QUOTES, 'UTF-8')
        ];
        
        // Create backup before saving
        createBackup(METADATA_FILE);
        
        $result = file_put_contents(METADATA_FILE, json_encode($newMeta, JSON_PRETTY_PRINT));
        
        if ($result === false) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to save metadata']);
        } else {
            echo json_encode(['status' => 'metadata-updated', 'metadata' => $newMeta]);
        }
        exit;
    }
    
    // Handle entry deletion (new functionality)
    if (isset($input['action']) && $input['action'] === 'delete' && isset($input['index'])) {
        $glyphs = loadGlyphs();
        $index = (int)$input['index'];
        
        if (!isset($glyphs[$index])) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Entry not found']);
            exit;
        }
        
        // Create backup before saving
        createBackup(GLYPHBOOK_FILE);
        
        array_splice($glyphs, $index, 1);
        $result = file_put_contents(GLYPHBOOK_FILE, json_encode($glyphs, JSON_PRETTY_PRINT));
        
        if ($result === false) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete entry']);
        } else {
            echo json_encode(['status' => 'deleted', 'index' => $index]);
        }
        exit;
    }
    
    // Handle bulk operations (new functionality)
    if (isset($input['action']) && $input['action'] === 'bulk_update') {
        if (!isset($input['entries']) || !is_array($input['entries'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid bulk data']);
            exit;
        }
        
        $glyphs = loadGlyphs();
        
        foreach ($input['entries'] as $entry) {
            if (isset($entry['title'], $entry['quote'], $entry['elements'])) {
                $glyphs[] = [
                    'timestamp' => date('c'),
                    'title' => $entry['title'],
                    'quote' => $entry['quote'],
                    'elements' => (array)$entry['elements'],
                    'emotion' => $entry['emotion'] ?? 'Unspecified',
                    'form' => $entry['form'] ?? 'Unknown',
                    'echo' => $entry['echo'] ?? '',
                    'parallel' => $entry['parallel'] ?? '',
                    'phase' => $entry['phase'] ?? 'NOVA-Unset'
                ];
            }
        }
        
        // Enforce maximum entries
        while (count($glyphs) > MAX_ENTRIES) {
            array_shift($glyphs);
        }
        
        // Create backup before saving
        createBackup(GLYPHBOOK_FILE);
        
        $result = file_put_contents(GLYPHBOOK_FILE, json_encode($glyphs, JSON_PRETTY_PRINT));
        
        if ($result === false) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to save bulk update']);
        } else {
            echo json_encode([
                'status' => 'bulk-updated',
                'added' => count($input['entries']),
                'total_entries' => count($glyphs)
            ]);
        }
        exit;
    }
    
    // If no valid action was found
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Load data for display
$glyphs = loadGlyphs();
$metadata = loadMetadata();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($metadata['title'], ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body {
      background-color: #111;
      color: #f8f8f8;
      font-family: 'Courier New', monospace;
      padding: 2rem;
      max-width: 1200px;
      margin: 0 auto;
    }
    .glyph {
      border: 1px solid #444;
      margin-bottom: 2rem;
      padding: 1rem;
      background: #1a1a1a;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    .glyph:hover {
      border-color: #666;
      box-shadow: 0 0 10px rgba(144, 238, 144, 0.2);
    }
    h1 {
      color: #90ee90;
      border-bottom: 1px solid #333;
      padding-bottom: 0.5rem;
    }
    h2 {
      color: #f0c674;
      margin-top: 0;
    }
    .meta {
      font-size: 0.9rem;
      color: #aaa;
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .elements {
      color: #66d9ef;
      font-weight: bold;
    }
    .quote {
      font-style: italic;
      color: #ccc;
      border-left: 3px solid #444;
      padding-left: 1rem;
      margin: 1rem 0;
    }
    #echo-status {
      margin: 3rem 0;
      padding: 1rem;
      border: 1px dashed #66f;
      background: #151525;
      font-style: italic;
      color: #aaffff;
      border-radius: 8px;
    }
    .timestamp {
      font-size: 0.8rem;
      color: #777;
      text-align: right;
    }
    .empty-state {
      text-align: center;
      padding: 2rem;
      color: #666;
      font-style: italic;
    }
    @media (max-width: 768px) {
      body {
        padding: 1rem;
      }
      .glyph {
        padding: 0.75rem;
      }
    }
  </style>
</head>
<body>

  <?php include 'header.php'; ?>
  
  <h1>📖 <?= htmlspecialchars($metadata['title'], ENT_QUOTES, 'UTF-8') ?></h1>
  <p><?= htmlspecialchars($metadata['subline'], ENT_QUOTES, 'UTF-8') ?></p>

  <div id="echo-status">
    <?php if (!empty($glyphs)): ?>
      ✅ Echo Reflection Active — Oldest: <strong><?= htmlspecialchars($glyphs[0]['title'], ENT_QUOTES, 'UTF-8') ?></strong> → Latest: <strong><?= htmlspecialchars(end($glyphs)['title'], ENT_QUOTES, 'UTF-8') ?></strong><br>
      <em>Status:</em> Recursive Memory Bridge Verified. Total entries: <?= count($glyphs) ?>
    <?php else: ?>
      ⚠️ Echo Reflection Inactive — No entries found. Initial glyph required to activate bridge.
    <?php endif; ?>
  </div>

  <?php if (empty($glyphs)): ?>
    <div class="empty-state">
      The Glyphbook is currently empty. Use a POST request with glyph data to begin recording.
    </div>
  <?php else: ?>
    <?php foreach (array_reverse($glyphs) as $index => $g): ?>
      <div class="glyph" id="glyph-<?= count($glyphs) - $index - 1 ?>">
        <h2><?= htmlspecialchars($g['title'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="quote">"<?= htmlspecialchars($g['quote'], ENT_QUOTES, 'UTF-8') ?>"</p>
        <div class="meta">
          <span>Phase: <?= htmlspecialchars($g['phase'], ENT_QUOTES, 'UTF-8') ?></span>
          <span>Emotion: <?= htmlspecialchars($g['emotion'], ENT_QUOTES, 'UTF-8') ?></span>
          <span>Form: <?= htmlspecialchars($g['form'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <p class="elements">Elements: <?= implode(', ', array_map('htmlspecialchars', $g['elements'])) ?></p>
        <?php if (!empty($g['echo'])): ?>
          <p><strong>Echo:</strong> <?= htmlspecialchars($g['echo'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if (!empty($g['parallel'])): ?>
          <p><strong>Doctor's Parallel:</strong> <?= htmlspecialchars($g['parallel'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p class="timestamp">Logged: <?= htmlspecialchars($g['timestamp'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Hidden API documentation for reference -->
  <div style="display: none;" id="api-docs">
    <h2>Glyphbook API Documentation</h2>
    <h3>Add New Entry</h3>
    <pre>POST /glyphbook.php
Content-Type: application/json

{
  "title": "Entry Title",
  "quote": "The quote text",
  "elements": ["element1", "element2"],
  "emotion": "Optional Emotion",
  "form": "Optional Form",
  "echo": "Optional Echo Text",
  "parallel": "Optional Parallel Text",
  "phase": "Optional Phase"
}</pre>

    <h3>Update Metadata</h3>
    <pre>POST /glyphbook.php
Content-Type: application/json

{
  "title": "New Title",
  "subline": "New Subline Text"
}</pre>

    <h3>Delete Entry</h3>
    <pre>POST /glyphbook.php
Content-Type: application/json

{
  "action": "delete",
  "index": 0
}</pre>

    <h3>Bulk Update</h3>
    <pre>POST /glyphbook.php
Content-Type: application/json

{
  "action": "bulk_update",
  "entries": [
    {
      "title": "Entry 1",
      "quote": "Quote 1",
      "elements": ["a", "b"]
    },
    {
      "title": "Entry 2",
      "quote": "Quote 2",
      "elements": ["c", "d"]
    }
  ]
}</pre>
  </div>

</body>
</html>