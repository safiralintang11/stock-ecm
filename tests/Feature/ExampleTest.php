<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root URL redirects to the dashboard (login when guest).
     */
    public function test_the_application_redirects_the_root_url(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
