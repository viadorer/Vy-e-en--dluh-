<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use VyreseniDluhu\EcomailApi;

header('Content-Type: application/json');

try {
    // Načtení konfigurace
    $config = require 'config.php';
    if (!isset($config['smtp']) || !isset($config['ecomail'])) {
        throw new Exception('Chybí potřebná konfigurace.');
    }

    // Získání dat z formuláře
    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Neplatný formát dat.');
    }
    
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $location = trim($data['location'] ?? '');
    $propertyType = trim($data['propertyType'] ?? '');
    $message = trim($data['message'] ?? '');
    $email = trim($data['email'] ?? ''); // Přidáno pro Ecomail

    // Validace dat
    $errors = [];
    if (empty($name)) $errors[] = 'Jméno je povinné';
    if (empty($phone)) $errors[] = 'Telefon je povinný';
    if (empty($location)) $errors[] = 'Lokalita je povinná';
    if (empty($propertyType)) $errors[] = 'Typ nemovitosti je povinný';
    if (empty($email)) $errors[] = 'Email je povinný';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Zadejte platný email';
    
    if (!empty($errors)) {
        throw new Exception(implode(', ', $errors));
    }

    // Inicializace Ecomail API
    $ecomail = new EcomailApi($config['ecomail']);

    try {
        // Přidání kontaktu do Ecomailu
        $ecomailResponse = $ecomail->addContact([
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'location' => $location,
            'propertyType' => $propertyType,
            'message' => $message
        ]);

        // Vytvoření transakce v Ecomailu
        $ecomail->createTransaction([
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'location' => $location,
            'propertyType' => $propertyType,
            'message' => $message
        ]);

        // Sledování události
        $ecomail->trackEvent($email, 'property_inquiry', [
            'property_type' => $propertyType,
            'location' => $location
        ]);

    } catch (Exception $e) {
        error_log('Chyba při komunikaci s Ecomail API: ' . $e->getMessage());
        // Pokračujeme dál i při chybě Ecomailu
    }

    // Vytvoření instance PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Nastavení SMTP
        $mail->isSMTP();
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->Host = $config['smtp']['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp']['username'];
        $mail->Password = $config['smtp']['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp']['port'];
        $mail->CharSet = 'UTF-8';

        // Nastavení odesílatele a příjemce
        $mail->setFrom($config['smtp']['username'], 'Vyreseni Dluhu');
        $mail->addAddress($config['smtp']['username'], 'Vyreseni Dluhu');
        $mail->addReplyTo($email, $name);

        // Nastavení obsahu emailu
        $mail->isHTML(true);
        $mail->Subject = 'Nová poptávka - ' . $name;
        
        // HTML verze emailu
        $mail->Body = "
            <h2>Nový kontakt z webu VyreseniDluhu.cz</h2>
            <p>Kontakt byl úspěšně přidán do vašeho seznamu v Ecomail.</p>
            <table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;'>
                <tr>
                    <td style='padding: 12px; border: 1px solid #ddd; background-color: #f8f9fa; width: 150px;'><strong>Jméno:</strong></td>
                    <td style='padding: 12px; border: 1px solid #ddd;'>" . htmlspecialchars($name) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px; border: 1px solid #ddd; background-color: #f8f9fa;'><strong>Email:</strong></td>
                    <td style='padding: 12px; border: 1px solid #ddd;'>" . htmlspecialchars($email) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px; border: 1px solid #ddd; background-color: #f8f9fa;'><strong>Telefon:</strong></td>
                    <td style='padding: 12px; border: 1px solid #ddd;'>" . htmlspecialchars($phone) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px; border: 1px solid #ddd; background-color: #f8f9fa;'><strong>Město:</strong></td>
                    <td style='padding: 12px; border: 1px solid #ddd;'>" . htmlspecialchars($location) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px; border: 1px solid #ddd; background-color: #f8f9fa;'><strong>Typ nemovitosti:</strong></td>
                    <td style='padding: 12px; border: 1px solid #ddd;'>" . htmlspecialchars($propertyType) . "</td>
                </tr>
                <tr>
                    <td style='padding: 12px; border: 1px solid #ddd; background-color: #f8f9fa;'><strong>Zpráva:</strong></td>
                    <td style='padding: 12px; border: 1px solid #ddd;'>" . nl2br(htmlspecialchars($message)) . "</td>
                </tr>
            </table>
            <p style='margin-top: 20px; color: #666; font-size: 12px;'>
                Tento email byl automaticky vygenerován z kontaktního formuláře na webu VyreseniDluhu.cz
            </p>
        ";

        // Textová verze emailu
        $mail->AltBody = "
            Nová poptávka z webu VyreseniDluhu.cz
            
            Jméno: {$name}
            Email: {$email}
            Telefon: {$phone}
            Lokalita: {$location}
            Typ nemovitosti: {$propertyType}
            Zpráva: {$message}
            
            ---
            Tento email byl automaticky vygenerován z kontaktního formuláře na webu VyreseniDluhu.cz
        ";

        // Odeslání emailu
        $mail->send();

        // Úspěšná odpověď
        echo json_encode([
            'success' => true,
            'message' => 'Děkujeme za váš zájem! Budeme vás kontaktovat co nejdříve.'
        ]);

    } catch (Exception $e) {
        error_log('Chyba při odesílání emailu: ' . $e->getMessage());
        throw new Exception('Nepodařilo se odeslat email. Prosím zkuste to znovu později.');
    }

} catch (Exception $e) {
    // Chybová odpověď
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
