<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>🍽️ Gătește cu Noi</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
  <h1>🍲 Bine ai venit la <span style="color:yellow;">Gătește cu Noi</span>!</h1>
  <nav>
    <a href="index.php?page=lista" class="btn">📖 Vezi toate rețetele</a>
    <a href="index.php?page=adauga" class="btn">➕ Adaugă rețetă nouă</a>
    
    <?php if (isset($_SESSION['user_id'])): ?>
      <span class="btn" style="background: #4CAF50;">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
      <a href="logout.php" class="btn" style="background: #f44336;">🚪 Deconectare</a>
    <?php else: ?>
      <a href="index.php?page=login" class="btn">🔐 Autentificare</a>
      <a href="index.php?page=register" class="btn">🧾 Înregistrare</a>
    <?php endif; ?>
    
    <a href="index.php?page=despre" class="btn">ℹ️ Despre site</a>
  </nav>
</header>

<?php if (isset($_SESSION['success'])): ?>
  <div style="background: #4CAF50; color: white; padding: 15px; text-align: center;">
    ✅ <?= $_SESSION['success'] ?>
  </div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div style="background: #f44336; color: white; padding: 15px; text-align: center;">
    ❌ <?= $_SESSION['error'] ?>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>