<?php
namespace App\Controllers;

use App\Core\Request;
use App\Models\Admin;
use App\Core\RateLimiter;
use App\Core\Auth;
use Exception;

class AdminController {
  private function getApiKey(Request $request): ?string {
    // Get Authorization header via Request helper
    $authHeader = $request->getHeader('Authorization');

    if (empty($authHeader)) {
      return null;
    }

    // Clean the API key (remove "Bearer " if present)
    return trim(str_replace('Bearer ', '', $authHeader));
  }

  // Profile
  public function getProfile(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    // Only admins will be returned
    $admin = Admin::findByApiKey($apiKey, $csrfToken);

    if (!$admin) {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    $profile = [
      'user_id'       => $admin['user_id'] ?? '',
      'api_key'       => $admin['api_key'] ?? '',
      'csrf_token'    => $admin['csrf_token'] ?? '',
      'role'          => $admin['role'] ?? '',
      'profile'       => $admin['profile'] ?? '',
      'first_name'    => $admin['first_name'] ?? '',
      'last_name'     => $admin['last_name'] ?? '',
      'email_address' => $admin['email_address'] ?? '',
      'phone_number'  => $admin['phone_number'] ?? '',
      'address'       => $admin['address'] ?? '',
      'created_at'    => $admin['created_at'] ?? '',
      'updated_at'    => $admin['updated_at'] ?? ''
    ];

    return json_encode([
      'success' => true,
      'data'    => $profile
    ]);
  }

  public function updateProfile(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Get input data
    $data = $request->body();

    // Allowed fields to update
    $allowed = ['first_name', 'last_name', 'address', 'latitude', 'longitude', 'phone_number'];
    $updates = [];

    foreach ($allowed as $field) {
      if (!empty($data[$field])) {
        // Capitalize first + lowercase for names only
        if (in_array($field, ['first_name', 'last_name'])) {
          $updates[$field] = ucfirst(strtolower(trim($data[$field])));
        } else {
          $updates[$field] = trim($data[$field]);
        }
      }
    }

    if (empty($updates)) {
      http_response_code(400);
      return json_encode(['error' => 'No valid fields to update']);
    }

    // Perform update
    $success = Admin::updateProfile($admin['user_id'], $updates);

    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to update profile']);
    }

    // Build sanitized response (only allowed fields)
    $updatedProfile = [
      'first_name'   => $updates['first_name']   ?? $admin['first_name'],
      'last_name'    => $updates['last_name']    ?? $admin['last_name'],
      'address'      => $updates['address']      ?? $admin['address'],
      'latitude'     => $updates['latitude']     ?? $admin['latitude'],
      'longitude'    => $updates['longitude']    ?? $admin['longitude'],
      'phone_number' => $updates['phone_number'] ?? $admin['phone_number'],
    ];

    return json_encode([
      'success' => true,
      'message' => 'Profile updated successfully',
      'data'    => $updatedProfile
    ]);
  }

  // News & Events
  public function createNews(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Input data
    $data = $request->body();

    // Required fields
    $required = ['title', 'description', 'status'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        http_response_code(400);
        return json_encode(['error' => "$field is required"]);
      }
    }

    if (Admin::isDuplicateNews($data['title'], $data['description'])) {
      http_response_code(409);
      return json_encode(['error' => 'Duplicate news entry already exists']);
    }

    $newsId = bin2hex(random_bytes(16));

    // Handle event_date - set to current date if empty or null
    $eventDate = $data['event_date'] ?? null;
    if (empty($eventDate)) {
        $eventDate = date('Y-m-d');
    }

    $newsData = [
      'user_id'     => $admin['user_id'],
      'news_id'     => $newsId,
      'title'       => trim($data['title']),
      'description' => trim($data['description']),
      'image_url'   => $data['image_url'] ?? '',
      'event_date'  => $data['event_date'] ?? null,
      'status'      => $data['status'], 
    ];

