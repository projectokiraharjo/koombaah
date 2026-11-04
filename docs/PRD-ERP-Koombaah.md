# PRD — Sistem Internal Koombaah (CV Khia Nawasena)

**Dokumen ini merupakan Product Requirements Document untuk Sistem Internal (ERP-style) Koombaah**, disusun sebagai acuan pengembangan sekaligus artefak akademis dalam penelitian skripsi dengan metode Prototype Development.

---

## A. Bagian Overview

### A.1 Ringkasan Eksekutif

CV Khia Nawasena (brand: **Koombaah**) adalah mitra operasional bagi pelaku usaha laundry kiloan skala kecil-menengah (Tier 2, 2–5 mesin) di wilayah Jabodetabek (Ring 1: Pamulang, Ciputat, Sawangan; Ring 2: Pondok Aren, Cinere; Ring 3: Serpong/BSD). Berbeda dari distributor kimia konvensional, Koombaah memproduksi bahan kimia pembersih sendiri, menyediakan aplikasi SaaS manajemen untuk mitra, dan melakukan pendampingan langsung ke lapangan.

Saat ini seluruh operasional internal — mulai dari pembelian bahan baku, produksi, pemenuhan order, pengiriman, keuangan, hingga data karyawan — **belum memiliki sistem terpusat**. Proses berjalan manual tanpa pencatatan digital yang terstruktur, sehingga menyulitkan pengambilan keputusan berbasis data, terutama saat skala bisnis bertambah.

Dokumen ini mendefinisikan kebutuhan **Sistem Internal Koombaah**, sebuah aplikasi berbasis web yang berdiri sendiri (standalone), terpisah dari SaaS partner-facing yang digunakan mitra. Sistem ini mencakup 8 modul: BOM & Inventory, Purchasing/Procurement, Produksi, Sales & Order Fulfillment, Distribusi/Pengiriman, Keuangan, SDM, serta Dashboard & Reporting.

Karena belum ada data historis atau sistem pencatatan digital sebelumnya, seluruh struktur data dalam dokumen ini dirancang dari nol (*greenfield*), berdasarkan alur bisnis riil Koombaah dan proyeksi skala operasional dalam 6–12 bulan ke depan.

### A.2 Tujuan & Sasaran

**Tujuan bisnis:**
- Menggantikan proses pencatatan manual dengan sistem terpusat yang mengurangi human error dan mempercepat operasional harian.
- Memberikan visibilitas data real-time kepada founder untuk pengambilan keputusan (margin per produk, efisiensi distribusi per wilayah, dll).
- Membangun fondasi sistem yang dapat diskalakan seiring pertumbuhan jumlah mitra dan volume produksi.

**Tujuan akademis (konteks skripsi):**
- Dokumen ini menjadi bagian dari tahap **analisis kebutuhan (requirements analysis)** dalam metode **Prototype Development**, yang akan dilanjutkan ke tahap perancangan prototipe, evaluasi, dan iterasi berdasarkan umpan balik pengguna (dalam hal ini founder/pengguna internal Koombaah sendiri, mengingat skala tim saat ini).
- Seluruh 8 modul dalam dokumen ini **akan diprototipekan secara penuh**, bukan hanya direncanakan — sehingga PRD ini harus cukup rinci (hingga level field/tabel) untuk langsung diterjemahkan menjadi rancangan basis data dan antarmuka prototipe.

### A.3 Lingkup (Scope)

**Termasuk dalam lingkup:**
- 8 modul: BOM & Inventory, Purchasing/Procurement, Produksi, Sales & Order Fulfillment, Distribusi/Pengiriman, Keuangan, SDM, Dashboard & Reporting.
- Pengelolaan data internal perusahaan (bukan data yang diakses langsung oleh mitra laundry).
- Prototipe fungsional untuk seluruh modul, sesuai kebutuhan pembuktian metode Prototype Development dalam skripsi.

**Di luar lingkup (out of scope):**
- Aplikasi SaaS partner-facing (aplikasi yang digunakan mitra laundry untuk order, laporan keuangan mitra, dan absensi karyawan mitra) — dokumen ini **tidak** merancang ulang sistem tersebut.
- Integrasi otomatis/real-time antara Sistem Internal dan SaaS partner-facing. Sistem ini dirancang **standalone**; titik-titik potensi integrasi dicatat sebagai catatan untuk pengembangan lanjutan (lihat A.6), bukan dirancang detail di PRD ini.
- Modul Pendampingan/CRM internal, Manajemen Aset, dan Compliance/Perizinan — telah dipertimbangkan namun diputuskan untuk tidak dimasukkan pada fase ini.
- Aplikasi mobile native — sistem berbasis web (PWA), bukan aplikasi native iOS/Android terpisah.

### A.4 Pengguna Sistem & Role

Sistem dirancang dengan **3 role**, mendukung struktur tim saat ini (dipegang solo oleh Owner) sekaligus siap dipakai saat perusahaan merekrut staf ke depannya.

| Role | Deskripsi | Cakupan Akses |
|---|---|---|
| **Owner** (Founder) | Pemilik bisnis, akses penuh ke seluruh modul | Semua modul tanpa pembatasan, termasuk data gaji karyawan, laporan laba-rugi, dan pengaturan sistem |
| **Admin** | Menjalankan operasional harian (input data produksi, purchasing, order, distribusi) | BOM & Inventory, Purchasing (**termasuk harga beli dari supplier**), Produksi, Sales & Order Fulfillment, Distribusi — **tanpa** akses ke modul SDM (data gaji) dan laporan laba-rugi di modul Keuangan |
| **Admin Keuangan** | Mengelola pencatatan keuangan perusahaan | Modul Keuangan penuh (AR/AP, arus kas, HPP, laba-rugi) — **tanpa** akses ke modul SDM (data gaji karyawan tetap eksklusif untuk Owner) |

**Catatan implementasi:**
- Data gaji (modul SDM) hanya dapat diakses oleh **Owner**. Admin Keuangan mengelola arus kas/pembukuan perusahaan namun tidak melihat rincian gaji per individu — 🔶 [Perlu diperjelas saat masuk ke Bagian B Modul Keuangan & SDM: bagaimana biaya payroll tetap masuk ke perhitungan laba-rugi tanpa Admin Keuangan melihat rincian gaji per karyawan — kemungkinan solusi: payroll masuk sebagai satu angka agregat "Beban Gaji" di laporan laba-rugi, bukan rincian per orang].
- Modul **Distribusi/Pengiriman** tidak memiliki role/akun terpisah untuk kurir. Update status pengiriman (misal "sudah dikirim", "diterima mitra") diinput secara manual oleh **Admin**, bukan oleh kurir langsung.
- Karena saat ini seluruh role masih dipegang sendiri oleh Owner, sistem role & permission tetap dibangun sejak fase prototipe awal (bukan ditunda) — supaya struktur akses sudah teruji saat nanti staf pertama direkrut, tanpa perlu refactor besar di kemudian hari.

### A.5 Arsitektur Tingkat Tinggi

Sistem Internal Koombaah dirancang sebagai **satu aplikasi web monolitik** (bukan microservices, sesuai konteks solo developer), dengan modul-modul yang saling terhubung melalui satu basis data bersama (PostgreSQL). Alur data mengikuti proses bisnis dari hulu ke hilir:

```
┌─────────────────┐      ┌──────────────────┐
│   Purchasing/    │─────▶│  BOM & Inventory  │
│   Procurement    │      │  (Bahan Baku)     │
└─────────────────┘      └────────┬──────────┘
                                    │
                                    ▼
                          ┌──────────────────┐
                          │     Produksi      │
                          │  (Barang Jadi)    │
                          └────────┬──────────┘
                                    │
                                    ▼
┌─────────────────┐      ┌──────────────────┐      ┌──────────────────┐
│   Sales &        │◀─────│  BOM & Inventory  │      │   Distribusi /   │
│   Order          │─────▶│  (Stok Jadi)      │      │   Pengiriman     │
│   Fulfillment    │      └──────────────────┘      └──────────────────┘
└────────┬─────────┘                                          ▲
         │                                                     │
         └─────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────┐      ┌──────────────────┐
│    Keuangan      │◀─────│       SDM         │
│  (HPP, AR/AP,    │      │  (Payroll →       │
│   Laba-Rugi)     │      │   Biaya Operasi)  │
└────────┬─────────┘      └──────────────────┘
         │
         ▼
┌──────────────────────────────────┐
│     Dashboard & Reporting         │
│  (Agregasi lintas seluruh modul)  │
└──────────────────────────────────┘
```

**Prinsip arsitektur:**
- Setiap modul memiliki tabel data sendiri, namun saling terhubung lewat *foreign key* (misal: Produksi merujuk ke BOM, Sales merujuk ke Stok dari Inventory).
- Modul **Dashboard & Reporting** tidak memiliki data sendiri — murni agregasi/query dari modul lain.
- Modul **SDM** relatif independen dari alur produksi-penjualan, namun terhubung ke **Keuangan** lewat data payroll sebagai komponen biaya operasional.
- Stack: **Laravel (Blade + Livewire)**, **PostgreSQL**, PWA via **silviolleite/laravelpwa**, hosting di **Niagahoster**.

### A.6 Asumsi & Batasan

**Asumsi:**
- Tidak ada data historis/existing (Excel atau pencatatan manual terstruktur) yang menjadi acuan migrasi data — seluruh struktur data dirancang dari nol.
- Skala data dikalibrasi untuk target: **±50 mitra aktif**, **10 jenis produk kimia**, **±100 order/bulan**. Ini bukan skala enterprise — desain sistem mengutamakan kesederhanaan dan kemudahan maintenance oleh satu developer, bukan optimasi untuk skala besar.
- Sistem dikembangkan dan dikelola oleh **satu developer (solo)**, sehingga kompleksitas teknis (jumlah service, layer abstraksi, dsb) sengaja diminimalkan.
- Pengembangan berjalan paralel dengan penyelesaian skripsi, dengan **tenggat penyelesaian 31 Januari 2027**. Seluruh 8 modul perlu mencapai tahap prototipe fungsional sebelum tenggat tersebut, sehingga urutan pengerjaan pada Bagian Roadmap (C.2) disusun dengan mempertimbangkan sisa waktu yang tersedia dari sekarang hingga Januari 2027.

**Batasan:**
- Sistem ini **tidak** terintegrasi otomatis dengan SaaS partner-facing pada fase ini. Titik potensi integrasi di masa depan (dicatat untuk referensi, bukan dirancang sekarang):
  - Data order dari mitra (saat ini diinput manual oleh Admin di modul Sales & Order Fulfillment) — berpotensi ditarik otomatis dari SaaS partner-facing di masa depan.
  - Data mitra (nama, alamat, kontak) — saat ini akan disimpan sebagai data master sederhana di modul Sales, berpotensi disinkronkan dengan data mitra di SaaS partner-facing ke depannya.
- Modul Pendampingan/CRM, Aset, dan Compliance tidak termasuk — jika dibutuhkan pencatatan ringan terkait pendampingan mitra, untuk sementara dapat dicatat sebagai catatan bebas (notes field) di modul Sales & Order Fulfillment, bukan modul terpisah.
- Karena tim saat ini masih solo, desain role & permission (lihat A.4) perlu dibangun fleksibel namun **tidak perlu over-engineered** di fase awal — cukup mendukung 3 role yang sudah diidentifikasi.

---

**Status dokumen:** Bagian A (Overview) — final. Bagian B sedang berjalan (Modul 1 selesai, 7 modul tersisa).

---

## B. Bagian Per Modul

## B.1 Modul BOM & Inventory

### B.1.1 Tujuan Modul

Modul ini menjadi fondasi data bagi seluruh alur produksi — mendefinisikan **resep (formula)** setiap produk kimia yang diproduksi Koombaah, serta melacak **stok bahan baku** (bahan mentah pembuat kimia) dan **stok barang jadi** (produk kimia siap jual). Tanpa modul ini, modul Produksi tidak bisa menghitung kebutuhan bahan, dan modul Sales tidak bisa memvalidasi ketersediaan stok.

