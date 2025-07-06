<?php

namespace App\Services;

use Twilio\Rest\Client;
use Exception;

class SMSService
{
    private $config;
    private $client;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        
        if (!empty($this->config['sms']['account_sid']) && !empty($this->config['sms']['auth_token'])) {
            $this->client = new Client(
                $this->config['sms']['account_sid'],
                $this->config['sms']['auth_token']
            );
        }
    }

    public function sendOrderStatusSMS(array $order, string $phone): bool
    {
        if (!$this->client) {
            return false;
        }

        try {
            $message = "Mise à jour commande {$order['reference']}: {$order['status']}";
            
            $this->client->messages->create(
                $phone,
                [
                    'from' => $this->config['sms']['from_number'],
                    'body' => $message
                ]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendDeliveryConfirmationSMS(array $order, string $phone): bool
    {
        if (!$this->client) {
            return false;
        }

        try {
            $message = "Votre commande {$order['reference']} a été livrée avec succès. Merci!";
            
            $this->client->messages->create(
                $phone,
                [
                    'from' => $this->config['sms']['from_number'],
                    'body' => $message
                ]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendPickupNotificationSMS(array $bonLivraison, string $phone): bool
    {
        if (!$this->client) {
            return false;
        }

        try {
            $message = "Bon de livraison {$bonLivraison['code']} créé. Vos colis sont prêts pour ramassage.";
            
            $this->client->messages->create(
                $phone,
                [
                    'from' => $this->config['sms']['from_number'],
                    'body' => $message
                ]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendDistributionNotificationSMS(array $bonEnvoi, string $phone): bool
    {
        if (!$this->client) {
            return false;
        }

        try {
            $message = "Nouveau bon d'envoi {$bonEnvoi['code']} assigné. Zone: {$bonEnvoi['zone_name']}";
            
            $this->client->messages->create(
                $phone,
                [
                    'from' => $this->config['sms']['from_number'],
                    'body' => $message
                ]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function isConfigured(): bool
    {
        return $this->client !== null;
    }
}