# Rancangan Segmentasi Keluarga untuk Sistem IKS

## 1. Latar Belakang

Sistem Pendataan Keluarga Sehat/PIS-PK memiliki 12 indikator kesehatan keluarga. Saat ini, proses pengumpulan data masih dapat bergantung pada wawancara atau catatan kunjungan. Jika proses wawancara dihapuskan, machine learning sebaiknya tidak digunakan untuk menebak jawaban indikator secara langsung.

Pendekatan yang lebih aman adalah menggunakan kombinasi:

1. **Rule engine** untuk menghitung nilai IKS berdasarkan data yang tersedia.
2. **Segmentasi keluarga** untuk mengelompokkan keluarga berdasarkan kebutuhan dan masalahnya.
3. **Machine learning** untuk membantu validasi data, mendeteksi anomali, dan menentukan prioritas verifikasi atau intervensi.

## 2. Pengertian Segmentasi Keluarga

Segmentasi keluarga adalah proses mengelompokkan keluarga berdasarkan kesamaan kondisi, kebutuhan, dan risiko.

Segmentasi berbeda dengan klasifikasi IKS:

- **IKS** menjawab: seberapa baik capaian indikator kesehatan sebuah keluarga?
- **Segmentasi** menjawab: keluarga ini memiliki kebutuhan seperti apa dan intervensi apa yang relevan?

Dua keluarga dapat memiliki nilai IKS yang sama, tetapi membutuhkan intervensi yang berbeda. Satu keluarga mungkin memiliki masalah hipertensi, sedangkan keluarga lain memiliki masalah sanitasi.

## 3. Prinsip Segmentasi

### 3.1 Segmentasi boleh tumpang tindih

Satu keluarga dapat masuk ke beberapa segmen sekaligus.

Contoh:

```text
Keluarga A:
- Segmen ibu dan anak
- Segmen masalah JKN
- Segmen data perlu verifikasi
```

Pendekatan ini lebih realistis daripada memaksa satu keluarga hanya memiliki satu label.

### 3.2 Data kosong tidak sama dengan jawaban “Tidak”

Jika data belum tersedia, statusnya sebaiknya adalah:

```text
Belum diketahui / Perlu verifikasi
```

Jangan langsung mengubahnya menjadi “Tidak Sehat”.

### 3.3 Segmentasi bukan diagnosis medis

Segmentasi hanya membantu menentukan prioritas administrasi, edukasi, pemantauan, atau verifikasi. Segmentasi tidak boleh menggantikan diagnosis atau keputusan klinis tenaga kesehatan.

## 4. Rancangan Segmen Keluarga

### 4.1 Keluarga sehat dan stabil

**Ciri-ciri:**

- Sebagian besar indikator bernilai “Ya”.
- Data keluarga relatif lengkap.
- Tidak ada indikator kritis yang belum tertangani.
- Tidak terdapat anomali data yang signifikan.

**Tindakan:**

- Pemantauan rutin.
- Tidak menjadi prioritas utama kunjungan.
- Dapat digunakan sebagai kelompok pembanding.

### 4.2 Keluarga dengan masalah penyakit kronis

**Ciri-ciri:**

- Ada anggota keluarga dengan hipertensi atau tuberkulosis.
- Pengobatan tidak rutin atau status pengobatan belum jelas.
- Riwayat kontrol atau kunjungan layanan kesehatan rendah.

**Indikator terkait:**

- Penderita tuberkulosis paru berobat sesuai standar.
- Penderita hipertensi melakukan pengobatan secara teratur.
- Penderita gangguan jiwa berat diobati dan tidak ditelantarkan.

**Tindakan:**

- Prioritas pemantauan pengobatan.
- Pengingat kontrol.
- Verifikasi status pengobatan.
- Tindak lanjut oleh petugas yang berwenang.

### 4.3 Keluarga dengan kebutuhan ibu dan anak

