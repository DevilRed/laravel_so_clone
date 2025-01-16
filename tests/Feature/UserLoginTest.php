<?php

use App\Models\User;

test('user can login', function () {
    $password = 'password123';
    $user = User::factory()->create(['password' => bcrypt($password)]);
    $response = $this->postJson('/api/user/login', [
        'email' => $user->email,
        'password' => $password
    ]);
    $response->assertStatus(200);
    $responseData = $response->json('data');
    expect($responseData['id'])->toBe($user->id);
    expect($response['access_token'])->not()->toBeNull();
});
