<?php

/**
 * Configuration for repository pattern.
 *
 * This file contains default settings for generating repositories,
 * including namespace, path, and naming conventions.
 */

return [
    // Check if the repository pattern is use cached.
    'use_cached' => true,

    // Default namespace for repositories.
    'namespace' => 'App\\Repositories',

    // Default directory path for repositories.
    'path' => app_path('Repositories'),

    // Suffix for repository classes (e.g., "Repository" will be appended if not present).
    'repository_suffix' => 'Repository',

    // Suffix for repository interfaces (e.g., "RepositoryInterface").
    'interface_suffix' => 'RepositoryInterface',
];
