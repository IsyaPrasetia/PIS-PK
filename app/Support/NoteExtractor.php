<?php

namespace App\Support;

final class NoteExtractor
{
    /**
     * Hubungan keluarga → [label form, jenis kelamin default].
     */
    private const RELATIONS = [
        'istri' => ['Istri', 'P'],
        'suami' => ['Suami', 'L'],
        'putra' => ['Anak', 'L'],
        'putri' => ['Anak', 'P'],
        'anak' => ['Anak', 'L'],
        'bayi' => ['Anak', 'L'],
        'cucu' => ['Cucu', 'L'],
        'kakek' => ['Kakek', 'L'],
        'nenek' => ['Nenek', 'P'],
        'ibu' => ['Ibu', 'P'],
        'bapak' => ['Ayah', 'L'],
        'ayah' => ['Ayah', 'L'],
        'mertua' => ['Mertua', 'L'],
        'menantu' => ['Menantu', 'L'],
        'kakak' => ['Kakak', 'L'],
        'adik' => ['Adik', 'L'],
    ];

    /**
     * Akronim/istilah yang sering muncul di catatan tapi bukan nama orang.
     */
    private const BUKAN_NAMA = ['ASI', 'KB', 'BPJS', 'JKN', 'PDAM', 'IUD', 'IMPLAN', 'RT', 'RW', 'UMUR', 'TAHUN', 'BULAN', 'KAK'];

    /**
     * Pola umum: pernyataan "tidak ada anggota keluarga dengan <kondisi>"
     * (termasuk daftar dengan koma) berarti kondisi tidak berlaku.
     */
    private const NEGASI_KONDISI = '~(tidak|belum)\s+ada\s+(?:anggota\s+)?(?:sesuai\s+)?(?:keluarga\s+)?(?:yang\s+)?(?:\\bdengan\\b|mengalami|mempunyai)?[^;.]*?(tb|tuberkulosis|tbc|hipertensi|darah\s+tinggi|gangguan\s+jiwa|jiwa|skizofrenia|skizofreni)~i';