### B.1.2 Ruang Lingkup Fitur

- Manajemen data master **Bahan Baku** (raw material)
- Manajemen data master **Produk Jadi** (10 jenis produk kimia)
- Manajemen **BOM (Bill of Material)** — resep/komposisi setiap produk jadi, terdiri dari bahan baku + takaran
- Pencatatan **stok bahan baku** (masuk dari Purchasing, keluar terpakai Produksi)
- Pencatatan **stok barang jadi** (masuk dari Produksi, keluar terjual via Sales)
- Notifikasi/indikator **stok menipis** (low stock alert) untuk bahan baku maupun barang jadi
- Riwayat pergerakan stok (stock movement log) — audit trail siapa mengubah stok apa dan kapan
- Versi BOM — jika resep produk berubah di kemudian hari, versi lama tetap tersimpan untuk keperluan histori produksi

### B.1.3 User Stories

1. Sebagai **Owner**, saya ingin mendefinisikan resep (BOM) untuk setiap produk kimia, sehingga sistem tahu persis bahan baku apa dan berapa takaran yang dibutuhkan untuk memproduksi satu batch.
2. Sebagai **Admin**, saya ingin mencatat data bahan baku baru (nama, satuan, harga per satuan), sehingga bahan baku tersebut bisa dipakai dalam BOM dan dilacak stoknya.
3. Sebagai **Admin**, saya ingin melihat stok bahan baku yang tersisa saat ini, sehingga saya tahu kapan harus melakukan pembelian ulang ke supplier.
4. Sebagai **Admin**, saya ingin mendapat notifikasi saat stok bahan baku atau barang jadi berada di bawah ambang batas tertentu, sehingga saya tidak kehabisan stok secara mendadak.
5. Sebagai **Owner**, saya ingin melihat riwayat perubahan BOM suatu produk (versi lama vs baru), sehingga saya bisa menelusuri kapan dan mengapa formula produk berubah.
6. Sebagai **Admin**, saya ingin sistem otomatis mengurangi stok bahan baku saat ada batch produksi baru dijalankan, sehingga saya tidak perlu mengurangi stok secara manual.
7. Sebagai **Admin**, saya ingin sistem otomatis menambah stok barang jadi saat batch produksi selesai dan lolos QC, sehingga stok barang jadi selalu akurat.
8. Sebagai **Owner**, saya ingin melihat riwayat pergerakan stok (siapa mengubah, kapan, alasan apa) untuk satu item tertentu, sehingga saya bisa mengaudit jika ada selisih stok yang mencurigakan.

### B.1.4 Alur Proses (Flow)

**A. Alur Pembuatan BOM (dilakukan Owner, jarang berubah)**
1. Owner membuat/memilih data master Produk Jadi.
2. Owner menambahkan daftar bahan baku beserta takaran yang dibutuhkan untuk memproduksi 1 satuan/1 batch produk tersebut.
3. Sistem menyimpan BOM sebagai versi aktif (v1). Jika di kemudian hari resep diubah, sistem membuat versi baru (v2) dan menandai v1 sebagai non-aktif namun tetap tersimpan untuk histori.

**B. Alur Pengurangan Stok Bahan Baku (trigger dari Modul Produksi)**
1. Modul Produksi memulai batch produksi baru, merujuk ke BOM versi aktif suatu produk.
2. Sistem menghitung total kebutuhan bahan baku berdasarkan jumlah batch yang akan diproduksi × takaran di BOM.
3. Sistem memvalidasi apakah stok bahan baku mencukupi. Jika tidak mencukupi, sistem menampilkan peringatan dan mencegah batch dimulai (🔶 lihat Aturan Bisnis).
4. Jika mencukupi, sistem mengurangi stok bahan baku dan mencatat pergerakan stok (stock movement) dengan referensi ke batch produksi terkait.

**C. Alur Penambahan Stok Barang Jadi (trigger dari Modul Produksi)**
1. Batch produksi selesai dan lolos QC di Modul Produksi.
2. Sistem menambah stok barang jadi sesuai jumlah output batch, mencatat pergerakan stok dengan referensi ke batch produksi.

**D. Alur Pengurangan Stok Barang Jadi (trigger dari Modul Sales)**
1. Order dari mitra dikonfirmasi di Modul Sales & Order Fulfillment.
2. Sistem mengurangi stok barang jadi sesuai jumlah yang dipesan, mencatat pergerakan stok dengan referensi ke order terkait.

### B.1.5 Kebutuhan Data / Field-Level

**Tabel: `raw_materials` (Bahan Baku)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| kode_bahan | string, unique | Kode internal bahan baku, mis. "BB-001" |
| nama_bahan | string | Nama bahan baku |
| satuan | string (enum) | Satuan ukur: kg, liter, gram, ml, dll |
| harga_per_satuan_terakhir | decimal | Harga beli terakhir per satuan (referensi, diupdate dari Purchasing) |
| stok_saat_ini | decimal | Jumlah stok tersedia saat ini |
| stok_minimum | decimal | Ambang batas untuk trigger low stock alert |
| supplier_utama_id | FK → suppliers.id | Referensi ke modul Purchasing |
| status | string (enum) | aktif / nonaktif |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `finished_products` (Produk Jadi)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| kode_produk | string, unique | Kode internal produk, mis. "PRD-001" |
| nama_produk | string | Nama produk kimia |
| kategori | string | mis. deterjen, pewangi, pemutih, dll |
| satuan_jual | string (enum) | liter, kg, pcs, dll |
| harga_jual_per_satuan | decimal | Harga jual ke mitra |
| stok_saat_ini | decimal | Jumlah stok barang jadi tersedia |
| stok_minimum | decimal | Ambang batas untuk trigger low stock alert |
| status | string (enum) | aktif / nonaktif |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `bom_headers` (Header BOM per Produk, mendukung versioning)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| finished_product_id | FK → finished_products.id | Produk yang dimaksud |
| versi | integer | Nomor versi BOM (1, 2, 3, dst) |
| status | string (enum) | aktif / nonaktif — hanya satu versi aktif per produk |
| catatan_perubahan | text, nullable | Alasan perubahan versi (jika ada) |
| created_by | FK → users.id | Siapa yang membuat/mengubah |
| created_at | timestamp | Kapan versi ini dibuat |

**Tabel: `bom_details` (Item Bahan Baku dalam satu BOM)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| bom_header_id | FK → bom_headers.id | Merujuk ke header BOM |
| raw_material_id | FK → raw_materials.id | Bahan baku yang dipakai |
| takaran | decimal | Jumlah bahan baku dibutuhkan per 1 satuan/batch produk |
| satuan | string | Konsisten dengan satuan di raw_materials |

**Tabel: `stock_movements` (Log Pergerakan Stok — berlaku untuk bahan baku & barang jadi)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| item_type | string (enum) | "raw_material" / "finished_product" |
| item_id | bigint (polymorphic FK) | Merujuk ke raw_materials.id atau finished_products.id |
| tipe_pergerakan | string (enum) | masuk / keluar |
| jumlah | decimal | Jumlah yang berubah |
| referensi_tipe | string (enum) | "purchasing" / "produksi" / "sales" / "penyesuaian_manual" |
| referensi_id | bigint, nullable | ID transaksi terkait (PO, batch produksi, order, dll) |
| catatan | text, nullable | Keterangan tambahan, terutama untuk penyesuaian manual |
| dilakukan_oleh | FK → users.id | User yang melakukan aksi |
| created_at | timestamp | Kapan pergerakan terjadi |

### B.1.6 Relasi ke Modul Lain

- **Membutuhkan data dari:** Modul Purchasing (harga & data supplier bahan baku), Modul Produksi (trigger pengurangan bahan baku & penambahan barang jadi), Modul Sales & Order Fulfillment (trigger pengurangan barang jadi).
- **Datanya dipakai oleh:** Modul Produksi (validasi ketersediaan bahan sebelum batch dimulai), Modul Sales & Order Fulfillment (validasi ketersediaan stok sebelum order dikonfirmasi), Modul Keuangan (nilai stok untuk perhitungan HPP), Modul Dashboard & Reporting (laporan stok, produk paling laku, dll).

### B.1.7 Aturan Bisnis (Business Rules)

- Satu produk jadi hanya boleh memiliki **satu BOM versi aktif** pada satu waktu, dan **satu resep tetap** per produk (tidak ada varian BOM ganda untuk kebutuhan custom).
- Jika stok bahan baku tidak mencukupi kebutuhan suatu batch produksi, sistem **memblokir total** — batch produksi tidak dapat dimulai sampai stok bahan baku tersedia (via Purchasing) atau jumlah batch dikurangi.
- Ambang batas *stok minimum* (untuk notifikasi low stock) **diisi manual** per item oleh Admin/Owner — tidak ada perhitungan otomatis berbasis rata-rata pemakaian pada fase ini.
- Penyesuaian stok manual (misal karena bahan rusak, kadaluarsa, atau selisih stok fisik) harus selalu tercatat di `stock_movements` dengan `referensi_tipe = "penyesuaian_manual"` dan wajib diisi catatan alasan — tidak boleh mengubah angka stok langsung tanpa log.
- Perubahan BOM (versi baru) hanya dapat dilakukan oleh role **Owner** — Admin tidak memiliki akses untuk mengubah resep produk, sesuai pembagian akses di A.4.

### B.1.8 Catatan untuk Fase Prototipe

Data komposisi/resep untuk 10 produk kimia **akan dirancang dari nol** saat masuk fase prototipe (belum ada draf resep existing). Implikasinya:
- Saat pembuatan prototipe, data master `finished_products` dan `bom_details` akan diisi dengan data dummy/percobaan terlebih dahulu untuk menguji fungsi sistem (tambah bahan baku → hitung kebutuhan → kurangi stok, dst).
- Resep riil (komposisi kimia aktual) dapat diinput belakangan begitu formulasi produk final ditentukan — struktur tabel BOM di atas sudah mengakomodasi ini tanpa perlu perubahan skema.

---

**Status dokumen:** Bagian A (Overview) — final. Bagian B: Modul 1 (BOM & Inventory) — final. Modul 2 sedang berjalan.

---

## B.2 Modul Purchasing / Procurement

### B.2.1 Tujuan Modul

Modul ini mengelola proses pembelian bahan baku dari supplier — mulai dari data master supplier, pembuatan Purchase Order (PO), hingga pencatatan penerimaan barang yang memicu penambahan stok bahan baku di Modul BOM & Inventory. Modul ini juga menjadi sumber data harga beli aktual yang dipakai untuk perhitungan HPP di Modul Keuangan.

### B.2.2 Ruang Lingkup Fitur

- Manajemen data master **Supplier** (nama, kontak, alamat, bahan baku yang disuplai)
- Pembuatan dan pengelolaan **Purchase Order (PO)** ke supplier
- Pencatatan **status PO** (draft, dipesan, sebagian diterima, diterima penuh, dibatalkan)
- Pencatatan **penerimaan barang** (goods receipt) — memicu penambahan stok bahan baku
- Riwayat harga beli per bahan baku per supplier (untuk perbandingan harga antar supplier)
- Pencatatan **utang ke supplier** (jika pembelian dilakukan secara kredit/tempo) — data ini disediakan untuk Modul Keuangan (AP/Account Payable)

### B.2.3 User Stories

