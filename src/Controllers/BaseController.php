<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Services\EmailService;
use App\Services\SMSService;
use App\Services\PDFService;
use App\Services\FileUploadService;

abstract class BaseController
{
    protected $auth;
    protected $emailService;
    protected $smsService;
    protected $pdfService;
    protected $fileUploadService;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->emailService = new EmailService();
        $this->smsService = new SMSService();
        $this->pdfService = new PDFService();
        $this->fileUploadService = new FileUploadService();
        
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Content-Type: application/json');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function errorResponse(string $message, int $statusCode = 400): void
    {
        $this->jsonResponse(['error' => $message], $statusCode);
    }

    protected function successResponse(array $data = [], string $message = 'Success'): void
    {
        $this->jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
    }

    protected function getRequestData(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        return $data ?? [];
    }

    protected function validateRequired(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->errorResponse("Field '{$field}' is required");
            }
        }
    }

    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(strip_tags($value));
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validatePhone(string $phone): bool
    {
        // Moroccan phone number format: 0XXXXXXXXX
        return preg_match('/^0[5-7][0-9]{8}$/', $phone);
    }

    protected function logActivity(int $userId, string $action, array $details = []): void
    {
        // Log user activity for audit trail
        error_log("User {$userId} performed action: {$action} - " . json_encode($details));
    }

    protected function rateLimitCheck(string $identifier, int $maxRequests = 60, int $timeWindow = 3600): bool
    {
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
        $requests = [];
        
        if (file_exists($cacheFile)) {
            $requests = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        $now = time();
        $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($requests) >= $maxRequests) {
            return false;
        }
        
        $requests[] = $now;
        file_put_contents($cacheFile, json_encode($requests));
        
        return true;
    }

    protected function requireAuth(): array
    {
        return $this->auth->requireAuth();
    }

    protected function requireRole(string $role): array
    {
        return $this->auth->requireRole($role);
    }

    protected function getCurrentUser(): ?array
    {
        return $this->auth->getCurrentUser();
    }

    protected function uploadFile(string $fieldName, string $directory = 'general'): array
    {
        if (!isset($_FILES[$fieldName])) {
            $this->errorResponse('No file uploaded');
        }
        
        try {
            return $this->fileUploadService->uploadFile($_FILES[$fieldName], $directory);
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    protected function generateTrackingCode(): string
    {
        return 'TRK' . strtoupper(bin2hex(random_bytes(6)));
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2) . ' DH';
    }

    protected function parseDateRange(string $startDate, string $endDate): array
    {
        $start = date('Y-m-d 00:00:00', strtotime($startDate));
        $end = date('Y-m-d 23:59:59', strtotime($endDate));
        
        return [$start, $end];
    }

    protected function getPaginationParams(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        
        return ['page' => $page, 'limit' => $limit, 'offset' => $offset];
    }

    protected function buildPaginatedResponse(array $data, int $total, array $pagination): array
    {
        $totalPages = ceil($total / $pagination['limit']);
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['limit'],
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $pagination['page'] < $totalPages,
                'has_prev' => $pagination['page'] > 1
            ]
        ];
    }
}