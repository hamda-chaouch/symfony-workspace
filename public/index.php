<?php

use App\Kernel;

// Set UTC timezone for the entire application
date_default_timezone_set('UTC');

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
