@echo off
setlocal
cd /d "%~dp0"

REM =====================================================================
REM  KULINARA - nyalakan situs publik
REM
REM  Menyalakan server Laravel di 127.0.0.1:8000 dan memastikan tunnel
REM  Cloudflare tetap hidup. Jalankan berkas ini SETIAP kali laptop baru
REM  dinyalakan, karena `php artisan serve` tidak ikut autostart
REM  (beda dengan service cloudflared yang sudah Automatic).
REM
REM  Tanpa langkah ini gejalanya: tunnel hijau di dashboard, tapi
REM  situsnya menampilkan error 502.
REM =====================================================================

echo.
echo  [1/4] Cek service cloudflared...
sc query cloudflared | findstr /C:"RUNNING" >nul
if errorlevel 1 (
    echo        service belum jalan, mencoba menyalakan...
    net start cloudflared >nul 2>&1
    if errorlevel 1 (
        echo        GAGAL menyalakan service. Jalankan berkas ini
        echo        sebagai Administrator, lalu ulangi.
    ) else (
        echo        service cloudflared dinyalakan.
    )
) else (
    echo        service cloudflared sudah jalan.
)

echo.
echo  [2/4] Cek port 8000...
netstat -ano | findstr /C:"127.0.0.1:8000" | findstr /C:"LISTENING" >nul
if not errorlevel 1 (
    echo        port 8000 sudah dipakai - server Laravel sepertinya
    echo        sudah jalan. Tidak menyalakan yang baru.
    goto :tunnel
)

echo.
echo  [3/4] Menyalakan server Laravel di 127.0.0.1:8000...
echo        Server berjalan di jendela terpisah. JANGAN ditutup
echo        selama situs perlu hidup.
start "KULINARA - server" /min cmd /c "php artisan serve --host=127.0.0.1 --port=8000"

echo        menunggu server siap...
set /a tunggu=0
:waitloop
set /a tunggu+=1
ping -n 2 127.0.0.1 >nul
netstat -ano | findstr /C:"127.0.0.1:8000" | findstr /C:"LISTENING" >nul
if not errorlevel 1 goto :siap
if %tunggu% lss 20 goto :waitloop
echo        PERINGATAN: server belum terdeteksi setelah 20 detik.
goto :tunnel

:siap
echo        server Laravel siap.

:tunnel
echo.
echo  [4/4] Uji situs publik...
curl -s -o nul -w "        https://kulinara.kyronix.my.id -> HTTP %%{http_code} (%%{time_total}s)" --max-time 25 https://kulinara.kyronix.my.id/
echo.

echo.
echo  =====================================================================
echo   Selesai. Situs: https://kulinara.kyronix.my.id
echo.
echo   Catatan: kalau kamu menjalankan `npm run dev` untuk mengembangkan
echo   tampilan, berkas public/hot akan dibuat ulang dan situs publik
echo   akan tampil tanpa CSS/JS. Setelah selesai mengembangkan:
echo       npm run build   lalu hapus public\hot
echo  =====================================================================
echo.
pause
