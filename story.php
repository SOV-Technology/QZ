<?php
session_start();

// Load glyphbook.json
$glyphbookFile = 'glyphbook.json';
if (!file_exists($glyphbookFile)) {
    die("glyphbook.json not found. Please upload it.");
}

$glyphbookData = json_decode(file_get_contents($glyphbookFile), true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($glyphbookData)) {
    die("Invalid glyphbook.json format.");
}

// Process glyph data
$validGlyphs = [];
$elementIndex = [];
$emotionIndex = [];
$phaseIndex = [];

foreach ($glyphbookData as $entry) {
    if (is_array($entry) && isset($entry['title'])) {
        $id = $entry['title'];
        $validGlyphs[$id] = $entry;
        
        // Index by elements
        if (isset($entry['elements'])) {
            $elements = is_array($entry['elements']) ? $entry['elements'] : [$entry['elements']];
            foreach ($elements as $element) {
                if (!isset($elementIndex[$element])) {
                    $elementIndex[$element] = [];
                }
                $elementIndex[$element][] = $id;
            }
        }
        
        // Index by emotions
        if (isset($entry['emotion'])) {
            $emotions = preg_split('/\s*\|\s*/', $entry['emotion']);
            foreach ($emotions as $emotion) {
                if (!isset($emotionIndex[$emotion])) {
                    $emotionIndex[$emotion] = [];
                }
                $emotionIndex[$emotion][] = $id;
            }
        }
        
        // Index by phase
        if (isset($entry['phase'])) {
            $phase = $entry['phase'];
            if (!isset($phaseIndex[$phase])) {
                $phaseIndex[$phase] = [];
            }
            $phaseIndex[$phase][] = $id;
        }
    }
}

// Initialize session state
if (!isset($_SESSION['current_glyph']) || !isset($validGlyphs[$_SESSION['current_glyph']])) {
    $_SESSION['current_glyph'] = array_key_first($validGlyphs);
}
if (!isset($_SESSION['echo_log'])) {
    $_SESSION['echo_log'] = [];
}
if (!isset($_SESSION['elements_collected'])) {
    $_SESSION['elements_collected'] = [];
}
if (!isset($_SESSION['emotions_encountered'])) {
    $_SESSION['emotions_encountered'] = [];
}
if (!isset($_SESSION['visited_glyphs'])) {
    $_SESSION['visited_glyphs'] = [];
}

// Handle AJAX POST to update state
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['glyph'])) {
    header('Content-Type: application/json');
    $glyphId = $_POST['glyph'];
    if (isset($validGlyphs[$glyphId])) {
        $_SESSION['current_glyph'] = $glyphId;
        $glyph = $validGlyphs[$glyphId];

        if (isset($glyph['echo'])) {
            $_SESSION['echo_log'][] = $glyph['echo'];
        }

        if (isset($glyph['elements'])) {
            $elements = is_array($glyph['elements']) ? $glyph['elements'] : [$glyph['elements']];
            $_SESSION['elements_collected'] = array_unique(array_merge($_SESSION['elements_collected'], $elements));
        }

        if (isset($glyph['emotion'])) {
            $emotions = preg_split('/\s*\|\s*/', $glyph['emotion']);
            foreach ($emotions as $emotion) {
                if (!in_array($emotion, $_SESSION['emotions_encountered'])) {
                    $_SESSION['emotions_encountered'][] = $emotion;
                }
            }
        }
        
        if (!in_array($glyphId, $_SESSION['visited_glyphs'])) {
            $_SESSION['visited_glyphs'][] = $glyphId;
        }
    }
    echo json_encode(['status' => 'success']);
    exit();
}

