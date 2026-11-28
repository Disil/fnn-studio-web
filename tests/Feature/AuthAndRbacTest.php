<?php

use App\Models\Contact;
use App\Models\Deliverable;
use App\Models\JenisLayanan;
use App\Models\Klien;
use App\Models\Paket;
use App\Models\Payment;
use App\Models\Project;
use App\Models\UnitBisnis;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Schema::create('user', function (Blueprint $table): void {
        $table->id();
        $table->string('username')->unique();
        $table->string('email')->unique();
        $table->string('password');
        $table->string('role');
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->unsignedBigInteger('contact_id')->nullable();
        $table->string('tipe_kontrak')->nullable();
        $table->string('keahlian')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('unit_bisnis', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_unit');
        $table->timestamps();
    });

    Schema::create('klien', function (Blueprint $table): void {
        $table->id();
        $table->string('nama_perusahaan');
        $table->timestamps();
    });

    Schema::create('contact', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('klien_id')->nullable();
        $table->string('nama');
        $table->timestamps();
    });

    Schema::create('jenis_layanan', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('unit_id');
        $table->string('kode');
        $table->string('nama');
        $table->timestamps();
    });

    Schema::create('paket', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('jenis_layanan_id');
        $table->decimal('harga_dasar', 15, 2);
        $table->string('satuan');
        $table->string('tipe');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('project', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('klien_id');
        $table->unsignedBigInteger('dibuat_oleh')->nullable();
        $table->string('status')->default('menunggu_konfirmasi');
        $table->string('sumber');
        $table->date('tanggal_mulai')->nullable();
        $table->date('tanggal_selesai')->nullable();
        $table->date('deadline')->nullable();
        $table->string('nomor_po')->nullable();
        $table->date('tanggal_po')->nullable();
        $table->text('link_dokumen_po')->nullable();
        $table->timestamps();
    });

    Schema::create('project_item', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('project_id');
        $table->unsignedBigInteger('paket_id');
        $table->string('tipe_item');
        $table->unsignedInteger('kuantitas');
        $table->decimal('harga_satuan_snapshot', 15, 2);
        $table->timestamps();
    });

    Schema::create('project_crew', function (Blueprint $table): void {
        $table->unsignedBigInteger('project_id');
        $table->unsignedBigInteger('user_id');
        $table->string('peran');
        $table->primary(['project_id', 'user_id']);
    });

    Schema::create('project_contact', function (Blueprint $table): void {
        $table->unsignedBigInteger('project_id');
        $table->unsignedBigInteger('contact_id');
        $table->string('peran');
        $table->primary(['project_id', 'contact_id', 'peran']);
    });

    Schema::create('payment', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('project_id');
        $table->unsignedBigInteger('dicatat_oleh');
        $table->decimal('jumlah', 15, 2);
        $table->string('metode');
        $table->date('tanggal_bayar');
        $table->text('bukti')->nullable();
        $table->text('catatan')->nullable();
        $table->timestamps();
    });

    Schema::create('deliverable', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('project_id');
        $table->unsignedInteger('versi');
        $table->string('judul');
        $table->text('link');
        $table->string('tipe');
        $table->string('tahap')->nullable();
        $table->boolean('tampil_ke_klien')->default(false);
        $table->timestampTz('tanggal_kirim')->useCurrent();
    });
});

function createRbacTestProject(): Project
{
    $client = Klien::query()->create([
        'nama_perusahaan' => 'Client '.Str::uuid(),
    ]);

    return Project::query()->create([
        'klien_id' => $client->getKey(),
        'status' => 'produksi',
        'sumber' => 'staff',
    ]);
}

function attachRbacTestPackage(Project $project, int $unitId): void
{
    $service = JenisLayanan::query()->create([
        'unit_id' => $unitId,
        'kode' => (string) Str::uuid(),
        'nama' => 'Service',
    ]);
    $package = Paket::query()->create([
        'jenis_layanan_id' => $service->getKey(),
        'harga_dasar' => 100,
        'satuan' => 'paket',
        'tipe' => 'utama',
        'is_active' => true,
    ]);

    $project->projectItems()->create([
        'paket_id' => $package->getKey(),
        'tipe_item' => 'utama',
        'kuantitas' => 1,
        'harga_satuan_snapshot' => 100,
    ]);
}

test('renders a login page with username or email credentials', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Username atau email')
        ->assertSee('Kata sandi');
});

