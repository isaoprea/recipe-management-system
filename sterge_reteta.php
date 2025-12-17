<?php
session_start();
require_once 'config.php';
require_once __DIR__ . '/model/Recipe.php';
require_once __DIR__ . '/model/exceptions.php';

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
    // Instanțiem Model-ul (MVC)
    $recipeModel = new Recipe($conn);
    
    // Verificăm dacă rețeta există și aparține utilizatorului
    $recipe = $recipeModel->getRecipeById($recipe_id);
    
    // Verificăm ownership (dacă rețeta aparține user-ului curent)
    $stmt = $conn->prepare("SELECT user_id FROM recipes WHERE recipe_id = ?");
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $owner = $result->fetch_assoc();
    $stmt->close();
    
    if (!$owner || $owner['user_id'] != $_SESSION['user_id']) {
        $_SESSION['error'] = 'Nu ai permisiunea să ștergi această rețetă!';
        header('Location: index.php?page=lista');
        exit();
    }
    
    // Salvăm calea imaginii pentru ștergere ulterioară
    $image_path = $recipe['image'];
    
    // ✅ Folosim Model-ul pentru ștergere (MVC pattern)
    $recipeModel->deleteRecipe($recipe_id);
    
    // Ștergem imaginea dacă nu este cea implicită
    if (!empty($image_path) && $image_path !== 'imagini/default.jpeg' && file_exists($image_path)) {
        unlink($image_path);
    }
    
    $_SESSION['success'] = '✅ Rețeta a fost ștearsă cu succes!';
    header('Location: index.php?page=lista');
    exit();
    
} catch (RecipeNotFoundException $e) {
    $_SESSION['error'] = '❌ ' . $e->getMessage();
    header('Location: index.php?page=lista');
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = '❌ Eroare la ștergerea rețetei: ' . $e->getMessage();
    header('Location: index.php?page=lista');
    exit();
}
?>
