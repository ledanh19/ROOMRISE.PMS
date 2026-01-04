ls
sudo su -
cd /www/wwwroot/pms
git config --global --add safe.directory /www/wwwroot/pms
git pull origin staging
rm -f /usr/bin/php
ln -s /www/server/php/82/bin/php /usr/bin/php
composer install
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan event:clear
php artisan storage:link
php artisan migrate
chmod 777 storage/api-docs
chmod 777 storage/api-docs/*
source /www/server/nvm/nvm.sh
nvm use 22.10.0
npm -v
npm install
npm run build
exit
