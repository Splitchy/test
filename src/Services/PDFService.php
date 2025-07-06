<?php

namespace App\Services;

use TCPDF;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class PDFService
{
    private $config;
    private $barcodeGenerator;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
        $this->barcodeGenerator = new BarcodeGeneratorPNG();
    }

    public function generateBonLivraisonPDF(array $bonLivraison, array $orders): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Logistics System');
        $pdf->SetTitle('Bon de Livraison - ' . $bonLivraison['code']);
        $pdf->SetSubject('Bon de Livraison');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, $this->config['pdf']['company_name'], 'Bon de Livraison');
        
        // Set margins
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 12);
        
        // Add content
        $html = $this->generateBonLivraisonHTML($bonLivraison, $orders);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Generate barcode for the BL code
        $barcode = $this->generateBarcode($bonLivraison['code']);
        $pdf->Image($barcode, 150, 40, 40, 10, 'PNG');
        
        // Save file
        $filename = 'bon_livraison_' . $bonLivraison['code'] . '.pdf';
        $filepath = $this->config['uploads']['path'] . 'pdfs/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }

    public function generateBonEnvoiPDF(array $bonEnvoi, array $orders): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Logistics System');
        $pdf->SetTitle('Bon d\'Envoi - ' . $bonEnvoi['code']);
        $pdf->SetSubject('Bon d\'Envoi');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, $this->config['pdf']['company_name'], 'Bon d\'Envoi');
        
        // Set margins
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 12);
        
        // Add content
        $html = $this->generateBonEnvoiHTML($bonEnvoi, $orders);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Generate barcode for the BE code
        $barcode = $this->generateBarcode($bonEnvoi['code']);
        $pdf->Image($barcode, 150, 40, 40, 10, 'PNG');
        
        // Save file
        $filename = 'bon_envoi_' . $bonEnvoi['code'] . '.pdf';
        $filepath = $this->config['uploads']['path'] . 'pdfs/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }

    public function generateClientInvoicePDF(array $invoice, array $order): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Logistics System');
        $pdf->SetTitle('Facture Client - ' . $invoice['invoice_number']);
        $pdf->SetSubject('Facture Client');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, $this->config['pdf']['company_name'], 'Facture Client');
        
        // Set margins
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 12);
        
        // Add content
        $html = $this->generateClientInvoiceHTML($invoice, $order);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Save file
        $filename = 'facture_client_' . $invoice['invoice_number'] . '.pdf';
        $filepath = $this->config['uploads']['path'] . 'pdfs/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }

    public function generateLivreurInvoicePDF(array $invoice, array $orders): string
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Logistics System');
        $pdf->SetTitle('Facture Livreur - ' . $invoice['invoice_number']);
        $pdf->SetSubject('Facture Livreur');
        
        // Set default header data
        $pdf->SetHeaderData('', 0, $this->config['pdf']['company_name'], 'Facture Livreur');
        
        // Set margins
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 12);
        
        // Add content
        $html = $this->generateLivreurInvoiceHTML($invoice, $orders);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Save file
        $filename = 'facture_livreur_' . $invoice['invoice_number'] . '.pdf';
        $filepath = $this->config['uploads']['path'] . 'pdfs/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }

    public function generateOrderTicket(array $order): string
    {
        $pdf = new TCPDF('P', 'mm', array(80, 120), true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Logistics System');
        $pdf->SetTitle('Étiquette - ' . $order['reference']);
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(5, 5, 5);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 8);
        
        // Add content
        $html = $this->generateOrderTicketHTML($order);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Generate barcode for the order reference
        $barcode = $this->generateBarcode($order['reference']);
        $pdf->Image($barcode, 10, 70, 60, 8, 'PNG');
        
        // Generate QR code for tracking
        $qr = $this->generateQRCode($order['tracking_code'] ?? $order['reference']);
        $pdf->Image($qr, 10, 85, 20, 20, 'PNG');
        
        // Save file
        $filename = 'ticket_' . $order['reference'] . '.pdf';
        $filepath = $this->config['uploads']['path'] . 'pdfs/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }

    private function generateBonLivraisonHTML(array $bonLivraison, array $orders): string
    {
        $html = '<h1>Bon de Livraison</h1>';
        $html .= '<h2>Code: ' . $bonLivraison['code'] . '</h2>';
        $html .= '<p><strong>Date:</strong> ' . date('d/m/Y H:i', strtotime($bonLivraison['created_at'])) . '</p>';
        $html .= '<p><strong>Client:</strong> ' . $bonLivraison['first_name'] . ' ' . $bonLivraison['last_name'] . '</p>';
        $html .= '<p><strong>Magasin:</strong> ' . ($bonLivraison['store_name'] ?? 'N/A') . '</p>';
        $html .= '<p><strong>Statut:</strong> ' . $bonLivraison['status'] . '</p>';
        
        $html .= '<h3>Liste des Colis</h3>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Référence</th><th>Produit</th><th>Quantité</th><th>Prix</th><th>Ville</th><th>Téléphone</th></tr>';
        
        $total = 0;
        foreach ($orders as $order) {
            $html .= '<tr>';
            $html .= '<td>' . $order['reference'] . '</td>';
            $html .= '<td>' . $order['product_name'] . '</td>';
            $html .= '<td>' . $order['quantity'] . '</td>';
            $html .= '<td>' . number_format($order['price'], 2) . ' DH</td>';
            $html .= '<td>' . $order['city_name'] . '</td>';
            $html .= '<td>' . $order['phone'] . '</td>';
            $html .= '</tr>';
            $total += $order['price'];
        }
        
        $html .= '<tr><td colspan="3"><strong>Total</strong></td><td><strong>' . number_format($total, 2) . ' DH</strong></td><td colspan="2"></td></tr>';
        $html .= '</table>';
        
        return $html;
    }

    private function generateBonEnvoiHTML(array $bonEnvoi, array $orders): string
    {
        $html = '<h1>Bon d\'Envoi</h1>';
        $html .= '<h2>Code: ' . $bonEnvoi['code'] . '</h2>';
        $html .= '<p><strong>Date:</strong> ' . date('d/m/Y H:i', strtotime($bonEnvoi['created_at'])) . '</p>';
        $html .= '<p><strong>Livreur:</strong> ' . $bonEnvoi['first_name'] . ' ' . $bonEnvoi['last_name'] . '</p>';
        $html .= '<p><strong>Zone:</strong> ' . $bonEnvoi['zone_name'] . '</p>';
        $html .= '<p><strong>Statut:</strong> ' . $bonEnvoi['status'] . '</p>';
        
        $html .= '<h3>Liste des Colis</h3>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Référence</th><th>Produit</th><th>Quantité</th><th>Prix</th><th>Ville</th><th>Adresse</th></tr>';
        
        $total = 0;
        foreach ($orders as $order) {
            $html .= '<tr>';
            $html .= '<td>' . $order['reference'] . '</td>';
            $html .= '<td>' . $order['product_name'] . '</td>';
            $html .= '<td>' . $order['quantity'] . '</td>';
            $html .= '<td>' . number_format($order['price'], 2) . ' DH</td>';
            $html .= '<td>' . $order['city_name'] . '</td>';
            $html .= '<td>' . substr($order['address'], 0, 50) . '...</td>';
            $html .= '</tr>';
            $total += $order['price'];
        }
        
        $html .= '<tr><td colspan="3"><strong>Total</strong></td><td><strong>' . number_format($total, 2) . ' DH</strong></td><td colspan="2"></td></tr>';
        $html .= '</table>';
        
        return $html;
    }

    private function generateClientInvoiceHTML(array $invoice, array $order): string
    {
        $html = '<h1>Facture Client</h1>';
        $html .= '<h2>N° ' . $invoice['invoice_number'] . '</h2>';
        $html .= '<p><strong>Date:</strong> ' . date('d/m/Y H:i', strtotime($invoice['generated_at'])) . '</p>';
        
        $html .= '<h3>Détails de la commande</h3>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Description</th><th>Quantité</th><th>Prix Unitaire</th><th>Total</th></tr>';
        
        $html .= '<tr>';
        $html .= '<td>Produit: ' . $order['product_name'] . '</td>';
        $html .= '<td>' . $order['quantity'] . '</td>';
        $html .= '<td>' . number_format($invoice['product_amount'], 2) . ' DH</td>';
        $html .= '<td>' . number_format($invoice['product_amount'], 2) . ' DH</td>';
        $html .= '</tr>';
        
        $html .= '<tr>';
        $html .= '<td>Frais de livraison</td>';
        $html .= '<td>1</td>';
        $html .= '<td>' . number_format($invoice['delivery_fee'], 2) . ' DH</td>';
        $html .= '<td>' . number_format($invoice['delivery_fee'], 2) . ' DH</td>';
        $html .= '</tr>';
        
        // Add extra services if any
        if (!empty($invoice['extra_services'])) {
            $extraServices = json_decode($invoice['extra_services'], true);
            foreach ($extraServices as $service) {
                $html .= '<tr>';
                $html .= '<td>' . $service['service'] . '</td>';
                $html .= '<td>1</td>';
                $html .= '<td>' . number_format($service['amount'], 2) . ' DH</td>';
                $html .= '<td>' . number_format($service['amount'], 2) . ' DH</td>';
                $html .= '</tr>';
            }
        }
        
        $html .= '<tr><td colspan="3"><strong>Total</strong></td><td><strong>' . number_format($invoice['total_amount'], 2) . ' DH</strong></td></tr>';
        $html .= '</table>';
        
        return $html;
    }

    private function generateLivreurInvoiceHTML(array $invoice, array $orders): string
    {
        $html = '<h1>Facture Livreur</h1>';
        $html .= '<h2>N° ' . $invoice['invoice_number'] . '</h2>';
        $html .= '<p><strong>Date:</strong> ' . date('d/m/Y H:i', strtotime($invoice['created_at'])) . '</p>';
        
        $html .= '<h3>Détails des livraisons</h3>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Référence</th><th>Produit</th><th>Statut</th><th>Commission</th></tr>';
        
        foreach ($orders as $order) {
            $html .= '<tr>';
            $html .= '<td>' . $order['reference'] . '</td>';
            $html .= '<td>' . $order['product_name'] . '</td>';
            $html .= '<td>' . $order['status'] . '</td>';
            $html .= '<td>' . number_format($order['commission'], 2) . ' DH</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr><td colspan="3"><strong>Total</strong></td><td><strong>' . number_format($invoice['total_amount'], 2) . ' DH</strong></td></tr>';
        $html .= '</table>';
        
        return $html;
    }

    private function generateOrderTicketHTML(array $order): string
    {
        $html = '<h3>' . $this->config['pdf']['company_name'] . '</h3>';
        $html .= '<p><strong>Réf:</strong> ' . $order['reference'] . '</p>';
        $html .= '<p><strong>Produit:</strong> ' . substr($order['product_name'], 0, 20) . '</p>';
        $html .= '<p><strong>Qté:</strong> ' . $order['quantity'] . '</p>';
        $html .= '<p><strong>Prix:</strong> ' . number_format($order['price'], 2) . ' DH</p>';
        $html .= '<p><strong>Ville:</strong> ' . $order['city_name'] . '</p>';
        $html .= '<p><strong>Tél:</strong> ' . $order['phone'] . '</p>';
        $html .= '<p><strong>Adresse:</strong><br>' . substr($order['address'], 0, 50) . '</p>';
        
        return $html;
    }

    private function generateBarcode(string $code): string
    {
        $barcodeData = $this->barcodeGenerator->getBarcode($code, $this->barcodeGenerator::TYPE_CODE_128);
        $filename = 'barcode_' . $code . '.png';
        $filepath = $this->config['uploads']['path'] . 'temp/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $barcodeData);
        
        return $filepath;
    }

    private function generateQRCode(string $data): string
    {
        $qrCode = QrCode::create($data);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        $filename = 'qr_' . md5($data) . '.png';
        $filepath = $this->config['uploads']['path'] . 'temp/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $result->getString());
        
        return $filepath;
    }

    public function cleanupTempFiles(): void
    {
        $tempDir = $this->config['uploads']['path'] . 'temp/';
        if (is_dir($tempDir)) {
            $files = glob($tempDir . '*');
            foreach ($files as $file) {
                if (is_file($file) && time() - filemtime($file) > 3600) { // 1 hour
                    unlink($file);
                }
            }
        }
    }
}