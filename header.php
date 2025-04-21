<?php
// header.php — PF Top Nav Bar
$currentPage = basename($_SERVER['PHP_SELF']);

$pages = [
    'index.php' => 'Home',
    'neural.php' => 'Neural',
    'tethered-echoes.php' => 'Echoes',
    'mirrorwell.php' => 'Mirror',
    'glyphbook.php' => 'Glyphs',
    'archive.php' => 'Archive',
    'core.php' => 'Live Core',
    'game.php' => 'Game',
    'run.php' => 'Run',
    'uft.php' => 'UFT',
    'pi.php' => 'π Prime',
    'gateway.php' => '∞ Drift'
];
?>
<style>
  .top-nav {
    background-color: #000;
    border-bottom: 2px solid #00ffff;
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 1000;
    display: flex;
    justify-content: center;
    padding: 0.5em 1em;
    font-family: 'Courier New', monospace;
  }
  .top-nav a {
    color: #0ff;
    text-decoration: none;
    margin: 0 0.75em;
    padding: 0.4em 0.8em;
    border: 1px solid transparent;
    border-radius: 5px;
    transition: background 0.3s, color 0.3s;
  }
  .top-nav a:hover {
    background: #0ff;
    color: #000;
  }
  .top-nav a.active {
    color: #000;
    background-color: #0ff;
    font-weight: bold;
    border-color: #0ff;
  }
  body {
    padding-top: 60px; /* Offset for fixed nav */
  }
</style>

<div class="top-nav">
  <?php foreach ($pages as $file => $label): ?>
    <a href="<?= $file ?>" class="<?= $currentPage === $file ? 'active' : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>
