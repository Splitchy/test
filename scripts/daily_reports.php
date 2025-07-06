<?php
/**
 * Daily Reports Script - Run daily via cron
 * Generates and sends daily summary reports
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
use App\Models\Order;
use App\Models\User;
use App\Models\BonLivraison;
use App\Models\BonEnvoi;
use App\Services\EmailService;

class DailyReportsScript
{
    private $db;
    private $orderModel;
    private $userModel;
    private $bonLivraisonModel;
    private $bonEnvoiModel;
    private $emailService;
    private $logFile;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->orderModel = new Order();
        $this->userModel = new User();
        $this->bonLivraisonModel = new BonLivraison();
        $this->bonEnvoiModel = new BonEnvoi();
        $this->emailService = new EmailService();
        $this->logFile = __DIR__ . '/../logs/reports.log';
    }

    public function run(): void
    {
        $this->log("Starting daily reports generation...");
        
        try {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            
            // Generate daily summary
            $summary = $this->generateDailySummary($yesterday);
            
            // Send email to admins
            $this->sendDailySummaryEmail($summary, $yesterday);
            
            $this->log("Daily reports sent successfully");
            
        } catch (Exception $e) {
            $this->log("Error generating reports: " . $e->getMessage());
            exit(1);
        }
    }

    private function generateDailySummary(string $date): array
    {
        $startDate = $date . ' 00:00:00';
        $endDate = $date . ' 23:59:59';
        
        // Orders statistics
        $ordersData = $this->getOrdersStats($startDate, $endDate);
        
        // Deliveries statistics
        $deliveriesData = $this->getDeliveriesStats($startDate, $endDate);
        
        // User registrations
        $usersData = $this->getUsersStats($startDate, $endDate);
        
        // Revenue
        $revenueData = $this->getRevenueStats($startDate, $endDate);
        
        return [
            'date' => $date,
            'orders' => $ordersData,
            'deliveries' => $deliveriesData,
            'users' => $usersData,
            'revenue' => $revenueData
        ];
    }

    private function getOrdersStats(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN type = 'ramassage' THEN 1 ELSE 0 END) as ramassage_orders,
                    SUM(CASE WHEN type = 'stock' THEN 1 ELSE 0 END) as stock_orders,
                    SUM(CASE WHEN status = 'EN_ATTENTE_RAMASSAGE' THEN 1 ELSE 0 END) as pending_pickup,
                    SUM(CASE WHEN status = 'RAMASSE' THEN 1 ELSE 0 END) as picked_up,
                    SUM(CASE WHEN status = 'MISE_EN_DISTRIBUTION' THEN 1 ELSE 0 END) as in_delivery
                FROM orders 
                WHERE created_at BETWEEN ? AND ?";
        
        return $this->db->fetch($sql, [$startDate, $endDate]);
    }

    private function getDeliveriesStats(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_deliveries,
                    SUM(CASE WHEN status = 'LIVRE' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'REFUSE' THEN 1 ELSE 0 END) as refused,
                    SUM(CASE WHEN status = 'ANNULE' THEN 1 ELSE 0 END) as cancelled
                FROM orders 
                WHERE delivery_date BETWEEN ? AND ?";
        
        return $this->db->fetch($sql, [$startDate, $endDate]);
    }

    private function getUsersStats(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_registrations,
                    SUM(CASE WHEN role = 'client' THEN 1 ELSE 0 END) as new_clients,
                    SUM(CASE WHEN role = 'livreur' THEN 1 ELSE 0 END) as new_livreurs,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_approval
                FROM users 
                WHERE created_at BETWEEN ? AND ?";
        
        return $this->db->fetch($sql, [$startDate, $endDate]);
    }

    private function getRevenueStats(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    COALESCE(SUM(price), 0) as total_revenue,
                    COUNT(*) as revenue_orders,
                    COALESCE(AVG(price), 0) as average_order_value
                FROM orders 
                WHERE status = 'LIVRE' 
                AND delivery_date BETWEEN ? AND ?";
        
        return $this->db->fetch($sql, [$startDate, $endDate]);
    }

    private function sendDailySummaryEmail(array $summary, string $date): void
    {
        // Get admin emails
        $admins = $this->userModel->getUsersByRole('admin');
        $adminEmails = array_column($admins, 'email');
        
        if (empty($adminEmails)) {
            $this->log("No admin emails found for daily report");
            return;
        }
        
        $subject = "Rapport quotidien - " . date('d/m/Y', strtotime($date));
        $message = $this->formatSummaryMessage($summary);
        
        $sent = $this->emailService->sendAdminNotification($subject, $message, $adminEmails);
        
        if ($sent) {
            $this->log("Daily summary email sent to " . count($adminEmails) . " admins");
        } else {
            $this->log("Failed to send daily summary email");
        }
    }

    private function formatSummaryMessage(array $summary): string
    {
        $date = date('d/m/Y', strtotime($summary['date']));
        
        $message = "<h3>Rapport quotidien du {$date}</h3>";
        
        // Orders section
        $message .= "<h4>📦 Commandes</h4>";
        $message .= "<ul>";
        $message .= "<li>Total: {$summary['orders']['total_orders']}</li>";
        $message .= "<li>Ramassage: {$summary['orders']['ramassage_orders']}</li>";
        $message .= "<li>Stock: {$summary['orders']['stock_orders']}</li>";
        $message .= "<li>En attente ramassage: {$summary['orders']['pending_pickup']}</li>";
        $message .= "<li>Ramassées: {$summary['orders']['picked_up']}</li>";
        $message .= "<li>En livraison: {$summary['orders']['in_delivery']}</li>";
        $message .= "</ul>";
        
        // Deliveries section
        $message .= "<h4>🚚 Livraisons</h4>";
        $message .= "<ul>";
        $message .= "<li>Total: {$summary['deliveries']['total_deliveries']}</li>";
        $message .= "<li>Livrées: {$summary['deliveries']['delivered']}</li>";
        $message .= "<li>Refusées: {$summary['deliveries']['refused']}</li>";
        $message .= "<li>Annulées: {$summary['deliveries']['cancelled']}</li>";
        $message .= "</ul>";
        
        // Users section
        $message .= "<h4>👥 Utilisateurs</h4>";
        $message .= "<ul>";
        $message .= "<li>Nouvelles inscriptions: {$summary['users']['total_registrations']}</li>";
        $message .= "<li>Nouveaux clients: {$summary['users']['new_clients']}</li>";
        $message .= "<li>Nouveaux livreurs: {$summary['users']['new_livreurs']}</li>";
        $message .= "<li>En attente d'approbation: {$summary['users']['pending_approval']}</li>";
        $message .= "</ul>";
        
        // Revenue section
        $message .= "<h4>💰 Revenus</h4>";
        $message .= "<ul>";
        $message .= "<li>Chiffre d'affaires: " . number_format($summary['revenue']['total_revenue'], 2) . " DH</li>";
        $message .= "<li>Commandes payées: {$summary['revenue']['revenue_orders']}</li>";
        $message .= "<li>Panier moyen: " . number_format($summary['revenue']['average_order_value'], 2) . " DH</li>";
        $message .= "</ul>";
        
        return $message;
    }

    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Run the daily reports
$reports = new DailyReportsScript();
$reports->run();