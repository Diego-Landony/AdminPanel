<?php

test('sistema responde correctamente', function () {
    $response = $this->get('/');

    // El sistema debe responder (puede ser 200 o 302 para redirección)
    expect($response->status())->toBeIn([200, 302]);
});

test('página de login es accesible', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('página de registro es accesible', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('base de datos está configurada correctamente', function () {
    // Verificar que podemos conectar a la base de datos
    $this->assertDatabaseHas('migrations', []);
});

test('modelos principales existen', function () {
    // Verificar que los modelos principales están disponibles
    expect(class_exists(\App\Models\User::class))->toBeTrue();
    expect(class_exists(\App\Models\Role::class))->toBeTrue();
    expect(class_exists(\App\Models\Permission::class))->toBeTrue();
});

test('servicios principales están disponibles', function () {
    // Verificar que el servicio de permisos está disponible
    expect(class_exists(\App\Services\PermissionDiscoveryService::class))->toBeTrue();
});
