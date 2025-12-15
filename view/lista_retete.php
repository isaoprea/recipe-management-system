<?php include __DIR__ . '/header.php'; ?>
<div class="grid">
<?php foreach($recipes as $row): ?>
  <div class='card'>
    <img src='<?= $row['image'] ?>' alt='<?= $row['recipe_name'] ?>'>
    <div class='card-content'>
      <h2><?= $row['recipe_name'] ?></h2>
      <p><?= $row['description'] ?></p>
      <div class='details'>
        <span>⏱️ Prep: <?= $row['prep_time'] ?> min</span>
        <span>🍳 Cook: <?= $row['cook_time'] ?> min</span>
      </div>
      <div class='details'>
        <span>🍽️ <?= $row['servings'] ?> porții</span>
        <span>🧂 <?= $row['difficulty'] ?></span>
      </div>
      <div class='card-actions'>
        <a href='index.php?page=detalii&id=<?= $row['recipe_id'] ?>' class='btn btn-view'>Detalii</a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href='index.php?page=editeaza&id=<?= $row['recipe_id'] ?>' class='btn btn-edit'>Editează</a>
          <a href='sterge_reteta.php?id=<?= $row['recipe_id'] ?>' 
             class='btn btn-delete' 
             onclick="return confirm('Sigur vrei să ștergi rețeta <?= htmlspecialchars($row['recipe_name']) ?>?')">Șterge</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>