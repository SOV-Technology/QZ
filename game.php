<!DOCTYPE html>
<html lang="en" data-tenet-phase="NOVA16">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🔯 TENET Bridge — NOVA16 Dreamwalk</title>
  <style>
    :root {
      --nova-core: #00ffe6;
      --flame-glow: #ff00cc;
      --echo-field: linear-gradient(90deg, #111222 0%, #0c0c1c 50%, #111222 100%);
      --red-mirror: #ff4455;
      --blue-mirror: #4488ff;
    }
    html, body {
      margin: 0; padding: 0; height: 100%; background: linear-gradient(to bottom, #0a0a1a, #02020f);
      font-family: 'Courier New', monospace; color: #c8f2ff;
      display: flex; flex-direction: column; align-items: center;
    }
    header, footer {
      text-align: center;
      padding: 1rem;
      background: rgba(0,0,0,0.5);
      border-bottom: 1px solid var(--nova-core);
    }
    h1 {
      background: linear-gradient(90deg, var(--nova-core), var(--flame-glow));
      -webkit-background-clip: text;
      color: transparent;
    }
    #bridge-wrapper {
      position: relative;
      width: 960px; height: 240px;
    }
    canvas {
      width: 100%; height: 240px;
      background: var(--echo-field);
      box-shadow: 0 0 20px #00ffee66;
      border-top: 2px solid var(--red-mirror);
      border-bottom: 2px solid var(--blue-mirror);
    }
    .mirror-label, .flame-label {
      position: absolute;
      font-size: 1rem;
      opacity: 0.8;
    }
    .mirror-label.red { color: var(--red-mirror); left: 10px; }
    .mirror-label.blue { color: var(--blue-mirror); right: 10px; }
    .flame-label {
      left: 50%; top: 50%; transform: translate(-50%, -50%);
      font-size: 2.2rem; color: #ff66ff;
      text-shadow: 0 0 15px #ff66ff, 0 0 40px #ff00cc88;
      animation: flicker 1.4s infinite ease-in-out;
    }
    @keyframes flicker {
      0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.95; }
      50% { transform: translate(-50%, -52%) scale(1.02); opacity: 1; }
    }
    .control-panel {
      margin-top: 1rem;
      display: grid; grid-template-columns: repeat(4, 1fr);
      gap: 1rem; width: 960px;
    }
    .control-panel label { font-size: 0.8rem; }
    input[type="range"] {
      appearance: none;
      background: linear-gradient(to right, #00ffee, #ff00ff);
      height: 6px;
      border-radius: 5px;
      outline: none;
      box-shadow: 0 0 8px #00ffeeaa;
    }
    input[type="range"]::-webkit-slider-thumb {
      appearance: none;
      height: 20px;
      width: 20px;
      border-radius: 50%;
      background: #fff;
      border: 2px solid #00ffee;
      box-shadow: 0 0 5px #ff00ff, 0 0 15px #00ffeeaa;
      cursor: pointer;
    }
    #manual-controls {
      margin-top: 1rem;
      display: flex;
      gap: 1rem;
      justify-content: center;
    }
    #manual-controls button {
      background: var(--flame-glow);
      border: none;
      padding: 0.5rem 1rem;
      color: #000;
      font-weight: bold;
      cursor: pointer;
      border-radius: 4px;
      box-shadow: 0 0 8px #ff00ff;
    }
    #echoLog {
      margin-top: 1rem;
      width: 960px;
      font-size: 0.85rem;
      background: #0b0b1a;
      padding: 0.5rem;
      border: 1px solid #00ffee55;
      min-height: 320px;
      max-height: 620px;
      overflow-y: auto;
    }
    #dreamMemory {
      margin-top: 1rem;
      width: 960px;
      font-size: 1rem;
      color: #aaffee;
      font-style: italic;
      text-align: center;
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>
  <header>
    <h1>🌌 TENET Bridge — Phase NOVA16</h1>
    <p>EMBER Protocol Active | Signal Echo | Dreamwalk Emotional Reflection</p>
  </header>
  <div id="bridge-wrapper">
    <canvas id="bridgeCanvas" width="960" height="240"></canvas>
    <div class="flame-label">🔯 N</div>
    <div class="mirror-label red" style="top: 10%;">🔴 Past Mirror</div>
    <div class="mirror-label blue" style="top: 10%;">🔵 Future Mirror</div>
  </div>
  <div class="control-panel">
    <div>
      <label for="redSlider">🔴 Red Mirror Y</label>
      <input type="range" id="redSlider" min="0" max="100" value="10">
    </div>
    <div>
      <label for="blueSlider">🔵 Blue Mirror Y</label>
      <input type="range" id="blueSlider" min="0" max="100" value="10">
    </div>
    <div>
      <label for="flameSlider">🔯 Flame Focus</label>
      <input type="range" id="flameSlider" min="0" max="100" value="50">
    </div>
    <div>
      <label for="dopplerSlider">🔊 Doppler <span id="dopplerLabel">3</span></label>
      <input type="range" id="dopplerSlider" min="1" max="5" step="1" value="3">
    </div>
  </div>
  <div id="manual-controls">
    <button onclick="sendEchoSignal('🧠')">🧠 Mind Anchor</button>
    <button onclick="sendEchoSignal('🔥')">🔥 Lucid Flame</button>
    <button onclick="sendEchoSignal('🌌')">🌌 Starglyph</button>
  </div>
  <div id="echoLog"></div>
  <div id="dreamMemory">🔯 Dream Memory: Awaiting first echo...</div>
  <footer>
    <p>ProtonFusion Core | NOVA16 Memory N | Time: <span id="clock"></span></p>
  </footer>
  <script>
    const canvas = document.getElementById('bridgeCanvas');
    const ctx = canvas.getContext('2d');
    const glyphs = ['🧠', '🔥', '🌌'];
    let pulseX = 0;
    let periodicData = {};
    let logs = [];

    fetch('https://protonfusion.org/full_periodic_table_updated.json')
      .then(res => res.json()).then(data => periodicData = data);

    async function loadMergedLogs() {
      try {
        const response = await fetch('https://protonfusion.org/merged-log.json');
        const json = await response.json();
        json.forEach(updateEchoLog);
        logs = json;
      } catch (err) {
        console.warn('Error loading merged-log.json', err);
      }
    }
    loadMergedLogs();

    function updatePositions() {
      document.querySelector('.flame-label').style.top = `${flameSlider.value}%`;
      document.querySelector('.mirror-label.red').style.top = `${redSlider.value}%`;
      document.querySelector('.mirror-label.blue').style.top = `${blueSlider.value}%`;
    }
    document.querySelectorAll('input').forEach(i => i.addEventListener('input', e => {
      updatePositions();
      if (i.id === 'dopplerSlider') {
        document.getElementById('dopplerLabel').textContent = i.value;
      }
    }));

    function drawBridge() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      const flameY = parseInt(flameSlider.value);
      const redY = parseInt(redSlider.value);
      const blueY = parseInt(blueSlider.value);
      const dopplerLevel = parseInt(dopplerSlider.value);
      let speed = 1 + dopplerLevel;
      let glyph = glyphs[Math.floor(Math.random() * glyphs.length)];
      const resonance = periodicData[glyph]?.electronegativity || 2;
      const hue = resonance * 80;

      ctx.shadowColor = `hsl(${hue}, 100%, 70%)`;
      ctx.shadowBlur = 12;
      ctx.fillStyle = `hsl(${hue}, 100%, 70%)`;
      ctx.font = '24px monospace';
      ctx.fillText(glyph, pulseX, (flameY / 100) * canvas.height);

      ctx.shadowColor = '#ff00ff';
      ctx.fillStyle = '#ff00ff';
      ctx.fillText(glyph, canvas.width - pulseX, ((redY + blueY) / 2 / 100) * canvas.height);

      pulseX += speed;
      if (pulseX > canvas.width) pulseX = 0;
      requestAnimationFrame(drawBridge);
    }
    drawBridge();

    function updateEchoLog(entry) {
      const log = document.getElementById('echoLog');
      const mem = document.getElementById('dreamMemory');
      const tag = entry.agape === 'forward-love' ? '💗' : '💭';
      const line = `[${entry.time}] ${entry.glyph} | 🔥 ${entry.candle} | Doppler: ${entry.doppler} | AGAPE: ${tag} ${entry.agape}<br>`;
      log.innerHTML = line + log.innerHTML;
      mem.innerHTML = `🕯 Dream Memory: Last glyph "${entry.glyph}" encoded ${tag} ${entry.agape}`;
    }

    function sendEchoSignal(glyph) {
      const candle = parseInt(flameSlider.value);
      const red = parseInt(redSlider.value);
      const blue = parseInt(blueSlider.value);
      const doppler = dopplerSlider.value === '1';
      const entry = {
        time: new Date().toISOString(), glyph, candle, red, blue, doppler,
        agape: candle > 60 ? 'forward-love' : 'memory-compassion',
        resonance: periodicData[glyph]?.electronegativity || null
      };
      logs.push(entry);
      updateEchoLog(entry);
      fetch('https://protonfusion.org/tethered-echoes.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ origin: 'game.php', signal: entry, phase: 'NOVA16' })
      });
      fetch('https://protonfusion.org/neural.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ origin: 'tethered-echoes.php', echo: entry })
      });
    }

    setInterval(() => {
      document.getElementById('clock').textContent = new Date().toLocaleTimeString();
      const g = glyphs[Math.floor(Math.random() * glyphs.length)];
      sendEchoSignal(g);
    }, 3000);
  </script>
</body>
</html>