    /**
     * Definisi 12 indikator: kapan berlaku, kapan Ya, kapan Tidak.
     *
     * @var array<string, array{applies: list<string>|null, apply_exclude: list<string>|null, yes: list<string>, no: list<string>}>
     */
    private const INDICATORS = [
        'kb' => [
            'applies' => ['~(istri|suami|pasangan|pasutri|menikah|menikah\s+usia)~i'],
            'apply_exclude' => [],
            'yes' => ['~(kb|keluarga\s+berencana|kontrasepsi|suntik\s+kb|kb\s+suntik|pil\s+kb|kb\s+pil|iud|implan)\b~i'],
            'no' => [
                '~(?:belum|tidak)\s+[^.;]*?\b(?:kb|kontrasepsi|keluarga\s+berencana)\b~i',
                '~tidak\s+ikut\s+program\s+kb\b~i',
            ],
        ],
        'bersalin' => [
            'applies' => ['~(melahirkan|persalinan|bersalin|lahir|hamil|anak|bayi|putra|putri|cucu)~i'],
            'apply_exclude' => [],
            'yes' => [
                '~(melahirkan|persalinan|bersalin|lahir)\s+(?:di|ke)\s+(?:fasilitas|bidan|puskesmas|rumah\s*sakit|klinik|polindes|poskesdes|pustu)~i',
                '~(persalinan|proses\s+melahirkan)\s+(?:ditolong|dibantu)\s+(?:bidan|dokter)~i',
                '~(melahirkan|bersalin|lahir)\s+dengan\s+(?:bantuan|ditolong)\s+(?:bidan|dokter|perawat)~i',
            ],
            'no' => [
                '~(melahirkan|persalinan|bersalin|lahir)\s+di\s+rumah~i',
                '~(melahirkan|bersalin|lahir)\s+(?:ditolong|dibantu)\s+(?:dukun|paraji)~i',
            ],
        ],
        'imunisasi' => [
            'applies' => ['~(bayi|balita|anak|punya\s+anak)~i'],
            'apply_exclude' => ['~(tidak|belum)\s+ada\s+(?:bayi|balita|anak)~i'],
            'yes' => [
                '~imunisasi\s+(?:dasar\s+)?(?:lengkap|tuntas|beres|sudah|telah|diberikan|di(?:b|beri))~i',
                '~(sudah|telah)\s+imunisasi\s+(?:lengkap)?~i',
                '~vaksin(?:asi)?\s+(?:dasar\s+)?lengkap~i',
            ],
            'no' => [
                '~(belum|tidak)\s+imunisasi~i',
                '~imunisasi\s+belum~i',
                '~imunisasi\s+(?:tidak|belum)\s+lengkap~i',
            ],
        ],
        'asi' => [
            'applies' => ['~(bayi|balita|punya\s+anak)~i'],
            'apply_exclude' => ['~(tidak|belum)\s+ada\s+(?:bayi|balita|anak)~i'],
            'yes' => [
                '~\b(asi|air\s+susu\s+ibu|susu\s+ibunya?)\b\s*(?:eksklusif|saja|sepenuhnya)?~i',
                '~masih\s+\basi\b~i',
                '~\basi\s+eksklusif~i',
            ],
            'no' => [
                '~(tidak|belum)\s+(?:diberi|dapat|diberi\s+\basi\b|menyusui)~i',
                '~\basi\s+(?:tidak|belum)~i',
                '~(diberi|dikasih)\s+susu\s+formula~i',
            ],
        ],
        'balita' => [
            'applies' => ['~(balita|bayi)~i'],
            'apply_exclude' => ['~(tidak|belum)\s+ada\s+(?:bayi|balita|anak)~i'],
            'yes' => [
                '~(ditimbang|timbang|posyandu|berat\s+badan|kms|stimulasi|perkembangan\s+(?:anak\s+)?dipantau)~i',
            ],
            'no' => [
                '~(tidak|belum)\s+(?:di)?timbang~i',
                '~(tidak|belum)\s+ke\s+posyandu~i',
                '~(tidak|belum)\s+(?:diukur|dipantau)\s+(?:tumbuh|berat)\s+balita~i',
            ],
        ],
        'tb' => [
            'applies' => ['~(tb|tuberkulosis|tbc|batuk\s+berdahak)~i'],
            'apply_exclude' => [self::NEGASI_KONDISI],
            'yes' => ['~(tb|tuberkulosis|tbc)[^;.]*(berobat|minum\s+obat|obat|rutin|pengobatan)~i'],
            'no' => ['~(tb|tuberkulosis|tbc)[^;.]*(tidak\s+berobat|belum\s+berobat|tidak\s+minum\s+obat|putus\s+obat|tidak\s+rutin)~i'],
        ],
        'hipertensi' => [
            'applies' => ['~(hipertensi|darah\s+tinggi)~i'],
            'apply_exclude' => [self::NEGASI_KONDISI],
            'yes' => ['~(hipertensi|darah\s+tinggi)[^;.]*(berobat|minum\s+obat|obat|teratur|rutin|tekanan\s+darah)~i'],
            'no' => ['~(hipertensi|darah\s+tinggi)[^;.]*(tidak\s+berobat|belum\s+berobat|tidak\s+minum\s+obat|tidak\s+teratur|tidak\s+rutin)~i'],
        ],
        'jiwa' => [
            'applies' => ['~(gangguan\s+jiwa|jiwa|skizofrenia|skizofreni|mental)~i'],
            'apply_exclude' => [self::NEGASI_KONDISI],
            'yes' => ['~(gangguan\s+jiwa|skizofrenia|skizofreni|jiwa|mental)[^;.]*(diobati|obat|berobat|dirawat|ditangani|rutin|terduga)~i'],
            'no' => ['~(gangguan\s+jiwa|skizofrenia|skizofreni|jiwa|mental)[^;.]*(ditelantarkan|tidak\s+diobati|tidak\s+dirawat|tidak\s+ditangani|putus\s+obat)~i'],
        ],
        'rokok' => [
            'applies' => null,
            'apply_exclude' => [],
            'yes' => [
                '~tidak\s+(?:ada\s+(?:anggota\s+yang\s+)?)?merokok~i',
                '~tidak\s+ada\s+(?:yang\s+)?(?:merokok|perokok)~i',
                '~bebas\s+rokok~i',
            ],
            'no' => ['~(?:merokok|perokok)~i'],
        ],
        'jkn' => [
            'applies' => null,
            'apply_exclude' => [],
            'yes' => [
                '~(jkn|bpjs|kartu\s*(jkn|bpjs|sehat)|peserta\s+(jkn|bpjs)|punya\s+(jkn|bpjs)|terdaftar\s+(jkn|bpjs)|mempunyai\s+(jkn|bpjs))~i',
            ],
            'no' => [
                '~(tidak\s+(?:punya|ikut|terdaftar|memiliki)\s+(jkn|bpjs))~i',
                '~(belum\s+(?:punya|ikut|terdaftar|memiliki)\s+(jkn|bpjs))~i',
                '~(tidak\s+ada\s+(jkn|bpjs))~i',
            ],
        ],
        'air' => [
            'applies' => null,
            'apply_exclude' => [],
            'yes' => [
                '~(pdam|air\s+(?:bersih|ledeng|perpipaan|kota)|sumur\s+bor|sumur\s+artesis|sumber\s+air\s+(?:bersih|pdam|sumur)|air\s+minum\s+bersih|air\s+bersih|sumur)~i',
            ],
            'no' => [
                '~(tidak\s+(?:punya|ada|memiliki)\s+air)~i',
                '~(air\s+(?:sungai|sawah|hujan|kali))~i',
                '~(sumur\s+(?:tidak|belum)\s+(?:layak|bersih|aman))~i',
            ],
        ],
        'jamban' => [
            'applies' => null,
            'apply_exclude' => [],
            'yes' => ['~(jamban|wc|toilet|kakus|closet|water\s+closet)~i'],
            'no' => [
                '~(tidak\s+(?:punya|ada|memiliki)\s+(?:jamban|wc|toilet))~i',
                '~(jamban\s+(?:tidak|belum).*?(sehat|layak))~i',
                '~(buang\s+air\s+besar\s+sembarangan|babs)~i',
            ],
        ],
    ];

