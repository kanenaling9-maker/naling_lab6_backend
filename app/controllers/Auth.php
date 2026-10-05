<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

class Auth extends ApiController
{
    public function login()
    {
        $this->api->rate_limit();
        $body = $this->json_body();
        $identifier = $body['identifier'] ?? $body['email'] ?? null;
        $password = $body['password'] ?? null;

        if (!is_string($identifier) || trim($identifier) === '' || strlen($identifier) > 255 || !is_string($password) || $password === '') {
            $this->api->respond_error('A username or email and password are required', 422);
        }

        $statement = $this->db->raw(
            'SELECT id, username, email, password, role, is_active FROM users WHERE email = ? OR username = ? LIMIT 1',
            [trim($identifier), trim($identifier)]
        );
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$user || !(int) $user['is_active'] || !password_verify($password, $user['password'])) {
            $this->api->respond_error('Invalid username/email or password', 401);
        }

        $tokens = $this->api->issue_tokens([
            'id' => (int) $user['id'],
            'role' => $user['role'],
        ]);

        $this->api->respond([
            'message' => 'Login successful',
            'user' => [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
            'tokens' => $tokens,
        ]);
    }
}
