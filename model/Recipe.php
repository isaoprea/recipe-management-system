<?php
include_once 'config.php';
include_once 'RecipeInterface.php';
include_once 'exceptions.php';
include_once 'BaseModel.php';

class Recipe extends BaseModel implements RecipeInterface {
    

    public function getAllRecipes() {
        $sql = "SELECT r.recipe_id, r.recipe_name, r.description, r.prep_time, r.cook_time, r.servings, r.difficulty, r.image, c.category_name
                FROM recipes r
                LEFT JOIN categories c ON r.category_id = c.category_id
                ORDER BY r.created_at DESC";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            throw new DatabaseException("Eroare la interogarea bazei de date: " . $this->conn->error);
        }
        
        $recipes = [];
        while($row = $result->fetch_assoc()) {
            $recipes[] = $row;
        }
        return $recipes;
    }

    public function getRecipeById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM recipes WHERE recipe_id = ?");
        $stmt->bind_param("i", $id); 
        $stmt->execute();
        $recipe = $stmt->get_result()->fetch_assoc();

        if (!$recipe)
        {
            throw new RecipeNotFoundException("Reteta cu ID -ul $id nu a fost gasita.");
        }

        $stmt = $this->conn->prepare("
            SELECT i.ingredient_name AS name, ri.quantity, i.unit_of_measure
            FROM recipe_ingredients ri
            JOIN ingredients i ON ri.ingredient_id = i.ingredient_id
            WHERE ri.recipe_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $ingredients = $stmt->get_result();
        $recipe['ingredients'] = [];
        while ($row = $ingredients->fetch_assoc()) {
            $recipe['ingredients'][] = $row;
        }

        // Preluăm pașii
        $stmt = $this->conn->prepare("
            SELECT step_number, step_description
            FROM preparation_steps
            WHERE recipe_id = ?
            ORDER BY step_number ASC
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $steps = $stmt->get_result();
        $recipe['steps'] = [];
        while ($row = $steps->fetch_assoc()) {
            $recipe['steps'][] = $row;
        }

        return $recipe;
    }

    public function addRecipe($data) {
        // Validare date
        if (empty($data['recipe_name']) || strlen($data['recipe_name']) < 3) {
            throw new InvalidRecipeDataException("Numele rețetei trebuie să aibă minim 3 caractere.");
        }
        
        if (empty($data['description'])) {
            throw new InvalidRecipeDataException("Descrierea rețetei este obligatorie.");
        }
        
        if ($data['prep_time'] < 0 || $data['cook_time'] < 0) {
            throw new InvalidRecipeDataException("Timpii de preparare și gătire trebuie să fie pozitivi.");
        }
        
        if ($data['servings'] < 1) {
            throw new InvalidRecipeDataException("Numărul de porții trebuie să fie minim 1.");
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO recipes (recipe_name, description, prep_time, cook_time, servings, difficulty, image, category_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new DatabaseException("Eroare la pregătirea interogării: " . $this->conn->error);
        }
        
        $stmt->bind_param(
            "ssiiiisi",
            $data['recipe_name'],
            $data['description'],
            $data['prep_time'],
            $data['cook_time'],
            $data['servings'],
            $data['difficulty'],
            $data['image'],
            $data['category_id']
        );
        
        if (!$stmt->execute()) {
            throw new DatabaseException("Eroare la adăugarea rețetei: " . $stmt->error);
        }
        
        $stmt->close();
        return $this->conn->insert_id;
    }

    public function updateRecipe($id, $data) {
        // Validare date
        if (empty($data['recipe_name']) || strlen($data['recipe_name']) < 3) {
            throw new InvalidRecipeDataException("Numele rețetei trebuie să aibă minim 3 caractere.");
        }
        
        if ($data['servings'] < 1) {
            throw new InvalidRecipeDataException("Numărul de porții trebuie să fie minim 1.");
        }
        
        // Verificăm dacă rețeta există
        $checkStmt = $this->conn->prepare("SELECT recipe_id FROM recipes WHERE recipe_id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new RecipeNotFoundException("Reteta cu ID-ul $id nu a fost gasita.");
        }
        $checkStmt->close();
        
        $stmt = $this->conn->prepare("
            UPDATE recipes SET
            recipe_name = ?, description = ?, prep_time = ?, cook_time = ?, servings = ?, difficulty = ?, image = ?, category_id = ?
            WHERE recipe_id = ?
        ");
        
        if (!$stmt) {
            throw new DatabaseException("Eroare la pregătirea actualizării: " . $this->conn->error);
        }
        
        $stmt->bind_param(
            "ssiiiisii",
            $data['recipe_name'],
            $data['description'],
            $data['prep_time'],
            $data['cook_time'],
            $data['servings'],
            $data['difficulty'],
            $data['image'],
            $data['category_id'],
            $id
        );
        
        if (!$stmt->execute()) {
            throw new DatabaseException("Eroare la actualizarea rețetei: " . $stmt->error);
        }
        
        $stmt->close();
    }

    public function deleteRecipe($id) {
        // Verificăm dacă rețeta există
        $checkStmt = $this->conn->prepare("SELECT recipe_id FROM recipes WHERE recipe_id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new RecipeNotFoundException("Reteta cu ID-ul $id nu a fost gasita.");
        }
        $checkStmt->close();
        
        $stmt = $this->conn->prepare("DELETE FROM recipes WHERE recipe_id = ?");
        
        if (!$stmt) {
            throw new DatabaseException("Eroare la pregătirea ștergerii: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new DatabaseException("Eroare la ștergerea rețetei: " . $stmt->error);
        }
        
        $stmt->close();
    }
}
