#!/usr/bin/env bash
# NatNetwork — deploy ke VPS (AlmaLinux 8, Nginx sedia ada).
# Selamat dijalankan berulang kali: .env dan database pelayan dikekalkan.
# Tidak mengubah konfigurasi Nginx / aplikasi lain (GrowBiz dsb).
set -euo pipefail

DOMAIN="app.natnetwork.net"
SERVER_IP="103.224.93.66"
APP_DIR="/var/www/natnetwork"
ARCHIVE="/root/natnetwork.tar.gz"
CERT_EMAIL="natnetwork888@gmail.com"
FPM_SOCK="/run/php-fpm/natnetwork.sock"
WEB_USER="nginx"

step() { echo; echo "==================== $* ===================="; }
as_web() { runuser -u "$WEB_USER" -- "$@"; }

[ "$(id -u)" -eq 0 ] || { echo "Sila jalankan sebagai root."; exit 1; }
[ -f "$ARCHIVE" ] || { echo "Fail $ARCHIVE tiada. Jalankan pack.bat di PC dahulu."; exit 1; }
id "$WEB_USER" >/dev/null 2>&1 || { echo "User $WEB_USER tiada — adakah Nginx dipasang?"; exit 1; }

step "1/8 PHP 8.4 (Remi, tanpa Apache)"
if [ ! -x /usr/sbin/php-fpm ]; then
  dnf -y install epel-release
  rpm -q remi-release >/dev/null 2>&1 || dnf -y install https://rpms.remirepo.net/enterprise/remi-release-8.rpm
  dnf -y module reset php
  dnf -y module enable php:remi-8.4
  dnf -y install --setopt=install_weak_deps=False \
    php-fpm php-cli php-common php-mbstring php-xml php-pdo php-bcmath \
    php-intl php-gd php-zip php-opcache php-process php-sodium
else
  echo "php-fpm sudah ada: $(php -v | head -1)"
fi
if rpm -q httpd >/dev/null 2>&1; then echo "AMARAN: pakej httpd (Apache) dipasang. Pastikan ia tidak berjalan: systemctl disable --now httpd"; fi
php -m | grep -qi pdo_sqlite || { echo "pdo_sqlite tiada"; exit 1; }

step "2/8 Composer"
if ! command -v composer >/dev/null 2>&1; then
  php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi
composer --version

