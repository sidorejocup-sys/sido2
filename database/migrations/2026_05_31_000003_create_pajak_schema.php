<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique();
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', [
                    'super_admin',
                    'kades',
                    'kasun_rw',
                    'rt',
                    'pengguna',
                ])->default('pengguna');
            }
        });

        Schema::create('subjek_pajak', function (Blueprint $table) {
            $table->string('NIK', 16)->primary();
            $table->string('nama', 150);
            $table->text('alamat');
            $table->string('RT', 3);
            $table->string('RW', 3);
            $table->string('no_hp', 15);
            $table->timestamps();
        });

        Schema::create('objek_pajak', function (Blueprint $table) {
            $table->string('nop', 18)->primary();
            $table->string('nik_pemilik', 16);
            $table->text('letak_objek');
            $table->integer('luas_bumi');
            $table->integer('luas_bangunan');
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();

            $table->foreign('nik_pemilik')
                ->references('NIK')
                ->on('subjek_pajak')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::create('sppt', function (Blueprint $table) {
            $table->id('id_sppt');
            $table->string('nop', 18);
            $table->integer('tahun');
            $table->decimal('njop_bumi', 15, 2);
            $table->decimal('njop_bangunan', 15, 2);
            $table->decimal('pajak_terhutang', 15, 2);
            $table->enum('status_bayar', [
                'piutang',
                'proses_pengajuan',
                'lunas',
                'ditolak',
            ])->default('piutang');
            $table->timestamps();

            $table->foreign('nop')
                ->references('nop')
                ->on('objek_pajak')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::create('riwayat_mutasi', function (Blueprint $table) {
            $table->id('id_mutasi');
            $table->string('nop_asal', 18);
            $table->string('nik_lama', 16);
            $table->string('nik_baru', 16);
            $table->string('jenis_mutasi', 50);
            $table->date('tgl_mutasi');
            $table->string('no_arsip', 100);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('nop_asal')
                ->references('nop')
                ->on('objek_pajak')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_bayar');
            $table->unsignedBigInteger('id_sppt');
            $table->timestamp('tgl_bayar');
            $table->decimal('jumlah_bayar', 15, 2);
            $table->foreignId('id_petugas')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_sppt')
                ->references('id_sppt')
                ->on('sppt')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('riwayat_mutasi');
        Schema::dropIfExists('sppt');
        Schema::dropIfExists('objek_pajak');
        Schema::dropIfExists('subjek_pajak');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
