# Portal Metalrubber

Portal independiente de autenticación y acceso para los módulos de Metalrubber.

## Requisitos

- PHP 8.1 o superior con PDO MySQL.
- MySQL o MariaDB.
- HTTPS en producción.
- Document root apuntando a `public/`.

## Instalación local o cPanel

1. Crea la base de datos `qlccl_portal` y un usuario MySQL con permisos sobre ella.
2. Importa [`database/schema.sql`](database/schema.sql) dentro de esa base.
3. Copia `.env.example` a `.env` fuera de `public/` y completa las credenciales.
4. Define una `INSTALL_KEY` larga y privada.
5. Configura el dominio para apuntar a `/home/qlccl/portal/public`.
6. Visita `/install.php` y crea el primer administrador.
7. Después de la instalación, cambia `INSTALL_KEY` o elimina el acceso público a `install.php`.

## Seguridad

El portal usa PDO con consultas preparadas, `password_hash`, sesiones con cookies HttpOnly/SameSite, regeneración de sesión, CSRF, mensajes de error genéricos, límite de intentos y auditoría básica. No comparte cookies, sesiones ni contraseñas con mantenciones o remuneraciones.

## Acceso y SSO

El portal valida sus propios usuarios y muestra los módulos autorizados. El acceso SSO es opcional por módulo; los logins locales continúan disponibles como respaldo.

## SSO con los módulos

El flujo SSO usa códigos de un solo uso con 60 segundos de vigencia. Ejecuta `database/migration_sso.sql` en la base del portal y agrega al `.env` los tres secretos SSO y las URLs de callback indicadas en `.env.example`. Usa un secreto distinto para cada módulo y configura el mismo secreto en el `.env` local de Mantenciones, Remuneraciones y Cotizaciones. Nunca publiques esos valores.

## Verificación

Con PHP disponible, ejecuta `php -l` sobre los archivos PHP y prueba los flujos de instalación, login, permisos, administración y logout. En cPanel, confirma que `.env` esté un nivel por encima de `public/` y que no sea descargable desde el navegador.
