<?php
require 'vendor/autoload.php';
use VyreseniDluhu\EcomailApi;

$config = require 'config.php';
$ecomail = new EcomailApi($config['ecomail']);

try {
    $fields = $ecomail->getFields();
    echo "Dostupná pole v seznamu:\n";
    foreach ($fields as $field) {
        echo "Název: {$field['name']}, Kód: {$field['code']}\n";
    }
} catch (Exception $e) {
    echo "Chyba: " . $e->getMessage() . "\n";
}
