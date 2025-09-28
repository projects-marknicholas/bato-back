<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Auth {
  public static function findByEmail($email) {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT * FROM users WHERE email_address = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public static function createUser($data) {
    $db = Database::connect();
    $sql = "INSERT INTO users 
      (user_id, api_key, csrf_token, role, profile, first_name, last_name, email_address, password, google_id, created_at, updated_at) 
      VALUES 
      (:user_id, :api_key, :csrf_token, :role, :profile, :first_name, :last_name, :email_address, :password, :google_id, :created_at, :updated_at)";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($data);
  }

  public static function updateGoogleId($id, $googleId) {
    $db = Database::connect();
    $stmt = $db->prepare("UPDATE users SET google_id = :google_id, updated_at = NOW() WHERE id = :id");
    return $stmt->execute(['google_id' => $googleId, 'id' => $id]);
  }

  public static function updateProfile($id, $profile) {
    $db = Database::connect();
    $stmt = $db->prepare("UPDATE users SET profile = :profile, updated_at = NOW() WHERE id = :id");
    return $stmt->execute(['profile' => $profile, 'id' => $id]);
  }

  public static function updateCsrfToken($userId, $csrfToken) {
    $db = Database::connect();
    $stmt = $db->prepare("UPDATE users SET csrf_token = :csrf, updated_at = NOW() WHERE user_id = :user_id");
    return $stmt->execute(['csrf' => $csrfToken, 'user_id' => $userId]);
  }
}