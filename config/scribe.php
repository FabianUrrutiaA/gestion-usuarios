<?php

use Knuckles\Scribe\Extracting\Strategies;
use Knuckles\Scribe\Config\Defaults;
use Knuckles\Scribe\Config\AuthIn;
use function Knuckles\Scribe\Config\{removeStrategies, configureStrategy};

// Only the most common configs are shown. See the https://scribe.knuckles.wtf/laravel/reference/config for all.

return [
    // Título de la documentación
    'title' => 'API - Gestión de Usuarios y Transferencias',

    // Descripción corta
    'description' => 'API REST para gestión de usuarios y transferencias bancarias con validaciones de seguridad.',

    // Texto de introducción
    'intro_text' => <<<INTRO
        Esta documentación proporciona toda la información necesaria para trabajar con nuestra API de gestión de usuarios y transferencias.

        ## Características principales
        - Gestión completa de usuarios (CRUD)
        - Sistema de transferencias entre usuarios
        - Validaciones de saldo y límites diarios
        - Prevención de transacciones duplicadas
        - Autenticación con Laravel Sanctum (Bearer Token)
        - Exportación de datos a CSV
        - Reportes y estadísticas optimizadas

        ## Autenticación
        Esta API utiliza **Bearer Token** para autenticación. Primero debes hacer login para obtener tu token:

        ```bash
        POST /api/login
        {
          "email": "usuario@example.com",
          "password": "tu_contraseña"
        }
        ```

        Luego incluye el token en el header de tus peticiones:

        ```
        Authorization: Bearer {tu_token_aqui}
        ```
    INTRO,

    // URL base de la API
    'base_url' => env('APP_URL', 'http://localhost:8000'),

    // Rutas a incluir en la documentación
    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/*'],
                'domains' => ['*'],
            ],
            'include' => [],
            'exclude' => [],
        ],
    ],

    // Tipo de documentación (laravel permite verla en /docs)
    'type' => 'laravel',

    'theme' => 'default',

    'static' => [
        'output_path' => 'public/docs',
    ],

    'laravel' => [
        'add_routes' => true,
        'docs_url' => '/docs',
        'assets_directory' => null,
        'middleware' => [],
    ],

    'external' => [
        'html_attributes' => []
    ],

    'try_it_out' => [
        // Habilitar botón "Try it out" para probar endpoints desde el navegador
        'enabled' => true,
        'base_url' => null,
        'use_csrf' => false,
        'csrf_url' => '/sanctum/csrf-cookie',
    ],

    // Configuración de autenticación
    'auth' => [
        'enabled' => true,
        'default' => false,
        'in' => AuthIn::BEARER->value,
        'name' => 'Authorization',
        'use_value' => env('SCRIBE_AUTH_KEY'),
        'placeholder' => '{YOUR_BEARER_TOKEN}',
        'extra_info' => 'Obtén tu token haciendo login en <code>POST /api/login</code> con tus credenciales.',
    ],

    // Lenguajes de ejemplo
    'example_languages' => [
        'bash',
        'javascript',
        'php',
        'python',
    ],

    // Generar colección de Postman
    'postman' => [
        'enabled' => true,
        'overrides' => [],
    ],

    // Generar especificación OpenAPI
    'openapi' => [
        'enabled' => true,
        'version' => '3.0.3',
        'overrides' => [],
        'generators' => [],
    ],

    'groups' => [
        'default' => 'Endpoints',
        'order' => [
            'Gestión de Usuarios',
            'Gestión de Transferencias',
        ],
    ],

    'logo' => false,

    'last_updated' => 'Última actualización: {date:d/m/Y}',

    'examples' => [
        'faker_seed' => 1234,
        'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
    ],

    'strategies' => [
        'metadata' => [
            ...Defaults::METADATA_STRATEGIES,
        ],
        'headers' => [
            ...Defaults::HEADERS_STRATEGIES,
            Strategies\StaticData::withSettings(data: [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]),
        ],
        'urlParameters' => [
            ...Defaults::URL_PARAMETERS_STRATEGIES,
        ],
        'queryParameters' => [
            ...Defaults::QUERY_PARAMETERS_STRATEGIES,
        ],
        'bodyParameters' => [
            ...Defaults::BODY_PARAMETERS_STRATEGIES,
        ],
        'responses' => [
            Strategies\Responses\UseResponseAttributes::class,
            Strategies\Responses\UseApiResourceTags::class,
            Strategies\Responses\UseTransformerTags::class,
            Strategies\Responses\UseResponseTag::class,
            Strategies\Responses\UseResponseFileTag::class,
        ],
        'responseFields' => [
            ...Defaults::RESPONSE_FIELDS_STRATEGIES,
        ]
    ],
    'database_connections_to_transact' => [config('database.default')],

    'fractal' => [
        'serializer' => null,
    ],
];
