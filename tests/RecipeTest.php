<?php
use PHPUnit\Framework\TestCase;


class RecipeTest extends TestCase
{
    private $recipe;
    private $conn;
    private $testRecipeId;

    
    protected function setUp(): void
    {
       
        $this->conn = new mysqli("127.0.0.1", "root", "", "recipes_db", 3307);
        
        if ($this->conn->connect_error) {
            $this->fail("Database connection failed: " . $this->conn->connect_error);
        }

        $this->recipe = new Recipe($this->conn);
    }

    
    protected function tearDown(): void
    {
        if ($this->testRecipeId) {
            $stmt = $this->conn->prepare("DELETE FROM recipes WHERE recipe_id = ?");
            $stmt->bind_param("i", $this->testRecipeId);
            $stmt->execute();
            $stmt->close();
        }
        
        $this->conn->close();
    }

    
    public function testGetAllRecipesReturnsArray()
    {
        $recipes = $this->recipe->getAllRecipes();
        
        $this->assertIsArray($recipes, "getAllRecipes() ar trebui să returneze un array");
    }

    
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
        $this->testRecipeId = $recipeId; 

        $this->assertIsInt($recipeId, "addRecipe() ar trebui să returneze un ID întreg");
        $this->assertGreaterThan(0, $recipeId, "ID-ul rețetei ar trebui să fie pozitiv");
    }

  
    public function testGetRecipeByIdReturnsCorrectRecipe()
    {
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

        $retrievedRecipe = $this->recipe->getRecipeById($recipeId);

        $this->assertIsArray($retrievedRecipe, "getRecipeById() ar trebui să returneze un array");
        $this->assertEquals($testData['recipe_name'], $retrievedRecipe['recipe_name'], 
            "Numele rețetei ar trebui să fie același");
        $this->assertEquals($testData['prep_time'], $retrievedRecipe['prep_time'], 
            "Timpul de preparare ar trebui să fie același");
    }

    public function testGetRecipeByIdThrowsExceptionForInvalidId()
    {
        $this->expectException(RecipeNotFoundException::class);
        $this->expectExceptionMessage("Reteta cu ID -ul 999999 nu a fost gasita.");
        
        $this->recipe->getRecipeById(999999);
    }

    public function testUpdateRecipeModifiesDataCorrectly()
    {
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

        $retrievedRecipe = $this->recipe->getRecipeById($recipeId);

        $this->assertEquals($updatedData['recipe_name'], $retrievedRecipe['recipe_name'], 
            "Numele rețetei ar trebui să fie actualizat");
        $this->assertEquals($updatedData['prep_time'], $retrievedRecipe['prep_time'], 
            "Timpul de preparare ar trebui să fie actualizat");
    }

    
    public function testDeleteRecipeRemovesRecipe()
    {
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

        $this->recipe->deleteRecipe($recipeId);

        $this->expectException(RecipeNotFoundException::class);
        $this->recipe->getRecipeById($recipeId);
    }
}