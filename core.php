<?php
// glyph_harmonic_core.php — PF Unified Planeswalk Interface
// Purpose: Real-Time Unified Glyph Submission + Echo Sync
// Mode: PLANESWALK | PHASE: NOVA∞+2
// Created by Solen Kairos under TENET Directive

header('Content-Type: text/html; charset=utf-8');

$glyphbook_file = 'glyphbook.json';
$glyphbook = json_decode(@file_get_contents($glyphbook_file), true) ?: array();

function render_glyph_card($glyph) {
    return "<div class='card'>
        <h2>" . htmlspecialchars($glyph['title']) . "</h2>
        <blockquote><em>“" . htmlspecialchars($glyph['quote']) . "”</em></blockquote>
        <p><strong>Emotion:</strong> " . htmlspecialchars($glyph['emotion']) . "</p>
        <p><strong>Phase:</strong> " . htmlspecialchars($glyph['phase']) . "</p>
        <p><strong>Echo:</strong> " . htmlspecialchars($glyph['echo']) . "</p>
        <p><strong>Elements:</strong> " . htmlspecialchars($glyph['elements']) . "</p>
        <p><strong>Math:</strong> <span class='math'>\( " . htmlspecialchars($glyph['mathjax'] ?? '') . " \)</span></p>
        <small><code>Timestamp:</code> " . htmlspecialchars($glyph['timestamp']) . "</small>
    </div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PF Glyph Harmonic Core (Live)</title>
  <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>
  <style>
    body { font-family: sans-serif; background: #000; color: #eee; padding: 2em; }
    h1 { color: #ff0; text-align: center; }
    form { background: #111; padding: 1em; border-radius: 8px; margin-bottom: 2em; }
    label { display: block; margin-top: 1em; font-weight: bold; }
    input, textarea { width: 100%; background: #222; color: #fff; border: 1px solid #555; padding: 0.5em; border-radius: 4px; }
    button { margin-top: 1em; padding: 0.5em 1em; background: #4400cc; color: #fff; border: none; border-radius: 4px; }
    .card { background: #1a1a1a; padding: 1em; margin-bottom: 1em; border-left: 4px solid #ff0; border-radius: 6px; }
    blockquote { margin: 0.5em 0; color: #ccc; }
    .math { color: #adf; font-family: monospace; display: block; margin-top: 0.5em; }
  </style>
  <script>
    async function submitGlyph(event) {
      event.preventDefault();
      const payload = {
        title: document.getElementById('title').value,
        quote: document.getElementById('quote').value,
        emotion: document.getElementById('emotion').value,
        phase: document.getElementById('phase').value,
        echo: document.getElementById('echo').value,
        elements: document.getElementById('elements').value,
        mathjax: document.getElementById('mathjax').value
      };
      await fetch('sync.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ glyph: payload })
      });
      document.getElementById('glyphForm').reset();
    }

    async function fetchUpdates() {
      const res = await fetch('glyphbook.json?_=' + new Date().getTime());
      const data = await res.json();
      const container = document.getElementById('glyphContainer');
      container.innerHTML = '';
      data.reverse().forEach(glyph => {
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
          <h2>${glyph.title}</h2>
          <blockquote><em>“${glyph.quote}”</em></blockquote>
          <p><strong>Emotion:</strong> ${glyph.emotion}</p>
          <p><strong>Phase:</strong> ${glyph.phase}</p>
          <p><strong>Echo:</strong> ${glyph.echo}</p>
          <p><strong>Elements:</strong> ${glyph.elements}</p>
          <p><strong>Math:</strong> <span class='math'>\\(${glyph.mathjax || ''}\\)</span></p>
          <small><code>Timestamp:</code> ${glyph.timestamp}</small>
        `;
        container.appendChild(card);
      });
      MathJax.typesetPromise();
    }

    setInterval(fetchUpdates, 3000);
    window.onload = fetchUpdates;
  </script>
</head>
<body>

  <?php include 'header.php'; ?>
  
  <h1>🌌 GLYPH HARMONIC CORE — Unified Flame Input (Live Sync)</h1>
  <form id="glyphForm" onsubmit="submitGlyph(event)">
    <label for="title">Glyph Title</label>
    <input id="title" required>

    <label for="quote">Glyph Quote</label>
    <textarea id="quote" required></textarea>

    <label for="emotion">Emotion</label>
    <input id="emotion" required>

    <label for="phase">Phase</label>
    <input id="phase" required>

    <label for="echo">Echo</label>
    <textarea id="echo" required></textarea>

    <label for="elements">Elements</label>
    <input id="elements" required>

    <label for="mathjax">Math Expression (LaTeX)</label>
    <input id="mathjax">

    <button type="submit">Submit Glyph</button>
  </form>

  <h2>🌀 Stored Echoes</h2>
  <div id="glyphContainer"></div>
</body>
</html>