<?php
require_once 'tests/bootstrap.php';

echo "==================================================\n";
echo "   RULARE TESTE PHPUNIT - Recipe Management\n";
echo "==================================================\n\n";

$passedTests = 0;
$totalTests = 0;

function runTest($testName, $callback) {
    global $passedTests, $totalTests;
    $totalTests++;
    echo "Test $totalTests: $testName... ";
    try {
        $result = $callback();
        if ($result) {
            echo "✅ PASS\n";
            $passedTests++;
        } else {
            echo "❌ FAIL\n";
        }
    } catch (Exception $e) {
        echo "❌ FAIL - " . $e->getMessage() . "\n";
    }
}

// Conexiune la baza de date
$conn = new mysqli("127.0.0.1", "root", "", "recipes_db", 3307);
if ($conn->connect_error) {
    die("❌ Conexiune eșuată: " . $conn->connect_error);
}

echo "📦 TESTE RECIPE MODEL\n";
echo "--------------------------------------------------\n";

$recipe = new Recipe($conn);
$testRecipeIds = [];

// Test 1: getAllRecipes returnează array
runTest("getAllRecipes() returnează array", function() use ($recipe) {
    $recipes = $recipe->getAllRecipes();
    return is_array($recipes);
});

// Test 2: addRecipe funcționează
runTest("addRecipe() adaugă rețetă cu succes", function() use ($recipe, &$testRecipeIds) {
    $testData = [
        'recipe_name' => 'Test Recipe PHPUnit',
        'description' => 'This is a test recipe for unit testing',
        'prep_time' => 10,
        'cook_time' => 20,
        'servings' => 4,
        'difficulty' => 1,
        'image' => 'imagini/default.jpeg',
        'category_id' => 1
    ];
    $id = $recipe->addRecipe($testData);
    $testRecipeIds[] = $id;
    return is_int($id) && $id > 0;
});

// Test 3: getRecipeById returnează rețeta corectă
runTest("getRecipeById() returnează rețeta corectă", function() use ($recipe, &$testRecipeIds) {
    $testData = [
        'recipe_name' => 'Test Recipe for GetById',
        'description' => 'Testing getRecipeById method',
        'prep_time' => 15,
        'cook_time' => 25,
        'servings' => 2,
        'difficulty' => 2,
        'image' => 'imagini/default.jpeg',
        'category_id' => 1
    ];
    $id = $recipe->addRecipe($testData);
    $testRecipeIds[] = $id;
    
    $retrievedRecipe = $recipe->getRecipeById($id);
    return is_array($retrievedRecipe) && 
           $retrievedRecipe['recipe_name'] === $testData['recipe_name'] &&
           $retrievedRecipe['prep_time'] == $testData['prep_time'];
});

// Test 4: Exception pentru ID invalid
runTest("getRecipeById() aruncă excepție pentru ID invalid", function() use ($recipe) {
    try {
        $recipe->getRecipeById(999999);
        return false; // Nu ar trebui să ajungă aici
    } catch (RecipeNotFoundException $e) {
        return strpos($e->getMessage(), "999999") !== false;
    }
});

// Test 5: updateRecipe modifică datele
runTest("updateRecipe() modifică datele corect", function() use ($recipe, &$testRecipeIds) {
    $originalData = [
        'recipe_name' => 'Original Recipe Name',
        'description' => 'Original description',
        'prep_time' => 10,
        'cook_time' => 20,
        'servings' => 4,
        'difficulty' => 1,
        'image' => 'imagini/default.jpeg',
        'category_id' => 1
    ];
    $id = $recipe->addRecipe($originalData);
    $testRecipeIds[] = $id;
    
    $updatedData = [
        'recipe_name' => 'Updated Recipe Name',
        'description' => 'Updated description',
        'prep_time' => 15,
        'cook_time' => 30,
        'servings' => 6,
        'difficulty' => 2,
        'image' => 'imagini/default.jpeg',
        'category_id' => 1
    ];
    $recipe->updateRecipe($id, $updatedData);
    
    $retrievedRecipe = $recipe->getRecipeById($id);
    return $retrievedRecipe['recipe_name'] === $updatedData['recipe_name'] &&
           $retrievedRecipe['prep_time'] == $updatedData['prep_time'];
});