1. Sebagai **Admin**, saya ingin mencatat data supplier baru (nama, kontak, bahan baku yang disuplai), sehingga saya bisa membuat PO ke supplier tersebut.
2. Sebagai **Admin**, saya ingin membuat Purchase Order berisi daftar bahan baku dan jumlah yang dipesan, sehingga saya punya dokumen resmi pemesanan ke supplier.
3. Sebagai **Admin**, saya ingin melihat daftar PO yang statusnya masih "dipesan" (belum diterima), sehingga saya bisa memantau kapan barang akan datang.
4. Sebagai **Admin**, saya ingin mencatat penerimaan barang saat bahan baku tiba, sehingga stok bahan baku otomatis bertambah di Modul BOM & Inventory.
5. Sebagai **Admin**, saya ingin mencatat penerimaan barang secara sebagian (partial receipt) jika supplier hanya mengirim sebagian dari PO, sehingga sistem tahu sisa barang yang masih ditunggu.
6. Sebagai **Owner**, saya ingin melihat riwayat harga beli suatu bahan baku dari beberapa supplier berbeda, sehingga saya bisa membandingkan dan memilih supplier dengan harga terbaik.
7. Sebagai **Admin Keuangan**, saya ingin melihat daftar PO yang dibeli secara kredit/tempo beserta jatuh temponya, sehingga saya bisa merencanakan pembayaran ke supplier tepat waktu.
8. Sebagai **Owner**, saya ingin melihat total pembelian per supplier dalam periode tertentu, sehingga saya bisa mengevaluasi supplier mana yang paling sering/banyak dipakai.

### B.2.4 Alur Proses (Flow)

**A. Alur Pembuatan PO**
1. Admin memilih supplier dan menambahkan daftar bahan baku beserta jumlah dan harga per satuan yang disepakati.
2. Sistem menghitung total nilai PO secara otomatis.
3. Admin menetapkan metode pembayaran: tunai (cash) atau kredit/tempo (dengan tanggal jatuh tempo).
4. PO disimpan dengan status "dipesan".

**B. Alur Penerimaan Barang**
1. Saat barang tiba, Admin membuka PO terkait dan mencatat jumlah barang yang benar-benar diterima per item (bisa penuh atau sebagian).
2. Sistem memicu penambahan stok bahan baku di Modul BOM & Inventory sesuai jumlah diterima, dengan referensi ke PO tersebut (lihat `stock_movements` di B.1.5).
3. Sistem memperbarui `harga_per_satuan_terakhir` di data master bahan baku (`raw_materials`) berdasarkan harga di PO ini.
4. Jika seluruh item dalam PO sudah diterima penuh, status PO berubah menjadi "diterima penuh". Jika masih ada sisa, status menjadi "sebagian diterima".

**C. Alur Pencatatan Utang (jika pembelian kredit)**
1. Jika metode pembayaran PO adalah kredit/tempo, sistem mencatat data ini sebagai utang usaha (Account Payable) yang akan tampil di Modul Keuangan.
2. Saat pembayaran ke supplier dilakukan (dicatat di Modul Keuangan), status utang PO ini diperbarui menjadi lunas.

### B.2.5 Kebutuhan Data / Field-Level

**Tabel: `suppliers` (Data Master Supplier)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| kode_supplier | string, unique | Kode internal, mis. "SUP-001" |
| nama_supplier | string | Nama perusahaan/perorangan supplier |
| kontak_person | string, nullable | Nama kontak |
| no_telepon | string, nullable | Nomor telepon/WA |
| alamat | text, nullable | Alamat supplier |
| status | string (enum) | aktif / nonaktif |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `purchase_orders` (Header PO)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| nomor_po | string, unique | Nomor PO, mis. "PO-2026-0001" |
| supplier_id | FK → suppliers.id | Supplier tujuan |
| tanggal_po | date | Tanggal PO dibuat |
| metode_pembayaran | string (enum) | tunai / kredit |
| tanggal_jatuh_tempo | date, nullable | Wajib diisi jika metode = kredit |
| status | string (enum) | draft / dipesan / sebagian_diterima / diterima_penuh / dibatalkan |
| total_nilai_po | decimal | Total nilai PO (akumulasi dari `purchase_order_items`) |
| status_pembayaran | string (enum) | belum_dibayar / lunas — relevan jika kredit |
| dibuat_oleh | FK → users.id | Admin yang membuat PO |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `purchase_order_items` (Detail Item dalam PO)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| purchase_order_id | FK → purchase_orders.id | Merujuk ke header PO |
| raw_material_id | FK → raw_materials.id | Bahan baku yang dipesan |
| jumlah_dipesan | decimal | Jumlah yang dipesan |
| jumlah_diterima | decimal, default 0 | Jumlah yang sudah diterima (untuk partial receipt) |
| harga_per_satuan | decimal | Harga beli disepakati per satuan |
| subtotal | decimal | jumlah_dipesan × harga_per_satuan |

**Tabel: `goods_receipts` (Log Penerimaan Barang)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| purchase_order_id | FK → purchase_orders.id | PO terkait |
| tanggal_terima | date | Tanggal barang diterima |
| diterima_oleh | FK → users.id | Admin yang mencatat penerimaan |
| catatan | text, nullable | Keterangan tambahan (misal: kondisi barang) |
| created_at | timestamp | Standar audit |

**Tabel: `goods_receipt_items` (Detail Item yang Diterima per Penerimaan)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| goods_receipt_id | FK → goods_receipts.id | Merujuk ke header penerimaan |
| purchase_order_item_id | FK → purchase_order_items.id | Item PO yang diterima |
| jumlah_diterima | decimal | Jumlah diterima pada transaksi ini |

### B.2.6 Relasi ke Modul Lain

- **Membutuhkan data dari:** Modul BOM & Inventory (data master bahan baku yang bisa dipesan).
- **Datanya dipakai oleh:** Modul BOM & Inventory (trigger penambahan stok bahan baku via `stock_movements`, update harga terakhir), Modul Keuangan (data utang/Account Payable dari PO kredit, data harga beli untuk perhitungan HPP), Modul Dashboard & Reporting (laporan pembelian per supplier, tren harga bahan baku).

### B.2.7 Aturan Bisnis (Business Rules)

- PO dengan status "draft" belum memicu apa pun di modul lain — hanya menjadi catatan sementara sebelum dikonfirmasi menjadi "dipesan".
- Penerimaan barang (goods receipt) **tidak dapat melebihi** jumlah yang dipesan di PO untuk item terkait (`jumlah_diterima` pada `purchase_order_items` tidak boleh > `jumlah_dipesan`).
- Update `harga_per_satuan_terakhir` di `raw_materials` terjadi otomatis setiap kali ada penerimaan barang baru — sehingga harga yang tercatat selalu mencerminkan transaksi pembelian paling akhir.
- **Admin dapat membuat dan mengeksekusi PO secara mandiri** tanpa perlu approval berjenjang dari Owner — tidak ada ambang batas nilai yang memerlukan persetujuan tambahan pada fase ini.
- **Seluruh pembelian bahan baku dilakukan secara tunai.** Field `metode_pembayaran` dan `tanggal_jatuh_tempo` pada tabel `purchase_orders` tetap dipertahankan dalam skema untuk fleksibilitas di masa depan, namun pada fase prototipe nilainya akan selalu "tunai" dan alur pencatatan utang (Account Payable) di Modul Keuangan **tidak perlu dibangun di fase awal** — cukup dicatat sebagai pengurang kas langsung saat transaksi.
- **Perbandingan harga antar supplier untuk satu bahan baku yang sama dibutuhkan.** Field `supplier_utama_id` pada tabel `raw_materials` (B.1.5) tetap dipertahankan sebagai *supplier default/rujukan* untuk mempercepat pembuatan PO baru, namun tidak membatasi satu bahan baku hanya bisa dibeli dari satu supplier. Riwayat harga per supplier untuk satu bahan baku yang sama dapat ditelusuri melalui kombinasi `raw_material_id` (di `purchase_order_items`) dan `supplier_id` (di `purchase_orders`) pada transaksi-transaksi sebelumnya — tidak memerlukan tabel tambahan, cukup query/laporan yang menampilkan riwayat ini di Modul Dashboard & Reporting.

---

**Status dokumen:** Bagian A (Overview) — final. Bagian B: Modul 1 (BOM & Inventory) — final. Modul 2 (Purchasing/Procurement) — final. Modul 3 sedang berjalan.

---

## B.3 Modul Produksi

### B.3.1 Tujuan Modul

Modul ini mencatat eksekusi produksi — proses nyata mengubah bahan baku menjadi produk kimia jadi, berdasarkan resep yang sudah didefinisikan di Modul BOM & Inventory. Modul ini menjadi jembatan antara "resep di atas kertas" (BOM) dan kenyataan produksi di lapangan (batch, tanggal, hasil, QC), sekaligus pemicu utama perubahan stok bahan baku dan barang jadi.

### B.3.2 Ruang Lingkup Fitur

- Pembuatan **batch produksi baru**, merujuk ke BOM versi aktif suatu produk
- Validasi otomatis ketersediaan bahan baku sebelum batch dapat dimulai (sesuai aturan blokir total di B.1.7)
- Pencatatan **hasil produksi** (jumlah output aktual, yang bisa saja berbeda dari target karena penyusutan/proses)
- Pencatatan **QC sederhana** — status lolos/reject per batch
- Penambahan otomatis stok barang jadi saat batch lolos QC
- Riwayat batch produksi per produk (untuk keperluan audit dan analisis produktivitas)
- Pencatatan biaya tenaga kerja produksi per batch (opsional, untuk kalkulasi HPP lebih akurat) — 🔶 lihat Open Questions

### B.3.3 User Stories

1. Sebagai **Admin**, saya ingin membuat batch produksi baru untuk suatu produk dengan menentukan jumlah yang ingin diproduksi, sehingga sistem menghitung otomatis kebutuhan bahan baku berdasarkan BOM.
2. Sebagai **Admin**, saya ingin sistem memvalidasi ketersediaan bahan baku sebelum batch dimulai, sehingga saya tidak memulai produksi yang bahannya tidak cukup.
3. Sebagai **Admin**, saya ingin mencatat tanggal mulai dan tanggal selesai suatu batch produksi, sehingga saya bisa melacak berapa lama proses produksi berlangsung.
4. Sebagai **Admin**, saya ingin mencatat jumlah output aktual dari suatu batch (yang mungkin berbeda dari target), sehingga stok barang jadi mencerminkan hasil produksi yang sebenarnya.
5. Sebagai **Admin**, saya ingin menandai status QC suatu batch (lolos/reject), sehingga hanya produk yang lolos QC yang masuk ke stok barang jadi siap jual.
6. Sebagai **Admin**, saya ingin mencatat alasan jika suatu batch di-reject QC, sehingga ada dokumentasi untuk evaluasi kualitas produksi ke depannya.
7. Sebagai **Owner**, saya ingin melihat riwayat seluruh batch produksi suatu produk (tanggal, jumlah, status QC), sehingga saya bisa menganalisis konsistensi kualitas dan produktivitas produksi.
8. Sebagai **Owner**, saya ingin melihat total produk yang diproduksi dalam periode tertentu, sehingga saya bisa membandingkannya dengan volume penjualan untuk perencanaan produksi berikutnya.

### B.3.4 Alur Proses (Flow)

1. Admin membuka Modul Produksi dan memilih produk yang ingin diproduksi beserta jumlah target output.
2. Sistem mengambil BOM versi aktif produk tersebut dan menghitung total kebutuhan bahan baku (takaran BOM × jumlah target).
3. Sistem memvalidasi stok bahan baku. Jika tidak mencukupi, batch **tidak dapat dimulai** (sesuai aturan bisnis B.1.7) — Admin diarahkan untuk melakukan pembelian tambahan lewat Modul Purchasing terlebih dahulu.
4. Jika stok mencukupi, Admin mengonfirmasi mulai produksi. Sistem membuat record batch produksi dengan status "sedang berjalan" dan mengurangi stok bahan baku sesuai kebutuhan (trigger ke `stock_movements`, referensi_tipe = "produksi").
5. Setelah produksi selesai secara fisik, Admin menutup batch dengan mencatat jumlah output aktual, dipecah menjadi **jumlah yang lolos QC** dan **jumlah yang reject QC** (partial pass didukung — tidak harus all-or-nothing).
6. Sistem menambah stok barang jadi **hanya sebesar jumlah_lolos_qc** (trigger ke `stock_movements`). Jumlah yang reject tidak masuk stok, namun bahan baku yang terpakai untuk keseluruhan batch (termasuk porsi yang reject) tetap tercatat sebagai pengurang stok permanen — tidak dikembalikan.
7. Status batch berubah menjadi "selesai", dengan `status_qc` otomatis terisi "lolos_penuh", "reject_penuh", atau "sebagian_lolos" tergantung perbandingan `jumlah_lolos_qc` dan `jumlah_reject_qc`.

