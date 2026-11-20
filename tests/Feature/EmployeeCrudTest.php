<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Employees\Index as EmployeeIndex;
use App\Models\BusinessSetting;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $pegawaiUser;

    protected Employee $pegawaiEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        BusinessSetting::current();

        $this->owner = User::factory()->create([
            'name' => 'Owner Gerai',
            'email' => 'owner@ayampotong.com',
            'phone' => '081111111111',
            'password' => Hash::make('password123'),
            'role' => 'owner',
        ]);

        $this->pegawaiUser = User::create([
            'name' => 'Budi Santoso',
            'email' => '082222222222@ayampotong.com',
            'phone' => '082222222222',
            'password' => Hash::make('pegawai123'),
            'role' => 'pegawai',
        ]);

        $this->pegawaiEmployee = Employee::create([
            'user_id' => $this->pegawaiUser->id,
            'name' => 'Budi Santoso',
            'phone' => '082222222222',
            'position' => 'Pegawai',
            'is_active' => true,
        ]);
    }

    public function test_owner_can_create_employee_via_api(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/employees', [
                'name' => 'Siti Nurhaliza',
                'phone' => '083333333333',
                'password' => 'siti1234',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Siti Nurhaliza',
                    'phone' => '083333333333',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('employees', [
            'name' => 'Siti Nurhaliza',
            'phone' => '083333333333',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Siti Nurhaliza',
            'phone' => '083333333333',
            'role' => 'pegawai',
        ]);

        $newUser = User::where('phone', '083333333333')->first();
        $this->assertTrue(Hash::check('siti1234', $newUser->password));
    }

    public function test_duplicate_phone_validation_fails_cleanly_via_api(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/employees', [
                'name' => 'Duplikat Budi',
                'phone' => '082222222222', // already used by Budi
                'password' => 'secret123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_owner_can_update_employee_via_api(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/employees/{$this->pegawaiEmployee->id}", [
                'name' => 'Budi Santoso Updated',
                'phone' => '082222222222', // keep same phone
                'password' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Budi Santoso Updated',
                ],
            ]);

        $this->pegawaiUser->refresh();
        $this->assertEquals('Budi Santoso Updated', $this->pegawaiUser->name);
        $this->assertTrue(Hash::check('newpassword123', $this->pegawaiUser->password));
    }

    public function test_pegawai_cannot_create_or_delete_employees_via_api(): void
    {
        $pegawaiToken = $this->pegawaiUser->createToken('pegawai-token')->plainTextToken;

        $createResponse = $this->withHeader('Authorization', "Bearer {$pegawaiToken}")
            ->postJson('/api/v1/employees', [
                'name' => 'Hacker Pegawai',
                'phone' => '089999999999',
                'password' => 'hack1234',
            ]);

        $createResponse->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Hanya pemilik (owner) yang memiliki akses untuk mengelola pegawai.',
            ]);

        $deleteResponse = $this->withHeader('Authorization', "Bearer {$pegawaiToken}")
            ->deleteJson("/api/v1/employees/{$this->pegawaiEmployee->id}");

        $deleteResponse->assertStatus(403);
    }

    public function test_deactivated_employee_cannot_login(): void
    {
        $this->pegawaiEmployee->update(['is_active' => false]);

        // API login test
        $apiResponse = $this->postJson('/api/v1/auth/login', [
            'email' => '082222222222',
            'password' => 'pegawai123',
        ]);

        $apiResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Web login test
        Livewire::test(Login::class)
            ->set('email', '082222222222')
            ->set('password', 'pegawai123')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    public function test_owner_can_delete_employee_and_user_is_removed(): void
    {
        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/employees/{$this->pegawaiEmployee->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('employees', ['id' => $this->pegawaiEmployee->id]);
        $this->assertDatabaseMissing('users', ['id' => $this->pegawaiUser->id]);
    }

    public function test_cannot_delete_employee_with_payroll_history(): void
    {
        Payroll::create([
            'employee_id' => $this->pegawaiEmployee->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'payment_date' => now()->toDateString(),
            'days_worked' => 20,
            'daily_rate_snapshot' => 100000,
            'total_salary' => 2000000,
            'notes' => 'Gaji bulanan',
        ]);

        $token = $this->owner->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/employees/{$this->pegawaiEmployee->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Pegawai memiliki riwayat penggajian dan tidak dapat dihapus.',
            ]);

        $this->assertDatabaseHas('employees', ['id' => $this->pegawaiEmployee->id]);
    }

    public function test_livewire_employee_index_crud(): void
    {
        $this->actingAs($this->owner);

        // Test Livewire Create
        Livewire::test(EmployeeIndex::class)
            ->call('openCreateModal')
            ->assertSet('showFormModal', true)
            ->set('name', 'Agus Prayogo')
            ->set('phone', '084444444444')
            ->set('password', 'agus1234')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('employees', ['name' => 'Agus Prayogo']);
        $this->assertDatabaseHas('users', ['phone' => '084444444444', 'role' => 'pegawai']);

        $agusEmp = Employee::where('phone', '084444444444')->first();

        // Test Livewire Edit
        Livewire::test(EmployeeIndex::class)
            ->call('edit', $agusEmp->id)
            ->assertSet('name', 'Agus Prayogo')
            ->set('name', 'Agus Prayogo SPV')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('employees', ['name' => 'Agus Prayogo SPV']);

        // Test Livewire Toggle Active
        Livewire::test(EmployeeIndex::class)
            ->call('toggleActive', $agusEmp->id);

        $agusEmp->refresh();
        $this->assertFalse($agusEmp->is_active);

        // Test Livewire Delete
        Livewire::test(EmployeeIndex::class)
            ->call('delete', $agusEmp->id);

        $this->assertDatabaseMissing('employees', ['id' => $agusEmp->id]);
    }

    public function test_pegawai_cannot_access_owner_only_api_endpoints(): void
    {
        $pegawaiToken = $this->pegawaiUser->createToken('pegawai-token')->plainTextToken;

        // 1. Employees index
        $this->withHeader('Authorization', "Bearer {$pegawaiToken}")
            ->getJson('/api/v1/employees')
            ->assertStatus(403);

        // 2. Payrolls index
        $this->withHeader('Authorization', "Bearer {$pegawaiToken}")
            ->getJson('/api/v1/payrolls')
            ->assertStatus(403);

        // 3. Cash flows index
        $this->withHeader('Authorization', "Bearer {$pegawaiToken}")
            ->getJson('/api/v1/cash-flows')
            ->assertStatus(403);

        // 4. Settings update
        $this->withHeader('Authorization', "Bearer {$pegawaiToken}")
            ->putJson('/api/v1/settings', ['business_name' => 'Hacked Name'])
            ->assertStatus(403);
    }

    public function test_deactivating_employee_revokes_active_tokens(): void
    {
        $token = $this->pegawaiUser->createToken('active-session')->plainTextToken;
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $this->pegawaiUser->id]);

        $this->actingAs($this->owner);

        // Deactivate via Livewire toggle
        Livewire::test(EmployeeIndex::class)
            ->call('toggleActive', $this->pegawaiEmployee->id);

        $this->pegawaiEmployee->refresh();
        $this->assertFalse($this->pegawaiEmployee->is_active);

        // All active tokens must be deleted immediately
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $this->pegawaiUser->id]);

        auth()->logout();
        app('auth')->forgetGuards();

        // Attempting to query API with old token fails with 401
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/products')
            ->assertStatus(401);
    }
}