    // Insert into DB
    $success = Admin::createNews($newsData);

    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to create news']);
    }

    return json_encode([
      'success' => true,
      'message' => 'News created successfully',
      'data'    => $newsData
    ]);
  }

  public function getNews(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    // Check rate limit
    if (!RateLimiter::check($apiKey)) {
      http_response_code(429); // Too Many Requests
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    // Verify CSRF Token
    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    // Verify admin authentication using API key
    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin) {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Get query parameters for pagination, filters, and search
    $page      = max(1, intval($request->getQuery('page', 1)));
    $limit     = max(1, min(100, intval($request->getQuery('limit', 10))));
    $offset    = ($page - 1) * $limit;

    $search    = $request->getQuery('search', '');
    $status    = $request->getQuery('status', '');
    $eventDate = $request->getQuery('event_date', '');

    // Fetch news with pagination, search, and filters
    $result = Admin::getAllNews($limit, $offset, $search, $status, $eventDate);

    if ($result === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve news']);
    }

    // Get total count for pagination metadata
    $totalNews = Admin::getTotalNews($search, $status, $eventDate);

    if ($totalNews === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve news count']);
    }

    $totalPages = ceil($totalNews / $limit);

    return json_encode([
      'success' => true,
      'data' => $result,
      'pagination' => [
        'current_page' => $page,
        'per_page'     => $limit,
        'total_items'  => $totalNews,
        'total_pages'  => $totalPages,
        'has_next'     => $page < $totalPages,
        'has_prev'     => $page > 1
      ]
    ]);
  }

  public function updateNews(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Input data
    $data = $request->body();

    // Check required field: news_id
    if (empty($data['news_id'])) {
      http_response_code(400);
      return json_encode(['error' => 'news_id is required']);
    }

    // Check if the news exists
    $existingNews = Admin::getNewsById($data['news_id']);
    if (!$existingNews) {
      http_response_code(404);
      return json_encode(['error' => 'News not found']);
    }

    // Prepare update data
    $updateData = [];
    $allowedFields = ['title', 'description', 'image_url', 'event_date', 'status'];

    foreach ($allowedFields as $field) {
      if (isset($data[$field])) {
        $updateData[$field] = trim($data[$field]);
      }
    }

    if (empty($updateData)) {
      http_response_code(400);
      return json_encode(['error' => 'No fields provided to update']);
    }

    // Update in DB
    $success = Admin::updateNews($data['news_id'], $updateData);

    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to update news']);
    }

    return json_encode([
      'success' => true,
      'message' => 'News updated successfully',
      'data'    => array_merge(['news_id' => $data['news_id']], $updateData)
    ]);
  }

  public function deleteNews(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Prefer body, fallback to query
    $data = $request->body();
    $deleteAll = $data['delete_all'] ?? $request->getQuery('delete_all', false);
    $newsId = $data['news_id'] ?? $request->getQuery('news_id', null);

    // If news_id is provided → delete one
    if (!empty($newsId)) {
      $existingNews = Admin::getNewsById($newsId);
      if (!$existingNews) {
        http_response_code(404);
        return json_encode(['error' => 'News not found']);
      }

      $success = Admin::deleteNewsById($newsId);
      if (!$success) {
        http_response_code(500);
        return json_encode(['error' => 'Failed to delete news']);
      }

      return json_encode([
        'success' => true,
        'message' => 'News deleted successfully',
        'deleted_id' => $newsId
      ]);
    }

    // If delete_all is true → batch delete all
    if ($deleteAll === "true" || $deleteAll === true) {
      $batchSize = 100;
      $totalDeleted = 0;

      while (true) {
        $deleted = Admin::deleteNewsBatch($batchSize);
        if ($deleted === false) {
          http_response_code(500);
          return json_encode(['error' => 'Failed during batch deletion']);
        }

        $totalDeleted += $deleted;

        if ($deleted < $batchSize) {
          break; // no more rows
        }
      }

      return json_encode([
        'success' => true,
        'message' => 'All news deleted successfully in batches',
        'total_deleted' => $totalDeleted
      ]);
    }

    http_response_code(400);
    return json_encode(['error' => 'Provide either news_id or delete_all=true']);
  }

  // Accounts
  public function getAccounts(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    // Check rate limit
    if (!RateLimiter::check($apiKey)) {
      http_response_code(429); 
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    // Verify CSRF Token
    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    // Verify admin authentication
    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Get query parameters for pagination, filters, and search
    $page      = max(1, intval($request->getQuery('page', 1)));
    $limit     = max(1, min(100, intval($request->getQuery('limit', 10))));
    $offset    = ($page - 1) * $limit;

    $search    = $request->getQuery('search', '');
    $role      = $request->getQuery('role', '');
    $status    = $request->getQuery('status', ''); 

    // Fetch accounts
    $result = Admin::getAllAccounts($limit, $offset, $search, $role, $status);

    if ($result === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve accounts']);
    }

    // Get total count for pagination metadata
    $totalAccounts = Admin::getTotalAccounts($search, $role, $status);

    if ($totalAccounts === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve accounts count']);
    }

    $totalPages = ceil($totalAccounts / $limit);

    return json_encode([
      'success' => true,
      'data' => $result,
      'pagination' => [
        'current_page' => $page,
        'per_page'     => $limit,
        'total_items'  => $totalAccounts,
        'total_pages'  => $totalPages,
        'has_next'     => $page < $totalPages,
        'has_prev'     => $page > 1
      ]
    ]);
  }

  public function updateAccount(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    // Validate admin
    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Input data
    $data = $request->body();

    if (empty($data['user_id'])) {
      http_response_code(400);
      return json_encode(['error' => 'User ID is required']);
    }

    // 🔹 Check if user exists
    $user = Admin::getAccountById($data['user_id']);
    if (!$user) {
      http_response_code(404);
      return json_encode(['error' => 'User not found']);
    }

    // Allowed fields
    $allowed = ['role', 'status'];
    $updates = [];

    // 🔹 ENUM constraints
    $validRoles   = ['guest', 'admin'];
    $validStatus  = ['active', 'suspended', 'banned'];

    foreach ($allowed as $field) {
      if (!empty($data[$field])) {
        $value = trim($data[$field]);

        if ($field === 'role' && !in_array($value, $validRoles, true)) {
          http_response_code(400);
          return json_encode(['error' => "Invalid role."]);
        }

        if ($field === 'status' && !in_array($value, $validStatus, true)) {
          http_response_code(400);
          return json_encode(['error' => "Invalid status."]);
        }

        $updates[$field] = $value;
      }
    }

    if (empty($updates)) {
      http_response_code(400);
      return json_encode(['error' => 'No valid fields to update']);
    }

    // Perform update
    $success = Admin::updateAccounts($data['user_id'], $updates);

    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to update account']);
    }

    return json_encode([
      'success' => true,
      'message' => 'Account updated successfully',
      'data'    => array_merge(['user_id' => $data['user_id']], $updates)
    ]);
  }

  // FAQ
  public function createFAQ(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Input data
    $data = $request->body();

    // Required fields
    $required = ['question', 'answer', 'category', 'status'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        http_response_code(400);
        return json_encode(['error' => "$field is required"]);
      }
    }

    $allowedStatuses = ['active', 'archive'];
    if (!in_array(strtolower($data['status']), $allowedStatuses, true)) {
      http_response_code(400);
      return json_encode(['error' => "Invalid status. Allowed values: active, archive"]);
    }

    // Prevent duplicate FAQ
    if (Admin::isDuplicateFAQ($data['question'], $data['answer'])) {
      http_response_code(409);
      return json_encode(['error' => 'Duplicate FAQ already exists']);
    }

    $faqId = bin2hex(random_bytes(8)); // 16 chars

    $faqData = [
      'faq_id'     => $faqId,
      'question'   => trim($data['question']),
      'answer'     => trim($data['answer']),
      'category'   => trim($data['category']),
      'status'     => trim($data['status']),
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ];

    // Insert into DB
    $success = Admin::createFAQ($faqData);

    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to create FAQ']);
    }

    return json_encode([
      'success' => true,
      'message' => 'FAQ created successfully',
      'data'    => $faqData
    ]);
  }

  public function getFAQ(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin) {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Pagination and filters
    $page     = max(1, intval($request->getQuery('page', 1)));
    $limit    = max(1, min(100, intval($request->getQuery('limit', 10))));
    $offset   = ($page - 1) * $limit;

    $search   = $request->getQuery('search', '');
    $category = $request->getQuery('category', '');
    $status   = $request->getQuery('status', '');

    $result = Admin::getAllFAQs($limit, $offset, $search, $category, $status);
    if ($result === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve FAQs']);
    }

    $totalFAQs = Admin::getTotalFAQs($search, $category, $status);
    if ($totalFAQs === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve FAQ count']);
    }

    $totalPages = ceil($totalFAQs / $limit);

    return json_encode([
      'success' => true,
      'data' => $result,
      'pagination' => [
        'current_page' => $page,
        'per_page'     => $limit,
        'total_items'  => $totalFAQs,
        'total_pages'  => $totalPages,
        'has_next'     => $page < $totalPages,
        'has_prev'     => $page > 1
      ]
    ]);
  }

  public function updateFAQ(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    $data = $request->body();
    if (empty($data['faq_id'])) {
      http_response_code(400);
      return json_encode(['error' => 'faq_id is required']);
    }

    $existingFAQ = Admin::getFAQById($data['faq_id']);
    if (!$existingFAQ) {
      http_response_code(404);
      return json_encode(['error' => 'FAQ not found']);
    }

    $updateData = [];
    $allowedFields = ['question', 'answer', 'category', 'status'];

    foreach ($allowedFields as $field) {
      if (isset($data[$field])) {
        $updateData[$field] = trim($data[$field]);
      }
    }

    $allowedStatuses = ['active', 'archive'];
    if (!in_array(strtolower($data['status']), $allowedStatuses, true)) {
      http_response_code(400);
      return json_encode(['error' => "Invalid status. Allowed values: active, archive"]);
    }

    if (empty($updateData)) {
      http_response_code(400);
      return json_encode(['error' => 'No fields provided to update']);
    }

    $success = Admin::updateFAQ($data['faq_id'], $updateData);
    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to update FAQ']);
    }

    return json_encode([
      'success' => true,
      'message' => 'FAQ updated successfully',
      'data'    => array_merge(['faq_id' => $data['faq_id']], $updateData)
    ]);
  }

  public function deleteFAQ(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    $data = $request->body();
    $deleteAll = $data['delete_all'] ?? $request->getQuery('delete_all', false);
    $faqId = $data['faq_id'] ?? $request->getQuery('faq_id', null);

    if (!empty($faqId)) {
      $existingFAQ = Admin::getFAQById($faqId);
      if (!$existingFAQ) {
        http_response_code(404);
        return json_encode(['error' => 'FAQ not found']);
      }

      $success = Admin::deleteFAQById($faqId);
      if (!$success) {
        http_response_code(500);
        return json_encode(['error' => 'Failed to delete FAQ']);
      }

      return json_encode([
        'success' => true,
        'message' => 'FAQ deleted successfully',
        'deleted_id' => $faqId
      ]);
    }

    if ($deleteAll === "true" || $deleteAll === true) {
      $batchSize = 100;
      $totalDeleted = 0;

      while (true) {
        $deleted = Admin::deleteFAQBatch($batchSize);
        if ($deleted === false) {
          http_response_code(500);
          return json_encode(['error' => 'Failed during batch deletion']);
        }

        $totalDeleted += $deleted;
        if ($deleted < $batchSize) break;
      }

      return json_encode([
        'success' => true,
        'message' => 'All FAQs deleted successfully in batches',
        'total_deleted' => $totalDeleted
      ]);
    }

    http_response_code(400);
    return json_encode(['error' => 'Provide either faq_id or delete_all=true']);
  }

  // Resources
  public function createResource(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Input data
    $data = $request->body();

    // Required fields
    $required = ['resource_name', 'resource_type', 'capacity', 'status', 'day_rate', 'night_rate', 'description'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        http_response_code(400);
        return json_encode(['error' => "$field is required"]);
      }
    }

    // ✅ Validate status (only allowed values)
    $allowedStatuses = ['occupied', 'reserved', 'closed', 'available'];
    if (!in_array(strtolower($data['status']), $allowedStatuses, true)) {
      http_response_code(400);
      return json_encode(['error' => "Invalid status. Allowed values: " . implode(', ', $allowedStatuses)]);
    }

    $resourceId = bin2hex(random_bytes(8));
    $imageId    = bin2hex(random_bytes(8));
    $ammenitiesId = bin2hex(random_bytes(8));

    $resourceData = [
      'resource_id'   => $resourceId,
      'ammenities_id' => $ammenitiesId,
      'image_id'      => $imageId,
      'resource_name' => trim($data['resource_name']),
      'resource_type' => trim($data['resource_type']),
      'capacity'      => intval($data['capacity']),
      'status'        => strtolower(trim($data['status'])),
      'day_rate'      => floatval($data['day_rate']),
      'night_rate'    => floatval($data['night_rate']),
      'description'   => trim($data['description']),
      'latitude'      => !empty($data['latitude']) ? trim($data['latitude']) : null,
      'longitude'     => !empty($data['longitude']) ? trim($data['longitude']) : null,
      'created_at'    => date('Y-m-d H:i:s'),
      'updated_at'    => date('Y-m-d H:i:s')
    ];

    // Insert Resource
    $success = Admin::createResource($resourceData);

    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to create resource']);
    }

    // ✅ Insert multiple images (resource_images)
    if (!empty($data['images']) && is_array($data['images'])) {
      foreach ($data['images'] as $imgPath) {
        Admin::createResourceImage([
          'resource_id' => $resourceId,
          'image_id'    => $imageId,
          'path'        => trim($imgPath)
        ]);
      }
    }

    // ✅ Insert ammenities (ammenities table)
    if (!empty($data['ammenities']) && is_array($data['ammenities'])) {
      foreach ($data['ammenities'] as $ammenity) {
        Admin::createAmmenity([
          'resource_id'   => $resourceId,
          'ammenities_id' => $ammenitiesId,
          'ammenity'      => trim($ammenity)
        ]);
      }
    }

    return json_encode([
      'success' => true,
      'message' => 'Resource created successfully',
      'data'    => $resourceData
    ]);
  }

  public function getResources(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin) {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Pagination & filters
    $page   = max(1, intval($request->getQuery('page', 1)));
    $limit  = max(1, min(100, intval($request->getQuery('limit', 10))));
    $offset = ($page - 1) * $limit;

    $search      = $request->getQuery('search', '');
    $status      = $request->getQuery('status', '');
    $resourceType = $request->getQuery('resource_type', '');

    // Fetch resources
    $resources = Admin::getAllResources($limit, $offset, $search, $status, $resourceType);
    if ($resources === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve resources']);
    }

    // Count total for pagination
    $totalResources = Admin::getTotalResources($search, $status, $resourceType);
    if ($totalResources === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve resources count']);
    }

    // Attach images & ammenities to each resource
    foreach ($resources as &$res) {
      $res['images'] = Admin::getResourceImages($res['resource_id']);
      $res['ammenities'] = Admin::getResourceAmmenities($res['resource_id']);
    }

    $totalPages = ceil($totalResources / $limit);

    return json_encode([
      'success' => true,
      'data' => $resources,
      'pagination' => [
        'current_page' => $page,
        'per_page'     => $limit,
        'total_items'  => $totalResources,
        'total_pages'  => $totalPages,
        'has_next'     => $page < $totalPages,
        'has_prev'     => $page > 1
      ]
    ]);
  }

  public function updateResource(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin || $admin['role'] !== 'admin') {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Input data
    $data = $request->body();

    if (empty($data['resource_id'])) {
      http_response_code(400);
      return json_encode(['error' => 'resource_id is required']);
    }

    $resourceId = $data['resource_id'];

    // Required fields
    $required = ['resource_name', 'resource_type', 'capacity', 'status', 'day_rate', 'night_rate', 'description'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        http_response_code(400);
        return json_encode(['error' => "$field is required"]);
      }
    }

    // ✅ Validate status
    $allowedStatuses = ['occupied', 'reserved', 'closed', 'available'];
    if (!in_array(strtolower($data['status']), $allowedStatuses, true)) {
      http_response_code(400);
      return json_encode(['error' => "Invalid status. Allowed values: " . implode(', ', $allowedStatuses)]);
    }

    // Build update data
    $resourceData = [
      'resource_id'   => $resourceId,
      'resource_name' => trim($data['resource_name']),
      'resource_type' => trim($data['resource_type']),
      'capacity'      => intval($data['capacity']),
      'status'        => strtolower(trim($data['status'])),
      'day_rate'      => floatval($data['day_rate']),
      'night_rate'    => floatval($data['night_rate']),
      'description'   => trim($data['description']),
      'latitude'      => !empty($data['latitude']) ? trim($data['latitude']) : null,
      'longitude'     => !empty($data['longitude']) ? trim($data['longitude']) : null,
      'updated_at'    => date('Y-m-d H:i:s')
    ];

    // Update resource
    $success = Admin::updateResource($resourceData);
    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to update resource']);
    }

    // ✅ Replace images
    if (!empty($data['images']) && is_array($data['images'])) {
      Admin::deleteResourceImages($resourceId);
      $imageId = bin2hex(random_bytes(8));
      foreach ($data['images'] as $imgPath) {
        Admin::createResourceImage([
          'resource_id' => $resourceId,
          'image_id'    => $imageId,
          'path'        => trim($imgPath)
        ]);
      }
    }

    // ✅ Replace ammenities
    if (!empty($data['ammenities']) && is_array($data['ammenities'])) {
      Admin::deleteResourceAmmenities($resourceId);
      $ammenitiesId = bin2hex(random_bytes(8));
      foreach ($data['ammenities'] as $ammenity) {
        Admin::createAmmenity([
          'resource_id'   => $resourceId,
          'ammenities_id' => $ammenitiesId,
          'ammenity'      => trim($ammenity)
        ]);
      }
    }

    return json_encode([
      'success' => true,
      'message' => 'Resource updated successfully',
      'data'    => $resourceData
    ]);
  }

  // Bookings
  public function createBooking(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin) {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Input data
    $data = $request->body();

    // Required fields
    $required = ['user_id', 'resource_id', 'check_in', 'check_out', 'guests', 'rate'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        http_response_code(400);
        return json_encode(['error' => "$field is required"]);
      }
    }

    // Validate guests numeric
    if (!is_numeric($data['guests']) || intval($data['guests']) <= 0) {
      http_response_code(400);
      return json_encode(['error' => 'guests must be a positive number']);
    }

    // Validate datetime format (basic check)
    $checkIn  = date('Y-m-d H:i:s', strtotime($data['check_in']));
    $checkOut = date('Y-m-d H:i:s', strtotime($data['check_out']));
    if ($checkOut <= $checkIn) {
      http_response_code(400);
      return json_encode(['error' => 'check_out must be later than check_in']);
    }

    // Validate booking status
    $allowedStatuses = ['pending', 'confirmed', 'cancelled'];
    $status = !empty($data['status']) ? strtolower(trim($data['status'])) : 'pending';
    if (!in_array($status, $allowedStatuses, true)) {
      http_response_code(400);
      return json_encode(['error' => "Invalid status. Allowed values: " . implode(', ', $allowedStatuses)]);
    }

    // Validate payment_status
    $allowedPayments = ['pending', 'paid'];
    $paymentStatus = !empty($data['payment_status']) ? strtolower(trim($data['payment_status'])) : 'pending';
    if (!in_array($paymentStatus, $allowedPayments, true)) {
      http_response_code(400);
      return json_encode(['error' => "Invalid payment_status. Allowed values: " . implode(', ', $allowedPayments)]);
    }

    // Validate rate
    $allowedRates = ['day', 'night'];
    $rate = strtolower(trim($data['rate']));
    if (!in_array($rate, $allowedRates, true)) {
      http_response_code(400);
      return json_encode(['error' => "Invalid rate. Allowed values: " . implode(', ', $allowedRates)]);
    }

    // Check if user exists
    $user = Admin::findUserById(trim($data['user_id']));
    if (!$user) {
      http_response_code(404);
      return json_encode(['error' => 'User not found']);
    }

    // Check if resource exists
    $resource = Admin::findResourceById(trim($data['resource_id']));
    if (!$resource) {
      http_response_code(404);
      return json_encode(['error' => 'Resource not found']);
    }

    // Generate booking_id
    $bookingId = bin2hex(random_bytes(8));

    $bookingData = [
      'booking_id'      => $bookingId,
      'user_id'         => trim($data['user_id']),
      'resource_id'     => trim($data['resource_id']),
      'check_in'        => $checkIn,
      'check_out'       => $checkOut,
      'guests'          => strval(intval($data['guests'])),
      'rate'            => $rate,
      'status'          => $status,
      'payment_status'  => $paymentStatus,
      'special_request' => !empty($data['special_request']) ? trim($data['special_request']) : null,
      'created_at'      => date('Y-m-d H:i:s'),
      'updated_at'      => date('Y-m-d H:i:s')
    ];

    // Insert into DB
    $success = Admin::createBooking($bookingData);

    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to create booking']);
    }

    return json_encode([
      'success' => true,
      'message' => 'Booking created successfully',
      'data'    => $bookingData
    ]);
  }

  public function getBookings(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin) {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Pagination
    $page   = max(1, intval($request->getQuery('page', 1)));
    $limit  = max(1, min(100, intval($request->getQuery('limit', 10))));
    $offset = ($page - 1) * $limit;

    // Filters
    $search        = $request->getQuery('search', '');
    $status        = $request->getQuery('status', '');
    $paymentStatus = $request->getQuery('payment_status', '');
    $userId        = $request->getQuery('user_id', '');
    $resourceId    = $request->getQuery('resource_id', '');

    // Fetch bookings
    $bookings = Admin::getAllBookings($limit, $offset, $search, $status, $paymentStatus, $userId, $resourceId);
    if ($bookings === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve bookings']);
    }

    // Count total for pagination
    $totalBookings = Admin::getTotalBookings($search, $status, $paymentStatus, $userId, $resourceId);
    if ($totalBookings === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve bookings count']);
    }

    $totalPages = ceil($totalBookings / $limit);

    return json_encode([
      'success' => true,
      'data' => $bookings,
      'pagination' => [
        'current_page' => $page,
        'per_page'     => $limit,
        'total_items'  => $totalBookings,
        'total_pages'  => $totalPages,
        'has_next'     => $page < $totalPages,
        'has_prev'     => $page > 1
      ]
    ]);
  }

  public function updateBooking(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin) {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Input
    $data = $request->body();
    $bookingId = $data['booking_id'] ?? null;

    if (empty($bookingId)) {
      http_response_code(400);
      return json_encode(['error' => 'booking_id is required']);
    }

    if (empty($data['status']) && empty($data['payment_status']) && empty($data['rate'])) {
      http_response_code(400);
      return json_encode(['error' => 'At least one of status, payment_status, or rate is required']);
    }

    // Validate status
    $allowedStatuses = ['pending', 'confirmed', 'cancelled'];
    $status = null;
    if (!empty($data['status'])) {
      $status = strtolower(trim($data['status']));
      if (!in_array($status, $allowedStatuses, true)) {
        http_response_code(400);
        return json_encode(['error' => "Invalid status."]);
      }
    }

    // Validate payment_status
    $allowedPayments = ['pending', 'paid'];
    $paymentStatus = null;
    if (!empty($data['payment_status'])) {
      $paymentStatus = strtolower(trim($data['payment_status']));
      if (!in_array($paymentStatus, $allowedPayments, true)) {
        http_response_code(400);
        return json_encode(['error' => "Invalid payment_status."]);
      }
    }

    // Validate rate
    $allowedRates = ['day', 'night'];
    $rate = null;
    if (!empty($data['rate'])) {
      $rate = strtolower(trim($data['rate']));
      if (!in_array($rate, $allowedRates, true)) {
        http_response_code(400);
        return json_encode(['error' => "Invalid rate. Allowed values: " . implode(', ', $allowedRates)]);
      }
    }

    // Check if booking exists
    $booking = Admin::findBookingById($bookingId);
    if (!$booking) {
      http_response_code(404);
      return json_encode(['error' => 'Booking not found']);
    }

    // Use old values if not provided
    $status        = $status ?? $booking['status'];
    $paymentStatus = $paymentStatus ?? $booking['payment_status'];
    $rate          = $rate ?? $booking['rate'];

    // Update booking
    $success = Admin::updateBooking($bookingId, $status, $paymentStatus, $rate);
    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to update booking']);
    }

    return json_encode([
      'success' => true,
      'message' => 'Booking updated successfully',
      'data' => [
        'booking_id'     => $bookingId,
        'status'         => $status,
        'payment_status' => $paymentStatus,
        'rate'           => $rate
      ]
    ]);
  }

  // Guests List
  public function getGuests(Request $request) {
    $apiKey = $this->getApiKey($request);

    if (empty($apiKey)) {
      http_response_code(401);
      return json_encode(['error' => 'API key is required']);
    }

    if (!RateLimiter::check($apiKey)) {
      http_response_code(429);
      return json_encode(['error' => 'Rate limit exceeded. Try again later.']);
    }

    $csrfToken = $request->getHeader('X-CSRF-Token');
    if (empty($csrfToken)) {
      http_response_code(403);
      return json_encode(['error' => 'CSRF token is required']);
    }

    $admin = Admin::findByApiKey($apiKey, $csrfToken);
    if (!$admin) {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    // Pagination
    $page   = max(1, intval($request->getQuery('page', 1)));
    $limit  = max(1, min(100, intval($request->getQuery('limit', 10))));
    $offset = ($page - 1) * $limit;

    // Filters
    $search = $request->getQuery('search', '');

    // Fetch guests
    $guests = Admin::getGuests($limit, $offset, $search);
    if ($guests === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve guests']);
    }

    // Count total for pagination
    $totalGuests = Admin::getTotalGuests($search);
    if ($totalGuests === false) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to retrieve guests count']);
    }

    $totalPages = ceil($totalGuests / $limit);

    return json_encode([
      'success' => true,
      'data' => $guests,
      'pagination' => [
        'current_page' => $page,
        'per_page'     => $limit,
        'total_items'  => $totalGuests,
        'total_pages'  => $totalPages,
        'has_next'     => $page < $totalPages,
        'has_prev'     => $page > 1
      ]
    ]);
  }
}