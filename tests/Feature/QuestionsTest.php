<?php

use Illuminate\Support\Facades\Hash;
use App\Models\Question;
use App\Models\User;


// Instead of beforeAll, use the Dataset approach
dataset('user', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    Question::factory()
        ->count(5)
        ->state([
            'user_id' => $user->id
        ])
        ->create();

    return $user;
});

describe('unauthenticated routes', function () {
    it('get all questions', function () {
        $this->withoutExceptionHandling();
        $response = $this->get('/api/questions');
        $response->assertStatus(200);
        expect(count(json_decode($response->content(), true)))->toBe(3);
    });
});
