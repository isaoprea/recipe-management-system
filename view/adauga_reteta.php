<?php 
include __DIR__ . '/header.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Trebuie să fii autentificat pentru a adăuga rețete!';
    header('Location: index.php?page=login');
    exit();
}
?>

<div class="add-recipe-container">
    <h1 class="page-title">➕ Adaugă o rețetă nouă</h1>
    
    <form action="salveaza_reteta.php" method="post" enctype="multipart/form-data" class="modern-form">
        
        <div class="form-section">  
            <h2 class="section-title">📋 Informații de bază</h2>
            
            <div class="form-group">
                <label for="recipe_name">Nume rețetă *</label>
                <input type="text" id="recipe_name" name="recipe_name" required placeholder="Ex: Paste Carbonara">
            </div>

            <div class="form-group">
                <label for="description">Descriere *</label>
                <textarea id="description" name="description" grows="4" required placeholder="O scurtă descriere a rețetei..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="prep_time">⏱️ Timp preparare (min)</label>
                    <input type="number" id="prep_time" name="prep_time" min="0" value="15">
                </div>

                <div class="form-group">
                    <label for="cook_time">🍳 Timp gătire (min)</label>
                    <input type="number" id="cook_time" name="cook_time" min="0" value="30">
                </div>

                <div class="form-group">
                    <label for="servings">🍽️ Porții</label>
                    <input type="number" id="servings" name="servings" min="1" value="4">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="difficulty">🧂 Dificultate</label>
                    <select id="difficulty" name="difficulty">
                        <option value="1">Ușor</option>
                        <option value="2" selected>Mediu</option>
                        <option value="3">Dificil</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category_id">📂 Categorie</label>
                    <select id="category_id" name="category_id">
                        <?php
                        $result = $conn->query("SELECT * FROM categories ORDER BY category_name");
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['category_id']}'>{$row['category_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">📷 Imagine (opțional)</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2 class="section-title">🥕 Ingrediente</h2>
            <p class="hint">Adaugă ingredientele necesare (câte unul pe linie)</p>
            
            <div id="ingredients-container">
                <div class="ingredient-row">
                    <input type="text" name="ingredients[]" placeholder="Ex: 500g paste" class="ingredient-input">
                    <button type="button" class="btn-remove" onclick="removeRow(this)">✖</button>
                </div>
            </div>
            
            <button type="button" class="btn-add" onclick="addIngredient()">➕ Adaugă ingredient</button>
        </div>

        <div class="form-section">
            <h2 class="section-title">👨‍🍳 Mod de preparare</h2>
            <p class="hint">Adaugă pașii de preparare în ordine</p>
            
            <div id="steps-container">
                <div class="step-row">
                    <span class="step-number">1.</span>
                    <textarea name="steps[]" rows="3" placeholder="Descrie acest pas..." class="step-input"></textarea>
                    <button type="button" class="btn-remove" onclick="removeRow(this)">✖</button>
                </div>
            </div>
            
            <button type="button" class="btn-add" onclick="addStep()">➕ Adaugă pas</button>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Salvează rețeta</button>
            <a href="index.php?page=lista" class="btn btn-secondary">❌ Anulează</a>
        </div>
    </form>
</div>

<script>
let ingredientCount = 1;
let stepCount = 1;

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

<?php include __DIR__ . '/footer.php'; ?>