### B.3.5 Kebutuhan Data / Field-Level

**Tabel: `production_batches` (Batch Produksi)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| nomor_batch | string, unique | Kode batch, mis. "BATCH-2026-0001" |
| finished_product_id | FK → finished_products.id | Produk yang diproduksi |
| bom_header_id | FK → bom_headers.id | BOM versi aktif yang dipakai saat batch dibuat |
| jumlah_target | decimal | Jumlah output yang ditargetkan |
| jumlah_aktual | decimal, nullable | Jumlah output aktual total (diisi saat batch ditutup) |
| jumlah_lolos_qc | decimal, nullable | Jumlah output yang lolos QC dan masuk ke stok barang jadi |
| jumlah_reject_qc | decimal, nullable | Jumlah output yang reject QC (jumlah_aktual = jumlah_lolos_qc + jumlah_reject_qc) |
| tanggal_mulai | date | Tanggal produksi dimulai |
| tanggal_selesai | date, nullable | Tanggal produksi selesai |
| status | string (enum) | sedang_berjalan / selesai / dibatalkan |
| status_qc | string (enum), nullable | lolos_penuh / reject_penuh / sebagian_lolos — dihitung otomatis dari jumlah_lolos_qc vs jumlah_reject_qc |
| catatan_qc | text, nullable | Alasan reject (jika ada), atau catatan QC lainnya |
| dibuat_oleh | FK → users.id | Admin yang membuat batch |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `production_batch_materials` (Bahan Baku Terpakai per Batch — snapshot dari BOM)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| production_batch_id | FK → production_batches.id | Merujuk ke batch |
| raw_material_id | FK → raw_materials.id | Bahan baku yang dipakai |
| jumlah_terpakai | decimal | Jumlah aktual yang dikurangi dari stok (dihitung dari BOM × jumlah_target saat batch dimulai) |

> Catatan: tabel ini adalah *snapshot* — dibuat saat batch dimulai, agar riwayat produksi tetap akurat meskipun BOM produk tersebut berubah versi di kemudian hari.

### B.3.6 Relasi ke Modul Lain

- **Membutuhkan data dari:** Modul BOM & Inventory (BOM aktif, validasi stok bahan baku).
- **Datanya dipakai oleh:** Modul BOM & Inventory (trigger pengurangan stok bahan baku & penambahan stok barang jadi via `stock_movements`), Modul Keuangan (biaya bahan baku terpakai untuk perhitungan HPP, dan jika diperlukan biaya tenaga kerja produksi — lihat Open Questions), Modul Dashboard & Reporting (laporan produktivitas, tingkat reject QC, dll).

### B.3.7 Aturan Bisnis (Business Rules)

- Batch produksi **tidak dapat dimulai** jika stok bahan baku tidak mencukupi kebutuhan (selaras dengan aturan di B.1.7 — blokir total, bukan sekadar peringatan).
- Bahan baku yang terpakai dalam satu batch **tidak dikembalikan ke stok**, baik untuk porsi yang lolos maupun reject QC — seluruhnya dianggap terpakai permanen saat batch dimulai.
- Stok barang jadi hanya bertambah **sebesar jumlah yang lolos QC** (`jumlah_lolos_qc`). QC bersifat **satu kali di akhir batch** (bukan bertahap), namun hasilnya dapat berupa **sebagian lolos, sebagian reject** dalam satu batch yang sama.
- BOM yang dipakai suatu batch dicatat sebagai referensi tetap (`bom_header_id` merujuk ke versi spesifik) — jika BOM produk berubah versi setelah batch ini dibuat, riwayat batch tidak ikut berubah.
- **Biaya tenaga kerja produksi tidak dialokasikan per batch.** Perhitungan HPP pada fase prototipe awal murni berdasarkan biaya bahan baku terpakai (lihat `production_batch_materials`); biaya tenaga kerja produksi dicatat sebagai beban operasional umum di Modul Keuangan, terpisah dari kalkulasi HPP per produk.
- Hanya role **Admin** dan **Owner** yang dapat membuat/menutup batch produksi.

---

**Status dokumen:** Bagian A (Overview) — final. Bagian B: Modul 1 (BOM & Inventory) — final. Modul 2 (Purchasing/Procurement) — final. Modul 3 (Produksi) — final. Modul 4 sedang berjalan.

---

## B.4 Modul Sales & Order Fulfillment

### B.4.1 Tujuan Modul

Modul ini mengelola order dari mitra laundry, mulai dari pencatatan order masuk, validasi ketersediaan stok, hingga status pemenuhan order sampai siap dikirim. Karena sistem ini standalone dari SaaS partner-facing (lihat A.6), order dari mitra **dicatat secara manual oleh Admin** — bukan ditarik otomatis dari aplikasi mitra.

### B.4.2 Ruang Lingkup Fitur

- Manajemen data master **Mitra** (nama usaha, alamat, kontak, wilayah/Ring)
- Pencatatan **order baru** dari mitra (produk dipesan, jumlah, harga)
- Validasi otomatis ketersediaan stok barang jadi saat order dikonfirmasi
- Pengelolaan **status order** (baru, dikonfirmasi, diproses, siap kirim, terkirim, selesai, dibatalkan)
- Pencatatan **piutang mitra** (jika order dibayar secara kredit/tempo) — data untuk Modul Keuangan (AR/Account Receivable)
- Riwayat order per mitra (untuk analisis mitra paling aktif, produk paling laku per mitra)

### B.4.3 User Stories

1. Sebagai **Admin**, saya ingin mendaftarkan data mitra baru (nama usaha, alamat, wilayah/Ring, kontak), sehingga saya bisa mencatat order dari mitra tersebut.
2. Sebagai **Admin**, saya ingin mencatat order baru dari mitra dengan memilih produk dan jumlah yang dipesan, sehingga sistem menghitung total nilai order secara otomatis.
3. Sebagai **Admin**, saya ingin sistem memvalidasi ketersediaan stok barang jadi saat order dikonfirmasi, sehingga saya tidak menjanjikan produk yang stoknya tidak cukup.
4. Sebagai **Admin**, saya ingin mengubah status order (dikonfirmasi → diproses → siap kirim → terkirim), sehingga saya bisa melacak progres pemenuhan setiap order.
5. Sebagai **Admin**, saya ingin menandai metode pembayaran suatu order (tunai atau kredit/tempo), sehingga sistem tahu apakah perlu mencatat piutang mitra.
6. Sebagai **Admin Keuangan**, saya ingin melihat daftar order yang dibayar kredit beserta jatuh temponya, sehingga saya bisa memantau piutang mitra yang perlu ditagih.
7. Sebagai **Owner**, saya ingin melihat riwayat order suatu mitra tertentu, sehingga saya bisa menilai pola pembelian dan loyalitas mitra tersebut.
8. Sebagai **Owner**, saya ingin melihat produk apa yang paling banyak dipesan dalam periode tertentu, sehingga saya bisa merencanakan produksi dan strategi penjualan.

### B.4.4 Alur Proses (Flow)

1. Admin mencatat order baru: memilih mitra, produk yang dipesan beserta jumlah, dan metode pembayaran (tunai/kredit).
2. Sistem menghitung total nilai order otomatis berdasarkan harga jual produk saat itu.
3. Saat Admin mengonfirmasi order (status berubah dari "baru" ke "dikonfirmasi"), sistem memvalidasi ketersediaan stok barang jadi untuk seluruh item order.
4. Jika stok tidak mencukupi, sistem menampilkan peringatan — 🔶 lihat Aturan Bisnis untuk apakah ini memblokir atau sekadar informasi.
5. Jika stok mencukupi, sistem mengurangi stok barang jadi (trigger ke `stock_movements`, referensi_tipe = "sales") dan order berlanjut ke status "diproses".
6. Order yang sudah disiapkan secara fisik diubah statusnya menjadi "siap kirim", lalu diteruskan ke Modul Distribusi/Pengiriman untuk dijadwalkan pengirimannya.
7. Setelah barang diterima mitra (dikonfirmasi lewat Modul Distribusi), status order menjadi "terkirim", lalu "selesai" setelah pembayaran (jika kredit) dilunasi atau segera jika tunai.
8. Jika order dibayar kredit, sistem mencatat piutang mitra yang akan tampil di Modul Keuangan (AR/Account Receivable) hingga dilunasi.

### B.4.5 Kebutuhan Data / Field-Level

**Tabel: `partners` (Data Master Mitra)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| kode_mitra | string, unique | Kode internal, mis. "MTR-001" |
| nama_usaha | string | Nama usaha laundry mitra |
| nama_pemilik | string, nullable | Nama pemilik/PIC |
| no_telepon | string, nullable | Kontak mitra |
| alamat | text | Alamat lengkap |
| wilayah_ring | string (enum) | Ring 1 / Ring 2 / Ring 3 |
| status | string (enum) | aktif / nonaktif |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `sales_orders` (Header Order)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| nomor_order | string, unique | Nomor order, mis. "SO-2026-0001" |
| partner_id | FK → partners.id | Mitra pemesan |
| tanggal_order | date | Tanggal order dibuat |
| metode_pembayaran | string (enum) | tunai / kredit |
| tanggal_jatuh_tempo | date, nullable | Wajib diisi jika metode = kredit |
| status | string (enum) | baru / dikonfirmasi / diproses / siap_kirim / terkirim / selesai / dibatalkan |
| status_pembayaran | string (enum) | belum_dibayar / lunas |
| total_nilai_order | decimal | Total nilai order (akumulasi dari `sales_order_items`) |
| dicatat_oleh | FK → users.id | Admin yang mencatat order |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `sales_order_items` (Detail Item dalam Order)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| sales_order_id | FK → sales_orders.id | Merujuk ke header order |
| finished_product_id | FK → finished_products.id | Produk yang dipesan |
| jumlah_dipesan | decimal | Jumlah yang dipesan |
| harga_per_satuan | decimal | Harga jual saat order dibuat (snapshot, tidak berubah meski harga_jual_per_satuan di master berubah kemudian) |
| subtotal | decimal | jumlah_dipesan × harga_per_satuan |

### B.4.6 Relasi ke Modul Lain

- **Membutuhkan data dari:** Modul BOM & Inventory (data master produk jadi & validasi stok).
- **Datanya dipakai oleh:** Modul BOM & Inventory (trigger pengurangan stok barang jadi via `stock_movements`), Modul Distribusi/Pengiriman (order dengan status "siap_kirim" menjadi input untuk penjadwalan pengiriman), Modul Keuangan (data piutang/Account Receivable dari order kredit, data pendapatan penjualan), Modul Dashboard & Reporting (laporan penjualan, mitra paling aktif, produk terlaris).

### B.4.7 Aturan Bisnis (Business Rules)

