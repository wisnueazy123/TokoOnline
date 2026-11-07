<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleCallbackTest extends TestCase
{
    // Semua perubahan DB dibungkus transaksi dan otomatis di-rollback,
    // sehingga data asli (db_tokoonline) tidak berubah.
    use DatabaseTransactions;

    private function googleUser(
        string $email = 'google.tester@example.com',
        string $id = '109876543210',
        ?string $name = 'Google Tester'
    ): SocialiteUser {
        $socialUser = (new SocialiteUser())->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);
        $socialUser->token = 'fake-access-token';

        return $socialUser;
    }

    private function fakeGoogle(SocialiteUser $socialUser): void
    {
        $driver = Mockery::mock(SocialiteProvider::class);
        $driver->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    private function makeUser(string $email, string $role): User
    {
        return User::create([
            'nama' => 'Test ' . ucfirst($role),
            'email' => $email,
            'role' => $role,
            'status' => 1,
            'password' => bcrypt('secret'),
        ]);
    }

    public function test_login_button_redirects_to_google_account_chooser(): void
    {
        $response = $this->get('/auth/redirect');

        $response->assertRedirect();

        $url = $response->headers->get('Location');

        $this->assertStringContainsString('accounts.google.com', $url);
        $this->assertStringContainsString('prompt=select_account', $url);
    }

    public function test_new_google_user_creates_user_and_customer(): void
    {
        $this->fakeGoogle($this->googleUser());

        $this->get('/auth/google/callback')->assertRedirect(route('beranda'));

        $user = User::where('email', 'google.tester@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('customer', $user->role);
        $this->assertSame(1, (int) $user->status);
        $this->assertDatabaseHas('customer', [
            'user_id' => $user->id,
            'google_id' => '109876543210',
            'google_token' => 'fake-access-token',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_customer_without_customer_row_gets_one(): void
    {
        $user = $this->makeUser('existing.customer@example.com', 'customer');

        $this->assertDatabaseMissing('customer', ['user_id' => $user->id]);

        $this->fakeGoogle($this->googleUser('existing.customer@example.com', '555000111'));

        $this->get('/auth/google/callback')->assertRedirect(route('beranda'));

        $this->assertSame(1, Customer::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('customer', [
            'user_id' => $user->id,
            'google_id' => '555000111',
        ]);
    }

    public function test_existing_customer_is_not_duplicated(): void
    {
        $user = $this->makeUser('duplicate.customer@example.com', 'customer');
        Customer::create([
            'user_id' => $user->id,
            'google_id' => 'old-id',
            'google_token' => 'old-token',
        ]);

        $this->fakeGoogle($this->googleUser('duplicate.customer@example.com', '777888999'));

        $this->get('/auth/google/callback');

        $this->assertSame(1, Customer::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('customer', [
            'user_id' => $user->id,
            'google_id' => '777888999',
        ]);
    }

    public function test_admin_account_is_not_converted_and_gets_no_customer(): void
    {
        $admin = $this->makeUser('admin.google@example.com', 'admin');

        $this->fakeGoogle($this->googleUser('admin.google@example.com', '123123123'));

        $this->get('/auth/google/callback')->assertRedirect(route('beranda'));

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertDatabaseMissing('customer', ['user_id' => $admin->id]);
    }
}
