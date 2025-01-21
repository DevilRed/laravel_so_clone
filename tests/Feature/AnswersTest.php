<?php

use App\Models\Answer;
use Illuminate\Support\Facades\Hash;
use App\Models\Question;
use App\Models\User;

beforeEach(function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
    $user2 = User::factory()->create();

    $questions = Question::factory()
        ->count(5)
        ->state([
            'user_id' => $user->id
        ])
        ->has(Answer::factory()
            ->state([
                'user_id' => $user2->id
            ]))
        ->create();

    // Store in test instance
    $this->user = $user;
    $this->user2 = $user2;
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
    it('should update an answer', function () {
        $this->actingAs($this->user2);
        $question = $this->questions[0];
        $answer = $question->answers->first();
        $url = '/api/update/' . $question->slug . '/' . $answer->id . '/answer';
        $response = $this->put($url, [
            'body' => 'updated answer',
        ]);

        $response->assertStatus(200);
    });

    it('should return forbidden for non owner user when deleting an answer', function () {
        $this->actingAs($this->user);
        $question = $this->questions[0];
        $answer = $question->answers->first();
        $url = '/api/delete/' . $question->slug . '/' . $answer->id . '/answer';
        $response = $this->delete($url, [
            'body' => 'updated answer',
        ]);

        $response->assertStatus(403);
    });
    it('should return successfull response when deleting an answer for user who owns the answer', function () {
        $this->actingAs($this->user2);
        $question = $this->questions[0];
        $answer = $question->answers->first();
        $url = '/api/delete/' . $question->slug . '/' . $answer->id . '/answer';
        $response = $this->delete($url, [
            'body' => 'updated answer',
        ]);

        $response->assertStatus(200);
    });
});
