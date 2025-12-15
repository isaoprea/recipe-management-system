<?php include 'header.php'; ?>


<?php if (!$recipe): ?>
    <p>Rețeta nu a fost găsită!</p>

<?php else: ?>

<div class="recipe-details">

    <!-- Header cu titlu și acțiuni -->
    <div class="recipe-header">
        <h1><?= htmlspecialchars($recipe['recipe_name']) ?></h1>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="recipe-actions">
              <a href="index.php?page=editeaza&id=<?= $recipe['recipe_id'] ?>" class="btn btn-edit">✏️ Editează</a>

                <a href="sterge_reteta.php?id=<?= $recipe['recipe_id'] ?>" 
                   class="btn btn-delete" 
                   onclick="return confirm('Sigur vrei să ștergi această rețetă?')">🗑️ Șterge</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Imagine rețetă -->
    <?php if (!empty($recipe['image'])): ?>
        <img src="<?= htmlspecialchars($recipe['image']) ?>" 
             class="details-img" 
             alt="<?= htmlspecialchars($recipe['recipe_name']) ?>">
    <?php endif; ?>

    <!-- Descriere -->
    <p><?= nl2br(htmlspecialchars($recipe['description'])) ?></p>

    <!-- Ingrediente -->
    <h3>Ingrediente:</h3>
    <ul class="ingredients-list">
        <?php foreach ($recipe['ingredients'] as $ing): ?>
            <li>
                <strong><?= htmlspecialchars($ing['name']) ?></strong>
                – <?= htmlspecialchars($ing['quantity']) ?>
                <?= htmlspecialchars($ing['unit_of_measure']) ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Mod de preparare -->
    <h3>Mod de preparare:</h3>
    <ol class="steps-list">
        <?php foreach ($recipe['steps'] as $step): ?>
            <li><?= nl2br(htmlspecialchars($step['step_description'])) ?></li>
        <?php endforeach; ?>
    </ol>

</div>

<?php endif; ?>

<?php include 'footer.php'; ?>