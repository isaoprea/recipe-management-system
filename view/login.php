<?php
session_start();

include __DIR__ . '/../config.php';
include __DIR__ . '/../model/User.php';

$userModel = new User($conn);
$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $user = $userModel->getUserByUsername($username);

    if ($user && password_verify($password, $user['password_hash'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        $_SESSION['success'] = "Conectare reușită!";
          header("Location: /retete/index.php");
        exit();
    } else {
        $message = " Nume utilizator sau parolă incorectă!";
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Autentificare - Recipe Manager</title>
  <link rel="stylesheet" href="/retete/css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <div class="icon-circle">
          <i class="fas fa-sign-in-alt"></i>
        </div>
        <h1>Bine ai revenit!</h1>
        <p class="subtitle">Autentifică-te pentru a accesa rețetele tale</p>
      </div>

      <?php if ($message): ?>
        <div class="message error">
          <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <div class="input-group">
          <i class="fas fa-user"></i>
          <input type="text" name="username" placeholder="Nume utilizator" required autocomplete="username">
        </div>

        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" id="password" placeholder="Parolă" required autocomplete="current-password">
          <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
        </div>

        <button type="submit" class="btn-primary">
          <i class="fas fa-sign-in-alt"></i> Conectează-te
        </button>
      </form>

      <div class="auth-footer">
        <p>Nu ai cont? <a href="../index.php?page=register">Creează unul acum</a></p>
        <a href="../index.php" class="back-link">
          <i class="fas fa-arrow-left"></i> Înapoi la pagina principală
        </a>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(inputId) {
      const input = document.getElementById(inputId);
      const icon = input.parentElement.querySelector('.toggle-password');
      
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }
  </script>
</body>
</html>
