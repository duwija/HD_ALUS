<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for configuring which payment gateways are enabled
    | for the tenant. Values are retrieved from tenant database.
    | Set value to 1 to enable, 0 to disable.
    |
    */

    'bumdes_enabled' => tenant_config('payment_bumdes_enabled', 1),
    'winpay_enabled' => tenant_config('payment_winpay_enabled', 1),
    'tripay_enabled' => tenant_config('payment_tripay_enabled', 1),

];
