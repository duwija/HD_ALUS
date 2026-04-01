#!/bin/bash
# Test ENV Variables System

echo "======================================"
echo "Testing Environment Variables System"
echo "======================================"
echo ""

# Test 1: Check if tenant has env_variables
echo "📌 Test 1: Check KC tenant env_variables in database"
php artisan tinker --execute="
\$tenant = App\Tenant::where('rescode', 'KC')->first();
if (\$tenant && \$tenant->env_variables) {
    echo '✅ KC tenant has env_variables' . PHP_EOL;
    echo 'Variables: ' . count(\$tenant->env_variables) . PHP_EOL;
    print_r(\$tenant->env_variables);
} else {
    echo '❌ KC tenant has no env_variables' . PHP_EOL;
}
"

echo ""
echo "======================================"
echo "📌 Test 2: Test tenant_env() function"
php artisan tinker --execute="
\$tenant = App\Tenant::where('rescode', 'KC')->first();
app()->instance('tenant', \$tenant->toArray());

\$token = tenant_env('WHATSAPP_TOKEN', 'NOT_SET');
\$number = tenant_env('WHATSAPP_NUMBER', 'NOT_SET');
\$xendit = tenant_env('XENDIT_SECRET', 'NOT_SET');

if (\$token !== 'NOT_SET') {
    echo '✅ WHATSAPP_TOKEN: Found' . PHP_EOL;
} else {
    echo '❌ WHATSAPP_TOKEN: Not found' . PHP_EOL;
}

if (\$number !== 'NOT_SET') {
    echo '✅ WHATSAPP_NUMBER: Found' . PHP_EOL;
} else {
    echo '❌ WHATSAPP_NUMBER: Not found' . PHP_EOL;
}

if (\$xendit !== 'NOT_SET') {
    echo '✅ XENDIT_SECRET: Found' . PHP_EOL;
} else {
    echo '❌ XENDIT_SECRET: Not found' . PHP_EOL;
}
"

echo ""
echo "======================================"
echo "📌 Test 3: Check helper function exists"
if php -r "require 'vendor/autoload.php'; require 'app/Helpers/TenantHelpers.php'; echo (function_exists('tenant_env') ? 'YES' : 'NO');" | grep -q "YES"; then
    echo "✅ tenant_env() function exists"
else
    echo "❌ tenant_env() function not found"
fi

echo ""
echo "======================================"
echo "📌 Test 4: Check views have env variables section"
if grep -q "env-variables-container" resources/views/tenants/create.blade.php; then
    echo "✅ Create form has ENV variables section"
else
    echo "❌ Create form missing ENV variables section"
fi

if grep -q "env-variables-container" resources/views/tenants/edit.blade.php; then
    echo "✅ Edit form has ENV variables section"
else
    echo "❌ Edit form missing ENV variables section"
fi

echo ""
echo "======================================"
echo "📌 Test 5: Check controller has processEnvVariables method"
if grep -q "processEnvVariables" app/Http/Controllers/Admin/TenantManagementController.php; then
    echo "✅ Controller has processEnvVariables method"
else
    echo "❌ Controller missing processEnvVariables method"
fi

echo ""
echo "======================================"
echo "✅ ALL TESTS COMPLETED!"
echo "======================================"
echo ""
echo "📝 Documentation created:"
echo "   - ENV_VARIABLES_DATABASE_GUIDE.md"
echo "   - QUICK_START_ENV_VARIABLES.md"
echo ""
echo "🌐 Admin UI: https://kencana.alus.co.id/admin/login"
echo "   Email: admin@kencana.alus.co.id"
echo "   Password: Admin123!@#"
