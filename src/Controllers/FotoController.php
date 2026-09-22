<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Services/SigpesService.php';

class FotoController {

    public function serve(): void {
        $saram = trim($_GET['saram'] ?? '');
        $id = !empty($_GET['id']) ? (int)$_GET['id'] : null;

        $foto = SigpesService::getFoto($saram, $id);

        header('Content-Type: ' . $foto['mime']);
        header('Content-Length: ' . strlen($foto['data']));
        header('Cache-Control: public, max-age=86400'); // Cache no navegador por 1 dia
        header('X-Foto-Origem: ' . $foto['origem']);

        echo $foto['data'];
        exit;
    }
}
