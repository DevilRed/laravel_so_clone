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
});

describe('auth routes', function () {
    beforeEach(function () {
        //$this->withoutExceptionHandling();
        $this->actingAs($this->user);
    });
    it('get questions of logged in user', function () {
        $response = $this->get('/api/user/questions');
        $response->assertStatus(200);
        expect(count(json_decode($response->content(), true)))->not()->toBeEmpty();
    });

    it('store question', function () {
        $response = $this->post('/api/question/store', [
            'title' => 'title',
            'body' => 'body',
            'tags' => 'lala,jojo'
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'slug'
                ],
                'message'
            ]);
    });

    it('update question', function () {
        $this->withoutExceptionHandling();
        $question = $this->questions[0]->load('user');
        $url = "/api/update/{$question->slug}/question";

        $response = $this->put($url, [
            'title' => 'titleUpdated',
            'body' => 'bodyUpdated',
            'tags' => 'again'
        ]);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'tags',
                    'slug'
                ],
                'message',
                'user'
            ]);

        // Assert the database was updated
        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'title' => 'titleUpdated',
            'body' => 'bodyUpdated',
            'tags' => 'again'
        ]);
    });
    it('delete question', function () {
        $question = $this->questions[0];
        $url = "/api/delete/{$question->slug}/question";

        $response = $this->delete($url);
        $response->assertStatus(200);
    });
    it('stores and calculates votes dynamically', function () {
        $question = $this->questions[0];
        $urlUp = "/api/vote/{$question->slug}/up/question";
        $urlDown = "/api/vote/{$question->slug}/down/question";

        // User 1 votes up
        $user1 = User::factory()->create([
            'email' => 'user`@example.com',
            'password' => Hash::make('password'),
        ]);
        $response = $this->actingAs($user1)->put($urlUp);
        $response->assertStatus(200);
        $this->assertEquals(1, $question->votes()->where('type', 'up')->count());

        // User 2 votes down
        $user2 = User::factory()->create([
            'email' => 'user2`@example.com',
            'password' => Hash::make('password'),
        ]);
        $response = $this->actingAs($user2)->put($urlDown);
        $response->assertStatus(200);
        $this->assertEquals(1, $question->votes()->where('type', 'down')->count());

        // Check net votes
        $netVotes = $question->votes()->where('type', 'up')->count() - $question->votes()->where('type', 'down')->count();
        $this->assertEquals(0, $netVotes);
    });
});

describe('forbidden unauthorized access', function () {
    it('forbidden access unauthorized updates', function () {
        $user = User::factory()->create([
            'email' => 'test2@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);
        $question = $this->questions[0];
        $url = "/api/update/{$question->slug}/question";

        $response = $this->put($url, [
            'title' => 'titleUpdated',
            'body' => 'bodyUpdated',
            'tags' => 'again'
        ]);
        $response->assertStatus(403);
    });
    it('delete question is forbidden for unauthorized users', function () {
        $user = User::factory()->create([
            'email' => 'test2@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user);
        $question = $this->questions[0];
        $url = "/api/delete/{$question->slug}/question";

        $response = $this->delete($url, [
            'title' => 'titleUpdated',
            'body' => 'bodyUpdated',
            'tags' => 'again'
        ]);
        $response->assertStatus(403);
    });
});
