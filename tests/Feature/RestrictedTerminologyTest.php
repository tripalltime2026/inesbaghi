<?php

namespace Tests\Feature;

use App\Models\SiteContentEntry;
use App\Services\RestrictedTerminology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestrictedTerminologyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_do_not_expose_restricted_method_name(): void
    {
        $georgianTerm = 'მონ'.'ტესორი';
        $englishTerm = 'Monte'.'ssori';

        foreach (['/', '/metodologia', '/kitkhva-pasukhi', '/content/public'] as $uri) {
            $response = $this->get($uri);

            $response->assertSuccessful();
            $this->assertStringNotContainsString($georgianTerm, $response->getContent());
            $this->assertStringNotContainsStringIgnoringCase($englishTerm, $response->getContent());
        }
    }

    public function test_legacy_managed_content_is_sanitized_before_rendering(): void
    {
        $georgianTerm = 'მონ'.'ტესორი';

        SiteContentEntry::query()->updateOrCreate(
            ['key' => 'methodology.intro'],
            [
                'section' => 'methodology',
                'label' => 'მეთოდოლოგიის აღწერა',
                'value' => 'ჩვენ ვიყენებთ '.$georgianTerm.'ს ელემენტებს და თამაშით სწავლებას.',
                'input_type' => 'textarea',
                'sort_order' => 29,
            ],
        );

        $response = $this->get('/metodologia');

        $response->assertSuccessful();
        $response->assertDontSee($georgianTerm, false);
        $response->assertSee('სენსორულ და პრაქტიკულ აქტივობებს', false);
    }

    public function test_sanitizer_replaces_georgian_and_english_variants(): void
    {
        $georgianTerm = 'მონ'.'ტესორი';
        $englishTerm = 'Monte'.'ssori';
        $sanitizer = app(RestrictedTerminology::class);

        $value = $sanitizer->sanitize(
            $georgianTerm.'ს მეთოდი · '.$englishTerm.' elements',
        );

        $this->assertStringNotContainsString($georgianTerm, $value);
        $this->assertStringNotContainsStringIgnoringCase($englishTerm, $value);
        $this->assertStringContainsString('ბავშვზე ორიენტირებული სწავლება', $value);
        $this->assertStringContainsString('sensory and practical activities', $value);
    }
}
