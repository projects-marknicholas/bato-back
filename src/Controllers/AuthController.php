<?php
namespace App\Controllers;

use App\Core\Request;
use App\Models\Auth;
use Exception;
use Google\Client;

class AuthController {
  public function googleAuth(Request $request) {
    $data = $request->body();
    
    if (empty($data['google_token'])) {
      http_response_code(400);
      return json_encode(['error' => 'Google token is required']);
    }

    return $this->handleGoogleAuth($data['google_token']);
  }

  private function handleGoogleAuth($googleToken) {
    try {
      $client = new Client();
      $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
      $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
      
      $payload = $client->verifyIdToken($googleToken);
      
      if (!$payload) {
        http_response_code(401);
        return json_encode(['error' => 'Invalid Google token']);
      }

      $googleId   = $payload['sub'];
      $email      = $payload['email'];
      $firstName  = $payload['given_name'] ?? '';
      $lastName   = $payload['family_name'] ?? '';
      $profile    = $payload['picture'] ?? null;

      // Check if user exists
      $user = Auth::findByEmail($email);

      if (!$user) {
        $userData = [
          'user_id'       => bin2hex(random_bytes(16)),
          'api_key'       => bin2hex(random_bytes(16)), 
          'csrf_token'    => null, 
          'role'          => 'guest', 
          'profile'       => $profile,
          'first_name'    => $firstName,
          'last_name'     => $lastName,
          'email_address' => $email,
          'password'      => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
          'google_id'     => $googleId,
          'status'        => 'active',
          'created_at'    => date('Y-m-d H:i:s'),
          'updated_at'    => date('Y-m-d H:i:s')
        ];

        if (!Auth::createUser($userData)) {
          http_response_code(500);
          return json_encode(['success' => false, 'error' => 'Failed to create user account']);
        }

        $user = $userData; 
      } else {
        // 🚨 Check if suspended/banned before proceeding
        if (in_array(strtolower($user['status']), ['suspended', 'banned'])) {
          http_response_code(403);
          return json_encode([
            'success' => false,
            'error'   => 'Your account has been ' . strtolower($user['status']) . '. Please contact support.'
          ]);
        }
        
        // Update Google ID if not set
        if (empty($user['google_id'])) {
          Auth::updateGoogleId($user['id'], $googleId);
          $user['google_id'] = $googleId;
        }

        // Update profile picture if changed
        if ($profile && $user['profile'] !== $profile) {
          Auth::updateProfile($user['id'], $profile);
          $user['profile'] = $profile;
        }
      }

      // Generate CSRF token
      $csrfToken = bin2hex(random_bytes(16));
      Auth::updateCsrfToken($user['user_id'], $csrfToken);
      $user['csrf_token'] = $csrfToken;

      // Remove password before sending to client
      unset($user['password']);

      return json_encode([
        'success' => true,
        'message' => 'Google authentication successful',
        'user'    => $user
      ]);

    } catch (Exception $e) {
      error_log("Google auth error: " . $e->getMessage());
      http_response_code(500);
      return json_encode(['error' => 'Google authentication failed']);
    }
  }
}