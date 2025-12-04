<?php

declare(strict_types=1);

namespace App\Services\Ecuatracker;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EcuatrackerClient
{
    protected readonly string $baseUrl;
    protected readonly string $userApiHash;
    protected readonly int $timeout;
    protected readonly bool $debug;

    public function __construct(?string $userApiHash = null)
    {
        $this->baseUrl     = rtrim((string) config('ecuatracker.base_url'), '/');
        $this->userApiHash = $userApiHash ?: (string) config('ecuatracker.user_api_hash');
        $this->timeout     = (int) config('ecuatracker.timeout', 15);
        $this->debug       = (bool) config('app.debug', false);

        if ($this->userApiHash === '') {
            throw new ApiException(
                'ECUATRACKER_USER_API_HASH no está configurado.',
                0,
                ['config' => 'ecuatracker.user_api_hash']
            );
        }

        if ($this->debug) {
            Log::info('[EcuatrackerClient] Inicializado', [
                'base_url' => $this->baseUrl,
                'timeout'  => $this->timeout,
            ]);
        }
    }

    /**
     * GET /get_devices
     *
     * @return array<int, mixed>
     */
    public function getDevices(): array
    {
        $resp  = $this->get('/get_devices', ['lang' => 'en']);
        $items = is_array($resp['items'] ?? null) ? $resp['items'] : (is_array($resp) ? $resp : []);

        return $this->flattenDevices($items);
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, mixed>
     */
    protected function flattenDevices(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (isset($item['items']) && is_array($item['items'])) {
                $groupId    = $item['id']    ?? null;
                $groupTitle = $item['title'] ?? null;

                foreach ($item['items'] as $child) {
                    if (is_array($child)) {
                        $child['group_id']    = $groupId;
                        $child['group_title'] = $groupTitle;
                        $result[] = $child;
                    }
                }
            } elseif (is_array($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * GET /devices_groups
     *
     * @return array<int, mixed>
     */
    public function getDeviceGroups(): array
    {
        $resp = $this->get('/devices_groups', ['lang' => 'en']);

        return is_array($resp['items'] ?? null)
            ? $resp['items']
            : (is_array($resp) ? $resp : []);
    }

    /**
     * Genera reporte de km recorridos.
     *
     * @param array<int,int>      $deviceIds
     * @param string              $dateFrom Y-m-d
     * @param string              $dateTo   Y-m-d
     * @param array<string,mixed> $options
     *
     * @return array<string,mixed>
     */
    public function generateKmReport(array $deviceIds, string $dateFrom, string $dateTo, array $options = []): array
    {
        $type = (int) config('ecuatracker.report_types.analisis_recorrido_km');

        $payload = array_merge([
            'title'          => $options['title'] ?? "Reporte {$dateFrom} al {$dateTo}",
            'type'           => $type,
            'format'         => 'json',
            'speed_limit'    => 0,
            'devices'        => array_values($deviceIds),
            'geofences'      => [],
            'daily'          => 0,
            'weekly'         => 0,
            'send_to_email'  => '',
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
            'from_time'      => $options['from_time'] ?? '00:00:00',
            'to_time'        => $options['to_time'] ?? '23:59:59',
            'show_addresses' => false,
            'zones_instead'  => false,
            'stops'          => 0,
        ], $options['extra'] ?? []);

        $initial = $this->post('/generate_report', $payload);

        // Si la API devuelve un "job" con URL de resultado
        if (
            isset($initial['status'], $initial['url']) &&
            (int) $initial['status'] === 3 &&
            is_string($initial['url'])
        ) {
            return $this->fetchGeneratedReport($initial['url']);
        }

        // Si devuelve el reporte completo directo
        return is_array($initial) ? $initial : [];
    }

    /**
     * Segundo GET a la URL del reporte generado.
     *
     * @return array<string,mixed>
     */
    protected function fetchGeneratedReport(string $url): array
    {
        try {
            // Normalizar URL relativa a absoluta
            if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
                $url = rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
            }

            // Validar host destino contra baseUrl (seguridad básica)
            $baseHost   = parse_url($this->baseUrl, PHP_URL_HOST);
            $targetHost = parse_url($url, PHP_URL_HOST);

            if ($baseHost && $targetHost && ! hash_equals((string) $baseHost, (string) $targetHost)) {
                Log::warning('[EcuatrackerClient] Host de URL de reporte no coincide con base_url', [
                    'base_url'    => $this->baseUrl,
                    'url'         => $this->maskUrl($url),
                    'base_host'   => $baseHost,
                    'target_host' => $targetHost,
                ]);

                throw new ApiException(
                    'URL del reporte generado no es válida.',
                    0,
                    ['url' => $url]
                );
            }

            if ($this->debug) {
                Log::info('[EcuatrackerClient] GET reporte generado', [
                    'url' => $this->maskUrl($url),
                ]);
            }

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url);

            if ($response->failed()) {
                $this->logAndThrow('GET', $url, [], $response->status(), $response->body());
            }

            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (\Throwable $e) {
            Log::error('Error obteniendo reporte generado de Ecuatracker', [
                'url'   => $this->maskUrl($url),
                'error' => $e->getMessage(),
            ]);

            throw new ApiException(
                'Error al obtener el reporte generado desde Ecuatracker (GET)',
                0,
                ['url' => $url],
                $e
            );
        }
    }

    /**
     * @param  array<string,mixed>  $params
     * @return array<string,mixed>
     */
    protected function get(string $path, array $params = []): array
    {
        $query = array_merge($params, [
            'user_api_hash' => $this->userApiHash,
        ]);

        $url = $this->baseUrl . $path;

        try {
            if ($this->debug) {
                Log::info('[EcuatrackerClient] GET', [
                    'url'   => $this->maskUrl($url),
                    'query' => $this->maskArray($query),
                ]);
            }

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url, $query);

            if ($response->failed()) {
                $this->logAndThrow('GET', $path, $this->maskArray($query), $response->status(), $response->body());
            }

            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (\Throwable $e) {
            Log::error('Error llamando a Ecuatracker GET', [
                'path'  => $path,
                'query' => $this->maskArray($query),
                'error' => $e->getMessage(),
            ]);

            throw new ApiException(
                'Error de comunicación con Ecuatracker (GET)',
                0,
                ['path' => $path, 'query' => $this->maskArray($query)],
                $e
            );
        }
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    protected function post(string $path, array $payload = []): array
    {
        $query = [
            'user_api_hash' => $this->userApiHash,
            'lang'          => 'en',
        ];

        $url = $this->baseUrl . $path;

        if ($this->debug) {
            Log::info('[EcuatrackerClient] POST', [
                'url'        => $this->maskUrl($url),
                'query'      => $this->maskArray($query),
                // útil para verificar rápidamente qué hash se está usando, sin exponerlo completo
                'hash_start' => substr($this->userApiHash, 0, 8) . '***',
            ]);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->withQueryParameters($query)
                ->post($url, $payload);

            if ($response->failed()) {
                $this->logAndThrow(
                    'POST',
                    $path,
                    ['query' => $this->maskArray($query)],
                    $response->status(),
                    $response->body()
                );
            }

            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (\Throwable $e) {
            Log::error('Error llamando a Ecuatracker POST', [
                'path'  => $path,
                'query' => $this->maskArray($query),
                'error' => $e->getMessage(),
            ]);

            throw new ApiException(
                'Error de comunicación con Ecuatracker (POST)',
                0,
                ['path' => $path, 'query' => $this->maskArray($query)],
                $e
            );
        }
    }

    /**
     * @param  array<string,mixed>  $requestData
     */
    protected function logAndThrow(string $method, string $path, array $requestData, int $status, string $body): void
    {
        $safeRequest = $this->maskArray($requestData);

        Log::error('Respuesta no exitosa de Ecuatracker', [
            'method'  => $method,
            'path'    => $path,
            'request' => $safeRequest,
            'status'  => $status,
            'body'    => mb_substr($body, 0, 1000), // límite para no llenar logs
        ]);

        throw new ApiException(
            "Error en Ecuatracker ({$method} {$path}) [HTTP {$status}]",
            $status,
            [
                'request' => $safeRequest,
                'body'    => $body,
            ]
        );
    }

    /**
     * Enmascara el valor de user_api_hash en URLs tipo ?user_api_hash=...
     */
    private function maskUrl(string $url): string
    {
        $masked = \preg_replace('/(user_api_hash=)[^&]+/i', '$1[HIDDEN]', $url);

        return $masked ?? $url;
    }

    /**
     * Enmascara valores sensibles dentro de arrays de request/query.
     *
     * - Cualquier clave que contenga "hash" (case-insensitive) se reemplaza por [HIDDEN].
     * - Se aplica recursivamente.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function maskArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (\is_array($value)) {
                $data[$key] = $this->maskArray($value);
                continue;
            }

            if (\is_string($value) && \stripos((string) $key, 'hash') !== false) {
                $data[$key] = '[HIDDEN]';
            }
        }

        return $data;
    }
}
