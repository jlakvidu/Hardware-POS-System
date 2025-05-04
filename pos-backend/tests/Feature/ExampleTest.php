<?php

it('returns a successful response', function () {
    $response = $this->get('/api/v1');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'API is working'
        ]);
});
