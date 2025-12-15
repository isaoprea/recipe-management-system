<?php
use PHPUnit\Framework\TestCase;

/**
 * Test class pentru Recipe Model
 * Testează funcționalitățile CRUD ale rețetelor
 */
class RecipeTest extends TestCase
{
    private $recipe;
    private $conn;
    private $testRecipeId;

    /**
     * Setup executat înaintea fiecărui test
     * Creează conexiunea la BD și instanța Recipe
     */
    protected function setUp(): void
    {
        // Folosim baza de date principală pentru simplitate
        // Într-un mediu real, ar trebui să folosim o BD de test separată
        $this->conn = new mysqli("127.0.0.1", "root", "", "recipes_db", 3307);
        
        if ($this->conn->connect_error) {
            $this->fail("Database connection failed: " . $this->conn->connect_error);
        }

        $this->recipe = new Recipe($this->conn);
    }

    /**
     * Cleanup executat după fiecare test
     * Șterge datele de test și închide conexiunea
     */
    protected function tearDown(): void
    {
        // Curățăm datele de test dacă există
        if ($this->testRecipeId) {
            $stmt = $this->conn->prepare("DELETE FROM recipes WHERE recipe_id = ?");
            $stmt->bind_param("i", $this->testRecipeId);
            $stmt->execute();
            $stmt->close();
        }
        
        $this->conn->close();
    }

    /**
     * Test 1: Verifică dacă metoda getAllRecipes returnează un array
     */
    public function testGetAllRecipesReturnsArray()
    {
        $recipes = $this->recipe->getAllRecipes();
        
        $this->assertIsArray($recipes, "getAllRecipes() ar trebui să returneze un array");
    }

    /**
     * Test 2: Verifică dacă se poate adăuga o rețetă nouă
     */
    public function testAddRecipeSuccessfully()
    {
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

        $recipeId = $this->recipe->addRecipe($testData);
        $this->testRecipeId = $recipeId; // Salvăm pentru cleanup

        $this->assertIsInt($recipeId, "addRecipe() ar trebui să returneze un ID întreg");
        $this->assertGreaterThan(0, $recipeId, "ID-ul rețetei ar trebui să fie pozitiv");
    }

    /**
     * Test 3: Verifică dacă getRecipeById returnează rețeta corectă
     */
    public function testGetRecipeByIdReturnsCorrectRecipe()
    {
        // Mai întâi adăugăm o rețetă de test
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

        $recipeId = $this->recipe->addRecipe($testData);
        $this->testRecipeId = $recipeId;

        // Acum o preluăm
        $retrievedRecipe = $this->recipe->getRecipeById($recipeId);

        $this->assertIsArray($retrievedRecipe, "getRecipeById() ar trebui să returneze un array");
        $this->assertEquals($testData['recipe_name'], $retrievedRecipe['recipe_name'], 
            "Numele rețetei ar trebui să fie același");
        $this->assertEquals($testData['prep_time'], $retrievedRecipe['prep_time'], 
            "Timpul de preparare ar trebui să fie același");
    }

    /**
     * Test 4: Verifică dacă se aruncă excepție pentru ID invalid
     */
    public function testGetRecipeByIdThrowsExceptionForInvalidId()
    {
        $this->expectException(RecipeNotFoundException::class);
        $this->expectExceptionMessage("Reteta cu ID -ul 999999 nu a fost gasita.");
        
        // ID-ul 999999 nu ar trebui să existe
        $this->recipe->getRecipeById(999999);
    }

    /**
     * Test 5: Verifică dacă updateRecipe modifică corect datele
     */
    public function testUpdateRecipeModifiesDataCorrectly()
    {
        // Adăugăm o rețetă de test
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

        $recipeId = $this->recipe->addRecipe($originalData);
        $this->testRecipeId = $recipeId;

        // Actualizăm rețeta
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

        $this->recipe->updateRecipe($recipeId, $updatedData);

        // Verificăm dacă s-a actualizat
        $retrievedRecipe = $this->recipe->getRecipeById($recipeId);

        $this->assertEquals($updatedData['recipe_name'], $retrievedRecipe['recipe_name'], 
            "Numele rețetei ar trebui să fie actualizat");
        $this->assertEquals($updatedData['prep_time'], $retrievedRecipe['prep_time'], 
            "Timpul de preparare ar trebui să fie actualizat");
    }

    /**
     * Test 6: Verifică dacă deleteRecipe șterge efectiv rețeta
     */
    public function testDeleteRecipeRemovesRecipe()
    {
        // Adăugăm o rețetă de test
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

        $recipeId = $this->recipe->addRecipe($testData);

        // Ștergem rețeta
        $this->recipe->deleteRecipe($recipeId);

        // Verificăm că nu mai există
        $this->expectException(RecipeNotFoundException::class);
        $this->recipe->getRecipeById($recipeId);
    }
}