**Ciri-ciri:**

- Terdapat ibu hamil, bayi, atau balita.
- Imunisasi belum lengkap atau belum terverifikasi.
- Status ASI eksklusif belum jelas.
- Pertumbuhan balita belum dipantau rutin.
- Riwayat persalinan tidak tercatat di fasilitas kesehatan.

**Indikator terkait:**

- Keluarga mengikuti program KB.
- Ibu melakukan persalinan di fasilitas kesehatan.
- Bayi mendapat imunisasi dasar lengkap.
- Bayi mendapat ASI eksklusif.
- Pertumbuhan balita dipantau tiap bulan.

**Tindakan:**

- Prioritas pemantauan Posyandu.
- Pengingat imunisasi.
- Pemantauan pertumbuhan balita.
- Tindak lanjut kader, bidan, atau petugas terkait.

### 4.4 Keluarga dengan masalah sanitasi dan lingkungan

**Ciri-ciri:**

- Tidak memiliki atau tidak menggunakan jamban sehat.
- Akses air bersih bermasalah.
- Ada anggota keluarga yang merokok.
- Tinggal di wilayah dengan masalah sanitasi yang tinggi.

**Indikator terkait:**

- Anggota keluarga tidak ada yang merokok.
- Keluarga mempunyai akses atau menggunakan sarana air bersih.
- Keluarga mempunyai akses atau menggunakan jamban sehat.

**Tindakan:**

- Edukasi perilaku hidup bersih dan sehat.
- Program perbaikan sanitasi.
- Verifikasi kondisi air dan jamban.
- Prioritas bantuan atau intervensi lingkungan.

### 4.5 Keluarga dengan masalah perlindungan sosial

**Ciri-ciri:**

- Belum menjadi peserta JKN.
- Data kependudukan belum lengkap.
- NIK, KK, atau alamat tidak cocok dengan sumber data lain.
- Keluarga tidak aktif mengakses fasilitas kesehatan.

**Indikator terkait:**

- Keluarga sudah menjadi anggota Jaminan Kesehatan Nasional.

**Tindakan:**

- Pendampingan administrasi.
- Validasi data KK dan NIK.
- Bantuan pendaftaran atau perbaikan status JKN.
- Sinkronisasi data dengan sumber yang berwenang.

### 4.6 Keluarga dengan data perlu verifikasi

**Ciri-ciri:**

- Banyak indikator kosong.
- Banyak indikator bernilai “Tidak Berlaku” tanpa alasan yang jelas.
- Data anggota keluarga tidak konsisten.
- Satu keluarga muncul lebih dari satu kali.
- Umur, hubungan keluarga, atau alamat tidak masuk akal.
- Data sudah lama tidak diperbarui.

**Tindakan:**

- Masuk antrean validasi.
- Tidak langsung diberi status tidak sehat.
- Petugas memperbaiki atau melengkapi data.
- Mencatat sumber dan waktu pembaruan data.

## 5. Dua Jenis Segmen yang Perlu Dipisahkan

Sebaiknya sistem memisahkan dua kelompok segmentasi:

### 5.1 Segmen kebutuhan

Menjelaskan masalah atau kebutuhan keluarga.

Contoh:

- `ibu_anak`
- `penyakit_kronis`
- `sanitasi`
- `perlindungan_sosial`

### 5.2 Segmen kualitas data

Menjelaskan kualitas dan keandalan data.

Contoh:

- `data_lengkap`
- `data_perlu_verifikasi`
- `duplikasi_potensial`
- `data_kadaluarsa`

Dengan pemisahan ini, keluarga tidak akan dianggap memiliki masalah kesehatan hanya karena datanya belum lengkap.

## 6. Data yang Dibutuhkan

### 6.1 Data identitas dan keluarga

