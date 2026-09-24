<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function sso_client_config(string $clientId): ?array
{
    $config = [
        'maintenance' => ['module' => 'maintenance', 'secret' => 'SSO_MAINTENANCE_SECRET', 'callback' => 'SSO_MAINTENANCE_CALLBACK_URL'],
        'payroll' => ['module' => 'payroll', 'secret' => 'SSO_PAYROLL_SECRET', 'callback' => 'SSO_PAYROLL_CALLBACK_URL'],
        'quotations' => ['module' => 'quotations', 'secret' => 'SSO_QUOTATIONS_SECRET', 'callback' => 'SSO_QUOTATIONS_CALLBACK_URL'],
    ];
    if (!isset($config[$clientId])) return null;
    $config[$clientId]['secret_value'] = env_value($config[$clientId]['secret']);
    $config[$clientId]['callback_url'] = env_value($config[$clientId]['callback']);
    return $config[$clientId];
}

function sso_module_start_url(string $slug): string
{
    $base = ['maintenance' => env_value('MODULE_MANTENCIONES_URL'), 'payroll' => env_value('MODULE_REMUNERACIONES_URL'), 'quotations' => env_value('MODULE_COTIZACIONES_URL')][$slug] ?? '';
    return rtrim($base, '/') . '/sso/start.php';
}