- Harga jual pada `sales_order_items` diambil sebagai **snapshot** saat order dibuat — perubahan harga di data master `finished_products` tidak mengubah harga pada order yang sudah ada.
- Order **tidak dapat dikonfirmasi** jika stok barang jadi tidak mencukupi — konsisten dengan aturan blokir total di Modul Produksi (B.1.7, B.3.7). Admin harus menunggu batch produksi berikutnya atau menyesuaikan jumlah order.
- **Seluruh order mitra dibayar tunai.** Field `metode_pembayaran` dan `tanggal_jatuh_tempo` pada `sales_orders` tetap dipertahankan dalam skema untuk fleksibilitas di masa depan, namun pada fase prototipe nilainya akan selalu "tunai" dan alur pencatatan piutang (Account Receivable) di Modul Keuangan **tidak perlu dibangun di fase awal** — pendapatan langsung dicatat sebagai penambah kas saat order selesai.
- Order yang dibatalkan setelah stok sempat terpotong akan **mengembalikan stok secara otomatis** — sistem membuat entri baru di `stock_movements` dengan `tipe_pergerakan = "masuk"`, `referensi_tipe = "sales"`, dan catatan bahwa ini adalah pengembalian akibat pembatalan order.
- Data mitra (`partners`) dimulai **kosong dari nol** — tidak ada data existing yang dimigrasikan; seluruh pendaftaran mitra dilakukan manual oleh Admin melalui sistem sejak awal penggunaan.

---

**Status dokumen:** Bagian A (Overview) — final. Bagian B: Modul 1 (BOM & Inventory) — final. Modul 2 (Purchasing/Procurement) — final. Modul 3 (Produksi) — final. Modul 4 (Sales & Order Fulfillment) — final. Modul 5 sedang berjalan.

---

## B.5 Modul Distribusi / Pengiriman

### B.5.1 Tujuan Modul

Modul ini mengelola penjadwalan dan pelacakan pengiriman order yang sudah berstatus "siap_kirim" dari Modul Sales & Order Fulfillment ke lokasi mitra. Karena kurir tidak memiliki akun sendiri di sistem ini (sesuai keputusan A.4), seluruh pencatatan status pengiriman dilakukan **manual oleh Admin**, berdasarkan informasi dari kurir di lapangan.

### B.5.2 Ruang Lingkup Fitur

- Penjadwalan pengiriman untuk order berstatus "siap_kirim"
- Pengelompokan pengiriman berdasarkan **wilayah/Ring** (Ring 1, 2, 3) untuk efisiensi rute
- Pencatatan **status pengiriman** (dijadwalkan, dalam perjalanan, terkirim, gagal kirim)
- Pencatatan kurir/pengantar yang bertugas (sebagai data teks, bukan akun sistem)
- Pencatatan tanggal & waktu pengiriman aktual
- Riwayat pengiriman per mitra dan per wilayah (untuk evaluasi efisiensi distribusi)

### B.5.3 User Stories

1. Sebagai **Admin**, saya ingin melihat daftar order yang berstatus "siap_kirim", sehingga saya bisa menjadwalkan pengiriman berikutnya.
2. Sebagai **Admin**, saya ingin mengelompokkan beberapa order yang siap kirim berdasarkan wilayah/Ring yang sama, sehingga saya bisa merencanakan satu rute pengiriman untuk beberapa mitra sekaligus.
3. Sebagai **Admin**, saya ingin mencatat nama kurir/pengantar yang bertugas untuk suatu jadwal pengiriman, sehingga ada dokumentasi siapa yang mengantar ke mana.
4. Sebagai **Admin**, saya ingin memperbarui status pengiriman (dalam perjalanan → terkirim) berdasarkan informasi dari kurir di lapangan, sehingga status order di Modul Sales ikut terupdate otomatis.
5. Sebagai **Admin**, saya ingin mencatat jika suatu pengiriman gagal (misal mitra tutup/tidak ada di tempat), sehingga saya bisa menjadwalkan ulang pengiriman tersebut.
6. Sebagai **Owner**, saya ingin melihat waktu rata-rata dari order "siap_kirim" hingga "terkirim" per wilayah, sehingga saya bisa mengevaluasi efisiensi distribusi antar Ring.
7. Sebagai **Owner**, saya ingin melihat riwayat pengiriman ke suatu mitra tertentu, sehingga saya bisa memastikan konsistensi layanan pengiriman ke mitra tersebut.

### B.5.4 Alur Proses (Flow)

1. Admin membuka daftar order berstatus "siap_kirim" dan mengelompokkannya ke dalam satu **jadwal pengiriman** (bisa berisi satu atau beberapa order, terutama jika berada di wilayah/Ring yang sama).
2. Admin menetapkan tanggal rencana kirim dan mencatat nama kurir yang bertugas.
3. Status jadwal pengiriman berubah menjadi "dijadwalkan", lalu "dalam_perjalanan" saat kurir berangkat.
4. Setelah kurir kembali dan melaporkan hasil, Admin memperbarui status per order dalam jadwal tersebut: "terkirim" (berhasil) atau "gagal_kirim" (misal mitra tidak ada di tempat).
5. Order yang berstatus "terkirim" di modul ini memicu update status order terkait di Modul Sales & Order Fulfillment menjadi "terkirim" juga.
6. Order yang "gagal_kirim" tetap berstatus "siap_kirim" di Modul Sales, dan dapat dimasukkan kembali ke jadwal pengiriman berikutnya.

### B.5.5 Kebutuhan Data / Field-Level

**Tabel: `delivery_schedules` (Header Jadwal Pengiriman)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| kode_jadwal | string, unique | Kode jadwal, mis. "DEL-2026-0001" |
| wilayah_ring | string (enum) | Ring 1 / Ring 2 / Ring 3 — fokus wilayah jadwal ini |
| nama_kurir | string | Nama kurir/pengantar (data teks, bukan akun sistem) — satu kurir per jadwal pengiriman |
| tanggal_rencana_kirim | date | Tanggal direncanakan berangkat |
| tanggal_aktual_kirim | date, nullable | Tanggal aktual berangkat/selesai |
| estimasi_biaya_distribusi | decimal, nullable | Estimasi biaya (bensin, ongkos kurir, dll) untuk jadwal pengiriman ini |
| status | string (enum) | dijadwalkan / dalam_perjalanan / selesai | 
| dibuat_oleh | FK → users.id | Admin yang membuat jadwal |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `delivery_schedule_items` (Order dalam Satu Jadwal Pengiriman)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| delivery_schedule_id | FK → delivery_schedules.id | Merujuk ke jadwal |
| sales_order_id | FK → sales_orders.id | Order yang dikirim |
| status_pengiriman_item | string (enum) | menunggu / terkirim / gagal_kirim |
| catatan | text, nullable | Alasan jika gagal kirim |
| waktu_terkirim | timestamp, nullable | Waktu spesifik saat item ini berhasil dikirim |

### B.5.6 Relasi ke Modul Lain

- **Membutuhkan data dari:** Modul Sales & Order Fulfillment (order berstatus "siap_kirim" sebagai input penjadwalan).
- **Datanya dipakai oleh:** Modul Sales & Order Fulfillment (update status order menjadi "terkirim" saat pengiriman berhasil), Modul Dashboard & Reporting (laporan efisiensi distribusi per wilayah, waktu rata-rata pengiriman).

### B.5.7 Aturan Bisnis (Business Rules)

- Satu jadwal pengiriman (`delivery_schedules`) idealnya berisi order-order dengan `wilayah_ring` yang sama, namun sistem tidak memaksa validasi ini secara ketat — Admin tetap dapat menggabungkan order dari Ring berbeda jika diperlukan (fleksibilitas operasional).
- Order yang gagal dikirim tetap berstatus "siap_kirim" di Modul Sales dan bisa dimasukkan ke jadwal pengiriman baru — tidak ada batasan jumlah percobaan pengiriman ulang pada fase ini.
- Update status pengiriman sepenuhnya bergantung pada input manual Admin berdasarkan laporan kurir — sistem tidak memiliki pelacakan lokasi real-time (GPS tracking) pada fase prototipe ini.
- **Satu jadwal pengiriman selalu ditugaskan ke satu kurir** (`nama_kurir` sebagai field tunggal, bukan daftar).
- **Estimasi biaya distribusi dicatat per jadwal pengiriman** (`estimasi_biaya_distribusi`), untuk mendukung perhitungan biaya operasional per wilayah/Ring di Modul Keuangan dan Dashboard & Reporting.
- **Penjadwalan pengiriman dilakukan sepenuhnya manual** — tidak ada fitur penjadwalan berulang otomatis (recurring schedule) pada fase ini, meskipun suatu mitra secara rutin menerima kiriman di hari yang sama tiap minggu.
- Hanya role **Admin** dan **Owner** yang dapat mengelola jadwal pengiriman.

---

**Status dokumen:** Bagian A (Overview) — final. Bagian B: Modul 1 (BOM & Inventory) — final. Modul 2 (Purchasing/Procurement) — final. Modul 3 (Produksi) — final. Modul 4 (Sales & Order Fulfillment) — final. Modul 5 (Distribusi/Pengiriman) — final. Modul 6 sedang berjalan.

---

## B.6 Modul Keuangan

### B.6.1 Tujuan Modul

Modul ini menjadi pusat pencatatan keuangan perusahaan — menggabungkan data transaksi dari seluruh modul lain (pembelian, penjualan, produksi, distribusi, gaji) menjadi laporan arus kas, perhitungan HPP, dan laporan laba-rugi. Karena seluruh transaksi pembelian dan penjualan di Koombaah bersifat **tunai** (sesuai keputusan di Modul Purchasing dan Sales), modul ini dirancang dengan basis **cash-basis accounting** yang lebih sederhana — tanpa perlu mengelola siklus piutang/utang (AR/AP) secara kompleks.

### B.6.2 Ruang Lingkup Fitur

- Pencatatan **kas masuk** (dari penjualan) dan **kas keluar** (dari pembelian bahan baku, gaji, distribusi, biaya operasional lain)
- Perhitungan **HPP (Harga Pokok Produksi)** otomatis per batch produksi, berdasarkan total biaya bahan baku terpakai (sesuai keputusan di Modul Produksi — tanpa alokasi biaya tenaga kerja per batch)
- Pencatatan **biaya operasional umum** (beban gaji agregat, sewa, listrik, biaya distribusi, dll) yang tidak terikat ke satu batch/order tertentu
- Laporan **Laba-Rugi** periodik (bulanan) — pendapatan penjualan dikurangi HPP dan biaya operasional
- Laporan **Arus Kas** — ringkasan kas masuk vs kas keluar dalam periode tertentu
- 🔶 Pengaturan **kategori biaya operasional** yang dapat dikustomisasi (lihat Open Questions)

### B.6.3 User Stories

1. Sebagai **Admin Keuangan**, saya ingin sistem otomatis mencatat kas masuk setiap kali ada order yang selesai dibayar, sehingga saya tidak perlu input manual dari Modul Sales.
2. Sebagai **Admin Keuangan**, saya ingin sistem otomatis mencatat kas keluar setiap kali ada PO yang diterima dan dibayar, sehingga saya tidak perlu input manual dari Modul Purchasing.
3. Sebagai **Admin Keuangan**, saya ingin mencatat biaya operasional umum (gaji agregat, sewa, listrik) secara manual per bulan, sehingga biaya-biaya ini masuk ke perhitungan laba-rugi.
4. Sebagai **Admin Keuangan**, saya ingin melihat estimasi biaya distribusi dari Modul Distribusi otomatis masuk sebagai salah satu komponen biaya operasional, sehingga saya tidak perlu mencatatnya dua kali.
5. Sebagai **Owner**, saya ingin melihat HPP per produk berdasarkan biaya bahan baku dari batch produksi terkait, sehingga saya bisa mengevaluasi margin keuntungan tiap produk.
6. Sebagai **Owner**, saya ingin melihat laporan laba-rugi bulanan, sehingga saya tahu apakah bisnis untung atau rugi dalam periode tersebut.
7. Sebagai **Owner**, saya ingin melihat laporan arus kas (kas masuk vs keluar), sehingga saya bisa memantau kesehatan likuiditas perusahaan.
8. Sebagai **Owner**, saya ingin membandingkan laba-rugi antar bulan, sehingga saya bisa melihat tren pertumbuhan atau penurunan bisnis dari waktu ke waktu.