- ID keluarga internal.
- Nomor KK, jika diperbolehkan dan dilindungi dengan baik.
- NIK anggota keluarga, jika diperlukan dan memiliki dasar penggunaan yang sah.
- Nama kepala keluarga.
- Jumlah anggota keluarga.
- Umur dan hubungan anggota keluarga.
- RT, RW, desa, dan kecamatan.

### 6.2 Data kondisi keluarga

- Ada ibu hamil.
- Ada bayi atau balita.
- Ada lansia.
- Ada penderita hipertensi.
- Ada penderita tuberkulosis.
- Ada penderita gangguan jiwa berat.
- Status imunisasi.
- Status ASI eksklusif.
- Status pemantauan pertumbuhan.
- Status KB.
- Status JKN.
- Status air bersih.
- Status jamban sehat.
- Status merokok.

### 6.3 Data kualitas dan riwayat

- Tanggal terakhir pembaruan.
- Sumber data.
- Petugas yang memasukkan atau mengubah data.
- Riwayat koreksi.
- Kelengkapan data.
- Perbedaan data dengan sumber lain.
- Riwayat kunjungan atau verifikasi.

## 7. Aturan Segmentasi Awal

Aturan berikut dapat digunakan sebagai versi pertama sebelum menerapkan model machine learning.

```text
Jika ada anggota dengan hipertensi
DAN indikator pengobatan hipertensi = Tidak
MAKA tambahkan segmen penyakit_kronis
```

```text
Jika ada bayi atau balita
DAN indikator imunisasi = Tidak
MAKA tambahkan segmen ibu_anak
```

```text
Jika indikator air bersih = Tidak
ATAU indikator jamban sehat = Tidak
MAKA tambahkan segmen sanitasi
```

```text
Jika indikator JKN = Tidak
MAKA tambahkan segmen perlindungan_sosial
```

```text
Jika jumlah data kosong melebihi batas tertentu
ATAU terdapat konflik antar-sumber data
MAKA tambahkan segmen data_perlu_verifikasi
```

```text
Jika seluruh indikator dan data identitas lengkap
DAN tidak ada masalah prioritas
MAKA tambahkan segmen data_lengkap
```

## 8. Skor Prioritas

Segmentasi menjawab jenis kebutuhan. Skor prioritas menentukan urutan tindakan.

Contoh perhitungan sederhana:

```text
priority_score =
  skor_masalah_kesehatan
  + skor_ibu_anak
  + skor_sanitasi
  + skor_data_tidak_valid
  + skor_lamanya_data
```

Contoh kategori:

```text
0-29   = Rendah
30-59  = Sedang
60-79  = Tinggi
80-100 = Sangat Tinggi
```

Skor tidak boleh menjadi keputusan otomatis yang tidak dapat dijelaskan. Sistem harus menyimpan alasan mengapa skor tersebut diberikan.

## 9. Contoh Output Sistem

```json
{
  "keluarga_id": "KK001",
  "iks": 0.67,
  "kategori_iks": "Pra-Sehat",
  "segments": [
    "ibu_anak",
    "masalah_jkn",
    "data_perlu_verifikasi"
  ],
  "priority_score": 87,
  "priority_level": "tinggi",
  "reasons": [
    "Ada balita dengan status imunisasi belum lengkap",
    "Status JKN belum ditemukan",
    "Alamat belum diperbarui selama 14 bulan"
  ],
  "requires_human_review": true
}
```

## 10. Peran Machine Learning

### 10.1 Deteksi duplikasi

Model mencari kemungkinan satu keluarga tercatat lebih dari satu kali berdasarkan:

- Kemiripan nama.
- Nomor KK atau NIK.
- Alamat.
- Komposisi anggota keluarga.
- Wilayah tempat tinggal.

### 10.2 Deteksi anomali

Model menandai pola yang tidak umum, misalnya:

- Semua indikator memiliki jawaban yang sama.
- Umur anggota keluarga tidak masuk akal.
- Satu alamat memiliki terlalu banyak kepala keluarga.
- Jumlah anggota keluarga berubah secara ekstrem.
- Data sangat berbeda dari pembaruan sebelumnya.

