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
        $resp = $this->get('/get_devices', ['lang' => 'en']);

        return is_array($resp['items'] ?? null)
            ? $resp['items']
            : (is_array($resp) ? $resp : []);
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
     * @param array<int,int> $deviceIds
     * @param string         $dateFrom Y-m-d
     * @param string         $dateTo   Y-m-d
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
            if ($this->debug) {
                Log::info('[EcuatrackerClient] GET reporte generado', ['url' => $url]);
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
                'url'   => $url,
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
     * @param array<string,mixed> $params
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
                    'url'   => $url,
                    'query' => $query,
                ]);
            }

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get($url, $query);

            if ($response->failed()) {
                $this->logAndThrow('GET', $path, $query, $response->status(), $response->body());
            }

            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (\Throwable $e) {
            Log::error('Error llamando a Ecuatracker GET', [
                'path'  => $path,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            throw new ApiException(
                'Error de comunicación con Ecuatracker (GET)',
                0,
                ['path' => $path, 'query' => $query],
                $e
            );
        }
    }

    /**
     * @param array<string,mixed> $payload
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
                'url'        => $url,
                'query'      => $query,
                //OJO*  si el payload tuviera datos sensibles, aquí habría que ocultarlos
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
                    ['query' => $query],
                    $response->status(),
                    $response->body()
                );
            }

            $json = $response->json();

            return is_array($json) ? $json : [];
        } catch (\Throwable $e) {
            Log::error('Error llamando a Ecuatracker POST', [
                'path'  => $path,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            throw new ApiException(
                'Error de comunicación con Ecuatracker (POST)',
                0,
                ['path' => $path, 'query' => $query],
                $e
            );
        }
    }

    /**
     * @param array<string,mixed> $requestData
     */
    protected function logAndThrow(string $method, string $path, array $requestData, int $status, string $body): void
    {
        Log::error('Respuesta no exitosa de Ecuatracker', [
            'method'  => $method,
            'path'    => $path,
            'request' => $requestData,
            'status'  => $status,
            'body'    => mb_substr($body, 0, 1000), // límite para no llenar logs
        ]);

        throw new ApiException(
            "Error en Ecuatracker ({$method} {$path}) [HTTP {$status}]",
            $status,
            [
                'request' => $requestData,
                'body'    => $body,
            ]
        );
    }
}
