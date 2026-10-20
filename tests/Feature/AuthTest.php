<?php

use App\Models\Pemasukan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('mengarahkan tamu ke halaman login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('menolak login dengan password salah', function () {
    ['resto' => $resto] = masterDasar();

    $user = pengguna(User::ROLE_STAF, [$resto]);
    $user->update(['password' => Hash::make('Rahasia#123')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'salah-sekali'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('login berhasil diarahkan ke dashboard', function () {
    ['resto' => $resto] = masterDasar();

    $user = pengguna(User::ROLE_STAF, [$resto]);
    $user->update(['password' => Hash::make('Rahasia#123')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Rahasia#123'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user->refresh());
});

it('memaksa ganti password pada login pertama', function () {
    ['resto' => $resto] = masterDasar();

    $user = pengguna(User::ROLE_STAF, [$resto]);
    $user->update([
        'password' => Hash::make('Sementara#123'),
        'must_change_password' => true,
    ]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Sementara#123'])
        ->assertRedirect(route('password.form'));

    $this->actingAs($user->refresh())
        ->get('/dashboard')
        ->assertRedirect(route('password.form'));

    $this->actingAs($user->refresh())
        ->post('/ubah-password', [
            'password_lama' => 'Sementara#123',
            'password' => 'Rahasia#456',
            'password_confirmation' => 'Rahasia#456',
        ])
        ->assertRedirect(route('dashboard'));

    $this->assertFalse($user->refresh()->must_change_password);
});

it('staf tidak dapat membuka halaman pengguna dan master data', function () {
    ['resto' => $resto] = masterDasar();

    $staf = pengguna(User::ROLE_STAF, [$resto]);
    $owner = pengguna(User::ROLE_OWNER);

    $this->actingAs($staf)->get('/pengguna')->assertForbidden();
    $this->actingAs($staf)->get('/pengaturan')->assertForbidden();
    $this->actingAs($staf)->get('/master/unit')->assertForbidden();

    $this->actingAs($owner)->get('/pengguna')->assertOk();
    $this->actingAs($owner)->get('/master/unit')->assertOk();
});

it('staf hanya melihat pemasukan unit yang ditugaskan', function () {
    $master = masterDasar();

    $staf = pengguna(User::ROLE_STAF, [$master['resto']]);
    $pembuat = pengguna(User::ROLE_OWNER);

    Pemasukan::factory()->create([
        'unit_usaha_id' => $master['resto']->id,
        'kategori_pemasukan_id' => $master['kategori_resto']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 111_000,
        'dibuat_oleh' => $pembuat->id,
    ]);

    Pemasukan::factory()->create([
        'unit_usaha_id' => $master['kopi']->id,
        'kategori_pemasukan_id' => $master['kategori_kopi']->id,
        'metode_pembayaran_id' => $master['tunai']->id,
        'nominal' => 222_000,
        'dibuat_oleh' => $pembuat->id,
    ]);

    $this->actingAs($staf)->get('/pemasukan')
        ->assertOk()
        ->assertSee('Rp 111.000')
        ->assertDontSee('Rp 222.000');
});
