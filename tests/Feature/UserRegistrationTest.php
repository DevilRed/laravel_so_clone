<?php

// use Illuminate\Validation\ValidationException;

test('fields are required during user registration', function () {
    $response = $this->postJson(route('users.register'), ['data' => []])
        ->assertInvalid([
            'name' => 'The name field is required.',
            'email' => 'The email field is required.',
            'password' => 'The password field is required.',
        ]);
});
test('provide required fields but with incorrect format shows error', function () {
    $response = $this->postJson(route('users.register'), [
        'name' => 'name',
        'email' => 'email',
        'password' => 'pass'
    ])
        ->assertInvalid([
            'email' => 'The email field must be a valid email address.',
            'password' => 'The password field must be at least 8 characters.',
        ]);
});
test('user is created successfully after sending correct data', function () {
    $response = $this->postJson(route('users.register'), [
        'name' => 'name',
        'email' => 'email@email.com',
        'password' => 'pass123456'
    ])
        ->assertStatus(200);
});
