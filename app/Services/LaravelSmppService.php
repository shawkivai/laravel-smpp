<?php

namespace App\Services;

use Franzose\LaravelSmpp\Client;
use Franzose\LaravelSmpp\SmppAddress;
use Exception;
use SmppAddress as GlobalSmppAddress;

class LaravelSmppService
{
    protected $client;
    protected $isConnected = false;

    public function __construct()
    {
        $this->client = new Client([
            'host' => config('laravel-smpp.providers.smpp-simulator.host'),
            'port' => config('laravel-smpp.providers.smpp-simulator.port'),
            'timeout' => config('laravel-smpp.providers.smpp-simulator.timeout'),
            'system_id' => config('laravel-smpp.providers.smpp-simulator.login'),
            'password' => config('laravel-smpp.providers.smpp-simulator.password'),
        ]);
    }

    public function sendSMS($from, $to, $message)
    {
        try {
            // Ensure connection is established
            if (!$this->isConnected) {
                $this->connect();
            }

            $result = $this->client->submit([
                'source' => new GlobalSmppAddress($from),
                'destination' => new GlobalSmppAddress($to),
                'message' => $message
            ]);

            return [
                'success' => true,
                'message_id' => $result
            ];

        } catch (Exception $e) {
            $this->disconnect();
            throw new Exception('SMPP Error: ' . $e->getMessage());
        }
    }

    protected function connect()
    {
        try {
            $this->client->connect();
            $this->client->bind();
            $this->isConnected = true;
        } catch (Exception $e) {
            $this->isConnected = false;
            throw new Exception('SMPP Connection Error: ' . $e->getMessage());
        }
    }

    protected function disconnect()
    {
        if ($this->isConnected) {
            try {
                $this->client->close();
            } catch (Exception $e) {
                // Log error but don't throw
                // \Log::error('SMPP Disconnect Error: ' . $e->getMessage());
            }
            $this->isConnected = false;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}