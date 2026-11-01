@echo off
setlocal
cd /d "%~dp0"

REM =====================================================================
REM  KULINARA - bangun ulang tampilan
REM
REM  Jalankan berkas ini SETIAP kali selesai mengubah berkas tampilan
REM  (.jsx, .js, .css). Situs publik memakai hasil build di public/build,
REM  jadi tanpa langkah ini perubahanmu tidak terlihat sama sekali.
REM
REM  Berkas ini juga membuang public/hot, yang kalau dibiarkan akan
REM  membuat situs publik tampil tanpa CSS/JS sama sekali.
REM
REM  TIDAK perlu menjalankan ini kalau yang diubah cuma berkas .php.
REM =====================================================================

echo.
echo  =====================================================================
echo   KULINARA - bangun ulang tampilan
echo  =====================================================================

echo.
echo  [1/5] Cek npm tersedia...
where npm >nul 2>&1
if errorlevel 1 (
    echo        GAGAL: npm tidak ditemukan di PATH.
    echo        Pastikan Node.js terpasang.
    goto :gagal
)
echo        npm ditemukan.
set /a mati=0

echo.
echo  [2/5] Hentikan Vite dev server kalau sedang jalan...
REM Vite menulis ulang public/hot terus-menerus. Selama hidup, berkas itu
REM selalu kembali walaupun sudah dihapus, dan situs publik tetap rusak.
REM
REM Deteksi lewat PowerShell, bukan wmic: wmic sudah tidak disertakan pada
REM Windows versi baru, dan memakainya membuat skrip berhenti diam-diam.
REM Perintah ditulis dalam satu baris dengan kutip tunggal saja -- kutip
REM ganda bersarang tidak selamat melewati cmd.exe dan membuat parser
REM PowerShell error "The string is missing the terminator".
set "PSDETECT=Get-CimInstance Win32_Process -Filter 'Name=''node.exe''' | Where-Object { $_.CommandLine -like '*vite*' } | Select-Object -ExpandProperty ProcessId"
for /f %%p in ('powershell -NoProfile -Command "%PSDETECT%" 2^>nul') do (
    taskkill /pid %%p /f >nul 2>&1
    if not errorlevel 1 (
        echo        Vite dimatikan ^(PID %%p^).
        set /a mati+=1
    )
)
if %mati%==0 echo        Vite tidak sedang jalan.

echo.
echo  [3/5] Buang public\hot...
if exist "public\hot" (
    del /f /q "public\hot"
    if exist "public\hot" (
        echo        GAGAL menghapus. Tutup dulu proses yang memakainya.
        goto :gagal
    )
    echo        public\hot dihapus.
) else (
    echo        public\hot tidak ada.
)

echo.
echo  [4/5] Jalankan npm run build...
echo.
call npm run build
if errorlevel 1 (
    echo.
    echo        GAGAL: npm run build berakhir dengan error.
    echo        Baca pesan di atas untuk tahu berkas mana yang bermasalah.
    goto :gagal
)

echo.
echo  [5/5] Pastikan public\hot tidak muncul lagi setelah build...
if exist "public\hot" (
    echo        public\hot muncul lagi - ada Vite yang masih hidup.
    del /f /q "public\hot" >nul 2>&1
    echo        dihapus ulang.
) else (
    echo        bersih.
)

echo.
echo  =====================================================================
echo   Selesai. Tampilan sudah diperbarui.
echo.
echo   Buka https://kulinara.kyronix.my.id lalu tekan Ctrl+Shift+R
echo   untuk memaksa browser memuat ulang tanpa cache.
echo  =====================================================================
echo.
pause
exit /b 0

:gagal
echo.
echo  =====================================================================
echo   BERHENTI karena ada masalah di atas.
echo  =====================================================================
echo.
pause
exit /b 1
