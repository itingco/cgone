CGOne ERP V4 - Optimized Update / Overlay
=========================================

Paket ini berisi SEMUA file baru dan file yang berubah untuk update V4.
Ini bukan salinan ulang seluruh source 3+ GB yang tidak berubah.

CARA PAKAI
1. Backup source dan database CGOne production terlebih dahulu.
2. Extract isi ZIP ini ke ROOT project CGOne yang sekarang.
3. Izinkan overwrite untuk file yang sudah ada.
4. JANGAN mengganti .env. Paket ini memang tidak menyertakan .env.
5. Jalankan dari root project:

   php artisan optimize:clear
   php artisan migrate
   php artisan db:seed --class="Database\\Seeders\\DistributorTechnicalToolsCoaSeeder"
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache

CATATAN COA
- Seeder COA distributor alat teknik hanya mengisi bila chart_of_accounts masih kosong.
- Jika COA existing sudah ada, seeder berhenti dan tidak menimpa data.
- Template referensi juga tersedia di database/templates/coa_distributor_alat_teknik.csv.

DETAIL
Lihat docs/V4_DEPLOYMENT.md, docs/V4_VERIFICATION.md, dan V4_UPDATE_MANIFEST.txt.
