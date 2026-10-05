<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once APP_DIR . 'controllers/ApiController.php';

class Users extends ApiController
{
    public function create()
    {
        $this->require_admin();
        $body = $this->json_body();
        $username = $body['username'] ?? null;
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        if (!is_string($username) || trim($username) === '') {
            $this->api->respond_error('Username is required', 422);
        }

        $username = trim($username);
        $username_length = preg_match_all('/./us', $username, $matches);
        if ($username_length === false || $username_length > 100) {
            $this->api->respond_error('Username must be 100 characters or fewer', 422);
        }

        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $this->api->respond_error('A valid email address is required', 422);
        }

        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            $this->api->respond_error('Password must be 8 to 72 bytes long', 422);
        }

        $email = trim($email);
        $existing = $this->db->raw(
            'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $email]
        );
        if ($existing->fetch(PDO::FETCH_ASSOC)) {
            $this->api->respond_error('Username or email is already in use', 409);
        }

        $this->db->raw(
            'INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)',
            [$username, $email, password_hash($password, PASSWORD_DEFAULT), 'user', 1]
        );

        $this->api->respond([
            'message' => 'User created successfully',
            'user' => [
                'username' => $username,
                'email' => $email,
                'role' => 'user',
            ],
        ], 201);
    }
}
