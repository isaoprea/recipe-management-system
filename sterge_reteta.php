<?php
session_start();
require_once 'config.php';

// Verificăm autentificarea
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Trebuie să fii autentificat pentru a șterge rețete!';
    header('Location: index.php?page=login');
    exit();
}

// Verificăm dacă avem ID-ul rețetei
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID-ul rețetei lipsește!';
    header('Location: index.php?page=lista');
    exit();
}

$recipe_id = (int)$_GET['id'];

try {
    // Verificăm dacă rețeta există și aparține utilizatorului curent
    $stmt = $conn->prepare("SELECT recipe_id, image FROM recipes WHERE recipe_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $recipe_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Rețeta nu a fost găsită sau nu ai permisiunea să o ștergi!';
        header('Location: index.php?page=lista');
        exit();
    }
    
    $recipe = $result->fetch_assoc();
    $stmt->close();
    
    // Începe tranzacția
    $conn->begin_transaction();
    
    // Ștergem pașii de preparare
    $stmt = $conn->prepare("DELETE FROM preparation_steps WHERE recipe_id = ?");
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $stmt->close();
    
    // Ștergem ingredientele asociate
    $stmt = $conn->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?");
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $stmt->close();
    
    // Ștergem rețeta
    $stmt = $conn->prepare("DELETE FROM recipes WHERE recipe_id = ?");
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $stmt->close();
    
    // Confirmăm tranzacția
    $conn->commit();
    
    // Ștergem imaginea dacă nu este cea implicită
    if (!empty($recipe['image']) && $recipe['image'] !== 'imagini/default.jpeg' && file_exists($recipe['image'])) {
        unlink($recipe['image']);
    }
    
    $_SESSION['success'] = '✅ Rețeta a fost ștearsă cu succes!';
    header('Location: index.php?page=lista');
    exit();
    
} catch (Exception $e) {
    // Anulăm tranzacția în caz de eroare
    $conn->rollback();
    $_SESSION['error'] = '❌ Eroare la ștergerea rețetei: ' . $e->getMessage();
    header('Location: index.php?page=lista');
    exit();
}
?>
