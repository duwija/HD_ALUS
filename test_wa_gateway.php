<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test untuk lubax.olima.id
$_SERVER['HTTP_HOST'] = 'lubax.olima.id';

$tenant = App\Tenant::where('domain', 'lubax.olima.id')->first();

if (!$tenant) {
    echo "Tenant not found!\n";
    exit(1);
}

echo "=== Testing WhatsApp Gateway URL for lubax.olima.id ===\n\n";

// Simulate middleware
$arr = $tenant->toTenantArray();
config(['tenant' => $arr]);
foreach ($arr as $key => $value) {
    if (!is_array($value)) {
        $_ENV[strtoupper($key)] = $value;
    }
}

echo "1. Raw from DB env_variables:\n";
echo "   wa_gateway_url: " . ($arr['wa_gateway_url'] ?? 'NOT SET') . "\n\n";

echo "2. tenant_config() result:\n";
echo "   " . tenant_config('wa_gateway_url', 'DEFAULT_VALUE') . "\n\n";

echo "3. Final URL (as used in controller):\n";
$finalUrl = rtrim(tenant_config('wa_gateway_url', env('WA_GATEWAY_URL', 'http://127.0.0.1:3005')), '/') . '/api';
echo "   " . $finalUrl . "\n\n";

echo "Expected: http://103.156.74.19:3003/api\n";
