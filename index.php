<?php
include __DIR__ . '/config.php';
include __DIR__ . '/controller/RecipeController.php';

$recipeController = new RecipeController($conn);
$page = $_GET['page'] ?? 'home';

switch($page){
    case 'lista':
        $recipeController->listRecipes();
        break;

    case 'detalii':
        if(isset($_GET['id'])){
            $recipeController->showRecipe($_GET['id']);
        }
        break;

    case 'editeaza': 
        if(isset($_GET['id'])){
            include __DIR__ . '/editeaza_reteta.php';
        } else {
            echo "<p>ID-ul rețetei nu a fost specificat.</p>";
        }
        break;

    case 'adauga':
        include __DIR__ . '/view/adauga_reteta.php';
        break;

    case 'sterge':  
        if(isset($_GET['id'])){
            include __DIR__ . '/sterge_reteta.php';
        } else {
            echo "<p>ID-ul rețetei nu a fost specificat.</p>";
        }
        break;

    case 'login':
        include __DIR__ . '/view/login.php';
        break;

    case 'register':
        include __DIR__ . '/view/register.php';
        break;

    case 'despre':
        include __DIR__ . '/view/despre.php';
        break;

    case 'home':
    default:
        include __DIR__ . '/view/home.php';
        break;
}
?>
