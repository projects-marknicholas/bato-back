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
    $sql = "SELECT user_id, profile, first_name, last_name, email_address, phone_number, address, role, status, created_at, updated_at
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

  public static function getAccountById($userId) {
    try {
      $db = Database::connect();
      $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = :user_id LIMIT 1");
      $stmt->execute([':user_id' => $userId]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("getAccountById error: " . $e->getMessage());
      return false;
    }
  }

  public static function updateAccounts($userId, $updates) {
    try {
      $allowedFields = ['role', 'status'];
      $fields = [];
      $params = [':user_id' => $userId];

      foreach ($updates as $key => $value) {
        if (in_array($key, $allowedFields)) {
          $fields[] = "$key = :$key";
          $params[":$key"] = $value;
        }
      }

      if (empty($fields)) {
        return false; // nothing to update
      }

      $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE user_id = :user_id";

      $db = Database::connect();
      $stmt = $db->prepare($sql);
      return $stmt->execute($params);

    } catch (PDOException $e) {
      error_log("updateAccounts error: " . $e->getMessage());
      return false;
    }
  }

  // FAQ
  public static function isDuplicateFAQ($question, $answer) {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT COUNT(*) FROM faqs WHERE question = ? AND answer = ?");
    $stmt->execute([$question, $answer]);
    return $stmt->fetchColumn() > 0;
  }

  public static function createFAQ($faqData) {
    $db = Database::connect();
    $stmt = $db->prepare("
      INSERT INTO faqs (faq_id, question, answer, category, status, created_at, updated_at)
      VALUES (:faq_id, :question, :answer, :category, :status, :created_at, :updated_at)
    ");
    return $stmt->execute($faqData);
  }

  public static function getAllFAQs($limit, $offset, $search = '', $category = '', $status = '') {
    $db = Database::connect();

    $sql = "SELECT faq_id, question, answer, category, status, created_at, updated_at FROM faqs WHERE 1=1";
    $params = [];

    if (!empty($search)) {
      $sql .= " AND (question LIKE :search OR answer LIKE :search)";
      $params[':search'] = "%$search%";
    }

    if (!empty($category)) {
      $sql .= " AND category = :category";
      $params[':category'] = $category;
    }

    if (!empty($status)) {
      $sql .= " AND status = :status";
      $params[':status'] = $status;
    }

    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);

    // Bind dynamic params
    foreach ($params as $key => $value) {
      $stmt->bindValue($key, $value);
    }

    // ✅ Force integer for limit and offset
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    if ($stmt->execute()) {
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return false;
  }

  public static function getTotalFAQs($search = '', $category = '', $status = '') {
    $db = Database::connect();

    $sql = "SELECT COUNT(*) FROM faqs WHERE 1=1";
    $params = [];

    if (!empty($search)) {
      $sql .= " AND (question LIKE ? OR answer LIKE ?)";
      $params[] = "%$search%";
      $params[] = "%$search%";
    }

    if (!empty($category)) {
      $sql .= " AND category = ?";
      $params[] = $category;
    }

    if (!empty($status)) {
      $sql .= " AND status = ?";
      $params[] = $status;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
  }

  public static function getFAQById($faqId) {
    $db = Database::connect();
    $stmt = $db->prepare("SELECT * FROM faqs WHERE faq_id = ?");
    $stmt->execute([$faqId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public static function updateFAQ($faqId, $updateData) {
    $db = Database::connect();

    $fields = [];
    $params = [];

    foreach ($updateData as $field => $value) {
      $fields[] = "$field = ?";
      $params[] = $value;
    }

    $params[] = $faqId;

    $sql = "UPDATE faqs SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE faq_id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
  }

  public static function deleteFAQById($faqId) {
    $db = Database::connect();
    $stmt = $db->prepare("DELETE FROM faqs WHERE faq_id = ?");
    return $stmt->execute([$faqId]);
  }

  public static function deleteFAQBatch($limit = 100) {
    $db = Database::connect();
    $stmt = $db->prepare("DELETE FROM faqs ORDER BY created_at ASC LIMIT ?");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount();
  }

  // Resources
  public static function createResource($data) {
    $db = Database::connect();
    $sql = "INSERT INTO resources 
            (resource_id, ammenities_id, image_id, resource_name, resource_type, capacity, status, day_rate, night_rate, description, latitude, longitude, created_at, updated_at)
            VALUES (:resource_id, :ammenities_id, :image_id, :resource_name, :resource_type, :capacity, :status, :day_rate, :night_rate, :description, :latitude, :longitude, :created_at, :updated_at)";
    $stmt = $db->prepare($sql);
    return $stmt->execute($data);
  }

  public static function createResourceImage($data) {
    $db = Database::connect();
    $sql = "INSERT INTO resource_images (resource_id, image_id, path) 
            VALUES (:resource_id, :image_id, :path)";
    $stmt = $db->prepare($sql);
    return $stmt->execute($data);
  }

  public static function createAmmenity($data) {
    $db = Database::connect();
    $sql = "INSERT INTO ammenities (resource_id, ammenities_id, ammenity) 
            VALUES (:resource_id, :ammenities_id, :ammenity)";
    $stmt = $db->prepare($sql);
    return $stmt->execute($data);
  }

  public static function getAllResources($limit, $offset, $search = '', $status = '', $resourceType = '') {
    $db = Database::connect();
    $sql = "SELECT * FROM resources WHERE 1=1";
    $params = [];

    if (!empty($search)) {
      $sql .= " AND (resource_name LIKE :search OR description LIKE :search)";
      $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
      $sql .= " AND status = :status";
      $params[':status'] = strtolower($status);
    }

    if (!empty($resourceType)) {
      $sql .= " AND resource_type = :resource_type";
      $params[':resource_type'] = $resourceType;
    }

    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);

    // Bind values
    foreach ($params as $key => $val) {
      $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    if ($stmt->execute()) {
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return false;
  }

  public static function getTotalResources($search = '', $status = '', $resourceType = '') {
    $db = Database::connect();
    $sql = "SELECT COUNT(*) as total FROM resources WHERE 1=1";
    $params = [];

    if (!empty($search)) {
      $sql .= " AND (resource_name LIKE :search OR description LIKE :search)";
      $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
      $sql .= " AND status = :status";
      $params[':status'] = strtolower($status);
    }

    if (!empty($resourceType)) {
      $sql .= " AND resource_type = :resource_type";
      $params[':resource_type'] = $resourceType;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? intval($row['total']) : false;
  }

  public static function getResourceImages($resourceId) {
    $db = Database::connect();
    $sql = "SELECT path FROM resource_images WHERE resource_id = :resource_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':resource_id' => $resourceId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }

  public static function getResourceAmmenities($resourceId) {
    $db = Database::connect();
    $sql = "SELECT ammenity FROM ammenities WHERE resource_id = :resource_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':resource_id' => $resourceId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }

  public static function updateResource($data) {
    $db = Database::connect();
    $sql = "UPDATE resources 
            SET resource_name = :resource_name, resource_type = :resource_type,
                capacity = :capacity, status = :status, day_rate = :day_rate, 
                night_rate = :night_rate, description = :description, 
                latitude = :latitude, longitude = :longitude, updated_at = :updated_at
            WHERE resource_id = :resource_id";
    $stmt = $db->prepare($sql);
    return $stmt->execute($data);
  }

  public static function deleteResourceImages($resourceId) {
    $db = Database::connect();
    $sql = "DELETE FROM resource_images WHERE resource_id = :resource_id";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':resource_id' => $resourceId]);
  }

  public static function deleteResourceAmmenities($resourceId) {
    $db = Database::connect();
    $sql = "DELETE FROM ammenities WHERE resource_id = :resource_id";
    $stmt = $db->prepare($sql);
    return $stmt->execute([':resource_id' => $resourceId]);
  }

  // Bookings & Reservations
  public static function createBooking($data) {
    $db = Database::connect();
    $sql = "INSERT INTO bookings 
            (booking_id, user_id, resource_id, check_in, check_out, guests, status, payment_status, rate, special_request, created_at, updated_at)
            VALUES (:booking_id, :user_id, :resource_id, :check_in, :check_out, :guests, :status, :payment_status, :rate, :special_request, :created_at, :updated_at)";
    $stmt = $db->prepare($sql);
    return $stmt->execute($data);
  }

  public static function findUserById($userId) {
    $db = Database::connect();
    $sql = "SELECT user_id FROM users WHERE user_id = :user_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public static function findResourceById($resourceId) {
    $db = Database::connect();
    $sql = "SELECT resource_id FROM resources WHERE resource_id = :resource_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':resource_id' => $resourceId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public static function getAllBookings($limit, $offset, $search = '', $status = '', $paymentStatus = '', $userId = '', $resourceId = '') {
    $db = Database::connect();

    $sql = "SELECT 
              b.booking_id,
              u.first_name,
              u.last_name,
              r.resource_name,
              r.day_rate,
              r.night_rate,
              b.check_in,
              b.check_out,
              b.guests,
              b.status,
              b.payment_status,
              b.rate,
              b.special_request,
              b.created_at,
              b.updated_at,
              -- Calculate amount based on rate type and duration
              CASE 
                WHEN b.rate = 'day' THEN 
                  r.day_rate
                WHEN b.rate = 'night' THEN 
                  r.night_rate
                ELSE 0
              END as amount
            FROM bookings b
            JOIN users u ON b.user_id = u.user_id
            JOIN resources r ON b.resource_id = r.resource_id
            WHERE 1=1";

    $params = [];

    if (!empty($search)) {
      $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR r.resource_name LIKE :search OR b.special_request LIKE :search)";
      $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
      $sql .= " AND b.status = :status";
      $params[':status'] = $status;
    }

    if (!empty($paymentStatus)) {
      $sql .= " AND b.payment_status = :payment_status";
      $params[':payment_status'] = $paymentStatus;
    }

    if (!empty($userId)) {
      $sql .= " AND b.user_id = :user_id";
      $params[':user_id'] = $userId;
    }

    if (!empty($resourceId)) {
      $sql .= " AND b.resource_id = :resource_id";
      $params[':resource_id'] = $resourceId;
    }

    $sql .= " ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
      $stmt->bindValue($key, $value);
    }

    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

    if ($stmt->execute()) {
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return false;
  }

  public static function getTotalBookings($search = '', $status = '', $paymentStatus = '', $userId = '', $resourceId = '') {
    $db = Database::connect();

    $sql = "SELECT COUNT(*) as total
            FROM bookings b
            JOIN users u ON b.user_id = u.user_id
            JOIN resources r ON b.resource_id = r.resource_id
            WHERE 1=1";

    $params = [];

    if (!empty($search)) {
      $sql .= " AND (u.first_name LIKE :search OR r.resource_name LIKE :search OR b.special_request LIKE :search)";
      $params[':search'] = "%$search%";
    }

    if (!empty($status)) {
      $sql .= " AND b.status = :status";
      $params[':status'] = $status;
    }

    if (!empty($paymentStatus)) {
      $sql .= " AND b.payment_status = :payment_status";
      $params[':payment_status'] = $paymentStatus;
    }

    if (!empty($userId)) {
      $sql .= " AND b.user_id = :user_id";
      $params[':user_id'] = $userId;
    }

    if (!empty($resourceId)) {
      $sql .= " AND b.resource_id = :resource_id";
      $params[':resource_id'] = $resourceId;
    }

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
      $stmt->bindValue($key, $value);
    }

    if ($stmt->execute()) {
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return $row ? (int) $row['total'] : 0;
    }

    return false;
  }

  public static function updateBooking($bookingId, $status, $paymentStatus) {
    $db = Database::connect();

    $sql = "UPDATE bookings 
            SET status = :status, 
                payment_status = :payment_status, 
                updated_at = NOW()
            WHERE booking_id = :booking_id";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':payment_status', $paymentStatus);
    $stmt->bindParam(':booking_id', $bookingId);

    return $stmt->execute();
  }

  public static function findBookingById($bookingId) {
    $db = Database::connect();
    $sql = "SELECT * FROM bookings WHERE booking_id = :booking_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':booking_id', $bookingId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Guests
  public static function getGuests($limit, $offset, $search = '') {
    $db = Database::connect();
    $searchLike = '%' . $search . '%';

    $sql = "
      SELECT 
        u.user_id,
        u.first_name,
        u.last_name,
        u.email_address,
        u.phone_number,
        u.address,
        u.created_at,
        COUNT(b.booking_id) AS total_visits,
        MAX(b.check_out) AS last_visit,
        SUM(
          CASE 
            WHEN b.rate = 'day' AND b.status = 'confirmed' AND b.payment_status = 'paid'
              THEN r.day_rate
            WHEN b.rate = 'night' AND b.status = 'confirmed' AND b.payment_status = 'paid'
              THEN r.night_rate
            ELSE 0
          END
        ) AS total_spent
      FROM users u
      LEFT JOIN bookings b ON u.user_id = b.user_id
      LEFT JOIN resources r ON b.resource_id = r.resource_id
      WHERE (
          u.first_name LIKE :search OR 
          u.last_name LIKE :search OR 
          u.email_address LIKE :search OR 
          u.phone_number LIKE :search
        )
        AND u.user_id IN (SELECT DISTINCT user_id FROM bookings) -- Only users who have booked
      GROUP BY u.user_id, u.first_name, u.last_name, u.email_address, u.phone_number, u.address, u.created_at
      ORDER BY total_visits DESC, total_spent DESC
      LIMIT :limit OFFSET :offset
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':search', $searchLike, PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

    if ($stmt->execute()) {
      $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Add rank based on visits
      $rank = $offset + 1;
      foreach ($guests as &$guest) {
        $guest['rank'] = $rank++;
      }

      return $guests;
    }

    return false;
  }

  public static function getTotalGuests($search = '') {
    $db = Database::connect();
    $searchLike = '%' . $search . '%';

    $sql = "
      SELECT COUNT(DISTINCT u.user_id) AS total
      FROM users u
      WHERE (
          u.first_name LIKE :search OR 
          u.last_name LIKE :search OR 
          u.email_address LIKE :search OR 
          u.phone_number LIKE :search
        )
        AND u.user_id IN (SELECT DISTINCT user_id FROM bookings) -- Only users who have booked
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':search', $searchLike, PDO::PARAM_STR);

    if ($stmt->execute()) {
      return (int) $stmt->fetchColumn();
    }

    return false;
  }
}