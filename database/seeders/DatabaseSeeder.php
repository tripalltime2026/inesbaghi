<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['2-3 წელი','2-3',24,35],['3-4 წელი','3-4',36,47],['4-5 წელი','4-5',48,59],['5-6 წელი','5-6',60,71]] as [$name,$slug,$min,$max]) {
            DB::table('kindergarten_groups')->updateOrInsert(['slug'=>$slug], ['name'=>$name,'age_min_months'=>$min,'age_max_months'=>$max,'capacity'=>20,'monthly_fee'=>600,'academic_year'=>'2026-2027','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
}
