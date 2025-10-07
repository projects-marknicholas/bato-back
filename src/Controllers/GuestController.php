<?php
namespace App\Controllers;

use App\Core\Request;
use App\Models\Guest;
use App\Core\RateLimiter;
use App\Core\Auth;
use Exception;

class GuestController {
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

    // Only guests will be returned
    $guest = Guest::findByApiKey($apiKey, $csrfToken);

    if (!$guest) {
      http_response_code(401);
      return json_encode(['error' => 'Invalid API key or unauthorized access']);
    }

    $profile = [
      'user_id'       => $guest['user_id'] ?? '',
      'api_key'       => $guest['api_key'] ?? '',
      'csrf_token'    => $guest['csrf_token'] ?? '',
      'role'          => $guest['role'] ?? '',
      'profile'       => $guest['profile'] ?? '',
      'first_name'    => $guest['first_name'] ?? '',
      'last_name'     => $guest['last_name'] ?? '',
      'email_address' => $guest['email_address'] ?? '',
      'phone_number'  => $guest['phone_number'] ?? '',
      'address'       => $guest['address'] ?? '',
      'created_at'    => $guest['created_at'] ?? '',
      'updated_at'    => $guest['updated_at'] ?? ''
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

    $guest = Guest::findByApiKey($apiKey, $csrfToken);
    if (!$guest || $guest['role'] !== 'guest') {
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
    $success = Guest::updateProfile($guest['user_id'], $updates);

    if (!$success) {
      http_response_code(500);
      return json_encode(['error' => 'Failed to update profile']);
    }

    // Build sanitized response (only allowed fields)
    $updatedProfile = [
      'first_name'   => $updates['first_name']   ?? $guest['first_name'],
      'last_name'    => $updates['last_name']    ?? $guest['last_name'],
      'address'      => $updates['address']      ?? $guest['address'],
      'latitude'     => $updates['latitude']     ?? $guest['latitude'],
      'longitude'    => $updates['longitude']    ?? $guest['longitude'],
      'phone_number' => $updates['phone_number'] ?? $guest['phone_number'],
    ];

    return json_encode([
      'success' => true,
      'message' => 'Profile updated successfully',
      'data'    => $updatedProfile
    ]);
  }
}