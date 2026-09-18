<?php

namespace App\Http\Requests;

use App\Support\Indikator;
use Illuminate\Foundation\Http\FormRequest;

class FamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'kepala_keluarga' => ['required', 'string', 'max:255'],
            'no_kk' => ['nullable', 'string', 'max:32'],
            'jalan' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'desa' => ['nullable', 'string', 'max:255'],
            'kecamatan' => ['nullable', 'string', 'max:255'],
            'surveyor' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],

            'members' => ['required', 'array', 'min:1'],
            'members.*.nama' => ['required', 'string', 'max:255'],
            'members.*.umur' => ['nullable', 'string', 'max:10'],
            'members.*.jenis_kelamin' => ['required', 'in:L,P'],
            'members.*.hubungan' => ['nullable', 'string', 'max:100'],
            'members.*.nik' => ['nullable', 'string', 'max:32'],
        ];

        foreach (Indikator::ids() as $id) {
            $rules['ind_'.$id] = ['required', 'in:Y,T,N'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'kepala_keluarga' => 'nama kepala keluarga',
            'no_kk' => 'nomor KK',
            'members.*.nama' => 'nama anggota',
            'members.*.jenis_kelamin' => 'jenis kelamin anggota',
        ];
    }
}
