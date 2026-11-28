<?php

namespace Database\Seeders;

use App\Models\JenisLayanan;
use App\Models\Paket;
use App\Models\UnitBisnis;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = config('app.seeder_user_password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Set SEEDER_USER_PASSWORD before running the database seeder.');
        }

        if (Schema::hasTable('user')) {
            DB::transaction(function () use ($password): void {
                $this->seedSchemaSql($password);
            });

            return;
        }

        if (
            Schema::hasTable('users')
            && Schema::hasColumn('users', 'password_hash')
            && Schema::hasTable('staf')
            && Schema::hasTable('kru')
        ) {
            DB::transaction(function () use ($password): void {
                $this->seedExistingDatabaseSchema($password);
            });

            return;
        }

        throw new RuntimeException(
            'DatabaseSeeder supports the schema in database/schema.sql or the existing users/password_hash schema.',
        );
    }

    private function seedSchemaSql(string $password): void
    {
        $studio = UnitBisnis::query()->updateOrCreate(
            ['nama_unit' => 'Raya Kreatif Studio'],
            ['lokasi' => 'Ciracas, Jakarta Timur'],
        );
        $innovation = UnitBisnis::query()->updateOrCreate(
            ['nama_unit' => 'Raya Kreatif Innovation'],
            ['lokasi' => 'Ciracas, Jakarta Timur'],
        );

        User::query()->updateOrCreate(
            ['email' => 'owner@gmail.com'],
            [
                'username' => 'pemilik',
                'password' => $password,
                'role' => 'owner',
                'unit_id' => null,
                'contact_id' => null,
                'tipe_kontrak' => null,
                'keahlian' => null,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'staf1@gmail.com'],
            [
                'username' => 'staf1',
                'password' => $password,
                'role' => 'staff',
                'unit_id' => $studio->getKey(),
                'contact_id' => null,
                'tipe_kontrak' => 'internal',
                'keahlian' => null,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'staf2@gmail.com'],
            [
                'username' => 'staf2',
                'password' => $password,
                'role' => 'freelancer',
                'unit_id' => $innovation->getKey(),
                'contact_id' => null,
                'tipe_kontrak' => 'freelance',
                'keahlian' => null,
            ],
        );

        $studioService = JenisLayanan::query()->updateOrCreate(
            ['kode' => 'FOTO-PROD'],
            [
                'unit_id' => $studio->getKey(),
                'nama' => 'Fotografi Produk',
            ],
        );
        $innovationService = JenisLayanan::query()->updateOrCreate(
            ['kode' => 'VIDEO-PROD'],
            [
                'unit_id' => $innovation->getKey(),
                'nama' => 'Produksi Video',
            ],
        );

        Paket::query()->updateOrCreate(
            [
                'jenis_layanan_id' => $studioService->getKey(),
                'deskripsi' => 'Paket foto produk hingga 10 item',
            ],
            [
                'harga_dasar' => 2500000,
                'satuan' => 'paket',
                'tipe' => 'utama',
                'is_active' => true,
            ],
        );
        Paket::query()->updateOrCreate(
            [
                'jenis_layanan_id' => $innovationService->getKey(),
                'deskripsi' => 'Produksi video profil berdurasi 1-3 menit',
            ],
            [
                'harga_dasar' => 15000000,
                'satuan' => 'paket',
                'tipe' => 'utama',
                'is_active' => true,
            ],
        );
    }

    private function seedExistingDatabaseSchema(string $password): void
    {
        DB::table('unit_bisnis')->updateOrInsert(
            ['nama_unit' => 'Raya Kreatif Studio'],
            ['lokasi' => 'Ciracas, Jakarta Timur'],
        );
        DB::table('unit_bisnis')->updateOrInsert(
            ['nama_unit' => 'Raya Kreatif Innovation'],
            ['lokasi' => 'Ciracas, Jakarta Timur'],
        );

        $studioId = DB::table('unit_bisnis')
            ->where('nama_unit', 'Raya Kreatif Studio')
            ->value('id');
        $innovationId = DB::table('unit_bisnis')
            ->where('nama_unit', 'Raya Kreatif Innovation')
            ->value('id');

        if (! is_numeric($studioId) || ! is_numeric($innovationId)) {
            throw new RuntimeException('Could not resolve the seeded business unit IDs.');
        }

        $studioServiceId = $this->updateLegacyService(
            'FOTO-PROD',
            (int) $studioId,
            'Fotografi Produk',
        );
        $innovationServiceId = $this->updateLegacyService(
            'VIDEO-PROD',
            (int) $innovationId,
            'Produksi Video',
        );

        DB::table('paket')->updateOrInsert(
            [
                'jenis_layanan_id' => $studioServiceId,
                'deskripsi' => 'Paket foto produk hingga 10 item',
            ],
            [
                'harga_dasar' => 2500000,
                'satuan' => 'paket',
                'tipe' => 'utama',
                'is_active' => true,
            ],
        );
        DB::table('paket')->updateOrInsert(
            [
                'jenis_layanan_id' => $innovationServiceId,
                'deskripsi' => 'Produksi video profil berdurasi 1-3 menit',
            ],
            [
                'harga_dasar' => 15000000,
                'satuan' => 'paket',
                'tipe' => 'utama',
                'is_active' => true,
            ],
        );

        $this->updateLegacyUser(
            username: 'pemilik',
            email: 'owner@gmail.com',
            password: $password,
            role: 'owner',
            unitId: null,
        );
        $staffId = $this->updateLegacyUser(
            username: 'staf1',
            email: 'staf1@gmail.com',
            password: $password,
            role: 'staff',
            unitId: (int) $studioId,
        );
        $freelancerId = $this->updateLegacyUser(
            username: 'staf2',
            email: 'staf2@gmail.com',
            password: $password,
            role: 'freelancer',
            unitId: (int) $innovationId,
        );

        DB::table('staf')->updateOrInsert(
            ['user_id' => $staffId],
            ['jabatan' => 'Staf Internal'],
        );
        DB::table('kru')->updateOrInsert(
            ['user_id' => $freelancerId],
            [
                'tipe_kontrak' => 'freelance',
                'keahlian' => 'Videografi dan editing',
            ],
        );
    }

    private function updateLegacyService(string $code, int $unitId, string $name): int
    {
        DB::table('jenis_layanan')->updateOrInsert(
            ['kode' => $code],
            [
                'unit_id' => $unitId,
                'nama' => $name,
            ],
        );

        $serviceId = DB::table('jenis_layanan')
            ->where('kode', $code)
            ->value('id');

        if (! is_numeric($serviceId)) {
            throw new RuntimeException("Could not resolve the service ID for {$code}.");
        }

        return (int) $serviceId;
    }

    private function updateLegacyUser(
        string $username,
        string $email,
        string $password,
        string $role,
        ?int $unitId,
    ): int {
        DB::table('users')->updateOrInsert(
            ['email' => $email],
            [
                'username' => $username,
                'password_hash' => Hash::make($password),
                'role' => $role,
                'unit_id' => $unitId,
                'contact_id' => null,
                'updated_at' => now(),
            ],
        );

        $userId = DB::table('users')
            ->where('email', $email)
            ->value('id');

        if (! is_numeric($userId)) {
            throw new RuntimeException("Could not resolve the seeded user ID for {$email}.");
        }

        return (int) $userId;
    }
}
