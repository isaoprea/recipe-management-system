<?php
include_once __DIR__ . '/../model/Recipe.php';
include_once __DIR__ . '/../model/exceptions.php';
include_once __DIR__ . '/../config.php';

class RecipeController {
    private $model;

    public function __construct($conn) {
        $this->model = new Recipe($conn);
    }

    public function listRecipes() {
        try {
            $recipes = $this->model->getAllRecipes();
            include __DIR__ . '/../view/lista_retete.php';
        } catch (DatabaseException $e) {
            session_start();
            $_SESSION['error'] = 'Eroare la încărcarea rețetelor: ' . $e->getMessage();
            include __DIR__ . '/../view/header.php';
            echo '<div class="container"><p class="error">' . htmlspecialchars($_SESSION['error']) . '</p></div>';
            include __DIR__ . '/../view/footer.php';
        }
    }

    public function showRecipe($id) {
        try {
            $recipe = $this->model->getRecipeById($id);
            include __DIR__ . '/../view/detalii_reteta.php';
        } catch (RecipeNotFoundException $e) {
            session_start();
            $_SESSION['error'] = $e->getMessage();
            header('Location: index.php?page=lista');
            exit();
        } catch (Exception $e) {
            session_start();
            $_SESSION['error'] = 'Eroare la încărcarea rețetei: ' . $e->getMessage();
            header('Location: index.php?page=lista');
            exit();
        }
    }
}
