# Catatan Presentasi: Refactoring Logika Menu Aplikasi Sherina Mart

## 1. Ringkasan Versi Lama
Pada versi sebelumnya (di GitHub), struktur navigasi menu aplikasi dibangun dengan pendekatan yang kurang terstruktur:
- **Linear/Flat**: Pilihan menu seringkali mengeksekusi perintah lalu berhenti.
- **Exit Sembarangan**: Perintah "Keluar" atau "Kembali" seringkali mematikan seluruh aplikasi (`exit;` atau `die;`) di tengah-tengah submenu.
- **Rekursif Tidak Aman**: Terkadang "Kembali" diimplementasikan dengan memanggil ulang fungsi `menu()` di dalam fungsi `menu()`, yang bisa menyebabkan memory leak (stack overflow) jika dilakukan berulang kali.

## 2. Masalah di Versi Lama
Beberapa masalah krusial yang ditemukan:
- **Unresponsive**: Saat user masuk ke submenu (misal: Daftar Kategori) dan ingin kembali, aplikasi justru tertutup atau diam (freeze) karena tidak ada loop yang menjaganya tetap hidup.
- **Frustrasi Pengguna**: User harus me-restart aplikasi setiap kali selesai melakukan satu aksi (misal: setelah menambah barang, aplikasi close).
- **Spaghetti Code**: Alur "Kembali" dan "Keluar" tercampur aduk, menyulitkan debugging.

## 3. Perubahan di Versi Terbaru
Kami telah merombak total logika navigasi (Refactor) dengan aturan ketat:
- **Nested `while(true)` Loops**:
  - **Menu Utama**: Dibungkus dalam loop abadi. Hanya berhenti jika user eksplisit memilih "Exit".
  - **Submenu (Transaksi, List, dll)**: Dibungkus dalam loop sendiri.
- **Strict `return` Policy**: Tombol "Kembali" di submenu TIDAK BOLEH melakukan `exit` atau memanggil `mainMenu()`. Ia hanya melakukan `return;` agar program kembali secara alami ke loop Menu Utama (Parent Loop).
- **Global Error Handling**: Loop utama dilengkapi `try-catch` agar error di satatu bagian tidak membunuh seluruh aplikasi.

## 4. Manfaat Perubahan
- **Always On**: Aplikasi terasa seperti software profesional yang selalu siap menerima perintah selanjutnya.
- **Navigasi Natural**: User bisa masuk ke "Transaksi", batal, lalu masuk ke "Stok Barang", tanpa restart aplikasi.
- **Maintainability**: Kode lebih mudah dibaca karena blok logika terisolasi dalam fungsinya masing-masing.
- **Safety**: Mencegah data hilang akibat aplikasi tertutup tidak sengaja saat salah navigasi.

## 5. Kekurangan Aplikasi Setelah Update
- **Kompleksitas Kode**: Bertambahnya baris kode karena pembungkusan `while` dan komentar penjelasan.
- **Resource Usage**: Meskipun sangat kecil, aplikasi CLI yang berjalan terus menerus dalam loop tanpa henti membutuhkan manajemen memori yang baik (sudah ditangani dengan `return` yang membersihkan scope fungsi).
