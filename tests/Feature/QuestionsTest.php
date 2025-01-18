<?php

use Illuminate\Support\Facades\Hash;
use App\Models\Question;
use App\Models\User;


// Instead of beforeAll, use the Dataset approach
//dataset('user', function () {
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

describe('unauthenticated routes', function () {
    it('get all questions', function () {
        $this->withoutExceptionHandling();
        $response = $this->get('/api/questions');
        $response->assertStatus(200);
        expect(count(json_decode($response->content(), true)))->toBe(3);
    });

    it('shows a single question', function () {
        $response = $this->get('/api/question/' . $this->questions[0]->slug . '/show');
        $response->assertStatus(200);
        expect(count(json_decode($response->content(), true)))->not()->toBeEmpty();
    });

    it('get questions by given user', function () {
        $response = $this->post('/api/user/questions', [
            'user_id' => $this->user->id
        ]);
        $response->assertStatus(200);
        expect(count(json_decode($response->content(), true)))->not()->toBeEmpty();
    });

    it('get questions of logged in user', function () {
        $this->withoutExceptionHandling();
        $this->actingAs($this->user);
        $response = $this->get('/api/user/questions');
        $response->assertStatus(200);
        expect(count(json_decode($response->content(), true)))->not()->toBeEmpty();
    });
});