step "3/8 Kod aplikasi -> $APP_DIR"
mkdir -p "$APP_DIR"
[ -f "$APP_DIR/.env" ] && cp -a "$APP_DIR/.env" "/root/natnetwork.env.bak.$(date +%Y%m%d%H%M%S)"
tar -xzf "$ARCHIVE" -C "$APP_DIR"
cd "$APP_DIR"
mkdir -p storage/app/private storage/app/public storage/framework/{cache/data,sessions,views,testing} storage/logs bootstrap/cache database
rm -f bootstrap/cache/*.php

step "4/8 .env production"
if [ ! -f .env ]; then
  cp .env.example .env
  sed -i \
    -e "s|^APP_ENV=.*|APP_ENV=production|" \
    -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
    -e "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" \
    -e "s|^LOG_LEVEL=.*|LOG_LEVEL=warning|" .env
  grep -q '^SESSION_SECURE_COOKIE=' .env || echo "SESSION_SECURE_COOKIE=true" >> .env
  NEW_ENV=1
else
  echo ".env sedia ada dikekalkan."
  sed -i -e "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" .env
  grep -q '^APP_URL=' .env || echo "APP_URL=https://$DOMAIN" >> .env
  grep -q '^APP_KEY=base64:' .env || NEW_ENV=1
fi
echo "APP_ENV/APP_DEBUG/APP_URL semasa:"; grep -E '^(APP_ENV|APP_DEBUG|APP_URL|DB_CONNECTION)=' .env || true
[ -f database/database.sqlite ] || touch database/database.sqlite

step "5/8 Composer install + kebenaran fail"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress
[ "${NEW_ENV:-0}" = "1" ] && php artisan key:generate --force
# Kod: milik root, boleh dibaca oleh kumpulan nginx. Folder boleh tulis: milik nginx.
chown -R root:"$WEB_USER" "$APP_DIR"
chmod -R u+rwX,g+rX,g-w,o-rwx "$APP_DIR"
chmod o+x /var/www 2>/dev/null || true
chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache database
chmod -R ug+rwX storage bootstrap/cache database
chmod 640 .env && chown root:"$WEB_USER" .env
if command -v getenforce >/dev/null 2>&1 && [ "$(getenforce)" != "Disabled" ]; then
  restorecon -R "$APP_DIR" || true
  chcon -R -t httpd_sys_rw_content_t storage bootstrap/cache database
  setsebool -P httpd_can_network_connect 1
fi

step "6/8 Migration + cache"
as_web php artisan migrate --force
as_web php artisan optimize:clear
as_web php artisan config:cache
as_web php artisan route:cache
as_web php artisan view:cache

step "7/8 PHP-FPM pool natnetwork"
cat > /etc/php-fpm.d/natnetwork.conf <<POOL
[natnetwork]
user = $WEB_USER
group = $WEB_USER
listen = $FPM_SOCK
listen.owner = $WEB_USER
listen.group = $WEB_USER
listen.mode = 0660
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 4
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 12M
php_admin_value[date.timezone] = Asia/Kuala_Lumpur
php_admin_value[error_log] = /var/log/php-fpm/natnetwork-error.log
POOL
systemctl enable php-fpm >/dev/null 2>&1 || true
systemctl restart php-fpm
systemctl is-active --quiet php-fpm || { journalctl -u php-fpm -n 30 --no-pager; exit 1; }

step "8/8 Nginx vhost $DOMAIN + SSL"
VHOST=/etc/nginx/conf.d/$DOMAIN.conf
OTHER=$(grep -lE "server_name[^;]*\b$DOMAIN\b" /etc/nginx/conf.d/*.conf /etc/nginx/sites-enabled/* 2>/dev/null | grep -v "^$VHOST$" || true)
[ -n "$OTHER" ] && echo "AMARAN: $DOMAIN juga ditakrif dalam: $OTHER (tidak diubah)."
if [ ! -f "$VHOST" ] && [ -z "$OTHER" ]; then
  cat > "$VHOST" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;
    root $APP_DIR/public;
    index index.php;
    client_max_body_size 12M;
    charset utf-8;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php\$ {
        fastcgi_pass unix:$FPM_SOCK;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGINX
fi
if ! nginx -t; then echo "Konfigurasi Nginx gagal — vhost natnetwork dibuang, laman lain tidak terjejas."; rm -f "$VHOST"; nginx -t && systemctl reload nginx; exit 1; fi
systemctl reload nginx

if systemctl is-active --quiet firewalld; then
  for svc in http https; do firewall-cmd --quiet --query-service=$svc || firewall-cmd --permanent --add-service=$svc; done
  firewall-cmd --reload >/dev/null
fi

if grep -q "ssl_certificate" "$VHOST"; then
  echo "SSL sudah dipasang."
elif getent ahostsv4 "$DOMAIN" | awk '{print $1}' | grep -qx "$SERVER_IP"; then
  command -v certbot >/dev/null 2>&1 || dnf -y install certbot python3-certbot-nginx
  certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$CERT_EMAIL" --redirect
else
  echo "AMARAN: DNS $DOMAIN belum menghala ke $SERVER_IP. SSL dilangkau — jalankan semula skrip ini selepas DNS aktif."
  sed -i "s|^APP_URL=.*|APP_URL=http://$DOMAIN|" .env
  as_web php artisan config:cache
fi

step "SEO: robots.txt"
as_web php artisan seo:robots --stdout > public/robots.txt
chown root:"$WEB_USER" public/robots.txt && chmod 640 public/robots.txt
command -v restorecon >/dev/null 2>&1 && restorecon public/robots.txt || true
head -3 public/robots.txt

echo
echo "SIAP. Buka: $(grep '^APP_URL=' .env | cut -d= -f2)/admin/login"
if ! as_web php artisan tinker --execute 'echo App\Engines\Identity\Models\Admin::count();' 2>/dev/null | grep -qv '^0$'; then
  echo "Cipta admin:  cd $APP_DIR && runuser -u $WEB_USER -- php artisan admin:create emel@anda.com \"Nama\""
fi
