<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $config;
    private $mailer;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->setupMailer();
    }

    private function setupMailer(): void
    {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['email']['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['email']['smtp_username'];
            $this->mailer->Password = $this->config['email']['smtp_password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->config['email']['smtp_port'];
            
            // Default sender
            $this->mailer->setFrom($this->config['email']['from_email'], $this->config['email']['from_name']);
            
            // Encoding
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            throw new Exception("Mailer configuration failed: " . $e->getMessage());
        }
    }

    public function sendUserRegistrationNotification(array $user): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
            
            $this->mailer->Subject = 'Inscription réussie - En attente d\'approbation';
            $this->mailer->isHTML(true);
            
            $body = $this->getUserRegistrationTemplate($user);
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendUserApprovalNotification(array $user): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
            
            $this->mailer->Subject = 'Compte approuvé - Bienvenue!';
            $this->mailer->isHTML(true);
            
            $body = $this->getUserApprovalTemplate($user);
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendUserRejectionNotification(array $user, string $reason = ''): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
            
            $this->mailer->Subject = 'Demande d\'inscription refusée';
            $this->mailer->isHTML(true);
            
            $body = $this->getUserRejectionTemplate($user, $reason);
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendOrderStatusNotification(array $order, array $client): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($client['email'], $client['first_name'] . ' ' . $client['last_name']);
            
            $this->mailer->Subject = 'Mise à jour de votre commande ' . $order['reference'];
            $this->mailer->isHTML(true);
            
            $body = $this->getOrderStatusTemplate($order, $client);
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendBonLivraisonNotification(array $bonLivraison, array $client, string $pdfPath = ''): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($client['email'], $client['first_name'] . ' ' . $client['last_name']);
            
            $this->mailer->Subject = 'Bon de livraison créé - ' . $bonLivraison['code'];
            $this->mailer->isHTML(true);
            
            $body = $this->getBonLivraisonTemplate($bonLivraison, $client);
            $this->mailer->Body = $body;
            
            if (!empty($pdfPath) && file_exists($pdfPath)) {
                $this->mailer->addAttachment($pdfPath, 'bon_livraison.pdf');
            }
            
            $result = $this->mailer->send();
            
            // Clear attachments for next email
            $this->mailer->clearAttachments();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendBonEnvoiNotification(array $bonEnvoi, array $livreur, string $pdfPath = ''): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($livreur['email'], $livreur['first_name'] . ' ' . $livreur['last_name']);
            
            $this->mailer->Subject = 'Nouveau bon d'envoi - ' . $bonEnvoi['code'];
            $this->mailer->isHTML(true);
            
            $body = $this->getBonEnvoiTemplate($bonEnvoi, $livreur);
            $this->mailer->Body = $body;
            
            if (!empty($pdfPath) && file_exists($pdfPath)) {
                $this->mailer->addAttachment($pdfPath, 'bon_envoi.pdf');
            }
            
            $result = $this->mailer->send();
            
            // Clear attachments for next email
            $this->mailer->clearAttachments();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendInvoiceNotification(array $invoice, array $recipient, string $pdfPath = ''): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($recipient['email'], $recipient['first_name'] . ' ' . $recipient['last_name']);
            
            $this->mailer->Subject = 'Facture - ' . $invoice['invoice_number'];
            $this->mailer->isHTML(true);
            
            $body = $this->getInvoiceTemplate($invoice, $recipient);
            $this->mailer->Body = $body;
            
            if (!empty($pdfPath) && file_exists($pdfPath)) {
                $this->mailer->addAttachment($pdfPath, 'facture.pdf');
            }
            
            $result = $this->mailer->send();
            
            // Clear attachments for next email
            $this->mailer->clearAttachments();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetEmail(array $user, string $tempPassword): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
            
            $this->mailer->Subject = 'Réinitialisation de mot de passe';
            $this->mailer->isHTML(true);
            
            $body = $this->getPasswordResetTemplate($user, $tempPassword);
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendStockItemNotification(array $stockItem, array $client, string $status): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($client['email'], $client['first_name'] . ' ' . $client['last_name']);
            
            $subject = $status === 'APPROUVE' ? 'Article de stock approuvé' : 'Article de stock refusé';
            $this->mailer->Subject = $subject . ' - ' . $stockItem['reference'];
            $this->mailer->isHTML(true);
            
            $body = $this->getStockItemTemplate($stockItem, $client, $status);
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendAdminNotification(string $subject, string $message, array $adminEmails): bool
    {
        try {
            $this->mailer->clearAddresses();
            
            foreach ($adminEmails as $email) {
                $this->mailer->addAddress($email);
            }
            
            $this->mailer->Subject = '[ADMIN] ' . $subject;
            $this->mailer->isHTML(true);
            
            $body = $this->getAdminNotificationTemplate($subject, $message);
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    private function getUserRegistrationTemplate(array $user): string
    {
        return "
        <html>
        <body>
            <h2>Inscription réussie</h2>
            <p>Bonjour {$user['first_name']} {$user['last_name']},</p>
            <p>Votre demande d'inscription en tant que <strong>{$user['role']}</strong> a été reçue avec succès.</p>
            <p>Votre compte est actuellement en attente d'approbation par un administrateur.</p>
            <p>Vous recevrez un email de confirmation une fois votre compte approuvé.</p>
            <p>Cordialement,<br>L'équipe Logistics</p>
        </body>
        </html>";
    }

    private function getUserApprovalTemplate(array $user): string
    {
        return "
        <html>
        <body>
            <h2>Compte approuvé</h2>
            <p>Bonjour {$user['first_name']} {$user['last_name']},</p>
            <p>Félicitations! Votre compte a été approuvé.</p>
            <p>Vous pouvez maintenant vous connecter à votre compte et commencer à utiliser nos services.</p>
            <p>Cordialement,<br>L'équipe Logistics</p>
        </body>
        </html>";
    }

    private function getUserRejectionTemplate(array $user, string $reason): string
    {
        $reasonText = !empty($reason) ? "<p>Raison: {$reason}</p>" : '';
        
        return "
        <html>
        <body>
            <h2>Demande d'inscription refusée</h2>
            <p>Bonjour {$user['first_name']} {$user['last_name']},</p>
            <p>Nous regrettons de vous informer que votre demande d'inscription a été refusée.</p>
            {$reasonText}
            <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
            <p>Cordialement,<br>L'équipe Logistics</p>
        </body>
        </html>";
    }

    private function getOrderStatusTemplate(array $order, array $client): string
    {
        return "
        <html>
        <body>
            <h2>Mise à jour de votre commande</h2>
            <p>Bonjour {$client['first_name']} {$client['last_name']},</p>
            <p>Votre commande <strong>{$order['reference']}</strong> a été mise à jour.</p>
            <p><strong>Nouveau statut:</strong> {$order['status']}</p>
            <p><strong>Produit:</strong> {$order['product_name']}</p>
            <p><strong>Quantité:</strong> {$order['quantity']}</p>
            <p>Cordialement,<br>L'équipe Logistics</p>
        </body>
        </html>";
    }

    private function getBonLivraisonTemplate(array $bonLivraison, array $client): string
    {
        return "
        <html>
        <body>
            <h2>Bon de livraison créé</h2>
            <p>Bonjour {$client['first_name']} {$client['last_name']},</p>
            <p>Un nouveau bon de livraison a été créé pour vos commandes.</p>
            <p><strong>Code:</strong> {$bonLivraison['code']}</p>
            <p><strong>Date:</strong> " . date('d/m/Y H:i', strtotime($bonLivraison['created_at'])) . "</p>
            <p>Vous trouverez le bon de livraison en pièce jointe.</p>
            <p>Cordialement,<br>L'équipe Logistics</p>
        </body>
        </html>";
    }

    private function getBonEnvoiTemplate(array $bonEnvoi, array $livreur): string
    {
        return "
        <html>
        <body>
            <h2>Nouveau bon d'envoi</h2>
            <p>Bonjour {$livreur['first_name']} {$livreur['last_name']},</p>
            <p>Un nouveau bon d'envoi vous a été assigné.</p>
            <p><strong>Code:</strong> {$bonEnvoi['code']}</p>
            <p><strong>Zone:</strong> {$bonEnvoi['zone_name']}</p>
            <p><strong>Date:</strong> " . date('d/m/Y H:i', strtotime($bonEnvoi['created_at'])) . "</p>
            <p>Vous trouverez le bon d'envoi en pièce jointe.</p>
            <p>Cordialement,<br>L'équipe Logistics</p>
        </body>
        </html>";
    }

    private function getInvoiceTemplate(array $invoice, array $recipient): string
    {
        return "
        <html>
        <body>
            <h2>Nouvelle facture</h2>
            <p>Bonjour {$recipient['first_name']} {$recipient['last_name']},</p>
            <p>Une nouvelle facture a été générée.</p>
            <p><strong>Numéro:</strong> {$invoice['invoice_number']}</p>
            <p><strong>Montant:</strong> " . number_format($invoice['total_amount'], 2) . " DH</p>
            <p>Vous trouverez la facture en pièce jointe.</p>
            <p>Cordialement,<br>L'équipe Logistics</p>
        </body>
        </html>";
    }

    private function getPasswordResetTemplate(array $user, string $tempPassword): string
    {
        return "
        <html>
        <body>
            <h2>Réinitialisation de mot de passe</h2>
            <p>Bonjour {$user['first_name']} {$user['last_name']},</p>
            <p>Votre mot de passe a été réinitialisé.</p>
            <p><strong>Nouveau mot de passe temporaire:</strong> {$tempPassword}</p>
            <p><strong>Important:</strong> Changez ce mot de passe dès votre prochaine connexion.</p>
            <p>Cordialement,<br>L'équipe Logistics</p>
        </body>
        </html>";
    }

    private function getStockItemTemplate(array $stockItem, array $client, string $status): string
    {
        $statusText = $status === 'APPROUVE' ? 'approuvé' : 'refusé';
        
        return "
        <html>
        <body>
            <h2>Article de stock {$statusText}</h2>
            <p>Bonjour {$client['first_name']} {$client['last_name']},</p>
            <p>Votre article de stock <strong>{$stockItem['reference']}</strong> a été {$statusText}.</p>
            <p><strong>Description:</strong> {$stockItem['description']}</p>
            <p><strong>Quantité:</strong> {$stockItem['quantity']}</p>
            <p>Cordialement,<br>L'équipe Logistics</p>
        </body>
        </html>";
    }

    private function getAdminNotificationTemplate(string $subject, string $message): string
    {
        return "
        <html>
        <body>
            <h2>{$subject}</h2>
            <p>{$message}</p>
            <p>Envoyé automatiquement par le système Logistics</p>
        </body>
        </html>";
    }
}