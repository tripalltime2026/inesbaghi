<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_token')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_phone', 20)->nullable()->index();
            $table->string('status', 30)->default('new')->index();
            $table->string('mode', 20)->default('ai')->index();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('topic')->nullable()->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->json('context')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('admin_last_read_at')->nullable();
            $table->timestamp('user_last_read_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type', 20)->index();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->json('metadata')->nullable();
            $table->boolean('is_internal')->default(false)->index();
            $table->timestamps();
            $table->index(['support_conversation_id', 'created_at']);
        });

        Schema::create('support_knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('category', 50)->default('general')->index();
            $table->text('content');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        DB::table('support_knowledge_articles')->insert([
            [
                'key' => 'kindergarten_identity',
                'title' => 'რით გამოირჩევა ინეს ბაღი',
                'category' => 'brand',
                'content' => '„ინეს ბაღი“ არის ბავშვზე ორიენტირებული, თანამედროვე და ეკომეგობრული საგანმანათლებლო სივრცე. სწავლება აერთიანებს თამაშზე დაფუძნებულ მიდგომას, მონტესორის ელემენტებს, შემოქმედებით აქტივობებს და ბავშვის ინდივიდუალურ საჭიროებებზე მორგებულ განვითარებას. განსაკუთრებით მნიშვნელოვანია უსაფრთხო, მზრუნველი გარემო და მშობლებთან გამჭვირვალე კომუნიკაცია.',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'teaching_style',
                'title' => 'სწავლების სტილი',
                'category' => 'methodology',
                'content' => 'ბაღის სწავლების სტილი არის თბილი, ინდივიდუალური და თამაშზე დაფუძნებული. მონტესორის ელემენტები გამოიყენება დამოუკიდებლობის, პასუხისმგებლობისა და ცნობისმოყვარეობის გასაძლიერებლად; დღის რიტმი კი ითვალისწინებს ბავშვის ასაკს, ემოციურ მდგომარეობას და ბუნებრივ საჭიროებებს.',
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'admission_process',
                'title' => 'ჩარიცხვის პროცესი',
                'category' => 'admission',
                'content' => 'ჩარიცხვის დასაწყებად მშობელი ტოვებს ინტერესის განაცხადს ან გეგმავს გაცნობით ვიზიტს. ადმინისტრაცია აზუსტებს სასწავლო წელს, ბავშვის ასაკს, შესაბამის ჯგუფს და ადგილის სტატუსს. საბოლოო ჩარიცხვა და ადგილის გარანტია ძალაში შედის მხოლოდ ადმინისტრაციის დადასტურების შემდეგ.',
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'introductory_visit',
                'title' => 'გაცნობითი ვიზიტი',
                'category' => 'visit',
                'content' => 'გაცნობითი ვიზიტის დროს მშობელი ნახავს გარემოს, გაეცნობა პროგრამას, დღის რეჟიმს და ჯგუფის პირობებს. ვიზიტის დრო საბოლოოდ ადმინისტრაციასთან შეთანხმდება.',
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'documents',
                'title' => 'საჭირო დოკუმენტები',
                'category' => 'admission',
                'content' => 'რეალური ჩარიცხვის ეტაპზე საჭიროა ბავშვის დაბადების მოწმობის ასლი, ჯანმრთელობის ცნობა და მშობლის ან კანონიერი წარმომადგენლის პირადობის დამადასტურებელი დოკუმენტი. საბოლოო ჩამონათვალს ადმინისტრაცია მიაწვდის.',
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('support_knowledge_articles');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_conversations');
    }
};
