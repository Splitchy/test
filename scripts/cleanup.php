<?php
/**
 * Cleanup Script - Run daily via cron
 * Removes temporary files, old logs, expired sessions
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
        }
    }
}

use App\Database\Database;
use App\Services\PDFService;
use App\Services\FileUploadService;

class CleanupScript
{
    private $db;
    private $pdfService;
    private $fileService;
    private $logFile;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->pdfService = new PDFService();
        $this->fileService = new FileUploadService();
        $this->logFile = __DIR__ . '/../logs/cleanup.log';
    }

    public function run(): void
    {
        $this->log("Starting cleanup process...");
        
        try {
            // Clean expired sessions
            $this->cleanExpiredSessions();
            
            // Clean temporary files
            $this->cleanTempFiles();
            
            // Clean old logs
            $this->cleanOldLogs();
            
            // Clean old uploaded files
            $this->cleanOldUploads();
            
            // Optimize database
            $this->optimizeDatabase();
            
            $this->log("Cleanup process completed successfully");
            
        } catch (Exception $e) {
            $this->log("Error during cleanup: " . $e->getMessage());
            exit(1);
        }
    }

    private function cleanExpiredSessions(): void
    {
        $deleted = $this->db->query(
            'DELETE FROM sessions WHERE expires_at < NOW()'
        )->rowCount();
        
        $this->log("Cleaned {$deleted} expired sessions");
    }

    private function cleanTempFiles(): void
    {
        $deleted = 0;
        
        // Clean PDF temp files
        $this->pdfService->cleanupTempFiles();
        
        // Clean general temp files
        $deleted += $this->fileService->cleanupOldFiles(1); // 1 day old
        
        $this->log("Cleaned {$deleted} temporary files");
    }

    private function cleanOldLogs(): void
    {
        $logDir = __DIR__ . '/../logs/';
        $cutoffDate = time() - (30 * 24 * 60 * 60); // 30 days
        $deleted = 0;
        
        if (is_dir($logDir)) {
            $files = glob($logDir . '*.log');
            
            foreach ($files as $file) {
                if (basename($file) !== 'cleanup.log' && filemtime($file) < $cutoffDate) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        $this->log("Cleaned {$deleted} old log files");
    }

    private function cleanOldUploads(): void
    {
        $config = require __DIR__ . '/../config/app.php';
        $uploadsPath = $config['uploads']['path'];
        $cutoffDate = time() - (90 * 24 * 60 * 60); // 90 days
        $deleted = 0;
        
        // Clean old PDFs
        $pdfDir = $uploadsPath . 'pdfs/';
        if (is_dir($pdfDir)) {
            $files = glob($pdfDir . '*.pdf');
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffDate) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        $this->log("Cleaned {$deleted} old uploaded files");
    }

    private function optimizeDatabase(): void
    {
        $tables = [
            'users', 'orders', 'stock_items', 'bon_livraison', 'bon_envoi',
            'tracking_logs', 'sessions', 'client_invoices', 'livreur_invoices'
        ];
        
        foreach ($tables as $table) {
            $this->db->query("OPTIMIZE TABLE {$table}");
        }
        
        $this->log("Optimized database tables");
    }

    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Run the cleanup
$cleanup = new CleanupScript();
$cleanup->run();