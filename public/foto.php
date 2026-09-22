<?php
declare(strict_types=1);

/**
 * Endpoint de Alta Performance para Entrega de Fotos
 * Compatível com imagem.php e foto.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Controllers/FotoController.php';

(new FotoController())->serve();
