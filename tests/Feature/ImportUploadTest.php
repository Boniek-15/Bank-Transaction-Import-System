<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportUploadTest extends TestCase
{
    use RefreshDatabase;

    // Adjust to match your app.
    private const ROUTE = '/api/imports';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        // Remove if the route is not behind auth.
        $this->actingAs(User::factory()->create());
    }

    private function csv(string $name, array $rows): UploadedFile
    {
        $lines = array_merge(['date,amount,description'], $rows);

        return UploadedFile::fake()->createWithContent($name, implode("\n", $lines));
    }

    public function test_valid_file_is_imported_successfully(): void
    {
        $file = $this->csv('transactions.csv', [
            '2026-01-01,100.00,Coffee',
            '2026-01-02,250.50,Groceries',
            '2026-01-03,15.00,Bus ticket',
        ]);

        $this->postJson(self::ROUTE, ['file' => $file])
            ->assertCreated();

        $this->assertDatabaseHas('imports', ['status' => 'success']);
        $this->assertDatabaseCount('transactions', 3);
        $this->assertDatabaseCount('import_logs', 0);
    }

    public function test_mixed_file_is_partially_imported(): void
    {
        $file = $this->csv('mixed.csv', [
            '2026-01-01,100.00,Valid one',
            'not-a-date,50.00,Bad date',
            '2026-01-03,,Missing amount',
            '2026-01-04,75.25,Valid two',
        ]);

        $this->postJson(self::ROUTE, ['file' => $file])
            ->assertCreated(); // change if your controller returns 200/207 for partial

        $this->assertDatabaseHas('imports', ['status' => 'partial']);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('import_logs', 2);
    }

    public function test_txt_file_is_rejected_and_nothing_is_saved(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'transactions.txt',
            "date,amount,description\n2026-01-01,100.00,Coffee"
        );

        $this->postJson(self::ROUTE, ['file' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('imports', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('import_logs', 0);
    }
}