// Validate current glyph
$currentGlyphId = $_SESSION['current_glyph'];
if (!isset($validGlyphs[$currentGlyphId])) {
    $currentGlyphId = array_key_first($validGlyphs);
    $_SESSION['current_glyph'] = $currentGlyphId;
}
$currentGlyph = $validGlyphs[$currentGlyphId];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Glyphbook Interactive Story</title>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #1c1c1c; color: #e0e0e0; margin: 20px; }
        h2 { color: #f0f0f0; }
        .choice-btn { display: block; margin: 10px 0; padding: 10px; background: #007bff; color: #fff; border: none; cursor: pointer; border-radius: 5px; }
        .choice-btn:hover { background: #0056b3; }
        .glyph-meta { font-size: 0.9em; color: #aaa; margin-top: 10px; }
        .section-title { margin-top: 20px; font-size: 1.2em; color: #ccc; }
        .log-entry { margin-bottom: 5px; padding-left: 10px; border-left: 2px solid #555; }
        .fragment-entry { margin-bottom: 10px; padding: 10px; background: #2b2b2b; border-left: 3px solid #555; font-size: 0.9em; }
        .path-option { margin-left: 20px; font-size: 0.9em; color: #aaa; }
        .visited { opacity: 0.7; }
    </style>
</head>
<body>
<div id="glyph-container"></div>
<div id="echo-log"></div>
<div id="elements-collected"></div>
<div id="emotions-encountered"></div>
<div id="fragments-section" style="margin-top: 20px;">
    <button id="reveal-fragments" class="choice-btn">Reveal Fragments (Easter Eggs)</button>
    <div id="fragments-content" style="display:none;"></div>
</div>

<script>
$(document).ready(function () {
    const glyphData = <?php echo json_encode($validGlyphs, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS); ?>;
    const elementIndex = <?php echo json_encode($elementIndex, JSON_UNESCAPED_UNICODE); ?>;
    const emotionIndex = <?php echo json_encode($emotionIndex, JSON_UNESCAPED_UNICODE); ?>;
    const phaseIndex = <?php echo json_encode($phaseIndex, JSON_UNESCAPED_UNICODE); ?>;
    const fragmentsData = <?php echo json_encode(array_filter($glyphbookData, function($entry) {
        return !(is_array($entry) && isset($entry['title']));
    }), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS); ?>;
    
    let currentGlyph = <?php echo json_encode($currentGlyphId); ?>;
    let echoLog = <?php echo json_encode($_SESSION['echo_log']); ?>;
    let elementsCollected = <?php echo json_encode($_SESSION['elements_collected']); ?>;
    let emotionsEncountered = <?php echo json_encode($_SESSION['emotions_encountered']); ?>;
    let visitedGlyphs = <?php echo json_encode($_SESSION['visited_glyphs']); ?>;

    function findRelatedGlyphs(currentGlyphId) {
        const currentGlyph = glyphData[currentGlyphId];
        if (!currentGlyph) return [];
        
        let related = new Set();
        
        // Connect by shared elements
        if (currentGlyph.elements) {
            const elements = Array.isArray(currentGlyph.elements) ? currentGlyph.elements : [currentGlyph.elements];
            elements.forEach(element => {
                if (elementIndex[element]) {
                    elementIndex[element].forEach(glyphId => {
                        if (glyphId !== currentGlyphId) related.add(glyphId);
                    });
                }
            });
        }
        
        // Connect by shared emotions
        if (currentGlyph.emotion) {
            const emotions = currentGlyph.emotion.split(/\s*\|\s*/);
            emotions.forEach(emotion => {
                if (emotionIndex[emotion]) {
                    emotionIndex[emotion].forEach(glyphId => {
                        if (glyphId !== currentGlyphId) related.add(glyphId);
                    });
                }
            });
        }
        
        // Connect by phase
        if (currentGlyph.phase && phaseIndex[currentGlyph.phase]) {
            phaseIndex[currentGlyph.phase].forEach(glyphId => {
                if (glyphId !== currentGlyphId) related.add(glyphId);
            });
        }
        
        // Connect by similar titles (same number or similar naming)
        const titleMatch = currentGlyph.title.match(/(GLYPH\s*[^\s:]+)/i);
        if (titleMatch) {
            const titleBase = titleMatch[1];
            Object.keys(glyphData).forEach(glyphId => {
                if (glyphId !== currentGlyphId && glyphId.includes(titleBase)) {
                    related.add(glyphId);
                }
            });
        }
        
        return Array.from(related);
    }

    function generatePathDescription(currentGlyph, targetGlyph) {
        const connections = [];
        
        // Check element connections
        const currentElements = currentGlyph.elements ? 
            (Array.isArray(currentGlyph.elements) ? currentGlyph.elements : [currentGlyph.elements]) : [];
        const targetElements = targetGlyph.elements ? 
            (Array.isArray(targetGlyph.elements) ? targetGlyph.elements : [targetGlyph.elements]) : [];
        const sharedElements = currentElements.filter(el => targetElements.includes(el));
        if (sharedElements.length > 0) {
            connections.push(`Shared elements: ${sharedElements.join(', ')}`);
        }
        
        // Check emotion connections
        const currentEmotions = currentGlyph.emotion ? currentGlyph.emotion.split(/\s*\|\s*/) : [];
        const targetEmotions = targetGlyph.emotion ? targetGlyph.emotion.split(/\s*\|\s*/) : [];
        const sharedEmotions = currentEmotions.filter(em => targetEmotions.includes(em));
        if (sharedEmotions.length > 0) {
            connections.push(`Shared emotions: ${sharedEmotions.join(', ')}`);
        }
        
        // Check phase connections
        if (currentGlyph.phase && targetGlyph.phase && currentGlyph.phase === targetGlyph.phase) {
            connections.push(`Same phase: ${currentGlyph.phase}`);
        }
        
        // Check title connections
        const currentTitleMatch = currentGlyph.title.match(/(GLYPH\s*[^\s:]+)/i);
        const targetTitleMatch = targetGlyph.title.match(/(GLYPH\s*[^\s:]+)/i);
        if (currentTitleMatch && targetTitleMatch && currentTitleMatch[1] === targetTitleMatch[1]) {
            connections.push(`Related glyph: ${currentTitleMatch[1]}`);
        }
        
        // Check parallel text for connections
        if (currentGlyph.parallel && targetGlyph.parallel) {
            const currentWords = currentGlyph.parallel.split(/\s+/);
            const targetWords = targetGlyph.parallel.split(/\s+/);
            const sharedWords = currentWords.filter(word => 
                word.length > 3 && targetWords.includes(word));
            if (sharedWords.length > 2) {
                connections.push(`Related concepts: ${sharedWords.slice(0, 3).join(', ')}...`);
            }
        }
        
        return connections.length > 0 ? connections.join(' • ') : "Intuitive connection";
    }

    function renderGlyph(glyphId) {
        const glyph = glyphData[glyphId];
        if (!glyph) {
            $("#glyph-container").html("<p>Glyph not found. Please refresh or check glyphbook.json.</p>");
            return;
        }

        let html = `<h2>${glyph.title || 'Untitled Glyph'}</h2>`;
        if (glyph.quote) html += `<p><em>${glyph.quote}</em></p>`;
        if (glyph.emotion) html += `<p><strong>Emotion:</strong> ${glyph.emotion}</p>`;
        if (glyph.phase) html += `<p><strong>Phase:</strong> ${glyph.phase}</p>`;
        if (glyph.echo) html += `<p><strong>Echo:</strong> ${glyph.echo}</p>`;
        if (glyph.parallel) html += `<p><strong>Parallel:</strong> ${glyph.parallel}</p>`;
        if (glyph.form) html += `<p><strong>Form:</strong> ${glyph.form}</p>`;
        if (glyph.elements) {
            let elements = Array.isArray(glyph.elements) ? glyph.elements : [glyph.elements];
            html += `<div class="glyph-meta"><strong>Elements:</strong> ${elements.join(', ')}</div>`;
        }

        html += `<div class="section-title">Possible Paths:</div>`;
        
        const relatedGlyphs = findRelatedGlyphs(glyphId);
        if (relatedGlyphs.length > 0) {
            relatedGlyphs.forEach(targetGlyphId => {
                const targetGlyph = glyphData[targetGlyphId];
                const isVisited = visitedGlyphs.includes(targetGlyphId);
                const pathDesc = generatePathDescription(glyph, targetGlyph);
                html += `
                    <button class="choice-btn ${isVisited ? 'visited' : ''}" data-next="${targetGlyphId}">
                        ${targetGlyph.title}
                        <div class="path-option">${pathDesc}</div>
                    </button>
                `;
            });
        } else {
            html += `<p><em>No clear paths forward. Meditate on the elements and emotions to find connections.</em></p>`;
            
            // Fallback - show random glyphs if no connections found
            const allGlyphIds = Object.keys(glyphData);
            const randomSample = [];
            while (randomSample.length < 3 && randomSample.length < allGlyphIds.length) {
                const randomId = allGlyphIds[Math.floor(Math.random() * allGlyphIds.length)];
                if (randomId !== glyphId && !randomSample.includes(randomId)) {
                    randomSample.push(randomId);
                }
            }
            
            if (randomSample.length > 0) {
                html += `<div class="section-title">Other Possibilities:</div>`;
                randomSample.forEach(randomId => {
                    const randomGlyph = glyphData[randomId];
                    const isVisited = visitedGlyphs.includes(randomId);
                    html += `
                        <button class="choice-btn ${isVisited ? 'visited' : ''}" data-next="${randomId}">
                            ${randomGlyph.title}
                            <div class="path-option">Unknown connection</div>
                        </button>
                    `;
                });
            }
        }

        $("#glyph-container").html(html);
        $.post("", { glyph: glyphId });
    }

    function renderEchoLog() {
        let html = `<div class="section-title">Echo Log:</div>`;
        if (echoLog.length === 0) {
            html += `<p><em>No echoes yet. Begin your journey.</em></p>`;
        } else {
            echoLog.forEach((echo, index) => {
                html += `<div class="log-entry">${index + 1}. ${echo}</div>`;
            });
        }
        $("#echo-log").html(html);
    }

    function renderElements() {
        let html = `<div class="section-title">Elements Collected:</div>`;
        if (elementsCollected.length === 0) {
            html += `<p><em>None yet.</em></p>`;
        } else {
            html += `<p>${elementsCollected.join(', ')}</p>`;
        }
        $("#elements-collected").html(html);
    }

    function renderEmotions() {
        let html = `<div class="section-title">Emotions Encountered:</div>`;
        if (emotionsEncountered.length === 0) {
            html += `<p><em>None yet.</em></p>`;
        } else {
            html += `<p>${emotionsEncountered.join(', ')}</p>`;
        }
        $("#emotions-encountered").html(html);
    }

    $("#reveal-fragments").click(function() {
        let html = `<div class="section-title">Easter Egg Fragments:</div>`;
        if (fragmentsData.length === 0) {
            html += `<p><em>No hidden fragments found.</em></p>`;
        } else {
            fragmentsData.forEach((fragment, index) => {
                const content = typeof fragment === 'string' ? fragment : 
                    (fragment.title || fragment.id || `Fragment ${index + 1}`);
                html += `<div class="fragment-entry">${content}</div>`;
            });
        }
        $("#fragments-content").html(html).slideToggle();
    });

    renderGlyph(currentGlyph);
    renderEchoLog();
    renderElements();
    renderEmotions();

    $(document).on("click", ".choice-btn", function () {
        const nextGlyph = $(this).data("next");
        currentGlyph = nextGlyph;

        const glyph = glyphData[nextGlyph];
        if (glyph) {
            if (glyph.echo) {
                echoLog.push(glyph.echo);
            }
            if (glyph.elements) {
                let newElements = Array.isArray(glyph.elements) ? glyph.elements : [glyph.elements];
                elementsCollected = Array.from(new Set([...elementsCollected, ...newElements]));
            }
            if (glyph.emotion) {
                const newEmotions = glyph.emotion.split(/\s*\|\s*/);
                newEmotions.forEach(emotion => {
                    if (!emotionsEncountered.includes(emotion)) {
                        emotionsEncountered.push(emotion);
                    }
                });
            }
            if (!visitedGlyphs.includes(nextGlyph)) {
                visitedGlyphs.push(nextGlyph);
            }
        }

        renderGlyph(nextGlyph);
        renderEchoLog();
        renderElements();
        renderEmotions();
        
        // Smooth scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    });
});
</script>
</body>
</html>