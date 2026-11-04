# ERD de la base de datos

Este diagrama refleja las tablas definidas por las migraciones del proyecto. Incluye las entidades del negocio y las tablas auxiliares de Laravel.

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string role
        string telefono
        boolean activo
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    RUTAS {
        bigint id PK
        string nombre
        text descripcion
        bigint cobrador_id FK
        boolean activa
        timestamp created_at
        timestamp updated_at
    }

    CLIENTES {
        bigint id PK
        string nombre
        string documento UK
        string telefono
        string direccion
        string barrio
        string referencia_nombre
        string referencia_telefono
        bigint ruta_id FK
        text notas
        boolean activo
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    PRESTAMOS {
        bigint id PK
        bigint cliente_id FK
        bigint cobrador_id FK
        decimal monto
        decimal tasa_interes
        decimal total_a_pagar
        decimal saldo
        integer numero_cuotas
        decimal valor_cuota
        string frecuencia
        boolean incluye_domingos
        date fecha_desembolso
        date fecha_primera_cuota
        date fecha_vencimiento
        string estado
        text notas
        timestamp created_at
        timestamp updated_at
    }

    CUOTAS {
        bigint id PK
        bigint prestamo_id FK
        integer numero
        date fecha_vencimiento
        decimal valor
        decimal valor_pagado
        string estado
        timestamp created_at
        timestamp updated_at
    }

    PAGOS {
        bigint id PK
        bigint prestamo_id FK
        bigint user_id FK
        decimal monto
        date fecha
        string metodo
        text nota
        timestamp created_at
        timestamp updated_at
    }

    MOVIMIENTOS_CAJA {
        bigint id PK
        bigint user_id FK
        string tipo
        string concepto
        decimal monto
        date fecha
        string descripcion
        bigint prestamo_id FK
        bigint pago_id FK
        timestamp created_at
        timestamp updated_at
    }

    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        text name
        string token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    PASSWORD_RESET_TOKENS {
        string email PK
        string token
        timestamp created_at
    }

    SESSIONS {
        string id PK
        bigint user_id
        string ip_address
        text user_agent
        longtext payload
        integer last_activity
    }

    CACHE {
        string key PK
        mediumtext value
        bigint expiration
    }

    CACHE_LOCKS {
        string key PK
        string owner
        bigint expiration
    }

    JOBS {
        bigint id PK
        string queue
        longtext payload
        integer attempts
        integer reserved_at
        integer available_at
        integer created_at
    }

    JOB_BATCHES {
        string id PK
        string name
        integer total_jobs
        integer pending_jobs
        integer failed_jobs
        longtext failed_job_ids
        mediumtext options
        integer cancelled_at
        integer created_at
        integer finished_at
    }

    FAILED_JOBS {
        bigint id PK
        string uuid UK
        string connection
        string queue
        longtext payload
        longtext exception
        timestamp failed_at
    }

    USERS o|--o{ RUTAS : "cobrador asignado"
    RUTAS o|--o{ CLIENTES : "incluye"
    CLIENTES ||--o{ PRESTAMOS : "recibe"
    USERS ||--o{ PRESTAMOS : "gestiona"
    PRESTAMOS ||--o{ CUOTAS : "se divide en"
    PRESTAMOS ||--o{ PAGOS : "recibe"
    USERS ||--o{ PAGOS : "registra"
    USERS ||--o{ MOVIMIENTOS_CAJA : "registra"
    PRESTAMOS o|--o{ MOVIMIENTOS_CAJA : "referenciado por"
    PAGOS o|--o{ MOVIMIENTOS_CAJA : "referenciado por"
```

## Reglas y notas

- `RUTAS.cobrador_id` y `CLIENTES.ruta_id` son opcionales; al eliminar el cobrador o la ruta, el campo se establece en `NULL`.
- `CUOTAS.prestamo_id` se elimina en cascada cuando se elimina el préstamo. La combinación `(prestamo_id, numero)` es única.
- Las FK `PRESTAMOS.cliente_id`, `PRESTAMOS.cobrador_id`, `PAGOS.prestamo_id`, `PAGOS.user_id` y `MOVIMIENTOS_CAJA.user_id` son obligatorias y no especifican borrado en cascada.
- `MOVIMIENTOS_CAJA.prestamo_id` y `pago_id` son opcionales; se establecen en `NULL` al eliminar el préstamo o el pago correspondiente.
- `CLIENTES` usa borrado lógico (`deleted_at`). `documento` es único.
- `PERSONAL_ACCESS_TOKENS` usa una relación polimórfica (`tokenable_type`, `tokenable_id`), por lo que no tiene una FK convencional a `users`.
- `SESSIONS.user_id` está indexado, pero la migración no define una FK.
- Las tablas auxiliares de caché y colas (`CACHE`, `CACHE_LOCKS`, `JOBS`, `JOB_BATCHES`, `FAILED_JOBS`), junto con `PASSWORD_RESET_TOKENS`, no tienen relaciones FK declaradas.
- Las columnas `decimal` monetarias usan precisión `(14, 2)`; `PRESTAMOS.tasa_interes` usa `(6, 2)`. `CLIENTES.documento`, `telefono` y `referencia_telefono` tienen longitud máxima de 30 caracteres.