### B.6.4 Alur Proses (Flow)

**A. Alur Kas Masuk (otomatis dari Sales)**
1. Order di Modul Sales berubah status menjadi "selesai" (barang terkirim & tunai diterima).
2. Sistem otomatis membuat entri kas masuk di Modul Keuangan, dengan referensi ke `sales_order_id` dan nilai sebesar `total_nilai_order`.

**B. Alur Kas Keluar dari Pembelian (otomatis dari Purchasing)**
1. PO di Modul Purchasing berubah status menjadi "diterima_penuh" atau "sebagian_diterima" (barang diterima & tunai dibayar).
2. Sistem otomatis membuat entri kas keluar, dengan referensi ke `purchase_order_id` dan nilai sebesar total barang yang diterima pada transaksi tersebut.

**C. Alur Biaya Operasional (manual, per periode)**
1. Admin Keuangan mencatat biaya operasional bulanan secara manual — misal "Beban Gaji" (angka agregat, tanpa rincian per karyawan sesuai batasan akses di A.4), sewa tempat, listrik, dll.
2. Estimasi biaya distribusi dari `delivery_schedules.estimasi_biaya_distribusi` ditarik otomatis sebagai salah satu baris biaya operasional per periode (dijumlahkan dari seluruh jadwal pengiriman dalam bulan tersebut).

**D. Alur Perhitungan HPP**
1. Untuk setiap batch produksi yang selesai (`production_batches` dengan status "selesai"), sistem menghitung total biaya bahan baku dari `production_batch_materials` (jumlah_terpakai × harga_per_satuan_terakhir bahan baku terkait saat batch dimulai).
2. HPP per satuan produk = Total biaya bahan baku batch ÷ `jumlah_lolos_qc` batch tersebut.
3. Nilai HPP ini disimpan sebagai referensi historis (tidak berubah meski harga bahan baku kemudian berubah), untuk dipakai dalam perhitungan laba-rugi.

**E. Alur Laporan Laba-Rugi Bulanan**
1. Sistem menjumlahkan total pendapatan (kas masuk dari penjualan) dalam periode.
2. Sistem menjumlahkan total HPP dari seluruh produk terjual dalam periode (berdasarkan HPP historis tiap batch terkait).
3. Sistem menjumlahkan total biaya operasional (gaji agregat, distribusi, dan biaya lain) dalam periode.
4. Laba-Rugi = Pendapatan − HPP − Biaya Operasional.

### B.6.5 Kebutuhan Data / Field-Level

**Tabel: `cash_transactions` (Log Kas Masuk/Keluar)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| tipe | string (enum) | masuk / keluar |
| kategori | string (enum) | penjualan / pembelian_bahan_baku / gaji / distribusi / operasional_lain |
| nominal | decimal | Jumlah transaksi |
| tanggal_transaksi | date | Tanggal transaksi |
| referensi_tipe | string (enum), nullable | "sales_order" / "purchase_order" / "delivery_schedule" / null (jika manual) |
| referensi_id | bigint, nullable | ID transaksi terkait di modul lain, jika ada |
| catatan | text, nullable | Keterangan tambahan |
| dicatat_oleh | FK → users.id | Admin Keuangan/Owner yang mencatat (jika manual) |
| created_at | timestamp | Standar audit |

**Tabel: `operational_expenses` (Biaya Operasional Bulanan — manual)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| periode_bulan | date | Bulan berlaku, mis. "2026-09-01" (representasi awal bulan) |
| kategori | string (enum) | beban_gaji / sewa / listrik / distribusi / maintenance_alat / internet_pulsa / marketing / transportasi_non_distribusi / lainnya |
| nominal | decimal | Jumlah biaya |
| catatan | text, nullable | Keterangan tambahan |
| dicatat_oleh | FK → users.id | Admin Keuangan yang mencatat |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `production_batch_hpp` (HPP per Batch Produksi — hasil kalkulasi)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| production_batch_id | FK → production_batches.id | Batch terkait |
| total_biaya_bahan_baku | decimal | Total biaya bahan baku untuk batch ini |
| jumlah_lolos_qc | decimal | Disalin dari production_batches saat kalkulasi (snapshot) |
| hpp_per_satuan | decimal | total_biaya_bahan_baku ÷ jumlah_lolos_qc |
| dihitung_pada | timestamp | Kapan kalkulasi ini dijalankan |

**Tabel: `financial_periods` (Penutupan Periode Bulanan)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| periode_bulan | date, unique | Bulan yang direpresentasikan, mis. "2026-09-01" |
| status | string (enum) | terbuka / ditutup |
| total_pendapatan | decimal, nullable | Snapshot total pendapatan saat periode ditutup |
| total_hpp | decimal, nullable | Snapshot total HPP saat periode ditutup |
| total_biaya_operasional | decimal, nullable | Snapshot total biaya operasional saat periode ditutup |
| laba_rugi | decimal, nullable | total_pendapatan − total_hpp − total_biaya_operasional |
| ditutup_oleh | FK → users.id, nullable | Owner yang menutup periode |
| ditutup_pada | timestamp, nullable | Kapan periode ditutup |
| dibuka_kembali_oleh | FK → users.id, nullable | Owner yang membuka kembali periode (jika pernah) |
| dibuka_kembali_pada | timestamp, nullable | Kapan periode dibuka kembali (jika pernah) |
| alasan_buka_kembali | text, nullable | Wajib diisi saat periode dibuka kembali |
| created_at | timestamp | Standar audit |

### B.6.6 Relasi ke Modul Lain

- **Membutuhkan data dari:** Modul Sales & Order Fulfillment (pendapatan, trigger kas masuk), Modul Purchasing (trigger kas keluar), Modul Produksi (biaya bahan baku untuk HPP), Modul Distribusi (estimasi biaya distribusi), Modul SDM (data beban gaji agregat — lihat catatan A.4 soal batasan akses).
- **Datanya dipakai oleh:** Modul Dashboard & Reporting (laporan laba-rugi, arus kas, margin per produk ditampilkan ke Owner).

### B.6.7 Aturan Bisnis (Business Rules)

- Karena seluruh transaksi pembelian dan penjualan bersifat tunai, **tidak ada modul AR/AP terpisah** — kas masuk/keluar dicatat langsung saat transaksi terjadi (selaras dengan keputusan di B.2.7 dan B.4.7).
- **Beban Gaji dicatat sebagai satu angka agregat per bulan** di `operational_expenses` (kategori "beban_gaji") — Admin Keuangan mengisi nominal total, namun rincian gaji per karyawan tetap hanya dapat dilihat oleh Owner melalui Modul SDM (lihat A.4).
- HPP dihitung **murni dari biaya bahan baku** — biaya tenaga kerja produksi tidak dialokasikan ke HPP per batch, melainkan menjadi bagian dari "Beban Gaji" agregat (selaras dengan B.3.7).
- Nilai HPP suatu batch bersifat **historis/snapshot** — tidak berubah retroaktif meskipun harga bahan baku berubah setelah batch tersebut selesai.
- Kategori biaya operasional yang didukung: beban gaji, sewa, listrik, distribusi (otomatis dari Modul Distribusi), **maintenance alat produksi, internet/pulsa, marketing/iklan, dan transportasi non-distribusi** (kendaraan pribadi/operasional di luar pengiriman ke mitra), ditambah kategori "lainnya" untuk fleksibilitas.
- **Laporan laba-rugi hanya disusun per bulan** — tidak ada breakdown per Ring/wilayah pada fase prototipe ini, meskipun data wilayah tersedia di modul lain (dapat menjadi pengembangan lanjutan).
- **Penutupan periode (period closing) didukung.** Setelah Owner menutup suatu bulan di `financial_periods`, seluruh transaksi (`cash_transactions`, `operational_expenses`) pada periode tersebut **tidak dapat diubah/dihapus oleh Admin/Admin Keuangan**.
- **Periode yang sudah ditutup dapat dibuka kembali, khusus oleh Owner**, dengan mewajibkan pengisian `alasan_buka_kembali` sebagai jejak audit. Selama periode berstatus "dibuka kembali" (kembali ke status "terbuka"), transaksi pada periode tersebut dapat diubah seperti biasa, hingga Owner menutupnya kembali (yang akan menghitung ulang snapshot `total_pendapatan`, `total_hpp`, `total_biaya_operasional`, dan `laba_rugi`).
- Hanya role **Owner** yang dapat menutup maupun membuka kembali periode — Admin Keuangan tidak memiliki wewenang ini, sebagai kontrol tambahan atas integritas laporan keuangan.

---

**Status dokumen:** Bagian A (Overview) — final. Bagian B: Modul 1 (BOM & Inventory) — final. Modul 2 (Purchasing/Procurement) — final. Modul 3 (Produksi) — final. Modul 4 (Sales & Order Fulfillment) — final. Modul 5 (Distribusi/Pengiriman) — final. Modul 6 (Keuangan) — final. Modul 7 sedang berjalan.

---

## B.7 Modul SDM

### B.7.1 Tujuan Modul

Modul ini mengelola data karyawan **internal** Koombaah (staf produksi, admin, dll) — **bukan** data karyawan mitra laundry, yang dikelola secara terpisah di SaaS partner-facing (di luar scope PRD ini). Karena tim saat ini masih dipegang solo oleh Owner (lihat A.6), modul ini dirancang sebagai fondasi yang siap dipakai begitu perusahaan mulai merekrut staf, dengan data gaji yang aksesnya dibatasi ketat sesuai keputusan role di A.4.

### B.7.2 Ruang Lingkup Fitur

- Manajemen data master **Karyawan** (nama, posisi, tanggal mulai kerja, status aktif)
- Pencatatan **komponen gaji** per karyawan (gaji pokok, tunjangan, potongan) — akses eksklusif Owner
- Pencatatan **absensi/kehadiran** karyawan internal — 🔶 lihat Open Questions untuk menentukan kebutuhannya
- Riwayat penggajian per karyawan per periode (payroll history)
- Agregasi otomatis total gaji bulanan sebagai input ke Modul Keuangan (`operational_expenses`, kategori "beban_gaji") — tanpa membuka rincian per individu ke Admin Keuangan

### B.7.3 User Stories

1. Sebagai **Owner**, saya ingin mendaftarkan data karyawan baru (nama, posisi, tanggal mulai kerja), sehingga saya punya catatan digital seluruh tim internal.
2. Sebagai **Owner**, saya ingin mencatat komponen gaji setiap karyawan (gaji pokok, tunjangan, potongan), sehingga saya bisa menghitung total gaji yang harus dibayarkan tiap bulan.
3. Sebagai **Owner**, saya ingin melihat riwayat penggajian seorang karyawan dari bulan ke bulan, sehingga saya bisa melacak perubahan gaji (kenaikan, bonus, dll) dari waktu ke waktu.
4. Sebagai **Owner**, saya ingin sistem otomatis menjumlahkan total gaji seluruh karyawan dalam satu bulan dan mengirimkannya sebagai satu angka agregat ke Modul Keuangan, sehingga Admin Keuangan bisa menyusun laba-rugi tanpa melihat rincian gaji per orang.
5. Sebagai **Owner**, saya ingin menonaktifkan data karyawan yang sudah tidak bekerja lagi (resign), sehingga karyawan tersebut tidak lagi muncul dalam perhitungan gaji bulan berikutnya namun riwayatnya tetap tersimpan.

### B.7.4 Alur Proses (Flow)

1. Owner mendaftarkan karyawan baru dengan data dasar (nama, posisi, tanggal mulai kerja) dan komponen gaji (gaji pokok, tunjangan tetap jika ada).
2. Setiap bulan, Owner membuka modul SDM dan memproses **payroll run** untuk periode tersebut — sistem menghitung total gaji per karyawan (gaji pokok + tunjangan − potongan, jika ada penyesuaian bulan tersebut) dan menyimpannya sebagai catatan payroll historis.
3. Sistem menjumlahkan seluruh nominal payroll bulan tersebut menjadi satu angka agregat.
4. Angka agregat ini otomatis dikirim/dicatat ke Modul Keuangan sebagai entri `operational_expenses` dengan kategori "beban_gaji" untuk periode bulan yang sama (lihat B.6.4).
5. Detail payroll per individu **tidak pernah terekspos** ke luar Modul SDM — hanya Owner yang dapat melihatnya.

