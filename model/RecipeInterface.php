<?php
interface RecipeInterface 
{
    public function getAllRecipes();
    public function getRecipeById($id);
    public function addRecipe($data);
    public function updateRecipe($id, $data);   
    public function deleteRecipe($id);
    
}