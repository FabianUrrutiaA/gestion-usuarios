# Introduction

API REST para gestión de usuarios y transferencias bancarias con validaciones de seguridad.

<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>

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