test('authenticates users by username or email', function (string $identifier) {
    $user = User::factory()->create([
        'username' => 'studio-user',
        'email' => 'studio@example.test',
        'password' => 'secret-password',
        'role' => 'staff',
    ]);

    $this->post(route('login.store'), [
        'identifier' => $identifier,
        'password' => 'secret-password',
    ])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
})->with([
    'username' => 'studio-user',
    'email' => 'studio@example.test',
]);

test('rejects invalid login credentials', function () {
    $response = $this->from(route('login'))->post(route('login.store'), [
        'identifier' => 'missing-user',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirectBackWithErrors(['identifier']);
    $this->assertGuest();
});

test('logs authenticated users out and invalidates their session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('role middleware accepts any listed role and distinguishes freelancers from staff', function () {
    Route::middleware(['auth', 'role:owner,staff'])
        ->get('/__test/staff-only', fn () => response()->noContent());

    $staff = User::factory()->create(['role' => 'staff', 'tipe_kontrak' => 'internal']);
    $this->actingAs($staff)->get('/__test/staff-only')->assertNoContent();

    $freelancer = User::factory()->create(['role' => 'freelancer']);
    $this->actingAs($freelancer)->get('/__test/staff-only')->assertForbidden();

    $legacyFreelancer = User::factory()->create([
        'role' => 'staff',
        'tipe_kontrak' => 'freelance',
    ]);
    $this->actingAs($legacyFreelancer)->get('/__test/staff-only')->assertForbidden();
});

test('limits staff to projects with service items entirely in their unit', function () {
    $staffUnit = UnitBisnis::query()->create(['nama_unit' => 'Studio']);
    $otherUnit = UnitBisnis::query()->create(['nama_unit' => 'Innovation']);
    $staff = User::factory()->create([
        'role' => 'staff',
        'unit_id' => $staffUnit->getKey(),
    ]);
    $unitlessStaff = User::factory()->create(['role' => 'staff', 'unit_id' => null]);

    $ownProject = createRbacTestProject();
    attachRbacTestPackage($ownProject, $staffUnit->getKey());

    $otherProject = createRbacTestProject();
    attachRbacTestPackage($otherProject, $otherUnit->getKey());

    $mixedProject = createRbacTestProject();
    attachRbacTestPackage($mixedProject, $staffUnit->getKey());
    attachRbacTestPackage($mixedProject, $otherUnit->getKey());

    $emptyProject = createRbacTestProject();

    expect(Gate::forUser($staff)->allows('view', $ownProject))->toBeTrue()
        ->and(Gate::forUser($unitlessStaff)->allows('create', Project::class))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('view', $otherProject))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('view', $mixedProject))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('view', $emptyProject))->toBeFalse()
        ->and(Project::query()->visibleTo($staff)->pluck('id')->all())->toBe([$ownProject->getKey()]);
});

test('allows freelancers to view and update progress only on assigned projects', function () {
    $freelancer = User::factory()->create(['role' => 'freelancer']);
    $assignedProject = createRbacTestProject();
    $unassignedProject = createRbacTestProject();
    $assignedProject->crew()->attach($freelancer->getKey(), ['peran' => 'editor']);

    expect(Gate::forUser($freelancer)->allows('view', $assignedProject))->toBeTrue()
        ->and(Gate::forUser($freelancer)->allows('updateProgress', $assignedProject))->toBeTrue()
        ->and(Gate::forUser($freelancer)->allows('update', $assignedProject))->toBeFalse()
        ->and(Gate::forUser($freelancer)->allows('view', $unassignedProject))->toBeFalse()
        ->and(Project::query()->visibleTo($freelancer)->pluck('id')->all())->toBe([$assignedProject->getKey()]);
});

test('blocks freelancers from every contact and payment policy action', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $project = createRbacTestProject();
    $contact = Contact::query()->create(['nama' => 'Client contact']);
    $freelancer = User::factory()->create([
        'role' => 'freelancer',
        'contact_id' => $contact->getKey(),
    ]);
    $project->crew()->attach($freelancer->getKey(), ['peran' => 'editor']);
    $payment = Payment::query()->create([
        'project_id' => $project->getKey(),
        'dicatat_oleh' => $owner->getKey(),
        'jumlah' => 100,
        'metode' => 'transfer',
        'tanggal_bayar' => '2026-01-01',
    ]);

    $gate = Gate::forUser($freelancer);

    expect($gate->allows('viewAny', Contact::class))->toBeFalse()
        ->and($gate->allows('view', $contact))->toBeFalse()
        ->and($gate->allows('create', Contact::class))->toBeFalse()
        ->and($gate->allows('update', $contact))->toBeFalse()
        ->and($gate->allows('delete', $contact))->toBeFalse()
        ->and($gate->allows('viewAny', Payment::class))->toBeFalse()
        ->and($gate->allows('view', $payment))->toBeFalse()
        ->and($gate->allows('create', Payment::class))->toBeFalse()
        ->and($gate->allows('createForProject', [Payment::class, $project]))->toBeFalse()
        ->and($gate->allows('update', $payment))->toBeFalse()
        ->and($gate->allows('delete', $payment))->toBeFalse()
        ->and(Contact::query()->visibleTo($freelancer)->exists())->toBeFalse()
        ->and(Payment::query()->visibleTo($freelancer)->exists())->toBeFalse();
});

