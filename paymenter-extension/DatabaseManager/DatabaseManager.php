<?php

namespace App\Extensions\Servers\DatabaseManager;

use App\Classes\Extension\Server;
use Illuminate\Support\Facades\Http;
use Exception;

class DatabaseManager extends Server
{
    /**
     * Extension configuration inputs shown in Paymenter Admin -> Extensions -> DatabaseManager
     */
    public function getConfig(): array
    {
        return [
            [
                'name' => 'panel_url',
                'friendlyName' => 'Database Panel URL',
                'type' => 'text',
                'description' => 'Base URL of your Database Manager Panel (e.g. https://db.yourdomain.com or http://127.0.0.1:8000)',
                'required' => true,
            ],
            [
                'name' => 'api_key',
                'friendlyName' => 'API Key Token',
                'type' => 'text',
                'description' => 'Secret API Key generated in Admin -> API Keys',
                'required' => true,
            ],
        ];
    }

    /**
     * Product specific options in Paymenter Admin -> Products -> (Select Product) -> Server Config
     */
    public function getProductConfig($options): array
    {
        return [
            [
                'name' => 'product_type',
                'friendlyName' => 'Database Type',
                'type' => 'dropdown',
                'options' => [
                    'mysql' => 'MySQL 8.4',
                    'mariadb' => 'MariaDB 11',
                    'postgresql' => 'PostgreSQL 16',
                    'mongodb' => 'MongoDB 7',
                    'redis' => 'Redis Cache',
                ],
                'required' => true,
            ],
            [
                'name' => 'database_product_id',
                'friendlyName' => 'Database Product ID (Optional)',
                'type' => 'text',
                'description' => 'Explicit Product Package ID from Database Manager Panel catalog',
                'required' => false,
            ],
        ];
    }

    /**
     * Triggered automatically by Paymenter after invoice payment
     */
    public function createServer($user, $params, $order, $product, $configurableOptions): bool
    {
        $panelUrl = rtrim($this->config('panel_url'), '/');
        $apiKey = $this->config('api_key');

        $response = Http::withToken($apiKey)
            ->timeout(20)
            ->post("{$panelUrl}/api/external/provision", [
                'email' => $user->email,
                'name' => $user->name,
                'external_id' => (string) ($order->id ?? $params['service_id'] ?? uniqid()),
                'product_type' => $params['product_type'] ?? 'mysql',
                'product_id' => !empty($params['database_product_id']) ? (int) $params['database_product_id'] : null,
                'notes' => 'Provisioned from Paymenter Order #' . ($order->id ?? 'N/A'),
            ]);

        if (!$response->successful()) {
            throw new Exception('Database Manager Provisioning failed: ' . $response->body());
        }

        $data = $response->json('data');

        // Store credentials in Paymenter service properties/metadata
        if (isset($params['service'])) {
            $params['service']->properties()->updateOrCreate(
                ['key' => 'database_id'],
                ['value' => $data['id']]
            );
            $params['service']->properties()->updateOrCreate(
                ['key' => 'connection_string'],
                ['value' => $data['connection_string']]
            );
            $params['service']->properties()->updateOrCreate(
                ['key' => 'database_name'],
                ['value' => $data['database']]
            );
            $params['service']->properties()->updateOrCreate(
                ['key' => 'database_username'],
                ['value' => $data['username']]
            );
        }

        return true;
    }

    /**
     * Suspend server when invoice is overdue
     */
    public function suspendServer($user, $params, $order, $product, $configurableOptions): bool
    {
        $panelUrl = rtrim($this->config('panel_url'), '/');
        $apiKey = $this->config('api_key');
        $externalId = (string) ($order->id ?? $params['service_id']);

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post("{$panelUrl}/api/external/suspend", [
                'identifier' => $externalId,
            ]);

        return $response->successful();
    }

    /**
     * Unsuspend server when invoice is paid
     */
    public function unsuspendServer($user, $params, $order, $product, $configurableOptions): bool
    {
        $panelUrl = rtrim($this->config('panel_url'), '/');
        $apiKey = $this->config('api_key');
        $externalId = (string) ($order->id ?? $params['service_id']);

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post("{$panelUrl}/api/external/unsuspend", [
                'identifier' => $externalId,
            ]);

        return $response->successful();
    }

    /**
     * Terminate server when service is cancelled
     */
    public function terminateServer($user, $params, $order, $product, $configurableOptions): bool
    {
        $panelUrl = rtrim($this->config('panel_url'), '/');
        $apiKey = $this->config('api_key');
        $externalId = (string) ($order->id ?? $params['service_id']);

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post("{$panelUrl}/api/external/terminate", [
                'identifier' => $externalId,
            ]);

        return $response->successful();
    }
}
