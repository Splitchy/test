<?php
class Auth {
    private $db;
    private $current_user = null;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                $this->current_user = $user;
                
                // Update last login
                $stmt = $this->db->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        session_start();
        $this->current_user = null;
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        if ($this->current_user === null) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
                $stmt->execute([$_SESSION['user_id']]);
                $this->current_user = $stmt->fetch();
            } catch (Exception $e) {
                error_log("Get current user error: " . $e->getMessage());
                return null;
            }
        }
        
        return $this->current_user;
    }
    
    public function refreshCurrentUser() {
        $this->current_user = null;
        return $this->getCurrentUser();
    }
    
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    public function hasAnyRole($roles) {
        $user = $this->getCurrentUser();
        return $user && in_array($user['role'], $roles);
    }
    
    public function requireRole($role) {
        if (!$this->hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
    }
    
    public function requireAnyRole($roles) {
        if (!$this->hasAnyRole($roles)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public function createUser($userData) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, address, city_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userData['username'],
                $userData['email'],
                self::hashPassword($userData['password']),
                $userData['role'],
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone'] ?? null,
                $userData['address'] ?? null,
                $userData['city_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }
}
?>