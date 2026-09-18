<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('kecamatan');
            $table->string('desa')->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('rt', 10)->nullable();
            $table->timestamps();

            $table->unique(['kecamatan', 'desa', 'rw', 'rt'], 'wilayah_full_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
