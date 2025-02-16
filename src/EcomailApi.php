<?php

namespace VyreseniDluhu;

class EcomailApi
{
    private string $apiKey;
    private string $baseUrl;
    private string $listId;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->baseUrl = $config['base_url'];
        $this->listId = $config['list_id'];
    }

    /**
     * Přidá nový kontakt do seznamu
     */
    public function addContact(array $data): array
    {
        $payload = [
            'subscriber_data' => [
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'city' => $data['location'] ?? null
            ],
            'trigger_autoresponders' => true,
            'update_existing' => true,
            'resubscribe' => true
        ];

        return $this->makeRequest("/lists/{$this->listId}/subscribe", 'POST', $payload);
    }

    /**
     * Vytvoří novou transakční událost
     */
    public function createTransaction(array $data): array
    {
        $payload = [
            'transaction_id' => uniqid('property_', true),
            'email' => $data['email'],
            'amount' => 0,
            'payment_status' => 'inquiry',
            'transaction_subject' => 'Poptávka nemovitosti',
            'currency' => 'CZK',
            'custom_fields' => [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'location' => $data['location'],
                'property_type' => $data['propertyType'],
                'message' => $data['message']
            ]
        ];

        return $this->makeRequest('/tracker/transaction', 'POST', $payload);
    }

    /**
     * Odešle událost do Ecomailu
     */
    public function trackEvent(string $email, string $eventName, array $data = []): array
    {
        $payload = [
            'email' => $email,
            'event_name' => $eventName,
            'data' => $data
        ];

        return $this->makeRequest('/tracker/events', 'POST', $payload);
    }

    /**
     * Provede HTTP požadavek na Ecomail API
     */
    /**
     * Získá seznam všech dostupných seznamů kontaktů
     */
    public function getLists(): array
    {
        return $this->makeRequest('/lists');
    }

    /**
     * Získá seznam všech vlastních polí v seznamu
     */
    public function getFields(): array
    {
        return $this->makeRequest("/lists/{$this->listId}/fields");
    }

    /**
     * Provede HTTP požadavek na Ecomail API
     */
    private function makeRequest(string $endpoint, string $method = 'GET', ?array $data = null): array
    {
        $ch = curl_init();

        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Key: ' . $this->apiKey
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new \Exception("Chyba při komunikaci s Ecomail API: $error");
        }

        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = isset($result['message']) ? $result['message'] : 'Neznámá chyba';
            throw new \Exception("Ecomail API vrátilo chybu ($httpCode): $errorMessage");
        }

        return $result;
    }
}
