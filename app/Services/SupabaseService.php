<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('SUPABASE_URL'), '/') . '/rest/v1';
        $this->apiKey = env('SUPABASE_KEY');
    }

    /**
     * Get all records from a table
     */
    public function select(string $table, array $columns = ['*'], array $filters = [], ?int $limit = null): array
    {
        $url = "{$this->baseUrl}/{$table}";
        $query = ['select' => implode(',', $columns)];

        // Add filters (e.g., ['user_id' => 'eq.5'])
        foreach ($filters as $column => $condition) {
            $query[$column] = $condition;
        }

        if ($limit) {
            $query['limit'] = $limit;
        }

        $response = Http::withHeaders($this->headers())->get($url, $query);

        if ($response->failed()) {
            Log::error("Supabase SELECT error: " . $response->body());
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Insert a record into a table
     */
    public function insert(string $table, array $data): ?array
    {
        $url = "{$this->baseUrl}/{$table}";

        $response = Http::withHeaders(array_merge($this->headers(), [
            'Prefer' => 'return=representation'
        ]))->post($url, $data);

        if ($response->failed()) {
            Log::error("Supabase INSERT error: " . $response->body());
            return null;
        }

        $result = $response->json();
        return $result[0] ?? null;
    }

    /**
     * Update records in a table
     */
    public function update(string $table, array $data, array $filters): bool
    {
        $url = "{$this->baseUrl}/{$table}";
        $query = [];

        foreach ($filters as $column => $condition) {
            $query[$column] = $condition;
        }

        $response = Http::withHeaders($this->headers())
            ->patch($url . '?' . http_build_query($query), $data);

        if ($response->failed()) {
            Log::error("Supabase UPDATE error: " . $response->body());
            return false;
        }

        return true;
    }

    /**
     * Delete records from a table
     */
    public function delete(string $table, array $filters): bool
    {
        $url = "{$this->baseUrl}/{$table}";
        $query = [];

        foreach ($filters as $column => $condition) {
            $query[$column] = $condition;
        }

        $response = Http::withHeaders($this->headers())
            ->delete($url . '?' . http_build_query($query));

        if ($response->failed()) {
            Log::error("Supabase DELETE error: " . $response->body());
            return false;
        }

        return true;
    }

    /**
     * Get a single record by ID
     */
    public function find(string $table, int $id, array $columns = ['*']): ?array
    {
        $result = $this->select($table, $columns, ['id' => "eq.{$id}"], 1);
        return $result[0] ?? null;
    }

    /**
     * Common headers for Supabase requests
     */
    protected function headers(): array
    {
        return [
            'apikey' => $this->apiKey,
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }
}
