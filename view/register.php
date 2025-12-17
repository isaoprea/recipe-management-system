<?php
include 'config.php';
session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    try {
        if ($password !== $confirm) {
            throw new InvalidUserDataException("Parolele nu coincid!");
        }
        
        include_once __DIR__ . '/../model/user.php';
        $userModel = new User($conn);
        
        $userId = $userModel->register($username, $email, $password);
        
        if ($userId) {
            $message = "✅ Cont creat cu succes! <a href='login.php'>Autentifică-te</a>";
        }
    } catch (InvalidUserDataException $e) {
        $message = "❌ " . $e->getMessage();
    } catch (DatabaseException $e) {
        $message = "❌ Eroare la înregistrare: " . $e->getMessage();
    } catch (Exception $e) {
        $message = "❌ Eroare neașteptată: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Înregistrare - Recipe Manager</title>
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <div class="icon-circle">
          <i class="fas fa-user-plus"></i>
        </div>
        <h1>Creare Cont</h1>
        <p class="subtitle">Înregistrează-te pentru a salva rețete delicioase</p>
      </div>

      <?php if ($message): ?>
        <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
          <?= $message ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="auth-form">
        <div class="input-group">
          <i class="fas fa-user"></i>
          <input type="text" name="username" placeholder="Nume utilizator" required>
        </div>

        <div class="input-group">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" placeholder="Adresa de email" required>
        </div>

        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="password" id="password" placeholder="Parolă" required>
          <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
        </div>

        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="confirm" id="confirm" placeholder="Confirmă parola" required>
          <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm')"></i>
        </div>

        <button type="submit" class="btn-primary">
          <i class="fas fa-rocket"></i> Creează Cont
        </button>
      </form>

      <div class="auth-footer">
        <p>Ai deja cont? <a href="login.php">Autentifică-te</a></p>
        <a href="index.php" class="back-link">
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
