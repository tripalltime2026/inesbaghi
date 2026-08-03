<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        config([
            'seo.pages.blog.h1' => 'ბლოგი მშობლებისთვის',
            'seo.pages.admission.title' => 'საბავშვო ბაღის ვიზიტი ბათუმში | ინეს ბაღი',
            'seo.pages.admission.description' => 'დაგეგმეთ ინეს ბაღში გაცნობითი ვიზიტი და მიიღეთ ინფორმაცია გარემოს, პროგრამისა და ასაკობრივი ჯგუფების შესახებ.',
            'seo.pages.admission.eyebrow' => 'ვიზიტი',
            'seo.pages.admission.h1' => 'დაგეგმეთ გაცნობითი ვიზიტი',
            'seo.pages.admission.lead' => 'დატოვეთ საკონტაქტო ინფორმაცია და ადმინისტრაცია ვიზიტის დროს პირადად შეგითანხმებთ.',
            'seo.pages.admission.sections.0.title' => '1. დატოვეთ მოთხოვნა',
            'seo.pages.admission.sections.0.body' => 'მიუთითეთ მშობლის საკონტაქტო ინფორმაცია და ბავშვის ასაკობრივი ჯგუფი.',
            'seo.pages.admission.sections.1.title' => '2. დაგეგმეთ ვიზიტი',
            'seo.pages.admission.sections.1.body' => 'ადმინისტრაცია შეგითანხმებთ დროს და გაგაცნობთ სივრცეს, პროგრამასა და დღის რეჟიმს.',
            'seo.pages.admission.sections.2.title' => '3. მიიღეთ სრული ინფორმაცია',
            'seo.pages.admission.sections.2.body' => 'ვიზიტისას მიიღებთ პასუხებს ჯგუფების, ადგილებისა და შემდგომი ნაბიჯების შესახებ.',
            'seo.pages.contact.lead' => 'გაგაცნობთ გარემოს, პროგრამას, ჯგუფებსა და ვიზიტის დეტალებს.',
        ]);

        $this->prepareRuntimeDirectories();

        if (
            app()->runningInConsole()
            || ! config('services.demo_auth.enabled')
            || ! config('services.demo_auth.auto_migrate')
        ) {
            return;
        }

        try {
            $this->prepareSqliteDatabase();

            if (! Schema::hasTable('users')) {
                Artisan::call('migrate', ['--force' => true]);
                Log::info('Demo database schema created automatically.');
            }
        } catch (Throwable $exception) {
            // The public website must remain renderable even when an external
            // database resource has not been attached yet. Authenticated and
            // write operations will still report their normal database error.
            Log::error('Demo database bootstrap failed.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function prepareRuntimeDirectories(): void
    {
        foreach ([
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ] as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0775, true, true);
            }
        }
    }

    private function prepareSqliteDatabase(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');
        if (! is_string($database) || $database === ':memory:' || File::exists($database)) {
            return;
        }

        File::ensureDirectoryExists(dirname($database), 0775, true);
        File::put($database, '');
    }
}
