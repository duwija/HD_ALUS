<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate tenant KC
$tenant = \App\Tenant::where('rescode', 'KC')->first();

if ($tenant) {
    $tenantArray = $tenant->toTenantArray();
    
    echo "=== TENANT KC DATA ===\n";
    echo "Rescode: " . $tenant->rescode . "\n";
    echo "Domain: " . $tenant->domain . "\n\n";
    
    echo "=== INV_NOTE FROM DATABASE ===\n";
    if (isset($tenantArray['inv_note'])) {
        echo "Found in tenant array: " . $tenantArray['inv_note'] . "\n\n";
    } else {
        echo "NOT FOUND in tenant array\n\n";
    }
    
    echo "=== ENV_VARIABLES JSON ===\n";
    echo json_encode($tenant->env_variables, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test via helper (need to set tenant context)
    app()->instance('tenant', $tenantArray);
    
    echo "=== USING tenant_config() ===\n";
    $invNote = tenant_config('inv_note', 'DEFAULT_VALUE');
    echo "Result: " . $invNote . "\n\n";
    
    echo "=== USING env() FALLBACK ===\n";
    echo "env('INV_NOTE'): " . env('INV_NOTE') . "\n";
    
} else {
    echo "Tenant KC not found!\n";
}
