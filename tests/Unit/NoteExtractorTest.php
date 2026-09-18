<?php

namespace Tests\Unit;

use App\Support\NoteExtractor;
use PHPUnit\Framework\TestCase;

class NoteExtractorTest extends TestCase
{
    public function test_extract_members_and_common_indicators(): void
    {
        $draft = NoteExtractor::extract(
            'Keluarga Bapak Sutrisno, tinggal di Jl. Anggrek No 12 RT 03 RW 05 Desa Cibiru. '
            .'Istrinya bernama Wati, 34 tahun, ikut KB suntik. '
            .'Anak pertama Dimas umur 6 tahun, imunisasi lengkap saat bayi. '
            .'Anak kedua Nadia umur 1 tahun, masih ASI eksklusif, rutin ditimbang di posyandu. '
            .'Belum ada anggota keluarga dengan TB, hipertensi, atau gangguan jiwa. '
            .'Tidak ada yang merokok. Keluarga sudah punya kartu BPJS/JKN. '
            .'Sumber air dari PDAM, punya jamban sendiri.'
        );

        $this->assertSame('Sutrisno', $draft['kepala_keluarga']);
        $this->assertSame('Anggrek No 12', $draft['jalan']);
        $this->assertSame('03', $draft['rt']);
        $this->assertSame('05', $draft['rw']);
        $this->assertSame('Cibiru', $draft['desa']);

        $names = array_column($draft['anggota'], 'nama');
        $this->assertContains('Wati', $names);
        $this->assertContains('Dimas', $names);
        $this->assertContains('Nadia', $names);

        $wati = $draft['anggota'][array_search('Wati', array_column($draft['anggota'], 'nama'), true)];
        $this->assertSame('34', $wati['umur']);
        $this->assertSame('P', $wati['jenis_kelamin']);
        $this->assertSame('Istri', $wati['hubungan']);

        $this->assertSame('Y', $draft['indikator']['kb']);
        $this->assertSame('Y', $draft['indikator']['imunisasi']);
        $this->assertSame('Y', $draft['indikator']['asi']);
        $this->assertSame('Y', $draft['indikator']['balita']);
        $this->assertSame('Y', $draft['indikator']['rokok']);
        $this->assertSame('Y', $draft['indikator']['jkn']);
        $this->assertSame('Y', $draft['indikator']['air']);
        $this->assertSame('Y', $draft['indikator']['jamban']);

        foreach (['tb', 'hipertensi', 'jiwa'] as $id) {
            $this->assertSame('N', $draft['indikator'][$id]);
            $this->assertArrayNotHasKey($id, $draft['flagged']);
        }
    }

    public function test_negation_and_not_applicable_rules(): void
    {
        $draft = NoteExtractor::extract(
            'Keluarga Ibu Lina di Jl. Melati RT 02 RW 04 Desa Compreng. '
            .'Suaminya bernama Joko, 40 tahun, masih aktif merokok. '
            .'Belum pasangan ikut KB. Tidak ada bayi. '
            .'Punya jamban, air dari sumur, tidak punya BPJS.'
        );

        $this->assertSame('Lina', $draft['kepala_keluarga']);

        $this->assertSame('T', $draft['indikator']['kb']);
        $this->assertSame('T', $draft['indikator']['rokok']);
        $this->assertSame('T', $draft['indikator']['jkn']);
        $this->assertSame('N', $draft['indikator']['imunisasi']);
        $this->assertSame('N', $draft['indikator']['asi']);
        $this->assertSame('N', $draft['indikator']['balita']);

        $this->assertArrayNotHasKey('imunisasi', $draft['flagged']);
        $this->assertArrayNotHasKey('asi', $draft['flagged']);
    }

    public function test_disease_indicators_positive_and_excluded(): void
    {
        $draft = NoteExtractor::extract(
            'Keluarga Bapak Tono. Kakek menderita darah tinggi dan berobat rutin ke puskesmas. '
            .'Tidak ada yang merokok, air dari PDAM, jamban sendiri.'
        );

        $this->assertSame('Tono', $draft['kepala_keluarga']);
        $this->assertSame('Y', $draft['indikator']['hipertensi']);
        $this->assertSame('Y', $draft['indikator']['rokok']);
        $this->assertSame('Y', $draft['indikator']['air']);
        $this->assertSame('Y', $draft['indikator']['jamban']);
    }

    public function test_photo_extract_never_returns_acronyms_as_member(): void
    {
        $draft = NoteExtractor::extract(
            'Keluarga Bapak Agus, alamat RT 03 RW 01. Istri Sari 28 tahun sudah KB implan. '
            .'Ada bayi perempuan umur 2 bulan, diberi ASI eksklusif, imunisasi lengkap.'
        );

        $this->assertSame('Agus', $draft['kepala_keluarga']);
        $this->assertSame('Y', $draft['indikator']['kb']);
        $this->assertSame('Y', $draft['indikator']['imunisasi']);
        $this->assertSame('Y', $draft['indikator']['asi']);

        $names = array_column($draft['anggota'], 'nama');
        $this->assertContains('Sari', $names);
        foreach (['ASI', 'KB', 'BPJS', 'PDAM'] as $acronym) {
            $this->assertNotContains($acronym, $names);
        }
    }
}
