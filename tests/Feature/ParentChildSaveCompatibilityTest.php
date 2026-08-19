<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParentChildSaveCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_save_child_when_legacy_children_table_has_no_birth_year_column(): void
    {
        Schema::table('children', function ($table): void {
            $table->dropColumn('birth_year');
        });

        $parent = User::create([
            'name' => 'Parent Compatibility',
            'username' => 'parent-compatibility',
            'password' => 'password123',
            'phone' => '+995555710001',
            'role' => 'parent',
            'status' => 'active',
        ]);

        $this->actingAs($parent)
            ->post(route('account.children.store'), [
                'child_first_name' => 'ნიკა',
                'child_last_name' => 'ბერიძე',
                'child_birth_date' => '2022-04-11',
            ])
            ->assertRedirect(route('account.profile'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('children', [
            'first_name' => 'ნიკა',
            'last_name' => 'ბერიძე',
            'birth_date' => '2022-04-11',
        ]);

        $childId = $parent->fresh()->children()->value('children.id');
        $this->assertNotNull($childId);
        $this->assertDatabaseHas('child_guardians', [
            'user_id' => $parent->id,
            'child_id' => $childId,
        ]);
    }
}
