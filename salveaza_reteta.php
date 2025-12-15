<?php
session_start();
require_once 'config.php';

// Verificăm autentificarea
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Trebuie să fii autentificat!';
    header('Location: index.php?page=login');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=adauga');
    exit();
}

try {
    // Preluăm datele din formular
    $recipe_name = trim($_POST['recipe_name']);
    $description = trim($_POST['description']);
    $prep_time = (int)$_POST['prep_time'];
    $cook_time = (int)$_POST['cook_time'];
    $servings = (int)$_POST['servings'];
    $difficulty = (int)$_POST['difficulty'];
    $category_id = (int)$_POST['category_id'];
    $user_id = $_SESSION['user_id'];
    
    // Upload imagine (dacă există)
    $image_path = 'imagini/default.jpeg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/imagini/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'recipe_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = 'imagini/' . $new_filename;
        }
    }
    
    // Începe tranzacția
    $conn->begin_transaction();
    
    // Inserăm rețeta
    $stmt = $conn->prepare("
        INSERT INTO recipes (recipe_name, description, prep_time, cook_time, servings, difficulty, image, category_id, user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssiiisiii", $recipe_name, $description, $prep_time, $cook_time, $servings, $difficulty, $image_path, $category_id, $user_id);
    $stmt->execute();
    $recipe_id = $conn->insert_id;
    $stmt->close();
    
    // Inserăm ingredientele
    if (isset($_POST['ingredients']) && is_array($_POST['ingredients'])) {
        foreach ($_POST['ingredients'] as $ingredient) {
            $ingredient = trim($ingredient);
            if (!empty($ingredient)) {
                // Parsăm ingredientul (ex: "500g făină" -> quantity: 500, unit: g, name: făină)
                preg_match('/^(\d+(?:\.\d+)?)\s*([a-zA-Z]*)\s+(.+)$/', $ingredient, $matches);
                
                if (count($matches) === 4) {
                    $quantity = $matches[1];
                    $unit = !empty($matches[2]) ? $matches[2] : 'buc';
                    $ing_name = $matches[3];
                } else {
                    // Dacă nu putem parsa, salvăm tot ca nume
                    $quantity = 1;
                    $unit = 'buc';
                    $ing_name = $ingredient;
                }
                
                // Verificăm dacă ingredientul există
                $stmt = $conn->prepare("SELECT ingredient_id FROM ingredients WHERE ingredient_name = ?");
                $stmt->bind_param("s", $ing_name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    $ingredient_id = $row['ingredient_id'];
                } else {
                    // Creăm ingredientul nou
                    $stmt2 = $conn->prepare("INSERT INTO ingredients (ingredient_name, unit_of_measure) VALUES (?, ?)");
                    $stmt2->bind_param("ss", $ing_name, $unit);
                    $stmt2->execute();
                    $ingredient_id = $conn->insert_id;
                    $stmt2->close();
                }
                $stmt->close();
                
                // Adăugăm legătura rețetă-ingredient
                $stmt = $conn->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $recipe_id, $ingredient_id, $quantity);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    // Inserăm pașii de preparare
    if (isset($_POST['steps']) && is_array($_POST['steps'])) {
        $step_number = 1;
        foreach ($_POST['steps'] as $step) {
            $step = trim($step);
            if (!empty($step)) {
                $stmt = $conn->prepare("INSERT INTO preparation_steps (recipe_id, step_number, step_description) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $recipe_id, $step_number, $step);
                $stmt->execute();
                $stmt->close();
                $step_number++;
            }
        }
    }
    
    // Confirmăm tranzacția
    $conn->commit();
    
    $_SESSION['success'] = '✅ Rețeta a fost adăugată cu succes!';
    header('Location: index.php?page=detalii&id=' . $recipe_id);
    exit();
    
} catch (Exception $e) {
    // Anulăm tranzacția în caz de eroare
    $conn->rollback();
    $_SESSION['error'] = '❌ Eroare la adăugarea rețetei: ' . $e->getMessage();
    header('Location: index.php?page=adauga');
    exit();
}