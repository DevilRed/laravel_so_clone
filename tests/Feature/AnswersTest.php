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
    it('should vote for an answer', function () {
        $question = $this->questions[0];
        $answer = $question->answers->first();
        $urlUp = "/api/vote/{$answer->id}/up/answer";
        $urlDown = "/api/vote/{$answer->id}/down/answer";
        // vote up
        $response = $this->actingAs($this->user2)->put($urlUp);

        $response->assertStatus(200);
        $this->assertEquals(1, $answer->votes()->where('type', 'up')->count());

        // vote down
        $user22 = User::factory()->create();
        $response = $this->actingAs($user22)->put($urlDown);
        $response->assertStatus(200);
        $this->assertEquals(1, $answer->votes()->where('type', 'down')->count());
    });
    it('should vote for an answer twice is not allowed', function () {
        $question = $this->questions[0];
        $answer = $question->answers->first();
        $urlUp = "/api/vote/{$answer->id}/up/answer";
        $urlDown = "/api/vote/{$answer->id}/down/answer";
        // vote up
        $response = $this->actingAs($this->user2)->put($urlUp);
        // vote again
        $response = $this->actingAs($this->user2)->put($urlUp);
        $response->assertStatus(400);
    });
    it('should mark answer as best', function () {
        $question = $this->questions[0];
        $answer = $question->answers->first();
        $url = "/api/mark/{$answer->id}/best";

        $response = $this->actingAs($this->user)->put($url);
        // vote again
        $response->assertStatus(200);
        $this->assertDatabaseHas('answers', [
            'id' => $answer->id,
            'best_answer' => 1,
        ]);
    });
    it('should return forbidden when marking answer as best for non owner user', function () {
        $question = $this->questions[0];
        $answer = $question->answers->first();
        $url = "/api/mark/{$answer->id}/best";

        $response = $this->actingAs($this->user2)->put($url);
        // vote again
        $response->assertStatus(403);
    });
    it('should update best answer', function () {
        $question = $this->questions[0];
        $answer = $question->answers->first();
        $url = "/api/mark/{$answer->id}/best";

        $response = $this->actingAs($this->user)->put($url);
        // add another answer
        $urlAdd = '/api/answer/' . $this->questions[0]->slug . '/store';
        $response = $this->actingAs($this->user)->post($urlAdd, [
            'body' => 'sample answer',
        ]);
        // mark the new answer as best
        $question->refresh(); // refresh model to reload the relationship
        $newAnswer = $question->answers->last();
        $url = "/api/mark/{$newAnswer->id}/best";
        $response = $this->actingAs($this->user)->put($url);
        $response->assertStatus(200);

        expect($question->answers()->count())->toBe(2);
        expect($answer->fresh()->best_answer)->toBe(0);
        expect($newAnswer->fresh()->best_answer)->toBe(1);
    });
});
