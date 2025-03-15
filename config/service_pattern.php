<?php

/**
 * Configuration for service pattern.
 *
 * This configuration defines the default namespace, path,
 * and optional suffix for services within the application.
 */
return [
    // Default namespace for services
    'namespace' => 'App\\Services',

    // Default directory path for services
    'path' => app_path('Services'),

    // Optional suffix for services, e.g., if you want all services to end with "Service"
    'service_suffix' => 'Service',
];
