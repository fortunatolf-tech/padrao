<?php
declare(strict_types=1);

/**
 * Ponto de entrada raiz (Root Fallback)
 * Encaminha para public/index.php caso o DocumentRoot do servidor
 * aponte para a raiz do projeto em vez da pasta /public.
 */
require_once __DIR__ . '/public/index.php';
