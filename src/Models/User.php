<?php

namespace App\Models;

class User extends BaseModel
{
    protected $table = 'users';
    protected $fillable = [
        'email', 'password_hash', 'first_name', 'last_name', 'role', 'status',
        'cin', 'store_name', 'bank_info', 'stock_management_enabled',
        'delivery_fee', 'refusal_fee', 'cin_front_path', 'cin_back_path', 'rib_path'
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->findBy(['email' => $email]);
    }

    public function getPendingUsers(): array
    {
        return $this->findAll(['status' => 'pending'], 'created_at DESC');
    }

    public function getApprovedUsers(): array
    {
        return $this->findAll(['status' => 'approved'], 'created_at DESC');
    }

    public function getUsersByRole(string $role): array
    {
        return $this->findAll(['role' => $role, 'status' => 'approved'], 'created_at DESC');
    }

    public function approveUser(int $userId): bool
    {
        return $this->update($userId, ['status' => 'approved']);
    }

    public function rejectUser(int $userId): bool
    {
        return $this->update($userId, ['status' => 'rejected']);
    }

    public function suspendUser(int $userId): bool
    {
        return $this->update($userId, ['status' => 'suspended']);
    }

    public function updateUserFees(int $userId, float $deliveryFee, float $refusalFee): bool
    {
        return $this->update($userId, [
            'delivery_fee' => $deliveryFee,
            'refusal_fee' => $refusalFee
        ]);
    }

    public function updateBankInfo(int $userId, string $bankInfo): bool
    {
        return $this->update($userId, ['bank_info' => $bankInfo]);
    }

    public function toggleStockManagement(int $userId, bool $enabled): bool
    {
        return $this->update($userId, ['stock_management_enabled' => $enabled]);
    }

    public function getClientsByStockManagement(bool $enabled = true): array
    {
        return $this->findAll([
            'role' => 'client',
            'status' => 'approved',
            'stock_management_enabled' => $enabled
        ], 'store_name ASC');
    }

    public function getDeliveryPersons(): array
    {
        return $this->findAll(['role' => 'livreur', 'status' => 'approved'], 'first_name ASC');
    }

    public function getUserStats(): array
    {
        $stats = [];
        
        // Get user counts by role and status
        $sql = "SELECT role, status, COUNT(*) as count FROM users GROUP BY role, status";
        $results = $this->fetchAll($sql);
        
        foreach ($results as $row) {
            $stats[$row['role']][$row['status']] = (int) $row['count'];
        }
        
        return $stats;
    }

    public function searchUsers(string $query, string $role = '', string $status = ''): array
    {
        $sql = "SELECT * FROM users WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];
        
        if (!empty($role)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->fetchAll($sql, $params);
    }

    public function getFullName(array $user): string
    {
        return trim($user['first_name'] . ' ' . $user['last_name']);
    }

    public function canManageStock(int $userId): bool
    {
        $user = $this->find($userId);
        return $user && $user['role'] === 'client' && $user['stock_management_enabled'];
    }

    public function isApproved(int $userId): bool
    {
        $user = $this->find($userId);
        return $user && $user['status'] === 'approved';
    }

    public function hasRole(int $userId, string $role): bool
    {
        $user = $this->find($userId);
        return $user && $user['role'] === $role;
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $allowedFields = ['first_name', 'last_name', 'store_name', 'bank_info'];
        $filteredData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($filteredData)) {
            return false;
        }
        
        return $this->update($userId, $filteredData);
    }

    public function uploadDocument(int $userId, string $field, string $path): bool
    {
        $allowedFields = ['cin_front_path', 'cin_back_path', 'rib_path'];
        
        if (!in_array($field, $allowedFields)) {
            return false;
        }
        
        return $this->update($userId, [$field => $path]);
    }
}