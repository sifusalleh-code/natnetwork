@echo off
REM NatNetwork — bungkus projek dan hantar ke VPS
cd /d E:\NatNetwork
set ARCHIVE=%TEMP%\natnetwork.tar.gz
if exist "%ARCHIVE%" del "%ARCHIVE%"
echo Membungkus projek...
tar -czf "%ARCHIVE%" --exclude=./vendor --exclude=./node_modules --exclude=./.env --exclude=./database/database.sqlite --exclude=./storage/logs --exclude=./storage/framework --exclude=./storage/app --exclude=./bootstrap/cache --exclude=./deploy --exclude=./tests --exclude=./.phpunit.result.cache .
if errorlevel 1 ( echo Gagal membungkus. & exit /b 1 )
echo Menghantar ke VPS (masukkan password root bila diminta)...
scp -P 6262 "%ARCHIVE%" deploy\deploy.sh root@103.224.93.66:/root/
if errorlevel 1 ( echo Gagal menghantar. & exit /b 1 )
echo.
echo Siap dihantar. Seterusnya jalankan:
echo   ssh -p 6262 root@103.224.93.66 "bash /root/deploy.sh"
