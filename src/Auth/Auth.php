<?php

namespace App\Auth;

use App\Database\Database;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class Auth
{
    private $config;
    private $db;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->db = Database::getInstance();
    }

    public function register(array $data): array
    {
        // Validate required fields
        $requiredFields = ['email', 'password', 'first_name', 'last_name', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }

        // Check if user already exists
        $existingUser = $this->db->fetch(
            'SELECT id FROM users WHERE email = ?',
            [$data['email']]
        );

        if ($existingUser) {
            throw new Exception('User with this email already exists');
        }

        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);

        // Set default status as pending for approval
        $data['status'] = 'pending';

        // Insert user
        $userId = $this->db->insert('users', $data);

        return [
            'success' => true,
            'message' => 'User registered successfully. Awaiting approval.',
            'user_id' => $userId
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->db->fetch(
            'SELECT * FROM users WHERE email = ?',
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid email or password');
        }

        if ($user['status'] !== 'approved') {
            throw new Exception('Account not approved yet');
        }

        // Generate JWT token
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'exp' => time() + $this->config['jwt']['expire']
        ];

        $token = JWT::encode($payload, $this->config['jwt']['secret'], $this->config['jwt']['algorithm']);

        // Create session
        $sessionId = $this->generateSessionId();
        $this->db->insert('sessions', [
            'id' => $sessionId,
            'user_id' => $user['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config['jwt']['expire'])
        ]);

        return [
            'success' => true,
            'token' => $token,
            'session_id' => $sessionId,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
                'status' => $user['status']
            ]
        ];
    }

    public function logout(string $sessionId): bool
    {
        return $this->db->delete('sessions', ['id' => $sessionId]) > 0;
    }

    public function validateToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config['jwt']['secret'], $this->config['jwt']['algorithm']));
            
            // Check if user still exists and is approved
            $user = $this->db->fetch(
                'SELECT * FROM users WHERE id = ? AND status = ?',
                [$decoded->user_id, 'approved']
            );

            if (!$user) {
                throw new Exception('User not found or not approved');
            }

            return [
                'valid' => true,
                'user' => $user
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getCurrentUser(): ?array
    {
        $token = $this->getTokenFromHeader();
        if (!$token) {
            return null;
        }

        $validation = $this->validateToken($token);
        return $validation['valid'] ? $validation['user'] : null;
    }

    public function requireAuth(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        return $user;
    }

    public function requireRole(string $role): array
    {
        $user = $this->requireAuth();
        if ($user['role'] !== $role && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }
        return $user;
    }

    private function getTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(64));
    }

    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool
    {
        $user = $this->db->fetch('SELECT password_hash FROM users WHERE id = ?', [$userId]);
        
        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            throw new Exception('Current password is incorrect');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->db->update('users', ['password_hash' => $newHash], ['id' => $userId]) > 0;
    }

    public function resetPassword(string $email): bool
    {
        $user = $this->db->fetch('SELECT id FROM users WHERE email = ?', [$email]);
        
        if (!$user) {
            return false; // Don't reveal if email exists
        }

        // Generate temporary password
        $tempPassword = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

        $this->db->update('users', ['password_hash' => $hashedPassword], ['id' => $user['id']]);

        // Send email with temporary password
        // This should be implemented with proper email service
        
        return true;
    }
}