<?php
require 'vendor/autoload.php';
use VyreseniDluhu\EcomailApi;

$config = require 'config.php';
$ecomail = new EcomailApi($config['ecomail']);

try {
    // Vytvoření nového seznamu
    $newList = $ecomail->makeRequest('/lists', 'POST', [
        'name' => 'Vyreseni dluhu',
        'from_name' => 'Vyreseni dluhu',
        'from_email' => 'info@vyresenidluhu.cz',
        'reply_to' => 'info@vyresenidluhu.cz'
    ]);
    
    echo "Nový seznam vytvořen:\n";
    echo "ID: {$newList['id']}, Název: {$newList['name']}\n";
} catch (Exception $e) {
    echo "Chyba: " . $e->getMessage() . "\n";
}
