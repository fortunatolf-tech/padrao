<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Serviço de Integração com a API SIGPES da FAB / CCARJ
 * Consulta fotos oficiais e ficha funcional com suporte a fallback de upload manual e cache local.
 */
class SigpesService {

    /**
     * Retorna o binário da imagem do militar/servidor
     * Ordem de prioridade:
     * 1. Foto manual enviada pelo Administrador (em uploads/fotos)
     * 2. Cache local em disco (se baixado anteriormente do SIGPES)
     * 3. Chamada à API SIGPES CCARJ
     * 4. Placeholder padrão COMARA
     */
    public static function getFoto(string $saram, ?int $efetivoId = null): array {
        // 1. Verifica upload manual se ID informado
        if ($efetivoId) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT foto_custom FROM efetivo WHERE id = :id');
            $stmt->execute([':id' => $efetivoId]);
            $custom = $stmt->fetchColumn();

            if ($custom && file_exists(UPLOAD_FOTOS_PATH . '/' . $custom)) {
                $path = UPLOAD_FOTOS_PATH . '/' . $custom;
                $mime = mime_content_type($path) ?: 'image/jpeg';
                return [
                    'origem' => 'MANUAL_ADMIN',
                    'mime'   => $mime,
                    'data'   => file_get_contents($path)
                ];
            }
        }

        // Verifica arquivo manual pelo SARAM
        $exts = ['jpg', 'jpeg', 'png', 'webp'];
        foreach ($exts as $ext) {
            $manualFile = UPLOAD_FOTOS_PATH . "/saram_{$saram}.{$ext}";
            if (file_exists($manualFile)) {
                return [
                    'origem' => 'MANUAL_ADMIN',
                    'mime'   => mime_content_type($manualFile) ?: 'image/jpeg',
                    'data'   => file_get_contents($manualFile)
                ];
            }
        }

        // 2. Verifica Cache Local do SIGPES
        $cacheDir = ROOT_PATH . '/cache/fotos';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        $cacheFile = "{$cacheDir}/{$saram}.jpg";
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400 * 7)) { // Cache de 7 dias
            return [
                'origem' => 'SIGPES_CACHE',
                'mime'   => 'image/jpeg',
                'data'   => file_get_contents($cacheFile)
            ];
        }

        // 3. Consulta à API SIGPES
        $apiBinary = self::fetchFotoApi($saram);
        if ($apiBinary) {
            @file_put_contents($cacheFile, $apiBinary);
            return [
                'origem' => 'SIGPES_API',
                'mime'   => 'image/jpeg',
                'data'   => $apiBinary
            ];
        }

        // 4. Retorna Placeholder Vetorial Padronizado COMARA
        return [
            'origem' => 'PLACEHOLDER',
            'mime'   => 'image/svg+xml',
            'data'   => self::getSvgPlaceholder()
        ];
    }

    /**
     * Executa requisição cURL à API de fotos do SIGPES
     */
    private static function fetchFotoApi(string $saram): ?string {
        if (!function_exists('curl_init')) {
            return null;
        }

        $url = SIGPES_API_BASE . "/fotoes/" . urlencode($saram);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, SIGPES_API_TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            $json = json_decode((string)$response, true);
            $base64 = null;

            if (isset($json['_embedded']['fotoes'][0]['imFoto'])) {
                $base64 = $json['_embedded']['fotoes'][0]['imFoto'];
            } elseif (isset($json['imFoto'])) {
                $base64 = $json['imFoto'];
            }

            if ($base64) {
                $bin = base64_decode($base64);
                if ($bin !== false && strlen($bin) > 100) {
                    return $bin;
                }
            }
        }

        return null;
    }

    /**
     * Consulta dados cadastrais e ficha funcional do militar na API SIGPES
     */
    public static function fetchPessoaFisica(string $saram): ?array {
        if (!function_exists('curl_init')) {
            return null;
        }

        $url = SIGPES_API_BASE . "/pesfisComgeps/" . urlencode($saram);
        $res = self::callApi($url);

        if (!$res || isset($res['erro_api'])) {
            $busca = self::callApi(SIGPES_API_BASE . "/pesfisComgeps/search/findByNrOrdem?nrOrdem=" . urlencode($saram));
            $res = $busca['_embedded']['pesfisComgeps'][0] ?? null;
        }

        return is_array($res) ? $res : null;
    }

    private static function callApi(string $url): ?array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, SIGPES_API_TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            return json_decode((string)$response, true);
        }
        return ['erro_api' => true, 'http_code' => $httpCode];
    }

    /**
     * SVG profissional de placeholder da COMARA
     */
    private static function getSvgPlaceholder(): string {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="240" viewBox="0 0 200 240">
  <rect width="200" height="240" fill="#e9ecef"/>
  <circle cx="100" cy="85" r="45" fill="#6c757d"/>
  <path d="M 30 210 C 30 150, 170 150, 170 210 Z" fill="#6c757d"/>
  <rect x="0" y="215" width="200" height="25" fill="#0b3b60"/>
  <text x="100" y="232" font-family="Arial, sans-serif" font-size="11" font-weight="bold" fill="#ffffff" text-anchor="middle">COMARA</text>
</svg>
SVG;
    }
}
