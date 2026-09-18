<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('petugas')->after('email');
            $table->string('kecamatan')->nullable()->after('role');
            $table->string('desa')->nullable()->after('kecamatan');
            $table->string('rw', 10)->nullable()->after('desa');
            $table->string('rt', 10)->nullable()->after('rw');
            $table->foreignId('created_by')->nullable()->after('rt')->constrained('users')->nullOnDelete();
        });

        Schema::table('families', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('catatan')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['role', 'kecamatan', 'desa', 'rw', 'rt', 'created_by']);
        });
    }
};
