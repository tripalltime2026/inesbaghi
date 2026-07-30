<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table): void {
            $table->string('child_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('admission_applications')
            ->whereNull('child_name')
            ->update(['child_name' => 'დასაზუსტებელი']);

        Schema::table('admission_applications', function (Blueprint $table): void {
            $table->string('child_name')->nullable(false)->change();
        });
    }
};