### B.7.5 Kebutuhan Data / Field-Level

**Tabel: `employees` (Data Master Karyawan Internal)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| kode_karyawan | string, unique | Kode internal, mis. "EMP-001" |
| nama_lengkap | string | Nama karyawan |
| posisi | string | mis. "Staf Produksi", "Admin Operasional" |
| no_telepon | string, nullable | Kontak karyawan |
| tanggal_mulai_kerja | date | Tanggal mulai bekerja |
| tanggal_berhenti | date, nullable | Diisi jika sudah resign |
| status | string (enum) | aktif / nonaktif |
| user_id | FK → users.id, nullable | Terhubung ke akun sistem jika karyawan ini juga pengguna sistem (misal Admin) |
| created_at, updated_at | timestamp | Standar audit |

**Tabel: `employee_salary_components` (Komponen Gaji Tetap per Karyawan)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| employee_id | FK → employees.id | Karyawan terkait |
| gaji_pokok | decimal | Nominal gaji pokok bulanan |
| tunjangan | decimal, default 0 | Tunjangan tetap (jika ada) |
| berlaku_sejak | date | Tanggal komponen gaji ini mulai berlaku (mendukung riwayat jika ada kenaikan gaji) |
| created_at | timestamp | Standar audit |

**Tabel: `payroll_runs` (Proses Penggajian per Periode)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| periode_bulan | date, unique | Bulan payroll, mis. "2026-09-01" |
| total_gaji_agregat | decimal | Total seluruh gaji karyawan pada periode ini |
| status | string (enum) | draft / final |
| diproses_oleh | FK → users.id | Owner yang memproses |
| diproses_pada | timestamp | Kapan payroll run ini diproses |

**Tabel: `payroll_details` (Rincian Gaji per Karyawan per Periode)**
| Field | Tipe Data | Keterangan |
|---|---|---|
| id | UUID/bigint (PK) | Primary key |
| payroll_run_id | FK → payroll_runs.id | Merujuk ke proses payroll |
| employee_id | FK → employees.id | Karyawan terkait |
| gaji_pokok | decimal | Snapshot gaji pokok saat payroll diproses |
| tunjangan | decimal | Snapshot tunjangan |
| bonus_produksi | decimal, default 0 | Insentif berdasarkan jumlah/kinerja batch produksi bulan ini (diisi manual oleh Owner) |
| lembur | decimal, default 0 | Nominal lembur bulan ini (diisi manual oleh Owner) |
| thr | decimal, default 0 | Tunjangan Hari Raya — hanya terisi pada periode yang relevan (umumnya sekali setahun) |
| potongan | decimal, default 0 | Potongan bulan ini (jika ada) |
| total_diterima | decimal | gaji_pokok + tunjangan + bonus_produksi + lembur + thr − potongan |

### B.7.6 Relasi ke Modul Lain

- **Membutuhkan data dari:** Tidak ada — modul ini relatif independen, kecuali referensi opsional ke `users` (akun sistem) jika seorang karyawan juga menjadi pengguna aktif sistem (misal Admin).
- **Datanya dipakai oleh:** Modul Keuangan (total gaji agregat per bulan masuk sebagai `operational_expenses`, kategori "beban_gaji" — lihat B.6.4), Modul Dashboard & Reporting (jumlah karyawan aktif, jika relevan untuk laporan Owner — tanpa rincian gaji).

### B.7.7 Aturan Bisnis (Business Rules)

- **Data gaji (baik `employee_salary_components` maupun `payroll_details`) hanya dapat diakses oleh role Owner** — Admin dan Admin Keuangan sama sekali tidak memiliki akses baca/tulis ke tabel-tabel ini, sesuai keputusan A.4.
- Hanya **`total_gaji_agregat`** dari `payroll_runs` yang "menyeberang" ke Modul Keuangan (sebagai satu baris di `operational_expenses`) — tidak ada data individu yang ikut terkirim.
- Payroll run yang berstatus "final" tidak dapat diubah lagi — koreksi dilakukan di payroll run bulan berikutnya (selaras dengan prinsip snapshot historis di modul lain).
- Karyawan yang dinonaktifkan (`status = nonaktif`, ada `tanggal_berhenti`) tidak lagi dimasukkan ke perhitungan payroll run bulan-bulan berikutnya, namun riwayat payroll sebelumnya tetap tersimpan utuh.
- **Gaji bersifat tetap bulanan pada fase awal** — tidak ada pencatatan absensi/kehadiran harian yang memengaruhi potongan gaji secara otomatis. Jika Owner perlu memberi potongan (misal karena alasan tertentu), field `potongan` pada `payroll_details` tetap tersedia dan diisi manual per kasus, bukan dihitung otomatis dari data kehadiran.
- **Bonus produksi, lembur, dan THR diakomodasi** sebagai komponen tambahan pada `payroll_details`, diisi manual oleh Owner saat payroll run diproses — tidak ada perhitungan otomatis (misal otomatis menghitung bonus dari jumlah batch produksi yang diselesaikan karyawan tersebut), mengingat pada fase awal data produksi belum terhubung ke performa individu karyawan secara granular.

### B.7.8 Catatan untuk Fase Prototipe

Karena tim saat ini masih solo, modul ini akan **diuji menggunakan data dummy karyawan** saat pembuatan prototipe — mencakup skenario lengkap: pendaftaran karyawan, pengisian komponen gaji, proses payroll run bulanan (termasuk bonus/lembur/THR), hingga verifikasi bahwa hanya angka agregat yang terkirim ke Modul Keuangan. Data riil dapat menggantikan data dummy begitu perusahaan benar-benar merekrut staf.

---

**Status dokumen:** Bagian A (Overview) — final. Bagian B: Modul 1 (BOM & Inventory) — final. Modul 2 (Purchasing/Procurement) — final. Modul 3 (Produksi) — final. Modul 4 (Sales & Order Fulfillment) — final. Modul 5 (Distribusi/Pengiriman) — final. Modul 6 (Keuangan) — final. Modul 7 (SDM) — final. Modul 8 sedang berjalan.

---

## B.8 Modul Dashboard & Reporting

### B.8.1 Tujuan Modul

Modul ini adalah lapisan agregasi lintas seluruh modul (B.1–B.7), memberikan visibilitas data operasional dan finansial dalam satu tampilan terpusat untuk mendukung pengambilan keputusan Owner. Berbeda dari modul lain, modul ini **tidak memiliki data miliknya sendiri** — seluruh isinya adalah hasil query/agregasi dari tabel-tabel di modul lain (selaras dengan prinsip arsitektur di A.5).

### B.8.2 Ruang Lingkup Fitur

- **Ringkasan Stok** — jumlah bahan baku & barang jadi yang stoknya di bawah ambang minimum (low stock alert), ditarik dari Modul BOM & Inventory
- **Ringkasan Produksi** — jumlah batch produksi per periode, tingkat keberhasilan QC (lolos penuh/sebagian/reject), ditarik dari Modul Produksi
- **Ringkasan Penjualan** — total order, produk terlaris, mitra paling aktif per periode, ditarik dari Modul Sales & Order Fulfillment
- **Ringkasan Distribusi** — waktu rata-rata pengiriman per Ring, jumlah pengiriman gagal, total estimasi biaya distribusi per periode, ditarik dari Modul Distribusi
- **Ringkasan Keuangan** — laporan laba-rugi bulanan, arus kas, HPP rata-rata per produk, ditarik dari Modul Keuangan (akses terbatas sesuai role — lihat Aturan Bisnis)
- **Ringkasan SDM** — jumlah karyawan aktif (tanpa rincian gaji), ditarik dari Modul SDM
- **Perbandingan harga supplier** per bahan baku, ditarik dari Modul Purchasing (selaras dengan keputusan B.2.7)
- Filter periode (bulanan) untuk seluruh ringkasan di atas
- **Ekspor laporan** ke format PDF/Excel, untuk keperluan dokumentasi administratif maupun lampiran skripsi

### B.8.3 User Stories

1. Sebagai **Owner**, saya ingin melihat satu halaman ringkasan yang menampilkan kondisi stok, produksi, penjualan, dan keuangan sekaligus, sehingga saya bisa cepat memahami kondisi bisnis tanpa membuka satu per satu modul.
2. Sebagai **Owner**, saya ingin melihat daftar bahan baku dan barang jadi yang stoknya menipis, sehingga saya bisa segera bertindak (memesan bahan baku atau menjadwalkan produksi).
3. Sebagai **Owner**, saya ingin melihat grafik laba-rugi bulanan dari beberapa bulan terakhir, sehingga saya bisa melihat tren pertumbuhan bisnis.
4. Sebagai **Owner**, saya ingin melihat produk mana yang paling laku dan mitra mana yang paling aktif memesan, sehingga saya bisa fokus strategi penjualan pada segmen yang tepat.
5. Sebagai **Owner**, saya ingin melihat perbandingan harga bahan baku dari beberapa supplier, sehingga saya bisa mengevaluasi apakah perlu beralih supplier untuk efisiensi biaya.
6. Sebagai **Admin**, saya ingin melihat ringkasan stok dan status order/produksi terkini, sehingga saya bisa memprioritaskan pekerjaan operasional harian tanpa perlu akses ke data keuangan.
7. Sebagai **Admin Keuangan**, saya ingin melihat ringkasan arus kas dan laba-rugi terkini, sehingga saya bisa memantau kesehatan keuangan tanpa perlu membuka data gaji individu karyawan.

### B.8.4 Alur Proses (Flow)

1. Pengguna (Owner/Admin/Admin Keuangan) membuka Dashboard, sistem menampilkan ringkasan sesuai **hak akses role** masing-masing (lihat Aturan Bisnis).
2. Pengguna dapat memilih filter periode (bulan tertentu) untuk melihat data historis, dengan default menampilkan bulan berjalan.
3. Seluruh angka pada dashboard dihitung secara **real-time** dari query lintas modul saat halaman diakses — 🔶 lihat Open Questions untuk pertimbangan performa jika data bertambah besar di masa depan.
4. Untuk data yang berasal dari periode yang sudah "ditutup" di Modul Keuangan (`financial_periods`), sistem dapat langsung menampilkan angka snapshot yang tersimpan (`total_pendapatan`, `total_hpp`, `total_biaya_operasional`, `laba_rugi`) alih-alih menghitung ulang, untuk konsistensi dengan laporan yang sudah difinalisasi.

### B.8.5 Kebutuhan Data / Field-Level

Modul ini **tidak memiliki tabel data baru** — seluruh tampilan merupakan hasil query terhadap tabel-tabel yang sudah didefinisikan di modul-modul sebelumnya:

| Ringkasan Dashboard | Sumber Data (Tabel & Modul) |
|---|---|
| Low stock alert | `raw_materials`, `finished_products` (B.1) — filter `stok_saat_ini` < `stok_minimum` |
| Ringkasan produksi | `production_batches`, `production_batch_hpp` (B.3, B.6) |
| Ringkasan penjualan | `sales_orders`, `sales_order_items`, `partners` (B.4) |
| Ringkasan distribusi | `delivery_schedules`, `delivery_schedule_items` (B.5) |
| Laba-rugi & arus kas | `cash_transactions`, `operational_expenses`, `financial_periods` (B.6) |
| Ringkasan SDM | `employees`, `payroll_runs` — total_gaji_agregat saja (B.7) |
| Perbandingan harga supplier | `purchase_orders`, `purchase_order_items`, `suppliers` (B.2) |

