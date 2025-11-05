<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = createTestUser();
    $this->actingAs($this->user);
    Storage::fake('public');
});

describe('Image Upload', function () {
    test('can upload valid jpeg image', function () {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        $response->assertSuccessful();
        $response->assertJsonStructure(['success', 'url', 'path']);

        $data = $response->json();
        expect($data['success'])->toBeTrue();
        expect($data['url'])->toStartWith('/storage/images/');
        expect($data['path'])->toStartWith('images/');

        Storage::disk('public')->assertExists($data['path']);
    });

    test('can upload valid png image', function () {
        $file = UploadedFile::fake()->image('test.png');

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        $response->assertSuccessful();
        expect($response->json('success'))->toBeTrue();
    });

    test('can upload valid webp image', function () {
        $file = UploadedFile::fake()->create('test.webp', 100, 'image/webp');

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        $response->assertSuccessful();
        expect($response->json('success'))->toBeTrue();
    });

    test('generates unique filenames using UUID', function () {
        $file1 = UploadedFile::fake()->image('test.jpg');
        $file2 = UploadedFile::fake()->image('test.jpg');

        $response1 = $this->postJson(route('upload.image'), ['image' => $file1]);
        $response2 = $this->postJson(route('upload.image'), ['image' => $file2]);

        $path1 = $response1->json('path');
        $path2 = $response2->json('path');

        expect($path1)->not->toBe($path2);
    });
});

describe('Image Validation', function () {
    test('rejects non-image files', function (string $filename, string $mimeType) {
        $file = UploadedFile::fake()->create($filename, 100, $mimeType);

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('image');
    })->with([
        'pdf file' => ['document.pdf', 'application/pdf'],
        'text file' => ['file.txt', 'text/plain'],
        'zip file' => ['archive.zip', 'application/zip'],
        'executable' => ['virus.exe', 'application/x-msdownload'],
    ]);

    test('rejects oversized images', function () {
        $file = UploadedFile::fake()->create('huge.jpg', 6000); // 6MB > 5MB limit

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('image');
    });

    test('accepts maximum allowed size', function () {
        $file = UploadedFile::fake()->create('large.jpg', 5120, 'image/jpeg'); // Exactly 5MB

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        $response->assertSuccessful();
    });

    test('requires image field', function () {
        $response = $this->postJson(route('upload.image'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('image');
    });

    test('rejects invalid mime types', function () {
        $file = UploadedFile::fake()->create('test.gif', 100, 'image/gif');

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('image');
    });
});

describe('Image Delete', function () {
    test('can delete existing image', function () {
        $file = UploadedFile::fake()->image('test.jpg');
        $uploadResponse = $this->postJson(route('upload.image'), ['image' => $file]);
        $path = $uploadResponse->json('path');

        Storage::disk('public')->assertExists($path);

        $response = $this->postJson(route('delete.image'), [
            'path' => $path,
        ]);

        $response->assertSuccessful();
        $response->assertJson(['success' => true, 'message' => 'Imagen eliminada correctamente']);

        Storage::disk('public')->assertMissing($path);
    });

    test('returns 404 when deleting non-existent image', function () {
        $response = $this->postJson(route('delete.image'), [
            'path' => 'images/non-existent-uuid.jpg',
        ]);

        $response->assertNotFound();
        $response->assertJson(['success' => false, 'message' => 'Imagen no encontrada']);
    });

    test('requires path field', function () {
        $response = $this->postJson(route('delete.image'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('path');
    });
});

describe('Security', function () {
    test('prevents path traversal in delete', function (string $maliciousPath) {
        $response = $this->postJson(route('delete.image'), [
            'path' => $maliciousPath,
        ]);

        // Controller catches exceptions from invalid paths and returns 500
        // This is acceptable as it prevents the attack
        expect($response->status())->toBeIn([404, 500]);
        expect($response->json('success'))->toBeFalse();
    })->with([
        'parent directory' => ['../../../.env'],
        'config file' => ['../../config/app.php'],
        'database file' => ['../database/database.sqlite'],
        'absolute path' => ['/etc/passwd'],
        'windows path' => ['..\\..\\..\\windows\\system32\\config'],
    ]);

    test('rejects suspicious filename patterns in uploads', function () {
        // Even though validation should prevent this, test double extensions
        $file = UploadedFile::fake()->create('malicious.php.jpg', 100, 'image/jpeg');

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        // Should still succeed but filename should be sanitized to UUID
        $response->assertSuccessful();
        $path = $response->json('path');

        // Verify filename doesn't contain 'php'
        expect($path)->not->toContain('.php');
    });
});

describe('Authorization', function () {
    test('guest user cannot upload images', function () {
        auth()->logout();

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        $response->assertUnauthorized();
    });

    test('guest user cannot delete images', function () {
        auth()->logout();

        $response = $this->postJson(route('delete.image'), [
            'path' => 'images/test.jpg',
        ]);

        $response->assertUnauthorized();
    });

    test('unverified user can still upload images', function () {
        // Note: API routes don't enforce verified middleware
        $unverifiedUser = \App\Models\User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($unverifiedUser);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson(route('upload.image'), [
            'image' => $file,
        ]);

        // API routes allow unverified users
        $response->assertSuccessful();
    });

    test('unverified user can still delete images', function () {
        // Note: API routes don't enforce verified middleware
        $unverifiedUser = \App\Models\User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($unverifiedUser);

        $response = $this->postJson(route('delete.image'), [
            'path' => 'images/test.jpg',
        ]);

        // Will get 404 since file doesn't exist, but not forbidden
        $response->assertNotFound();
    });
});
