<?php

use App\Support\Indikator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('no_kk', 32)->nullable()->index();
            $table->string('kepala_keluarga');
            $table->string('jalan')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('surveyor')->nullable();
            $table->date('tanggal')->nullable();
            $table->text('catatan')->nullable();

            foreach (Indikator::ids() as $id) {
                $table->string('ind_'.$id, 2)->default('N');
            }

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
