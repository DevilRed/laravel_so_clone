<?php

use Illuminate\Support\Facades\Hash;
use App\Models\Question;
use App\Models\User;

beforeEach(function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $questions = Question::factory()
        ->count(5)
        ->state([
            'user_id' => $user->id
        ])
        ->create();

    // Store in test instance
    $this->user = $user;
    $this->questions = $questions;
});

describe('auth answer routes', function () {
    it('should return validation message if adding an empty answer', function () {
        $this->actingAs($this->user);
        $url = '/api/answer/' . $this->questions[0]->slug . '/store';
        $response = $this->post($url, [
            'body' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('body');
    });
    it('should add an answer', function () {
        $this->actingAs($this->user);
        $url = '/api/answer/' . $this->questions[0]->slug . '/store';
        $response = $this->post($url, [
            'body' => 'sample answer',
        ]);

        $response->assertStatus(200);
    });
});
