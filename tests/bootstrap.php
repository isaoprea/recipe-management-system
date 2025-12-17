<?php
/**
 * Bootstrap file pentru PHPUnit tests
 * Încarcă dependințele și configurația necesară pentru teste
 */

// Autoloader pentru PHPUnit (dacă e instalat prin composer)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Include configurația de bază
require_once __DIR__ . '/../config.php';

// Include toate clasele necesare pentru teste
require_once __DIR__ . '/../model/exceptions.php';
require_once __DIR__ . '/../model/BaseModel.php';
require_once __DIR__ . '/../model/RecipeInterface.php';
require_once __DIR__ . '/../model/Recipe.php';
require_once __DIR__ . '/../model/user.php';

function getTestDatabaseConnection() {
    $servername = "127.0.0.1";
    $username = "root";
    $password = "";
    $dbname = "recipes_db_test"; 
    $port = 3307;

    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        die("Test DB Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}