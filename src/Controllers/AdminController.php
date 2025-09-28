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
}