// Test 6: deleteRecipe șterge rețeta
runTest("deleteRecipe() șterge rețeta cu succes", function() use ($recipe) {
    $testData = [
        'recipe_name' => 'Recipe to Delete',
        'description' => 'This will be deleted',
        'prep_time' => 5,
        'cook_time' => 10,
        'servings' => 2,
        'difficulty' => 1,
        'image' => 'imagini/default.jpeg',
        'category_id' => 1
    ];
    $id = $recipe->addRecipe($testData);
    $recipe->deleteRecipe($id);
    
    try {
        $recipe->getRecipeById($id);
        return false; // Nu ar trebui să găsească rețeta
    } catch (RecipeNotFoundException $e) {
        return true; // Excepția confirmă că a fost ștearsă
    }
});

echo "\n👤 TESTE USER MODEL\n";
echo "--------------------------------------------------\n";

$user = new User($conn);
$testEmail = "phpunit.test@example.com";
$testUserId = null;

// Test 7: Register user
runTest("register() înregistrează utilizator nou", function() use ($user, $testEmail, &$testUserId) {
    $username = "phpunit_testuser";
    $password = "SecurePassword123!";
    
    $result = $user->register($username, $testEmail, $password);
    $testUserId = $result; // Salvăm ID-ul pentru cleanup
    return is_int($result) && $result > 0;
});

// Test 8: getUserByEmail funcționează
runTest("getUserByEmail() returnează utilizatorul corect", function() use ($user, $testEmail) {
    $retrievedUser = $user->getUserByEmail($testEmail);
    return is_array($retrievedUser) && 
           $retrievedUser['email'] === $testEmail;
});

// Test 9: Parola este hash-uită
runTest("Parola este hash-uită corect", function() use ($user, $testEmail) {
    $retrievedUser = $user->getUserByEmail($testEmail);
    
    return is_array($retrievedUser) && 
           isset($retrievedUser['password_hash']) &&
           strlen($retrievedUser['password_hash']) > 0 &&
           strpos($retrievedUser['password_hash'], '$2y$') === 0; // Verifică că e hash bcrypt
});

echo "\n⚠️  TESTE EXCEPȚII\n";
echo "--------------------------------------------------\n";

// Test 10: RecipeNotFoundException poate fi aruncată
runTest("RecipeNotFoundException poate fi aruncată", function() {
    try {
        throw new RecipeNotFoundException("Test exception message");
    } catch (RecipeNotFoundException $e) {
        return $e->getMessage() === "Test exception message";
    }
    return false;
});

// Test 11: Excepțiile moștenesc Exception
runTest("Excepțiile personalizate moștenesc Exception", function() {
    $exception1 = new RecipeNotFoundException("Test");
    $exception2 = new InvalidRecipeDataException("Test");
    $exception3 = new DatabaseException("Test");
    
    return ($exception1 instanceof Exception) &&
           ($exception2 instanceof Exception) &&
           ($exception3 instanceof Exception);
});

// Cleanup - Ștergem datele de test
echo "\n🧹 CURĂȚARE DATE DE TEST\n";
echo "--------------------------------------------------\n";

foreach ($testRecipeIds as $id) {
    try {
        $recipe->deleteRecipe($id);
        echo "✅ Șters recipe ID: $id\n";
    } catch (Exception $e) {
        echo "⚠️  Nu s-a putut șterge recipe ID: $id\n";
    }
}

// Ștergem utilizatorul de test
$stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
$stmt->bind_param("s", $testEmail);
if ($stmt->execute()) {
    echo "✅ Șters user de test: $testEmail\n";
}
$stmt->close();

$conn->close();

// Raport final
echo "\n==================================================\n";
echo "   REZULTATE FINALE\n";
echo "==================================================\n";
echo "Total teste: $totalTests\n";
echo "Teste reușite: $passedTests ✅\n";
echo "Teste eșuate: " . ($totalTests - $passedTests) . " ❌\n";
echo "Procentaj succes: " . round(($passedTests / $totalTests) * 100, 2) . "%\n";
echo "==================================================\n";

if ($passedTests === $totalTests) {
    echo "\n🎉 TOATE TESTELE AU TRECUT! 🎉\n";
   
} else {
    echo "\n⚠️  Unele teste au eșuat. Verifică implementarea!\n\n";
}