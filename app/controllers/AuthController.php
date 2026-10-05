<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: AuthController
 * 
 * Automatically generated via CLI.
 */
class AuthController extends Controller {
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');
        $this->call->model('UserModel');
    }

    public function register()
    {
        $this->api->require_method('POST');
        $data = $this->api->body();

        $username = $data['username'] ?? '';
        $email    = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (strlen($username) < 3 || strlen($username) > 100) {
            $this->api->respond_error('Username must be 3 to 100 characters.', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->api->respond_error('A valid email is required.', 422);
        }
        if (strlen($password) < 8) {
            $this->api->respond_error('Password must be at least 8 characters.', 422);
        }
        if ($this->UserModel->find_by('email', $email)) {
            $this->api->respond_error('Email is already registered.', 422);
        }
        if ($this->UserModel->find_by('username', $username)) {
            $this->api->respond_error('Username is already taken.', 422);
        }

        $id = $this->UserModel->insert([
            'username' => $username,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role'     => 'user',
        ]);

        $this->api->respond(['message' => 'Registered successfully.', 'id' => $id], 201);
    }

    public function login()
    {
        $this->api->require_method('POST');
        $data = $this->api->body();

        $email    = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->api->respond_error('Email and password are required.', 400);
        }

        $user = $this->UserModel->find_by('email', $email);
        if (!$user) {
            $this->api->respond_error('Invalid email or password.', 401);
        }
        $user = (array) $user;

        if (!password_verify($password, $user['password']) || (int) $user['is_active'] !== 1) {
            $this->api->respond_error('Invalid email or password.', 401);
        }

        $tokens = $this->api->issue_tokens(['id' => $user['id'], 'role' => $user['role']]);

        $this->api->respond([
            'message' => 'Login successful.',
            'user'    => [
                'id'       => $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ],
            'tokens'  => $tokens,
        ]);
    }

    public function refresh()
    {
        $this->api->require_method('POST');
        $data = $this->api->body();

        if (empty($data['refresh_token'])) {
            $this->api->respond_error('refresh_token is required.', 400);
        }
        $this->api->refresh_access_token($data['refresh_token']);
    }

    public function logout()
    {
        $this->api->require_method('POST');
        $data = $this->api->body();

        if (!empty($data['refresh_token'])) {
            $this->api->revoke_refresh_token($data['refresh_token']);
        }
        $this->api->respond(['message' => 'Logged out.']);
    }
}