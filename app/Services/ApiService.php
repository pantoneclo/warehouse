<?php

namespace App\Services;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class ApiService
{
    protected $token;
//    protected $baseUrl = 'https://api.pantoneclo.com/api/';
    protected $baseUrl = 'http://localhost:3000/api/';

    // Method to login and retrieve token
    public function login($email, $password, $userTypeId)
    {
        try {
            // Initialize cURL
            $ch = curl_init();

            // Set the cURL options
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . 'auth/login');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'email' => $email,
                'password' => $password,
                'userTypeId' => $userTypeId
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
            ]);

            // Execute the request and get the response
            $response = curl_exec($ch);

            // Check for cURL errors
            if (curl_errno($ch)) {
                \Log::error('cURL error:', ['message' => curl_error($ch)]);
                curl_close($ch);
                return [
                    'isSuccess' => false,
                    'message' => 'An error occurred: ' . curl_error($ch)
                ];
            }

            curl_close($ch);

            // Decode the response
            $content = json_decode($response, true);

            if (isset($content['isSuccess']) && $content['isSuccess']) {
                $token = $content['data']['token'];
                $this->token = $content['data']['token']; // Store the token
                // Cache the token for an hour (3600 seconds)
                Cache::put('api_token', $token, 3600);
                return $content; // Return the successful response
            }

            \Log::error('Login failed response:', $content);
            return [
                'isSuccess' => false,
                'message' => $content['message'] ?? 'Login failed'
            ];
        } catch (\Exception $e) {
            \Log::error('Login exception:', ['message' => $e->getMessage()]);
            return [
                'isSuccess' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
        }
    }

    // Method to manage stock by SKU
    public function manageStockBySku($warehouse, $operation, $items)
        {
            $client = new Client();
            $token = Cache::get('api_token');

            try {
                \Log::info('Sending request to external API', [
                    'warehouse' => $warehouse,
                    'operation' => $operation,
                    'items' => $items,
                ]);

                $response = $client->post($this->baseUrl . 'product/web/stockManageBySku', [
                    'json' => [
                        'warehouse' => $warehouse,
                        'operation' => $operation,
                        'items' => $items,
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 10, // Timeout after 10 seconds
                ]);

                $content = json_decode($response->getBody()->getContents(), true);

                \Log::info('External API response', $content);

                return $content;
            } catch (\Exception $e) {
                \Log::error('Manage stock exception:', [
                    'message' => $e->getMessage(),
                    'warehouse' => $warehouse,
                    'operation' => $operation,
                    'items' => $items,
                ]);
                return [
                    'isSuccess' => false,
                    'message' => 'An error occurred: ' . $e->getMessage()
                ];
            }
        }
}
