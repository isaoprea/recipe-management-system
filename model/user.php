<?php
include_once 'BaseModel.php';
include_once 'exceptions.php';

class UserNotFoundException extends Exception {}
class InvalidUserDataException extends Exception {}

class User extends BaseModel {

    public function __construct($conn) {
        parent::__construct($conn);
    }

    public function getUserByEmail($email) {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidUserDataException("Adresa de email nu este validă.");
        }
        
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        
        if (!$stmt) {
            throw new DatabaseException("Eroare la pregătirea interogării: " . $this->conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUserByUsername($username) {
        if (empty($username) || strlen($username) < 3) {
            throw new InvalidUserDataException("Numele de utilizator trebuie să aibă minim 3 caractere.");
        }
        
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        
        if (!$stmt) {
            throw new DatabaseException("Eroare la pregătirea interogării: " . $this->conn->error);
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function register($username, $email, $password) {
        // Validare username
        if (empty($username) || strlen($username) < 3) {
            throw new InvalidUserDataException("Numele de utilizator trebuie să aibă minim 3 caractere.");
        }
        
        // Validare email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidUserDataException("Adresa de email nu este validă.");
        }
        
        // Validare parolă
        if (empty($password) || strlen($password) < 6) {
            throw new InvalidUserDataException("Parola trebuie să aibă minim 6 caractere.");
        }
        
        // Verificăm dacă username-ul există deja
        $checkStmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            throw new InvalidUserDataException("Numele de utilizator există deja.");
        }
        $checkStmt->close();
        
        // Verificăm dacă email-ul există deja
        $checkStmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            throw new InvalidUserDataException("Adresa de email este deja înregistrată.");
        }
        $checkStmt->close();
        
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare(
            "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)"
        );
        
        if (!$stmt) {
            throw new DatabaseException("Eroare la pregătirea înregistrării: " . $this->conn->error);
        }
        
        $stmt->bind_param("sss", $username, $email, $hash);
        
        if (!$stmt->execute()) {
            throw new DatabaseException("Eroare la înregistrarea utilizatorului: " . $stmt->error);
        }
        
        return $stmt->insert_id;
    }
}
