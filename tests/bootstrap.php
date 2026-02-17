<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Cast to bool to avoid PHPStan "mixed in if condition"
if ((bool) ($_SERVER['APP_DEBUG'] ?? false)) {
    umask(0000);
}
