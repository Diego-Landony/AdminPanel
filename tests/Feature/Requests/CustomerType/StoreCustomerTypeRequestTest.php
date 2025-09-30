<?php

use App\Http\Requests\CustomerType\StoreCustomerTypeRequest;
use Illuminate\Support\Facades\Validator;

test('store customer type request passes with valid data', function () {
    $data = [
        'name' => 'Gold',
        'points_required' => 1000,
        'multiplier' => 1.5,
        'color' => 'yellow',
        'is_active' => true,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('store customer type request passes without optional color', function () {
    $data = [
        'name' => 'Silver',
        'points_required' => 500,
        'multiplier' => 1.2,
        'is_active' => true,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('store customer type request fails without name', function () {
    $data = [
        'points_required' => 1000,
        'multiplier' => 1.5,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
    expect($validator->errors()->first('name'))->toBe('El nombre del tipo de cliente es obligatorio');
});

test('store customer type request fails without points_required', function () {
    $data = [
        'name' => 'Gold',
        'multiplier' => 1.5,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('points_required'))->toBeTrue();
    expect($validator->errors()->first('points_required'))->toBe('Los puntos requeridos son obligatorios');
});

test('store customer type request fails without multiplier', function () {
    $data = [
        'name' => 'Gold',
        'points_required' => 1000,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('multiplier'))->toBeTrue();
    expect($validator->errors()->first('multiplier'))->toBe('El multiplicador es obligatorio');
});

test('store customer type request fails with negative points_required', function () {
    $data = [
        'name' => 'Gold',
        'points_required' => -100,
        'multiplier' => 1.5,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('points_required'))->toBeTrue();
    expect($validator->errors()->first('points_required'))->toBe('Los puntos requeridos no pueden ser negativos');
});

test('store customer type request fails with multiplier less than 1', function () {
    $data = [
        'name' => 'Gold',
        'points_required' => 1000,
        'multiplier' => 0.5,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('multiplier'))->toBeTrue();
    expect($validator->errors()->first('multiplier'))->toBe('El multiplicador debe ser al menos 1');
});

test('store customer type request fails with multiplier greater than 10', function () {
    $data = [
        'name' => 'Gold',
        'points_required' => 1000,
        'multiplier' => 15,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('multiplier'))->toBeTrue();
    expect($validator->errors()->first('multiplier'))->toBe('El multiplicador no puede exceder 10');
});

test('store customer type request fails with name exceeding max length', function () {
    $data = [
        'name' => str_repeat('a', 101),
        'points_required' => 1000,
        'multiplier' => 1.5,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
    expect($validator->errors()->first('name'))->toBe('El nombre no puede exceder 100 caracteres');
});

test('store customer type request fails with color exceeding max length', function () {
    $data = [
        'name' => 'Gold',
        'points_required' => 1000,
        'multiplier' => 1.5,
        'color' => str_repeat('a', 21),
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('color'))->toBeTrue();
    expect($validator->errors()->first('color'))->toBe('El color no puede exceder 20 caracteres');
});

test('store customer type request fails with non integer points_required', function () {
    $data = [
        'name' => 'Gold',
        'points_required' => 'not-a-number',
        'multiplier' => 1.5,
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('points_required'))->toBeTrue();
    expect($validator->errors()->first('points_required'))->toBe('Los puntos requeridos deben ser un número entero');
});

test('store customer type request fails with non numeric multiplier', function () {
    $data = [
        'name' => 'Gold',
        'points_required' => 1000,
        'multiplier' => 'not-a-number',
    ];

    $request = new StoreCustomerTypeRequest;
    $validator = Validator::make($data, $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('multiplier'))->toBeTrue();
    expect($validator->errors()->first('multiplier'))->toBe('El multiplicador debe ser un número');
});

test('store customer type request authorize returns true', function () {
    $request = new StoreCustomerTypeRequest;

    expect($request->authorize())->toBeTrue();
});
