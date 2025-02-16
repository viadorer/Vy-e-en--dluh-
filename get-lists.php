<?php
require 'vendor/autoload.php';
use VyreseniDluhu\EcomailApi;

$config = require 'config.php';
$ecomail = new EcomailApi($config['ecomail']);

try {
    $lists = $ecomail->getLists();
    echo "Dostupné seznamy kontaktů:\n";
    foreach ($lists as $list) {
        echo "ID: {$list['id']}, Název: {$list['name']}\n";
    }
} catch (Exception $e) {
    echo "Chyba: " . $e->getMessage() . "\n";
}
