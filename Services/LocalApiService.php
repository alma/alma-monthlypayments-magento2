<?php

namespace Alma\MonthlyPayments\Services;

use Alma\MonthlyPayments\Helpers\Logger;

class LocalApiService
{
    const API_URL = 'https://8f0684f4463f.ngrok.app/merchant-events/merchant_123456789';
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Logger $logger
    )
    {
        $this->logger = $logger;
    }

    public function sendPostRequest(string $eventType, array $eventDetails): array
    {
        // Initialisation de la session cURL
        $ch = curl_init();

        // Configuration des options cURL
        curl_setopt($ch, CURLOPT_URL, self::API_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Merchant-id: merchant_123456789',
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        // Encodage du payload en JSON
        $jsonPayload = json_encode(['event_type' => $eventType, 'event_details' => $eventDetails]);
        $this->logger->info('$jsonPayload', [$jsonPayload]);

        // Ajout du payload JSON dans le corps de la requête POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);

        // Exécution de la requête et récupération de la réponse
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Gestion des erreurs cURL
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Erreur cURL : " . $error_msg);
        }

        curl_close($ch);

        // Décodage de la réponse JSON
        $responseData = json_decode($response, true);
        $this->logger->info('$response', [$response]);

        if ($httpCode !== 200) {
            throw new \Exception("Erreur HTTP {$httpCode}: ");
        }

        return $responseData;
    }
}
