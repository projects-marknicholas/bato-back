<?php
namespace App\Models;

use App\Core\Database;
use App\Models\Auth;
use PDO;
use PDOException;

class Admin {
  // Profile
  public static function findByApiKey($apiKey, $csrfToken) {
    $db = Database::connect();
    $stmt = $db->prepare("
      SELECT * FROM users 
      WHERE api_key = :api_key 
        AND csrf_token = :csrf 
        AND role = 'admin'
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

  // News
  public static function isDuplicateNews($title, $description) {
    $db = Database::connect(); 
    $stmt = $db->prepare("SELECT COUNT(*) FROM news WHERE title = :title AND description = :description");
    $stmt->execute([
      ':title' => trim($title),
      ':description' => trim($description)
    ]);
    return $stmt->fetchColumn() > 0;
  }

  public static function createNews($newsData) {
    $db = Database::connect();

    $sql = "INSERT INTO news 
            (user_id, news_id, title, description, image_url, event_date, status, created_at, updated_at)
            VALUES (:user_id, :news_id, :title, :description, :image_url, :event_date, :status, NOW(), NOW())";

    $stmt = $db->prepare($sql);
    return $stmt->execute($newsData);
  }

  public static function getAllNews($limit = 10, $offset = 0, $search = '', $status = '', $eventDate = '') {
    try {
      $sql = "SELECT n.news_id, n.title, n.description, n.image_url, n.event_date, n.status, n.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by
              FROM news n
              JOIN users u ON n.user_id = u.user_id
              WHERE 1=1";

      $params = [];

      if (!empty($search)) {
        $sql .= " AND (n.title LIKE :search OR n.description LIKE :search)";
        $params[':search'] = "%" . $search . "%";
      }

      if (!empty($status)) {
        $sql .= " AND n.status = :status";
        $params[':status'] = $status;
      }

      if (!empty($eventDate)) {
        $sql .= " AND DATE(n.event_date) = :event_date";
        $params[':event_date'] = $eventDate;
      }

      $sql .= " ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset";

      $stmt = Database::connect()->prepare($sql);

      foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
      }

      $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
      $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

      $stmt->execute();
      return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
      error_log("Get news error: " . $e->getMessage());
      return false;
    }
  }

  public static function getTotalNews($search = '', $status = '', $eventDate = '') {
    try {
      $sql = "SELECT COUNT(*) as total FROM news n WHERE 1=1";
      $params = [];

      if (!empty($search)) {
        $sql .= " AND (n.title LIKE :search OR n.description LIKE :search)";
        $params[':search'] = "%" . $search . "%";
      }

      if (!empty($status)) {
        $sql .= " AND n.status = :status";
        $params[':status'] = $status;
      }

      if (!empty($eventDate)) {
        $sql .= " AND DATE(n.event_date) = :event_date";
        $params[':event_date'] = $eventDate;
      }

      $stmt = Database::connect()->prepare($sql);

      foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
      }

      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      return $result ? $result['total'] : 0;

    } catch (PDOException $e) {
      error_log("Get total news error: " . $e->getMessage());
      return false;
    }
  }

  public static function getNewsById($newsId) {
    try {
      $sql = "SELECT n.news_id, n.title, n.description, n.image_url, n.event_date, n.status, n.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by
              FROM news n
              JOIN users u ON n.user_id = u.user_id
              WHERE n.news_id = :news_id
              LIMIT 1";

      $stmt = Database::connect()->prepare($sql);
      $stmt->bindValue(':news_id', $newsId, PDO::PARAM_STR);
      $stmt->execute();

      return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
      error_log("Get news by ID error: " . $e->getMessage());
      return false;
    }
  }

  public static function updateNews($newsId, $data) {
    try {
      $fields = [];
      $params = [':news_id' => $newsId];

      foreach ($data as $key => $value) {
        $fields[] = "$key = :$key";
        $params[":$key"] = $value;
      }

      $sql = "UPDATE news SET " . implode(", ", $fields) . " WHERE news_id = :news_id";

      $stmt = Database::connect()->prepare($sql);
      return $stmt->execute($params);

    } catch (PDOException $e) {
      error_log("Update news error: " . $e->getMessage());
      return false;
    }
  }

  public static function deleteNewsById($newsId) {
    try {
      $sql = "DELETE FROM news WHERE news_id = :news_id";
      $stmt = Database::connect()->prepare($sql);
      $stmt->bindValue(':news_id', $newsId, PDO::PARAM_STR);
      return $stmt->execute();
    } catch (PDOException $e) {
      error_log("Delete news by ID error: " . $e->getMessage());
      return false;
    }
  }

  public static function deleteNewsBatch($limit = 100) {
    try {
      $db = Database::connect();

      // Select batch of IDs
      $sqlSelect = "SELECT news_id FROM news LIMIT :limit";
      $stmt = $db->prepare($sqlSelect);
      $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
      $stmt->execute();
      $newsIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

      if (empty($newsIds)) {
        return 0; // nothing left to delete
      }

      // Delete batch
      $inQuery = implode(',', array_fill(0, count($newsIds), '?'));
      $sqlDelete = "DELETE FROM news WHERE news_id IN ($inQuery)";
      $stmt = $db->prepare($sqlDelete);
      return $stmt->execute($newsIds) ? count($newsIds) : false;

    } catch (PDOException $e) {
      error_log("Batch delete news error: " . $e->getMessage());
      return false;
    }
  }

  // Accounts
  public static function getAllAccounts($limit, $offset, $search = '', $role = '') {
    $db = Database::connect();

    // Explicitly select only non-sensitive fields
    $sql = "SELECT first_name, last_name, email_address, phone_number, address, role, created_at, updated_at
            FROM users 
            WHERE 1=1";
    $params = [];

    if (!empty($search)) {
      $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email_address LIKE ?)";
      $params[] = "%$search%";
      $params[] = "%$search%";
      $params[] = "%$search%";
    }

    if (!empty($role)) {
      $sql .= " AND role = ?";
      $params[] = $role;
    }

    if (!empty($status)) {
      $sql .= " AND status = ?";
      $params[] = $status;
    }

    // Add safe LIMIT/OFFSET
    $sql .= " ORDER BY created_at DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getTotalAccounts($search = '', $role = '', $status = '') {
    $db = Database::connect();
    $sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    $params = [];

    if (!empty($search)) {
      $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email_address LIKE ?)";
      $params[] = "%$search%";
      $params[] = "%$search%";
      $params[] = "%$search%";
    }

    if (!empty($role)) {
      $sql .= " AND role = ?";
      $params[] = $role;
    }

    if (!empty($status)) {
      $sql .= " AND status = ?";
      $params[] = $status;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? intval($row['total']) : 0;
  }
}