    /**
     * Ubah catatan kunjungan bebas menjadi draf terstruktur keluarga.
     *
     * @return array<string, mixed>
     */
    public static function extract(string $note): array
    {
        $note = trim($note);
        $kepala = self::extractKepala($note);
        $anggota = self::extractAnggota($note, $kepala);
        $indikator = self::extractIndikator($note, $anggota);

        return [
            'kepala_keluarga' => $kepala,
            'no_kk' => self::extractNoKk($note),
            'jalan' => self::extractJalan($note),
            'rt' => self::extractRt($note),
            'rw' => self::extractRw($note),
            'desa' => self::extractDesa($note),
            'kecamatan' => self::extractKecamatan($note),
            'catatan' => $note,
            'anggota' => $anggota,
            'indikator' => $indikator['values'],
            'flagged' => $indikator['flagged'],
        ];
    }

    /**
     * @return array{values: array<string, string>, flagged: array<string, bool>}
     */
    private static function extractIndikator(string $note, array $anggota): array
    {
        $values = [];
        $flagged = [];

        foreach (self::INDICATORS as $id => $def) {
            $value = self::classify($note, $def, $id, $anggota);

            // '?' = relevan tapi tak jelas; ditandai flag dan dibiarkan '?' agar
            // form tidak menampilkan nilai palsu ("Tidak berlaku"). Petugas yang
            // memutuskan saat meninjau.
            if ($value === '?') {
                $flagged[$id] = true;
            }

            $values[$id] = $value;
        }

        return ['values' => $values, 'flagged' => $flagged];
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array<int, array<string, string>>  $anggota
     */
    private static function classify(string $note, array $def, string $id, array $anggota): string
    {
        if (! self::applies($note, $def, $id, $anggota)) {
            return 'N';
        }

        $cariYaDulu = in_array($id, ['rokok'], true);

        if ($cariYaDulu) {
            foreach ($def['yes'] as $pattern) {
                if (preg_match($pattern, $note)) {
                    return 'Y';
                }
            }
        }

        foreach ($def['no'] as $pattern) {
            if (preg_match($pattern, $note)) {
                return 'T';
            }
        }

        foreach ($def['yes'] as $pattern) {
            if (preg_match($pattern, $note)) {
                return 'Y';
            }
        }

        return '?';
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array<int, array<string, string>>  $anggota
     */
    private static function applies(string $note, array $def, string $id, array $anggota): bool
    {
        foreach ($def['apply_exclude'] as $pattern) {
            if (preg_match($pattern, $note)) {
                return false;
            }
        }

        if ($def['applies'] === null) {
            return true;
        }

        foreach ($def['applies'] as $pattern) {
            if (preg_match($pattern, $note)) {
                return true;
            }
        }

        if ($id === 'balita') {
            return self::hasBalita($anggota);
        }

        return false;
    }

    /**
     * @param  array<int, array<string, string>>  $anggota
     */
    private static function hasBalita(array $anggota): bool
    {
        foreach ($anggota as $member) {
            $umur = (int) ($member['umur'] ?? 0);

            if ($umur >= 1 && $umur <= 5) {
                return true;
            }
        }

        return false;
    }

    private static function extractKepala(string $note): string
    {
        $patterns = [
            '~Keluarga\s+(?:dari\s+)?(?:Bapak|Bpk\.?|Ibu|Ibu\.?)?\s*([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*)~',
            '~kepala\s+keluarga\s+(?:bernama\s+)?([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*)~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $note, $m)) {
                $name = trim(preg_replace('~(?:,\s*)?\b(?:di|tinggal|bertempat|kk|nomor|no\.?\s+kk)\b.*$~i', '', $m[1]));

                if ($name !== '') {
                    return $name;
                }
            }
        }

        return '';
    }

    private static function extractNoKk(string $note): string
    {
        if (preg_match('~(?:no\.?\s*(?:kartu\s+)?kk\b|nomor\s+kartu\s+keluarga)\s*[:=]?\s*(\d{16})~i', $note, $m)) {
            return $m[1];
        }

        return '';
    }

    private static function extractJalan(string $note): string
    {
        if (preg_match('~\bJ(?:l|l)\.?\s+([^,.;]+)~i', $note, $m)) {
            $jalan = trim($m[1]);
            $jalan = preg_split('~\s+(?:RT|RW|Desa|Kelurahan|Kecamatan)\b~i', $jalan)[0];

            return trim($jalan);
        }

        return '';
    }

    private static function extractRt(string $note): string
    {
        return self::nomorWilayah($note, 'RT');
    }

    private static function extractRw(string $note): string
    {
        return self::nomorWilayah($note, 'RW');
    }

    private static function nomorWilayah(string $note, string $label): string
    {
        if (preg_match('~\b'.$label.'\.?\s*:?\s*(\d{1,3})~i', $note, $m)) {
            return $m[1];
        }

        return '';
    }

    private static function extractDesa(string $note): string
    {
        if (preg_match('~\b(?:desa|kelurahan|kel)\b\.?\s+([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*)~i', $note, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    private static function extractKecamatan(string $note): string
    {
        if (preg_match('~\bkecamatan\b\.?\s+([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*)~i', $note, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * @return array<int, array{nama: string, umur: string, jenis_kelamin: string, hubungan: string, nik: string}>
     */
    private static function extractAnggota(string $note, string $kepala): array
    {
        $anggota = [];
        $kataHubungan = implode('|', array_keys(self::RELATIONS));
        $sentences = preg_split('/(?<=[.;!?])\s+/', $note) ?: [];

        foreach ($sentences as $sentence) {
            if (! preg_match('~(?:'.$kataHubungan.')(?:nya)?\b~i', $sentence)) {
                continue;
            }

            $nama = self::namaDariKalimat($sentence);
            $umur = self::umurDariKalimat($sentence);
            $relasi = self::relasiDariKalimat($sentence);

            if ($nama === '' || $relasi === null) {
                continue;
            }

            $anggota[] = [
                'nama' => $nama,
                'umur' => $umur,
                'jenis_kelamin' => self::jenisKelaminDariKalimat($sentence, $relasi),
                'hubungan' => $relasi,
                'nik' => self::nikDariKalimat($sentence),
            ];
        }

        self::tambahkanKepala($anggota, $kepala);

        return $anggota;
    }

    private static function namaDariKalimat(string $sentence): string
    {
        if (preg_match('~bernama\s+([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*)~', $sentence, $m)) {
            $potensial = trim($m[1]);

            if (! self::bukanNama($potensial)) {
                return $potensial;
            }
        }

        if (preg_match('~namanya\s+([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*)~', $sentence, $m)) {
            $potensial = trim($m[1]);

            if (! self::bukanNama($potensial)) {
                return $potensial;
            }
        }

        foreach (self::RELATIONS as $kata => $kataInfo) {
            if (! preg_match('~\b'.preg_quote($kata, '~').'(?:nya)?\b~i', $sentence)) {
                continue;
            }

            if (preg_match(
                '~\b(?:laki-laki|perempuan|wanita|pria)?\s*(?:pertama|kedua|ketiga|keempat|kelima|bungsu|terakhir|sulung)?\s*([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*)~',
                self::setelahKata($sentence, $kata),
                $nama
            )) {
                $hasil = trim($nama[1]);

                if ($hasil !== '' && ! preg_match('~(^|\s)(usia|umur|tahun|th)\b~i', $hasil) && ! self::bukanNama($hasil)) {
                    return $hasil;
                }
            }
        }

        return '';
    }

    private static function bukanNama(string $nama): bool
    {
        foreach (self::BUKAN_NAMA as $kata) {
            if (preg_match('~\b'.preg_quote($kata, '~').'\b~i', $nama)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ambil potongan kalimat dimulai tepat setelah kata kunci hubungan.
     */
    private static function setelahKata(string $sentence, string $kata): string
    {
        if (preg_match('~\b'.preg_quote($kata, '~').'(?:nya)?\b~i', $sentence, $m, PREG_OFFSET_CAPTURE)) {
            return substr($sentence, $m[0][1] + strlen($m[0][0]));
        }

        return $sentence;
    }

    private static function umurDariKalimat(string $sentence): string
    {
        $patterns = [
            '~umur\s*(\d{1,3})\s*(?:tahun|th|bulan)?~i',
            '~usia\s*(\d{1,3})~i',
            '~(\d{1,2})\s*(?:tahun|th)~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sentence, $m)) {
                return $m[1];
            }
        }

        return '';
    }

    private static function relasiDariKalimat(string $sentence): ?string
    {
        $kataHubungan = implode('|', array_keys(self::RELATIONS));

        foreach (self::RELATIONS as $kata => [$label]) {
            if (preg_match('~\b(?:'.$kata.')(?:nya)?\b~i', $sentence)) {
                return $label;
            }
        }

        return null;
    }

    private static function jenisKelaminDariKalimat(string $sentence, string $relasi): string
    {
        foreach (self::RELATIONS as $kata => [$label, $default]) {
            if ($relasi === $label && preg_match('~\b(?:'.$kata.')(?:nya)?\b~i', $sentence)) {
                if (preg_match('~\b(?:putri|anak\s+perempuan|istri|ibu|mertua?|bayi\s+perempuan)\b~i', $sentence)) {
                    return 'P';
                }

                if (preg_match('~\b(?:putra|anak\s+laki|suami|bapak|ayah|bayi\s+laki)\b~i', $sentence)) {
                    return 'L';
                }

                return $default;
            }
        }

        return 'L';
    }

    private static function nikDariKalimat(string $sentence): string
    {
        if (preg_match('~(\d{16})~', $sentence, $m)) {
            return $m[1];
        }

        return '';
    }

    /**
     * @param  array<int, array<string, string>>  $anggota
     */
    private static function tambahkanKepala(array &$anggota, string $kepala): void
    {
        if ($kepala === '') {
            return;
        }

        foreach ($anggota as &$member) {
            if (mb_strtolower($member['nama']) === mb_strtolower($kepala)) {
                $member['hubungan'] = 'Kepala Keluarga';

                return;
            }
        }
        unset($member);

        $anggota[] = [
            'nama' => $kepala,
            'umur' => '',
            'jenis_kelamin' => 'L',
            'hubungan' => 'Kepala Keluarga',
            'nik' => '',
        ];
    }
}
