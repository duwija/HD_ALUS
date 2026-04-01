#!/bin/bash

# Script untuk menambah tenant baru ke sistem multi-tenant
# Usage: ./add-tenant.sh

echo "=========================================="
echo "  MULTI-TENANT - TAMBAH TENANT BARU"
echo "=========================================="
echo ""

# Input data tenant
read -p "Domain (contoh: reseller.alus.co.id): " DOMAIN
read -p "Tenant ID (angka unik, contoh: 5): " TENANT_ID
read -p "Nama Aplikasi (contoh: Reseller ISP): " APP_NAME
read -p "Signature (contoh: Reseller Network): " SIGNATURE
read -p "Rescode (2-3 huruf, contoh: RS): " RESCODE
read -p "Nama Database (contoh: reseller_isp): " DB_NAME
read -p "Email From (contoh: admin@reseller.alus.co.id): " MAIL_FROM

echo ""
echo "=========================================="
echo "RINGKASAN:"
echo "Domain: $DOMAIN"
echo "Tenant ID: $TENANT_ID"
echo "App Name: $APP_NAME"
echo "Rescode: $RESCODE"
echo "Database: $DB_NAME"
echo "=========================================="
echo ""
read -p "Lanjutkan? (y/n): " CONFIRM

if [ "$CONFIRM" != "y" ]; then
    echo "Dibatalkan."
    exit 1
fi

echo ""
echo "==> 1. Membuat database..."
mysql -u root -p"Abc234def1!@" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import struktur database dari tenant yang sudah ada
echo "==> 2. Import struktur database dari tenant adiyasa..."
mysqldump -u root -p"Abc234def1!@" --no-data adiyasa_2.2 | mysql -u root -p"Abc234def1!@" $DB_NAME

echo "==> 3. Membuat direktori storage untuk tenant..."
mkdir -p /var/www/kencana.alus.co.id/storage/tenants/$RESCODE/{logs,app/public}
chown -R nginx:nginx /var/www/kencana.alus.co.id/storage/tenants/$RESCODE
chmod -R 755 /var/www/kencana.alus.co.id/storage/tenants/$RESCODE

echo "==> 4. Membuat konfigurasi nginx..."
cat > /etc/nginx/conf.d/$DOMAIN.conf << EOF
server {
    listen 80;
    server_name $DOMAIN;
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl;
    server_name $DOMAIN;

    root /var/www/kencana.alus.co.id/public;
    index index.php index.html;

    location ~ \.php\$ {
        include /etc/nginx/fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
        fastcgi_index index.php;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ /\.ht {
        deny all;
    }

    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
}
EOF

echo "==> 5. Setup SSL certificate dengan Let's Encrypt..."
certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email admin@alus.co.id || echo "SSL setup gagal, silakan jalankan manual: certbot --nginx -d $DOMAIN"

echo "==> 6. Test dan reload nginx..."
nginx -t && systemctl reload nginx

echo ""
echo "=========================================="
echo "SELESAI!"
echo "=========================================="
echo ""
echo "LANGKAH SELANJUTNYA:"
echo ""
echo "1. Tambahkan konfigurasi berikut ke config/tenants.php:"
echo ""
cat << EOF

        // Tenant: $APP_NAME
        '$DOMAIN' => [
            'tenant_id' => $TENANT_ID,
            'domain' => '$DOMAIN',
            'app_name' => '$APP_NAME',
            'signature' => '$SIGNATURE',
            'rescode' => '$RESCODE',
            
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_database' => '$DB_NAME',
            'db_username' => 'root',
            'db_password' => 'Abc234def1!@',
            
            'mail_from' => '$MAIL_FROM',
            'whatsapp_token' => null,
            'xendit_key' => null,
            
            'features' => [
                'accounting' => true,
                'ticketing' => true,
                'whatsapp' => true,
                'payment_gateway' => true,
            ],
        ],

EOF
echo ""
echo "2. Setelah edit config/tenants.php, jalankan:"
echo "   cd /var/www/kencana.alus.co.id"
echo "   php artisan config:clear"
echo "   php artisan cache:clear"
echo ""
echo "3. Pointing DNS domain $DOMAIN ke IP server"
echo ""
echo "4. Akses https://$DOMAIN untuk test"
echo ""
