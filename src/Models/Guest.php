<?php
namespace App\Models;

use App\Core\Database;
use App\Models\Auth;
use PDO;
use PDOException;

class Guest {
  // Profile
  public static function findByApiKey($apiKey, $csrfToken) {
    $db = Database::connect();
    $stmt = $db->prepare("
      SELECT * FROM users 
      WHERE api_key = :api_key 
        AND csrf_token = :csrf 
        AND role = 'guest'
      LIMIT 1
    ");
    $stmt->execute([
      'api_key' => $apiKey,
      'csrf'    => $csrfToken
    ]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public static function updateProfile($userId, $updates) {
    $db = Database::connect();

    // Build dynamic query
    $set = [];
    foreach ($updates as $field => $value) {
      $set[] = "$field = :$field";
    }

    $sql = "UPDATE users SET " . implode(', ', $set) . ", updated_at = NOW() WHERE user_id = :user_id";
    $stmt = $db->prepare($sql);

    $updates['user_id'] = $userId;
    return $stmt->execute($updates);
  }
}