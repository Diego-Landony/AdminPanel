<?php

use App\Http\Requests\User\StoreUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

test('store user request passes with valid data', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $request = new StoreUserRequest;
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('store user request fails without name', function () {
    $data = [
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $request = new StoreUserRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
    expect($validator->errors()->first('name'))->toBe('El nombre es obligatorio');
});

test('store user request fails without email', function () {
    $data = [
        'name' => 'John Doe',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $request = new StoreUserRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
    expect($validator->errors()->first('email'))->toBe('El correo electrónico es obligatorio');
});

test('store user request fails with invalid email', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $request = new StoreUserRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
    expect($validator->errors()->first('email'))->toBe('El correo electrónico debe ser válido');
});

test('store user request fails with duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $data = [
        'name' => 'John Doe',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $request = new StoreUserRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
    expect($validator->errors()->first('email'))->toBe('Este correo electrónico ya está registrado');
});

test('store user request fails without password', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ];

    $request = new StoreUserRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
    expect($validator->errors()->first('password'))->toBe('La contraseña es obligatoria');
});

test('store user request fails with unconfirmed password', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different123',
    ];

    $request = new StoreUserRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
    expect($validator->errors()->first('password'))->toBe('Las contraseñas no coinciden');
});

test('store user request fails with short password', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => '12345',
        'password_confirmation' => '12345',
    ];

    $request = new StoreUserRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
});

test('store user request fails with name exceeding max length', function () {
    $data = [
        'name' => str_repeat('a', 256),
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $request = new StoreUserRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
    expect($validator->errors()->first('name'))->toBe('El nombre no puede exceder 255 caracteres');
});

test('store user request authorize returns true', function () {
    $request = new StoreUserRequest;

    expect($request->authorize())->toBeTrue();
});