### 10.3 Penentuan prioritas

Model dapat mempelajari pola dari riwayat verifikasi dan menentukan keluarga mana yang lebih mungkin membutuhkan tindak lanjut.

### 10.4 Analisis tren wilayah

Model dapat membantu menemukan wilayah yang:

- Mengalami penurunan IKS.
- Memiliki masalah sanitasi yang meningkat.
- Memiliki banyak data tidak lengkap.
- Memiliki kebutuhan ibu dan anak yang tinggi.

## 11. Tahapan Implementasi yang Disarankan

### Tahap 1: Rule engine

- Definisikan status Ya, Tidak, dan Tidak Berlaku.
- Hitung IKS secara transparan.
- Buat aturan segmentasi berdasarkan 12 indikator.
- Pisahkan segmen kebutuhan dan segmen kualitas data.

### Tahap 2: Kualitas data

- Tambahkan validasi input.
- Deteksi duplikasi.
- Tambahkan sumber dan tanggal data.
- Tambahkan riwayat koreksi.

### Tahap 3: Dashboard segmentasi

Tampilkan:

- Jumlah keluarga per segmen.
- Sebaran segmen berdasarkan wilayah.
- Daftar keluarga prioritas.
- Alasan setiap keluarga masuk segmen.
- Perubahan segmen dari waktu ke waktu.

### Tahap 4: Machine learning

Setelah tersedia data historis yang cukup:

- Latih model deteksi anomali.
- Latih model prioritas verifikasi.
- Evaluasi hasil model bersama petugas.
- Gunakan confidence score.
- Sediakan fallback ke pemeriksaan manual.

## 12. Rekomendasi Arsitektur

```text
Sumber data
  ├── Import Excel
  ├── Database Puskesmas
  ├── Data Posyandu
  ├── Data JKN
  └── Input petugas

        ↓

Validasi format dan kelengkapan

        ↓

Pencocokan keluarga dan deteksi duplikasi

        ↓

Rule engine 12 indikator

        ↓

Perhitungan IKS

        ↓

Segmentasi kebutuhan keluarga

        ↓

Machine learning:
- deteksi anomali
- skor prioritas
- analisis tren

        ↓

Dashboard dan rekomendasi tindak lanjut

        ↓

Persetujuan atau verifikasi petugas
```

## 13. Hal yang Harus Dihindari

- Jangan menggunakan machine learning untuk menebak diagnosis.
- Jangan menyamakan data kosong dengan kondisi tidak sehat.
- Jangan mengubah jawaban indikator secara otomatis tanpa bukti.
- Jangan menggunakan lokasi atau kondisi sosial sebagai satu-satunya dasar pelabelan negatif.
- Jangan menjadikan hasil clustering sebagai keputusan final tanpa interpretasi petugas.
- Jangan menampilkan data pribadi sensitif kepada pengguna yang tidak berwenang.
- Jangan menghapus sumber dan riwayat perubahan data.

## 14. Kesimpulan

Segmentasi keluarga dapat menjadi fungsi utama machine learning setelah proses wawancara dihapuskan. Sistem tidak lagi berfokus pada menebak jawaban keluarga, tetapi membantu menjawab tiga pertanyaan:

1. Keluarga ini memiliki kebutuhan apa?
2. Keluarga mana yang perlu diprioritaskan?
3. Data mana yang masih perlu diverifikasi?

Rekomendasi utama adalah memulai dengan **segmentasi multi-label berbasis aturan**, kemudian menambahkan machine learning untuk deteksi anomali, deduplikasi, penilaian kualitas data, dan prioritas verifikasi.

IKS tetap dihitung menggunakan aturan yang transparan, sedangkan machine learning digunakan sebagai alat bantu analisis dan pengambilan keputusan petugas.