test('limits clients to projects linked to their contact and visible deliverables', function () {
    $client = Klien::query()->create(['nama_perusahaan' => 'Client']);
    $clientContact = Contact::query()->create([
        'klien_id' => $client->getKey(),
        'nama' => 'Client contact',
    ]);
    $otherContact = Contact::query()->create(['nama' => 'Other contact']);
    $user = User::factory()->create([
        'role' => 'klien',
        'contact_id' => $clientContact->getKey(),
    ]);

    $ownProject = Project::query()->create([
        'klien_id' => $client->getKey(),
        'status' => 'produksi',
        'sumber' => 'staff',
        'nomor_po' => 'INTERNAL-PO',
    ]);
    $ownProject->contacts()->attach($clientContact->getKey(), ['peran' => 'pic']);

    $otherProject = createRbacTestProject();
    $otherProject->contacts()->attach($otherContact->getKey(), ['peran' => 'pic']);

    $visibleDeliverable = Deliverable::query()->create([
        'project_id' => $ownProject->getKey(),
        'versi' => 1,
        'judul' => 'Final',
        'link' => 'https://example.test/final',
        'tipe' => 'file_final',
        'tampil_ke_klien' => true,
    ]);
    $hiddenDeliverable = Deliverable::query()->create([
        'project_id' => $ownProject->getKey(),
        'versi' => 2,
        'judul' => 'Internal draft',
        'link' => 'https://example.test/draft',
        'tipe' => 'file_final',
        'tampil_ke_klien' => false,
    ]);

    $gate = Gate::forUser($user);
    $visibleProject = Project::query()->visibleTo($user)->firstOrFail();

    expect($gate->allows('view', $ownProject))->toBeTrue()
        ->and($gate->allows('view', $otherProject))->toBeFalse()
        ->and($gate->allows('view', $clientContact))->toBeTrue()
        ->and($gate->allows('view', $otherContact))->toBeFalse()
        ->and($gate->allows('viewDeliverable', [$ownProject, $visibleDeliverable]))->toBeTrue()
        ->and($gate->allows('viewDeliverable', [$ownProject, $hiddenDeliverable]))->toBeFalse()
        ->and(Deliverable::query()->visibleTo($user)->pluck('id')->all())->toBe([$visibleDeliverable->getKey()])
        ->and(array_keys($visibleProject->getAttributes()))->toContain('status')->not->toContain('nomor_po');
});

test('limits staff payment access to projects in their unit', function () {
    $staffUnit = UnitBisnis::query()->create(['nama_unit' => 'Studio']);
    $otherUnit = UnitBisnis::query()->create(['nama_unit' => 'Innovation']);
    $staff = User::factory()->create([
        'role' => 'staff',
        'unit_id' => $staffUnit->getKey(),
    ]);
    $ownProject = createRbacTestProject();
    attachRbacTestPackage($ownProject, $staffUnit->getKey());
    $otherProject = createRbacTestProject();
    attachRbacTestPackage($otherProject, $otherUnit->getKey());
    $ownPayment = Payment::query()->create([
        'project_id' => $ownProject->getKey(),
        'dicatat_oleh' => $staff->getKey(),
        'jumlah' => 100,
        'metode' => 'transfer',
        'tanggal_bayar' => '2026-01-01',
    ]);
    $otherPayment = Payment::query()->create([
        'project_id' => $otherProject->getKey(),
        'dicatat_oleh' => $staff->getKey(),
        'jumlah' => 100,
        'metode' => 'transfer',
        'tanggal_bayar' => '2026-01-01',
    ]);

    expect(Gate::forUser($staff)->allows('view', $ownPayment))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('view', $otherPayment))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('createForProject', [Payment::class, $ownProject]))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('createForProject', [Payment::class, $otherProject]))->toBeFalse()
        ->and(Payment::query()->visibleTo($staff)->pluck('id')->all())->toBe([$ownPayment->getKey()]);
});
