
#!/bin/sh

# Database yang ingin kita backup
# Pisahkan dengan spasi untuk tiap database
databases="billingalus"

# Waktu saat ini
date=$(date +"%Y-%m-%d")

# User dan password dari database
# Gunakan root supaya lebih enak
user=root
pass=abc234def1!@

# User, password, dan alamat dari FTP servernya
# Untuk folder sesuai selera saja
ftpUser=duwija
ftpPass=Alus@2025!
ftpHost=cloudbali.alus.co.id
ftpFolder="BACKUP-DATABASE-BILLING/DB/"

# Tempat menyimpan database
# Ini di webserver dan bukan di FTP server
bPath="/home/backups/databases"

# Buat folder bPath diatas jika belum ada
if [ ! -d $bPath ]; then
    mkdir -p $bPath
fi

# Hapus file backup di bPath jika umurnya melebihi 3 hari
find $bPath/*.sql.gz -mtime +3 -exec rm {} \;


# Mulai membackup database
for db in $databases; do
    # Nama dari file backupnya
    file=$db-$date.sql.gz

    # Membackup database dengan mysqldump
    echo "Starting to dump the $db database as $file"
    mysqldump --user=$user --password=$pass $db | gzip -9 > $bPath/$file

    # Upload file tadi ke FTP menggunakan CURL
    echo "Starting to upload the $file to FTP server"
    curl -v --ftp-pasv --ftp-create-dirs -T $bPath/$file -u $ftpUser:$ftpPass ftp://$ftpHost/$ftpFolder

done

# Clear cache. Hanya untuk KVM, Xen
# ataupun dedicated server
free && sync && echo 3 > /proc/sys/vm/drop_caches && echo "" && free
