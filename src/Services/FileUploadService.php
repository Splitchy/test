<?php

namespace App\Services;

use Exception;

class FileUploadService
{
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    public function uploadFile(array $file, string $directory = 'general'): array
    {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new Exception('No file uploaded');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }

        // Validate file size
        if ($file['size'] > $this->config['uploads']['max_size']) {
            throw new Exception('File too large. Maximum size: ' . ($this->config['uploads']['max_size'] / 1024 / 1024) . 'MB');
        }

        // Validate file type
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $this->config['uploads']['allowed_types'])) {
            throw new Exception('File type not allowed. Allowed types: ' . implode(', ', $this->config['uploads']['allowed_types']));
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $fileExtension;
        $uploadPath = $this->config['uploads']['path'] . $directory . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fullPath = $uploadPath . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new Exception('Failed to move uploaded file');
        }

        return [
            'filename' => $filename,
            'path' => $fullPath,
            'relative_path' => $directory . '/' . $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type']
        ];
    }

    public function uploadCINDocument(array $file, int $userId, string $type): string
    {
        $allowedTypes = ['cin_front', 'cin_back'];
        if (!in_array($type, $allowedTypes)) {
            throw new Exception('Invalid CIN document type');
        }

        $result = $this->uploadFile($file, 'cin_documents');
        
        return $result['relative_path'];
    }

    public function uploadRIBDocument(array $file, int $userId): string
    {
        $result = $this->uploadFile($file, 'rib_documents');
        
        return $result['relative_path'];
    }

    public function uploadStockPhoto(array $file, int $stockItemId): string
    {
        // Validate that it's an image
        $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedImageTypes)) {
            throw new Exception('Only image files are allowed for stock photos');
        }

        $result = $this->uploadFile($file, 'stock_photos');
        
        return $result['relative_path'];
    }

    public function uploadPaymentProof(array $file, int $invoiceId): string
    {
        $result = $this->uploadFile($file, 'payment_proofs');
        
        return $result['relative_path'];
    }

    public function deleteFile(string $relativePath): bool
    {
        $fullPath = $this->config['uploads']['path'] . $relativePath;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }

    public function getFileUrl(string $relativePath): string
    {
        return '/uploads/' . $relativePath;
    }

    public function validateImageFile(array $file): bool
    {
        $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        return in_array($fileExtension, $allowedImageTypes);
    }

    public function resizeImage(string $filePath, int $maxWidth = 800, int $maxHeight = 600): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }

        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $imageType = $imageInfo[2];

        // Calculate new dimensions
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        if ($ratio >= 1) {
            return true; // No need to resize
        }

        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Create image resource based on type
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($filePath);
                break;
            default:
                return false;
        }

        // Create new image
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Save resized image
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($newImage, $filePath, 85);
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($newImage, $filePath);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($newImage, $filePath);
                break;
            default:
                $result = false;
        }

        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return $result;
    }

    public function cleanupOldFiles(int $daysOld = 30): int
    {
        $deletedCount = 0;
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        
        $directories = ['temp', 'payment_proofs'];
        
        foreach ($directories as $dir) {
            $dirPath = $this->config['uploads']['path'] . $dir . '/';
            
            if (is_dir($dirPath)) {
                $files = glob($dirPath . '*');
                
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < $cutoffTime) {
                        if (unlink($file)) {
                            $deletedCount++;
                        }
                    }
                }
            }
        }
        
        return $deletedCount;
    }
}