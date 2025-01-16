<?php

use App\Models\User;

test('users can update their profile', function () {
    $this->withoutExceptionHandling();
    $user = User::factory()->create(['name' => 'thulio', 'password' => bcrypt('12346578')]);
    $this->actingAs($user);
    $response = $this->putJson('/api/update/profile', [
        'name' => 'Josa',
        'email' => 'josa@email.com'
    ]);
    expect($user->name)->not()->toBe('thulio');
});

it('users can update their password', function () {
    $this->withoutExceptionHandling();
    $user = User::factory()->create(['password' => bcrypt('12346578')]);
    $this->actingAs($user);
    $response = $this->putJson('/api/update/password', [
        'current_password' => '12346578',
        'password' => 'abcdfghj',
    ]);
    $response->assertStatus(200);
});
