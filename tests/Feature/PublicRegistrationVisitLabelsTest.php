<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRegistrationVisitLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_uses_simple_heading(): void
    {
        $this->get('/registratsia')
            ->assertOk()
            ->assertSee('<h1>რეგისტრაცია</h1>', false)
            ->assertDontSee('რეგისტრაცია ორ ნაბიჯში');
    }

    public function test_public_pages_use_visit_as_the_action_label(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('ვიზიტი')
            ->assertSee('ვიზიტის დაგეგმვა')
            ->assertDontSee('ჩარიცხვის განაცხადი');

        $this->get('/charetskhva')
            ->assertOk()
            ->assertSee('დაგეგმეთ გაცნობითი ვიზიტი')
            ->assertSee('ვიზიტის მოთხოვნა')
            ->assertDontSee('>ჩარიცხვა</a>', false);
    }
}
