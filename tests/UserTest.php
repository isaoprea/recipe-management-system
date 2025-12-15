<?php
use PHPUnit\Framework\TestCase;

/**
 * Test class pentru User Model
 * Testează funcționalitățile de autentificare și gestionare utilizatori
 */
class UserTest extends TestCase
{
    private $user;
    private $conn;
    private $testUserId;

    protected function setUp(): void
{
    // AICI ERA PROBLEMA: Foloseai baza de date reală ('recipes_db')
    // Trebuie să folosești funcția definită în bootstrap.php
    $this->conn = getTestDatabaseConnection(); 
    
    

    $this->user = new User($this->conn);
}

    protected function tearDown(): void
    {
        // Curățăm utilizatorii de test
        if ($this->testUserId) {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $this->testUserId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Ștergem și după email de test
        $stmt = $this->conn->prepare("DELETE FROM users WHERE email = ?");
        $testEmail = "phpunit.test@example.com";
        $stmt->bind_param("s", $testEmail);
        $stmt->execute();
        $stmt->close();
        
        $this->conn->close();
    }

 
     
    public function testGetUserByEmailReturnsCorrectUser()
    {
        // Creăm un utilizator de test
        $username = "test_email_user";
        $email = "phpunit.test@example.com";
        $password = "TestPassword456!";
        
        $this->user->register($username, $email, $password);

        // Preluăm utilizatorul
        $retrievedUser = $this->user->getUserByEmail($email);

        $this->assertIsArray($retrievedUser, "getUserByEmail() ar trebui să returneze un array");
        $this->assertEquals($email, $retrievedUser['email'], "Email-ul ar trebui să fie corect");
        $this->assertEquals($username, $retrievedUser['username'], "Username-ul ar trebui să fie corect");
    }

    /**
     * Test 9: Verifică dacă parola este hash-uită corect
     */
    public function testPasswordIsHashedCorrectly()
    {
        $username = "hash_test_user";
        $email = "phpunit.test@example.com";
        $password = "PlainTextPassword789!";

        $this->user->register($username, $email, $password);

        $retrievedUser = $this->user->getUserByEmail($email);

        // Verificăm că parola este hash-uită (nu ar trebui să fie plain text)
        $this->assertNotEquals($password, $retrievedUser['password_hash'], 
            "Parola nu ar trebui să fie stocată ca plain text");
        
        // Verificăm că hash-ul este valid
        $this->assertTrue(password_verify($password, $retrievedUser['password_hash']), 
            "Hash-ul parolei ar trebui să fie verificabil cu password_verify()");
    }
}

return true;
