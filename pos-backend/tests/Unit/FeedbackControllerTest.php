<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class FeedbackControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $baseUrl = 'http://localhost:8000/api/v1';

    public function test_can_get_all_feedback()
    {
        Feedback::factory()->count(15)->create();

        $response = $this->getJson("{$this->baseUrl}/feedbacks");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'next_page_url',
                    'prev_page_url'
                ]);
    }

    public function test_can_store_feedback()
    {
        $feedbackData = [
            'customer_id' => $this->faker->randomNumber(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(),
        ];

        $response = $this->postJson("{$this->baseUrl}/feedbacks", $feedbackData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data',
                    'message'
                ]);
    }

    public function test_can_show_feedback()
    {
        $feedback = Feedback::factory()->create();

        $response = $this->getJson("{$this->baseUrl}/feedbacks/{$feedback->id}");

        $response->assertStatus(200)
                ->assertJson($feedback->toArray());
    }

    public function test_can_update_feedback()
    {
        $feedback = Feedback::factory()->create();
        $updateData = [
            'customer_id' => $this->faker->randomNumber(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(),
        ];

        $response = $this->putJson("{$this->baseUrl}/feedbacks/{$feedback->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson(['message' => 'Feedback updated successfully']);
    }

    public function test_can_delete_feedback()
    {
        $feedback = Feedback::factory()->create();

        $response = $this->deleteJson("{$this->baseUrl}/feedbacks/{$feedback->id}");

        $response->assertStatus(204);
    }

    public function test_can_get_average_rating()
    {
        Feedback::factory()->count(5)->create();

        $response = $this->getJson("{$this->baseUrl}/feedbacks/average");

        $response->assertStatus(200)
                ->assertJsonStructure(['average_rating']);
    }

    public function test_can_get_positive_feedback()
    {
        Feedback::factory()->count(3)->create(['rating' => 4]);

        $response = $this->getJson("{$this->baseUrl}/feedbacks/positive");

        $response->assertStatus(200)
                ->assertJsonCount(3);
    }

    public function test_can_get_negative_feedback()
    {
        Feedback::factory()->count(3)->create(['rating' => 2]);

        $response = $this->getJson("{$this->baseUrl}/feedbacks/negative");

        $response->assertStatus(200)
                ->assertJsonCount(3);
    }
}
