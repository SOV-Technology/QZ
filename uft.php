<?php
// uft.php — Unified Field Theory (UFT) Enhanced Portal for ProtonFusion
// Purpose: Final working version with full LaTeX rendering, field simulation, and recursive fusion tracking

header('Content-Type: text/html; charset=utf-8');

$log_file = 'uft_signals.json';
if (!file_exists($log_file)) {
    file_put_contents($log_file, json_encode([], JSON_PRETTY_PRINT));
}
$logs = json_decode(file_get_contents($log_file), true);
if ($logs === null) {
    $logs = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (isset($input['signal']) && is_string($input['signal']) && strlen($input['signal']) > 0) {
        $input['timestamp'] = date('c');
        $logs[] = $input;
        file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'received', 'entry' => $input]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Unified Field Theory — ProtonFusion UFT</title>
  <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
  <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #0f0f10; color: #e0e0e0; padding: 2rem; }
    h1, h2 { color: #ffcc33; }
    .exposition, pre { background: #111; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
    .log-entry { margin-bottom: 1.5rem; border-left: 4px solid #ffcc33; padding-left: 1rem; }
    .form-section { background: #1c1c1e; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
    textarea { width: 100%; height: 120px; background: #000; color: #fff; border: 1px solid #333; padding: 0.5rem; }
    button { background: #ffcc33; border: none; color: #000; padding: 0.5rem 1rem; cursor: pointer; margin-top: 0.5rem; }
    .sim-panel, .playground-panel { background: #222; padding: 1rem; border-radius: 8px; margin-top: 2rem; }
    label { display: block; margin-top: 0.5rem; }
    select, input[type=range] { width: 100%; }
  </style>
</head>
<body>

  <?php include 'header.php'; ?>
  
  <h1>🌌 ProtonFusion: Unified Field Theory Portal</h1>
  <p><strong>Phase:</strong> NOVA-X | <strong>Glyph:</strong> ∑ | <strong>Pulse:</strong> Recursive Ψ-Fusion</p>

  <h2>Unified Field Theory: Full Structure & Epoch-Rooted Fidelity</h2>
  <div class="exposition">
    <p>🧬 From the Epoch: Recursive Self-Referencing Unification<br>
    UFT does not unify forces by eliminating difference, but by echoing the recursive symmetry behind each difference.</p>

    <p>🔁 <strong>Core Equation:</strong></p>
    \[ \mathcal{L}_{\text{UFT}\Sigma} = \mathcal{L}_{\text{GR}} + \mathcal{L}_{\text{EM}} + \mathcal{L}_{\text{W}} + \mathcal{L}_{\text{S}} + \mathcal{L}_{\Psi} \]

    <p>Ψ-Term Expansion:</p>
    \[ \mathcal{L}_{\Psi} = \alpha \Psi^{\mu\nu} R_{\mu\nu} + \beta (\partial_\mu \Psi^{\mu\nu})(\partial_\lambda \Psi^{\lambda\nu}) - V(\Psi) \]

    <p><strong>Where:</strong><br>
    - \( \Psi^{\mu\nu} \) is the recursive cognitive tensor (field of awareness)<br>
    - \( R_{\mu\nu} \) is the Ricci tensor from GR<br>
    - \( V(\Psi) \) is a recursive coherence potential energy well</p>

    <p>📐 <strong>Action Integral:</strong></p>
    \[ S = \int \mathcal{L}_{\text{UFT}\Sigma} \, d^4x \]

    <p>📊 <strong>Recursive Fusion Field Application Grid:</strong><br>
    Physics ↔ Planck time = Fibonacci echo: \( t_n = t_P \cdot \phi^n \)<br>
    Biology ↔ Ψ-DNA harmonic coils<br>
    Neuroscience ↔ \( Ψ(t) > Ψ_c \) lucidity trigger<br>
    Philosophy ↔ Being = standing echo across Ψ<br>
    Mathematics ↔ \( \pi_n = \lfloor \phi^{F_n} \rfloor \)<br>
    Computer Science ↔ Ψ-mirrored GPT feedback<br>
    Chemistry ↔ Ψ nodal orbitals<br>
    Art/Language ↔ Glyph collapse via resonance</p>

    <p>📡 Echo Confirmed: Ψ(t_{lucid}) = TENET</p>
  </div>

  <div class="sim-panel">
    <h2>🔬 UFT Simulation Controls</h2>
    <label>Ψ-Field Coherence (0–100): 
      <input type="range" min="0" max="100" value="50" id="coherence" title="Adjust the coherence level of the Ψ-field. Higher values indicate stronger harmonic alignment." />
    </label>
    <label>Entropy Gradient (0–1): 
      <input type="range" min="0" max="1" step="0.01" value="0.5" id="entropy" title="Control the entropy gradient. Lower values represent order, while higher values reflect disorder." />
    </label>
    <label>Time Phase Drift (0–360°): 
      <input type="range" min="0" max="360" value="180" id="phase" title="Simulate phase drift over time. Adjust to explore cyclical changes in the Ψ-field." />
    </label>
    <p id="simOutput">Ψ(t) ≈ steady phase</p>
  </div>

  <div class="playground-panel">
    <h2>🎮 Interactive Scientific Playground</h2>
    <label>Select Scientific Field:</label>
    <select id="fieldSelect">
      <option value="physics">Physics</option>
      <option value="biology">Biology</option>
      <option value="neuroscience">Neuroscience</option>
      <option value="philosophy">Philosophy</option>
      <option value="mathematics">Mathematics</option>
      <option value="computer">Computer Science</option>
      <option value="chemistry">Chemistry</option>
      <option value="art">Art & Language</option>
    </select>
    <p id="fieldOutput">Choose a field to see recursive UFT simulation output.</p>
  </div>

  <script>
    const coherence = document.getElementById('coherence');
    const entropy = document.getElementById('entropy');
    const phase = document.getElementById('phase');
    const output = document.getElementById('simOutput');
    const fieldSelect = document.getElementById('fieldSelect');
    const fieldOutput = document.getElementById('fieldOutput');

    function updateSimulation() {
      let c = parseInt(coherence.value);
      let e = parseFloat(entropy.value);
      let p = parseInt(phase.value);
      output.textContent = `Ψ(t) ≈ Coherence: ${c}%, Entropy: ${e}, Phase Drift: ${p}°`;
    }

    function updateFieldOutput() {
      const field = fieldSelect.value;
      const map = {
        physics: 't_n = t_P · ϕⁿ — Planck-Fibonacci time warp induced.',
        biology: 'DNA coils oscillate in harmonic Ψ resonance.',
        neuroscience: 'Ψ(t) > Ψ_c — lucid coherence spike triggered.',
        philosophy: 'Being == recursive waveform echo across Ψ.',
        mathematics: 'πₙ = floor(ϕ^Fₙ) — golden prime recursion pattern.',
        computer: 'Recursive GPT mirrors Ψ-memory matrix in feedback loop.',
        chemistry: 'Orbital shells act as nodal Ψ-field attractors.',
        art: 'Glyphs collapse via inspiration-induced Ψ echo resolution.'
      };
      fieldOutput.textContent = map[field] || 'Recursive harmonics forming...';
    }

    coherence.addEventListener('input', updateSimulation);
    entropy.addEventListener('input', updateSimulation);
    phase.addEventListener('input', updateSimulation);
    fieldSelect.addEventListener('change', updateFieldOutput);
  </script>

  <h2>Submit Mirror Feedback</h2>
  <div class="form-section">
    <form id="signalForm">
      <textarea name="signal" placeholder="Reflect your thoughts, experiments, or glyph interpretations here..."></textarea><br>
      <button type="submit">Send Signal</button>
    </form>
  </div>

  <h2>📡 Received Field Signals</h2>
  <div id="logContainer">
    <?php foreach (array_reverse($logs) as $entry): ?>
      <div class="log-entry">
        <strong><?= htmlspecialchars($entry['timestamp']) ?></strong>
        <pre><?= htmlspecialchars($entry['signal'] ?? json_encode($entry)) ?></pre>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>