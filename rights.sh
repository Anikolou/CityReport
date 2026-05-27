#!/bin/bash
echo "The automated config of the uploads dir is starting..."

# 1. Create the uploads directory (if it wasn't created)
mkdir -p /var/www/html/uploads

# 2. Change the admin on Apache (www-data)
chown -R www-data:www-data /var/www/html/uploads

# 3. Full rights (Put 777 to be 100% sure that this bash script runs in every machine platform)
chmod -R 777 /var/www/html/uploads

echo "The rights config has been successfully set!"

# 4. Start the Apache server (This is the default command that runs the official php docker image)
exec apache2-foreground