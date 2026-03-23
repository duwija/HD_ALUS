<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | Daftar semua tenant/reseller dengan konfigurasi masing-masing
    | Setiap tenant menggunakan database terpisah
    |
    */
    
    'list' => [
        
        // Tenant 1: Adiyasa (Main)
        'adiyasa.alus.co.id' => [
            'tenant_id' => 1,
            'domain' => 'adiyasa.alus.co.id',
            'app_name' => 'ADIYASA',
            'signature' => 'Adiyasa Alusnet',
            'rescode' => 'AD',
            
            // Database Config
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'adiyasa_2.2',
            'db_username' => 'root',
            'db_password' => 'Abc234def1!@',
            
            // Mail Config
            'mail_from' => 'trikamedia.bali@gmail.com',
            
            // WhatsApp Config (opsional)
            'whatsapp_token' => null,
            
            // Payment Gateway (opsional)
            'xendit_key' => null,
            
            // Features enabled
            'features' => [
                'accounting' => true,
                'ticketing' => true,
                'whatsapp' => true,
                'payment_gateway' => true,
            ],
        ],
        
        // Tenant 2: Reseller 1
        'reseller1.example.com' => [
            'tenant_id' => 2,
            'domain' => 'reseller1.example.com',
            'app_name' => 'Reseller 1 ISP',
            'signature' => 'Reseller 1 Network',
            'rescode' => 'R1',
            
            // Database Config
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'reseller1_isp',
            'db_username' => 'root',
            'db_password' => 'Abc234def1!@',
            
            // Mail Config
            'mail_from' => 'admin@reseller1.example.com',
            
            // WhatsApp Config
            'whatsapp_token' => null,
            
            // Payment Gateway
            'xendit_key' => null,
            
            // Features enabled
            'features' => [
                'accounting' => true,
                'ticketing' => true,
                'whatsapp' => false,
                'payment_gateway' => true,
            ],
        ],
        
        // Tenant 3: Reseller 2
        'reseller2.example.com' => [
            'tenant_id' => 3,
            'domain' => 'reseller2.example.com',
            'app_name' => 'Reseller 2 ISP',
            'signature' => 'Reseller 2 Network',
            'rescode' => 'R2',
            
            // Database Config
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'reseller2_isp',
            'db_username' => 'root',
            'db_password' => 'Abc234def1!@',
            
            // Mail Config
            'mail_from' => 'admin@reseller2.example.com',
            
            // WhatsApp Config
            'whatsapp_token' => null,
            
            // Payment Gateway
            'xendit_key' => null,
            
            // Features enabled
            'features' => [
                'accounting' => false,  // Reseller 2 tidak pakai accounting
                'ticketing' => true,
                'whatsapp' => true,
                'payment_gateway' => false,
            ],
        ],
        
        // Localhost untuk development
        'localhost' => [
            'tenant_id' => 1,
            'domain' => 'localhost',
            'app_name' => 'ADIYASA DEV',
            'signature' => 'Adiyasa Development',
            'rescode' => 'AD',
            
            // Database Config
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'adiyasa_2.2',
            'db_username' => 'root',
            'db_password' => 'Abc234def1!@',
            
            'mail_from' => 'dev@localhost',
            'whatsapp_token' => null,
            'xendit_key' => null,
            
            'features' => [
                'accounting' => true,
                'ticketing' => true,
                'whatsapp' => true,
                'payment_gateway' => true,
            ],
        ],
        
        '127.0.0.1' => [
            'tenant_id' => 1,
            'domain' => '127.0.0.1',
            'app_name' => 'ADIYASA DEV',
            'signature' => 'Adiyasa Development',
            'rescode' => 'AD',
            
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'adiyasa_2.2',
            'db_username' => 'root',
            'db_password' => 'Abc234def1!@',
            
            'mail_from' => 'dev@localhost',
            'whatsapp_token' => null,
            'xendit_key' => null,
            
            'features' => [
                'accounting' => true,
                'ticketing' => true,
                'whatsapp' => true,
                'payment_gateway' => true,
            ],
        ],
        
        // Kencana tenant
        'kencana.alus.co.id' => [
            'tenant_id' => 4,
            'domain' => 'kencana.alus.co.id',
            'app_name' => 'KENCANA',
            'signature' => 'Kencana Alusnet',
            'rescode' => 'KC',
            
            // Database Config
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'kencana',
            'db_username' => 'root',
            'db_password' => 'Abc234def1!@',
            
            // Mail Config
            'mail_from' => 'admin@kencana.alus.co.id',
            
            // WhatsApp Config
            'whatsapp_token' => null,
            
            // Payment Gateway
            'xendit_key' => null,
            
            // Features enabled
            'features' => [
                'accounting' => true,
                'ticketing' => true,
                'whatsapp' => true,
                'payment_gateway' => true,
            ],
        ],
        
        // Tenant 5: Perumnet
        'perumnet.alus.co.id' => [
            'tenant_id' => 5,
            'domain' => 'perumnet.alus.co.id',
            'app_name' => 'PERUMNET',
            'signature' => 'Perumnet Alusnet',
            'rescode' => 'PN',
            
            // Database Config
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => 'perumnet',
            'db_username' => 'root',
            'db_password' => 'Kencana2022',
            
            // Mail Config
            'mail_from' => 'admin@perumnet.alus.co.id',
            
            // WhatsApp Config
            'whatsapp_token' => null,
            
            // Payment Gateway
            'xendit_key' => null,
            
            // Features enabled
            'features' => [
                'accounting' => true,
                'ticketing' => true,
                'whatsapp' => true,
                'payment_gateway' => true,
            ],
        ],
        
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Master Database (Opsional)
    |--------------------------------------------------------------------------
    | Database master untuk menyimpan daftar tenant jika ingin dinamis
    | (tidak wajib jika menggunakan config file di atas)
    */
    
    'master_db' => [
        'host' => env('MASTER_DB_HOST', '127.0.0.1'),
        'port' => env('MASTER_DB_PORT', '3306'),
        'database' => env('MASTER_DB_DATABASE', 'isp_master'),
        'username' => env('MASTER_DB_USERNAME', 'root'),
        'password' => env('MASTER_DB_PASSWORD', ''),
    ],
    
];