🔶 Jika di kemudian hari performa query real-time menjadi lambat (misal setelah data bertambah signifikan), dapat dipertimbangkan tabel ringkasan/cache (*materialized view* atau tabel agregat terjadwal) — namun ini **di luar scope fase prototipe awal**, mengingat skala data yang ditargetkan (±50 mitra, ±100 order/bulan) masih tergolong kecil.

### B.8.6 Relasi ke Modul Lain

- **Membutuhkan data dari:** Seluruh 7 modul lainnya (B.1–B.7) — modul ini murni konsumen data, tidak pernah menjadi sumber data bagi modul lain.
- **Datanya dipakai oleh:** Tidak ada modul lain yang bergantung pada Dashboard & Reporting — ini adalah titik akhir (terminal) dari alur data sistem.

### B.8.7 Aturan Bisnis (Business Rules)

- Tampilan dashboard **disesuaikan dengan role pengguna**, konsisten dengan pembatasan akses di A.4:
  - **Owner**: melihat seluruh ringkasan tanpa batasan, termasuk laba-rugi detail dan ringkasan SDM.
  - **Admin**: melihat ringkasan stok, produksi, penjualan, dan distribusi — **tidak** melihat ringkasan keuangan (laba-rugi) maupun data SDM.
  - **Admin Keuangan**: melihat ringkasan keuangan (laba-rugi, arus kas) dan dapat melihat ringkasan operasional lain (stok, produksi, penjualan, distribusi) sebagai konteks pendukung — namun **tidak** melihat rincian data SDM (hanya angka agregat "beban_gaji" yang sudah masuk sebagai bagian dari laba-rugi).
- Data dari periode yang belum ditutup (`financial_periods.status = "terbuka"`) dihitung real-time; data dari periode yang sudah ditutup ditampilkan dari snapshot, untuk menjaga konsistensi antara Dashboard dan laporan resmi.
- **Prioritas rilis prototipe pertama**: ringkasan Stok, Produksi, Penjualan, dan Keuangan dibangun terlebih dahulu, karena keempatnya paling langsung mendukung keputusan operasional harian dan menjadi bukti utama integrasi lintas modul untuk keperluan skripsi. Ringkasan Distribusi, SDM, dan Perbandingan Harga Supplier dapat menyusul di iterasi berikutnya (lihat Roadmap di C.2).
- **Ekspor laporan ke PDF/Excel didukung**, minimal untuk laporan Laba-Rugi dan ringkasan Stok — untuk keperluan dokumentasi administratif maupun lampiran skripsi.

### B.8.9 Catatan untuk Roadmap

Karena tidak seluruh ringkasan dashboard perlu dibangun sekaligus, pengembangan modul ini dibagi dua tahap:
- **Tahap 1** (prioritas, masuk rilis prototipe awal): Ringkasan Stok, Produksi, Penjualan, Keuangan + fitur ekspor PDF/Excel untuk keduanya.
- **Tahap 2** (menyusul): Ringkasan Distribusi, Ringkasan SDM, Perbandingan Harga Supplier.

---

**Status dokumen:** Bagian A (Overview) — final. Bagian B: seluruh 8 modul — final. Bagian C sedang berjalan.

---

## C. Bagian Penutup

### C.1 Kebutuhan Non-Fungsional

**Performa**
- Sistem dirancang untuk skala kecil-menengah (±50 mitra aktif, 10 jenis produk, ±100 order/bulan) — volume ini tergolong ringan untuk PostgreSQL dan arsitektur monolitik Laravel, sehingga tidak dibutuhkan optimasi performa khusus (caching kompleks, database sharding, dll) pada fase prototipe.
- Query dashboard (B.8) dijalankan real-time untuk data periode berjalan; data periode yang sudah ditutup memakai snapshot tersimpan (lihat B.8.4) untuk menjaga waktu muat tetap cepat.
- Sebagai PWA (via silviolleite/laravelpwa), sistem harus tetap dapat diakses dan terasa responsif di perangkat mobile dengan koneksi internet standar (bukan low-end 2G/3G, mengingat pengguna adalah internal perusahaan, bukan mitra di lapangan terpencil).

**Keamanan**
- Autentikasi pengguna menggunakan mekanisme standar Laravel (hashing password, session-based/token-based auth).
- Kontrol akses berbasis role (Owner, Admin, Admin Keuangan) diberlakukan **di level aplikasi maupun query**, bukan hanya disembunyikan di antarmuka — mencegah akses data sensitif (gaji, laba-rugi detail) meski pengguna mencoba mengakses langsung lewat URL/API.
- Seluruh trafik menggunakan **HTTPS**, sesuai standar hosting modern (Niagahoster mendukung SSL).
- Data gaji karyawan (`employee_salary_components`, `payroll_details`) mendapat proteksi akses paling ketat di seluruh sistem — hanya dapat dibaca/ditulis oleh akun dengan role Owner, divalidasi di level backend (bukan hanya disembunyikan di UI).

**Backup & Pemulihan Data**
- Backup basis data PostgreSQL dilakukan **berkala (harian direkomendasikan)** melalui fasilitas backup Niagahoster atau cron job terjadwal di sisi aplikasi — 🔶 [Perlu ditentukan: mekanisme backup spesifik akan dikonfirmasi saat fase implementasi/deployment, disesuaikan dengan fasilitas yang tersedia di paket hosting Niagahoster yang dipakai].
- Data yang sudah melalui proses *period closing* (B.6.7) idealnya turut disertakan dalam backup terpisah/arsip, mengingat sifatnya sebagai catatan resmi keuangan perusahaan.

**Ketersediaan (Availability)**
- Sistem berjalan pada satu server (bukan arsitektur high-availability/multi-server), sesuai konteks solo developer dan skala bisnis saat ini. Downtime singkat untuk maintenance dapat diterima, mengingat sistem ini adalah alat kerja internal — bukan layanan publik yang harus 24/7 tanpa gangguan.

**Audit Trail**
- Seluruh aksi yang berdampak pada stok, keuangan, dan payroll tercatat dengan referensi pengguna (`dilakukan_oleh` / `dicatat_oleh` / `diproses_oleh`) dan timestamp, sesuai desain tabel di masing-masing modul — mendukung ketertelusuran jika terjadi selisih data atau kebutuhan audit di kemudian hari.

### C.2 Fase Pengembangan (Roadmap)

Roadmap disusun mundur dari tenggat skripsi **31 Januari 2027**, dimulai dari tanggal dokumen ini disusun (10 September 2026) — memberi ruang waktu sekitar **4,5 bulan** pengembangan, dengan urutan mengikuti dependency antar modul yang telah ditetapkan di A.5.

| Fase | Periode (Estimasi) | Cakupan |
|---|---|---|
| **Fase 1 — Fondasi** | Sep–Okt 2026 | Setup project (Laravel + Livewire + PostgreSQL + PWA), sistem autentikasi & role (Owner/Admin/Admin Keuangan), Modul 1 (BOM & Inventory), Modul 2 (Purchasing/Procurement) |
| **Fase 2 — Alur Produksi & Penjualan** | Nov 2026 | Modul 3 (Produksi), Modul 4 (Sales & Order Fulfillment) — termasuk pengujian alur end-to-end dari pembelian bahan baku hingga order terkonfirmasi |
| **Fase 3 — Distribusi & Keuangan** | Des 2026 | Modul 5 (Distribusi/Pengiriman), Modul 6 (Keuangan) — termasuk perhitungan HPP otomatis dan laporan laba-rugi bulanan pertama |
| **Fase 4 — SDM & Dashboard Tahap 1** | Awal Jan 2027 (minggu 1–2) | Modul 7 (SDM, diuji dengan data dummy karyawan), Dashboard & Reporting Tahap 1 (Stok, Produksi, Penjualan, Keuangan + ekspor PDF/Excel) |
| **Fase 5 — Pengujian & Evaluasi Prototipe** | Jan 2027 (minggu 2–3) | Pengujian menyeluruh seluruh modul dengan data dummy/skenario riil, evaluasi terhadap kebutuhan awal (sesuai metode Prototype Development), perbaikan bug dan penyesuaian berdasarkan hasil evaluasi. Dashboard Tahap 2 (Distribusi, SDM, Perbandingan Supplier) dikerjakan jika waktu memungkinkan, atau dicatat sebagai pengembangan lanjutan pasca-skripsi. |
| **Fase 6 — Dokumentasi & Finalisasi Skripsi** | Jan 2027 (minggu 3–4, hingga 31 Jan) | Penyusunan laporan skripsi berdasarkan hasil prototipe, dokumentasi teknis, persiapan sidang/pengumpulan |

🔶 **Catatan:** Roadmap ini adalah estimasi berbasis dependency modul dan sisa waktu yang tersedia — belum memperhitungkan alokasi waktu spesifik untuk penulisan bab-bab skripsi non-teknis (mis. bab pendahuluan, tinjauan pustaka) yang mungkin berjalan paralel. Kamu mungkin perlu menyesuaikan detail mingguan berdasarkan jadwal bimbingan/kalender akademis kampus.

### C.3 Glosarium Istilah

| Istilah | Definisi |
|---|---|
| **Mitra** | Pelaku usaha laundry kiloan yang menjadi pelanggan/mitra operasional Koombaah |
| **Ring 1/2/3** | Pembagian wilayah operasional Koombaah berdasarkan jarak dari basis operasi: Ring 1 (Pamulang, Ciputat, Sawangan), Ring 2 (Pondok Aren, Cinere), Ring 3 (Serpong/BSD) |
| **Pendampingan** | Dukungan operasional Koombaah ke mitra melalui konten media sosial dan blog — bukan sesi pelatihan formal (lihat catatan brand terkait "pendampingan ≠ pelatihan") |
| **BOM (Bill of Material)** | Resep/komposisi bahan baku yang dibutuhkan untuk memproduksi satu produk kimia |
| **Batch Produksi** | Satu siklus eksekusi produksi untuk menghasilkan sejumlah produk jadi berdasarkan BOM tertentu |
| **QC (Quality Control)** | Proses pemeriksaan kualitas hasil produksi sebelum masuk ke stok barang jadi |
| **PO (Purchase Order)** | Dokumen pemesanan bahan baku ke supplier |
| **HPP (Harga Pokok Produksi)** | Total biaya untuk memproduksi satu satuan produk, pada PRD ini dihitung dari biaya bahan baku |
| **AR/AP (Account Receivable/Payable)** | Piutang/utang usaha — pada sistem ini tidak dibangun secara aktif karena seluruh transaksi bersifat tunai |
| **Cash-basis accounting** | Metode pencatatan keuangan di mana transaksi dicatat saat kas benar-benar berpindah tangan (bukan saat transaksi disepakati) |
| **Period Closing** | Proses penutupan periode akuntansi bulanan agar data tidak dapat diubah, kecuali dibuka kembali oleh Owner |
| **Prototype Development** | Metode pengembangan perangkat lunak yang digunakan dalam skripsi ini — membangun prototipe fungsional berdasarkan kebutuhan, mengujinya, dan mengiterasi berdasarkan evaluasi |
| **Tier 2 (segmen mitra)** | Klasifikasi usaha laundry kiloan skala kecil-menengah dengan 2–5 mesin, menjadi target segmen utama Koombaah |

### C.4 Riwayat Perubahan Dokumen

| Versi | Tanggal | Perubahan | Disusun oleh |
|---|---|---|---|
| 1.0 | 10 September 2026 | Draf lengkap pertama — Bagian A (Overview), seluruh 8 modul (Bagian B), dan Bagian C (Penutup) disusun berdasarkan sesi diskusi kebutuhan bersama Owner | Ashari (Owner/Founder) bersama asisten AI |

---

**Status dokumen: PRD LENGKAP — Bagian A, B, dan C selesai disusun.**



Dokumen ini siap dijadikan acuan pengembangan sekaligus lampiran metodologi skripsi.
