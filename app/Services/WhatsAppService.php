<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $baseUrl;
    protected $clientId;
    protected $apiKey;
    protected $basicAuthUser;
    protected $basicAuthPassword;
    protected $useApiCredentials;

    public function __construct()
    {
        $whatsappConfig = config('whatsapp');

        $this->baseUrl = $whatsappConfig['base_url'] ?? 'http://localhost:3000';
        $this->clientId = $whatsappConfig['client_id'] ?? null;
        $this->apiKey = $whatsappConfig['api_key'] ?? null;
        $this->basicAuthUser = data_get($whatsappConfig, 'basic_auth.user');
        $this->basicAuthPassword = data_get($whatsappConfig, 'basic_auth.password');
        $this->useApiCredentials = $whatsappConfig['use_api_credentials'] ?? false;
    }

    /**
     * Send text message via WhatsApp
     */
    public function sendTextMessage($phoneNumber, $message)
    {
        try {
            $response = $this->httpClient()
                ->timeout(30)
                ->post($this->baseUrl . '/send/message', $this->buildTextPayload($phoneNumber, $message));

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'phone' => $phoneNumber,
                    'response' => $response->json()
                ]);
                return $response->json();
            } else {
                Log::error('WhatsApp message failed', [
                    'phone' => $phoneNumber,
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp service error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send image message via WhatsApp
     */
    public function sendImageMessage($phoneNumber, $imageUrl, $caption = '')
    {
        try {
            $response = $this->httpClient()
                ->timeout(30)
                ->post($this->baseUrl . '/send/image', [
                'client_id' => $this->clientId,
                'api_key' => $this->apiKey,
                'data' => [
                    'jid' => $this->formatPhoneNumber($phoneNumber),
                    'image' => $imageUrl,
                    'caption' => $caption
                ]
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp image sent successfully', [
                    'phone' => $phoneNumber,
                    'response' => $response->json()
                ]);
                return $response->json();
            } else {
                Log::error('WhatsApp image failed', [
                    'phone' => $phoneNumber,
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp image service error', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check WhatsApp connection status
     */
    public function checkConnection()
    {
        try {
            // First, check if GOWA is running with basic auth
            $response = $this->httpClient()
                ->timeout(10)
                ->get($this->baseUrl);
            
            if (!$response->successful()) {
                Log::error('GOWA server not accessible', [
                    'url' => $this->baseUrl,
                    'status' => $response->status()
                ]);
                return false;
            }

            // If we have client_id and api_key, just check if server is running
            if ($this->clientId && $this->apiKey) {
                Log::info('GOWA server is running with credentials configured');
                return true;
            }

            // If no credentials, just check if server is running
            Log::info('GOWA server is running but no credentials configured');
            return true;
            
        } catch (\Exception $e) {
            Log::error('WhatsApp connection check failed', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl
            ]);
            return false;
        }
    }

    /**
     * Format phone number for WhatsApp
     */
    protected function formatPhoneNumber($phoneNumber)
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if not present (assuming Indonesia +62)
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . ltrim($phone, '0');
        }
        
        // Add @s.whatsapp.net suffix
        return $phone . '@s.whatsapp.net';
    }

    /**
     * Format phone number for GOWA API
     */
    protected function formatPhoneNumberForAPI($phoneNumber)
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if not present (assuming Indonesia +62)
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . ltrim($phone, '0');
        }
        
        return $phone;
    }
    /**
     * Build payload for sending text messages that supports both credential types.
     */
    protected function buildTextPayload($phoneNumber, $message)
    {
        if ($this->useApiCredentials && $this->clientId && $this->apiKey) {
            return [
                'client_id' => $this->clientId,
                'api_key' => $this->apiKey,
                'data' => [
                    'phone' => $this->formatPhoneNumberForAPI($phoneNumber),
                    'jid' => $this->formatPhoneNumber($phoneNumber),
                    'message' => $message,
                ],
            ];
        }

        return [
            'phone' => $this->formatPhoneNumberForAPI($phoneNumber),
            'message' => $message,
        ];
    }

    /**
     * Get default HTTP client with configured authentication.
     */
    protected function httpClient()
    {
        $client = Http::acceptJson();

        if ($this->basicAuthUser && $this->basicAuthPassword) {
            return $client->withBasicAuth($this->basicAuthUser, $this->basicAuthPassword);
        }

        return $client;
    }
}