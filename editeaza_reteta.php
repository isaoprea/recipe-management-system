<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    require_once __DIR__ . '/config.php';
}
include_once __DIR__ . '/model/Recipe.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Trebuie să fii autentificat pentru a edita rețete!';
    header('Location: index.php?page=login');
    exit;
}

$recipeModel = new Recipe($conn);
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    $recipe = $recipeModel->getRecipeById($id);
} catch (Exception $e) {
    $_SESSION['error'] = 'Rețeta nu a fost găsită!';
    header('Location: index.php?page=lista');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $recipe_name = trim($_POST['recipe_name']);
        $description = trim($_POST['description']);
        $prep_time = (int)$_POST['prep_time'];
        $cook_time = (int)$_POST['cook_time'];
        $servings = (int)$_POST['servings'];
        $difficulty = (int)$_POST['difficulty'];
        $category_id = (int)$_POST['category_id'];
        
        $image_path = $recipe['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/imagini/';
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'recipe_' . time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                if (!empty($recipe['image']) && $recipe['image'] !== 'imagini/default.jpeg' && file_exists($recipe['image'])) {
                    unlink($recipe['image']);
                }
                $image_path = 'imagini/' . $new_filename;
            }
        }
        
        $recipeData = [
            'recipe_name' => $recipe_name,
            'description' => $description,
            'prep_time' => $prep_time,
            'cook_time' => $cook_time,
            'servings' => $servings,
            'difficulty' => $difficulty,
            'image' => $image_path,
            'category_id' => $category_id
        ];
        
        $recipeModel->updateRecipe($id, $recipeData);
        
        $stmt = $conn->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        if (isset($_POST['ingredients']) && is_array($_POST['ingredients'])) {
            foreach ($_POST['ingredients'] as $ingredient) {
                $ingredient = trim($ingredient);
                if (!empty($ingredient)) {
                    preg_match('/^(\d+(?:\.\d+)?)\s*([a-zA-Z]*)\s+(.+)$/', $ingredient, $matches);
                    
                    if (count($matches) === 4) {
                        $quantity = $matches[1];
                        $unit = !empty($matches[2]) ? $matches[2] : 'buc';
                        $ing_name = $matches[3];
                    } else {
                        $quantity = 1;
                        $unit = 'buc';
                        $ing_name = $ingredient;
                    }
                    
                    $stmt = $conn->prepare("SELECT ingredient_id FROM ingredients WHERE ingredient_name = ?");
                    $stmt->bind_param("s", $ing_name);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($row = $result->fetch_assoc()) {
                        $ingredient_id = $row['ingredient_id'];
                    } else {
                        $stmt2 = $conn->prepare("INSERT INTO ingredients (ingredient_name, unit_of_measure) VALUES (?, ?)");
                        $stmt2->bind_param("ss", $ing_name, $unit);
                        $stmt2->execute();
                        $ingredient_id = $conn->insert_id;
                        $stmt2->close();
                    }
                    $stmt->close();
                    
                    $stmt = $conn->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id, quantity) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $id, $ingredient_id, $quantity);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM preparation_steps WHERE recipe_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        if (isset($_POST['steps']) && is_array($_POST['steps'])) {
            $step_number = 1;
            foreach ($_POST['steps'] as $step) {
                $step = trim($step);
                if (!empty($step)) {
                    $stmt = $conn->prepare("INSERT INTO preparation_steps (recipe_id, step_number, step_description) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $id, $step_number, $step);
                    $stmt->execute();
                    $stmt->close();
                    $step_number++;
                }
            }
        }
        
        $_SESSION['success'] = '✅ Rețeta a fost actualizată cu succes!';
        header('Location: index.php?page=detalii&id=' . $id);
        exit();
        
    } catch (InvalidRecipeDataException $e) {
        $_SESSION['error'] = '❌ Date invalide: ' . $e->getMessage();
    } catch (RecipeNotFoundException $e) {
        $_SESSION['error'] = '❌ ' . $e->getMessage();
        header('Location: index.php?page=lista');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = '❌ Eroare la actualizarea rețetei: ' . $e->getMessage();
    }
}

include __DIR__ . '/view/header.php';
?>

