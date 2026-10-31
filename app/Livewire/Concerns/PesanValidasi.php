<?php

namespace App\Livewire\Concerns;

trait PesanValidasi
{
    protected function messages(): array
    {
        return [
            'required'        => ':attribute wajib diisi.',
            'numeric'         => ':attribute harus berupa angka.',
            'min'             => ':attribute tidak boleh kurang dari :min.',
            'max'             => ':attribute maksimal :max karakter.',
            'gt'              => ':attribute harus lebih dari :value.',
            'unique'          => ':attribute sudah terdaftar.',
            'enum'            => ':attribute yang dipilih tidak valid.',
            'array'           => ':attribute tidak valid.',
            'distinct'        => ':attribute tidak boleh dipilih lebih dari sekali.',
            'exists'          => ':attribute tidak ditemukan atau sudah nonaktif.',
            'date'            => ':attribute bukan tanggal yang valid.',
            'before_or_equal' => ':attribute tidak boleh melewati hari ini.',
        ];
    }
}