<div class="add-recipe-container">
    <h1 class="page-title">✏️ Editează rețeta</h1>
    
    <form action="" method="post" enctype="multipart/form-data" class="modern-form">
        
        <div class="form-section">
            <h2 class="section-title">📋 Informații de bază</h2>
            
            <div class="form-group">
                <label for="recipe_name">Nume rețetă *</label>
                <input type="text" id="recipe_name" name="recipe_name" required 
                       value="<?= htmlspecialchars($recipe['recipe_name']) ?>">
            </div>

            <div class="form-group">
                <label for="description">Descriere *</label>
                <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($recipe['description']) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="prep_time">⏱️ Timp preparare (min)</label>
                    <input type="number" id="prep_time" name="prep_time" min="0" 
                           value="<?= htmlspecialchars($recipe['prep_time']) ?>">
                </div>

                <div class="form-group">
                    <label for="cook_time">🍳 Timp gătire (min)</label>
                    <input type="number" id="cook_time" name="cook_time" min="0" 
                           value="<?= htmlspecialchars($recipe['cook_time']) ?>">
                </div>

                <div class="form-group">
                    <label for="servings">🍽️ Porții</label>
                    <input type="number" id="servings" name="servings" min="1" 
                           value="<?= htmlspecialchars($recipe['servings']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="difficulty">🧂 Dificultate</label>
                    <select id="difficulty" name="difficulty">
                        <option value="1" <?= $recipe['difficulty'] == 1 ? 'selected' : '' ?>>Ușor</option>
                        <option value="2" <?= $recipe['difficulty'] == 2 ? 'selected' : '' ?>>Mediu</option>
                        <option value="3" <?= $recipe['difficulty'] == 3 ? 'selected' : '' ?>>Dificil</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category_id">📂 Categorie</label>
                    <select id="category_id" name="category_id">
                        <?php
                        $result = $conn->query("SELECT * FROM categories ORDER BY category_name");
                        while ($row = $result->fetch_assoc()) {
                            $selected = ($row['category_id'] == $recipe['category_id']) ? 'selected' : '';
                            echo "<option value='{$row['category_id']}' {$selected}>{$row['category_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">📷 Imagine nouă (opțional)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <?php if (!empty($recipe['image'])): ?>
                        <small>Imagine curentă: <?= basename($recipe['image']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2 class="section-title">🥕 Ingrediente</h2>
            <p class="hint">Editează ingredientele necesare (câte unul pe linie)</p>
            
            <div id="ingredients-container">
                <?php if (!empty($recipe['ingredients'])): ?>
                    <?php foreach ($recipe['ingredients'] as $ing): ?>
                        <div class="ingredient-row">
                            <input type="text" name="ingredients[]" 
                                   value="<?= htmlspecialchars($ing['quantity'] . ' ' . $ing['unit_of_measure'] . ' ' . $ing['name']) ?>" 
                                   class="ingredient-input">
                            <button type="button" class="btn-remove" onclick="removeRow(this)">✖</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="ingredient-row">
                        <input type="text" name="ingredients[]" placeholder="Ex: 500g paste" class="ingredient-input">
                        <button type="button" class="btn-remove" onclick="removeRow(this)">✖</button>
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="button" class="btn-add" onclick="addIngredient()">➕ Adaugă ingredient</button>
        </div>

        <div class="form-section">
            <h2 class="section-title">👨‍🍳 Mod de preparare</h2>
            <p class="hint">Editează pașii de preparare în ordine</p>
            
            <div id="steps-container">
                <?php if (!empty($recipe['steps'])): ?>
                    <?php foreach ($recipe['steps'] as $index => $step): ?>
                        <div class="step-row">
                            <span class="step-number"><?= $index + 1 ?>.</span>
                            <textarea name="steps[]" rows="3" class="step-input"><?= htmlspecialchars($step['step_description']) ?></textarea>
                            <button type="button" class="btn-remove" onclick="removeRow(this)">✖</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="step-row">
                        <span class="step-number">1.</span>
                        <textarea name="steps[]" rows="3" placeholder="Descrie acest pas..." class="step-input"></textarea>
                        <button type="button" class="btn-remove" onclick="removeRow(this)">✖</button>
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="button" class="btn-add" onclick="addStep()">➕ Adaugă pas</button>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Salvează modificările</button>
            <a href="index.php?page=detalii&id=<?= $id ?>" class="btn btn-secondary">❌ Anulează</a>
        </div>
    </form>
</div>

<script>
let ingredientCount = <?= count($recipe['ingredients'] ?? [1]) ?>;
let stepCount = <?= count($recipe['steps'] ?? [1]) ?>;

function addIngredient() {
    ingredientCount++;
    const container = document.getElementById('ingredients-container');
    const newRow = document.createElement('div');
    newRow.className = 'ingredient-row';
    newRow.innerHTML = `
        <input type="text" name="ingredients[]" placeholder="Ex: 200g bacon" class="ingredient-input">
        <button type="button" class="btn-remove" onclick="removeRow(this)">✖</button>
    `;
    container.appendChild(newRow);
}

function addStep() {
    stepCount++;
    const container = document.getElementById('steps-container');
    const newRow = document.createElement('div');
    newRow.className = 'step-row';
    newRow.innerHTML = `
        <span class="step-number">${stepCount}.</span>
        <textarea name="steps[]" rows="3" placeholder="Descrie acest pas..." class="step-input"></textarea>
        <button type="button" class="btn-remove" onclick="removeRow(this)">✖</button>
    `;
    container.appendChild(newRow);
    updateStepNumbers();
}

function removeRow(button) {
    const row = button.parentElement;
    row.remove();
    updateStepNumbers();
}

function updateStepNumbers() {
    const steps = document.querySelectorAll('.step-row');
    steps.forEach((step, index) => {
        const numberSpan = step.querySelector('.step-number');
        if (numberSpan) {
            numberSpan.textContent = (index + 1) + '.';
        }
    });
    stepCount = steps.length;
}
</script>

<?php include __DIR__ . '/view/footer.php'; ?>
