<?php

use App\Models\Restaurant;

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['restaurants.view']);
    $this->actingAs($this->user);
});

describe('Geofences Index Page', function () {
    test('displays page successfully', function () {
        Restaurant::factory(3)->create();

        $response = $this->get(route('restaurants.geofences'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('restaurants/geofences')
            ->has('restaurants', 3)
            ->has('stats')
        );
    });

    test('includes geofence coordinates when KML exists', function () {
        $validKML = '<?xml version="1.0" encoding="UTF-8"?>
<kml><coordinates>-90.5069,14.6349,0</coordinates></kml>';

        Restaurant::factory()->create(['geofence_kml' => $validKML]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('restaurants.0.has_geofence', true)
            ->has('restaurants.0.geofence_coordinates', 1)
        );
    });

    test('returns empty geofence_coordinates when no KML', function () {
        Restaurant::factory()->create(['geofence_kml' => null]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertInertia(fn ($page) => $page
            ->where('restaurants.0.has_geofence', false)
            ->where('restaurants.0.geofence_coordinates', [])
        );
    });

    test('calculates statistics correctly', function () {
        Restaurant::factory(5)->create(['geofence_kml' => null, 'is_active' => true]);
        Restaurant::factory(3)->create([
            'geofence_kml' => '<kml><coordinates>-90,14,0</coordinates></kml>',
            'is_active' => true,
        ]);
        Restaurant::factory(2)->create(['is_active' => false]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertInertia(fn ($page) => $page
            ->where('stats.total_restaurants', 10)
            ->where('stats.restaurants_with_geofence', 3)
            ->where('stats.inactive_restaurants', 2)
        );
    });
});

describe('KML Coordinate Parsing', function () {
    test('parses single coordinate from KML', function () {
        $kml = '<kml><coordinates>-90.5069,14.6349,0</coordinates></kml>';

        Restaurant::factory()->create(['geofence_kml' => $kml]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertInertia(fn ($page) => $page
            ->where('restaurants.0.geofence_coordinates.0.lat', fn ($lat) => abs($lat - 14.6349) < 0.0001)
            ->where('restaurants.0.geofence_coordinates.0.lng', fn ($lng) => abs($lng - (-90.5069)) < 0.0001)
        );
    });

    test('parses multiple coordinates from KML', function () {
        $kml = '<kml><coordinates>-90.50,14.63,0 -90.51,14.64,0 -90.52,14.65,0</coordinates></kml>';

        Restaurant::factory()->create(['geofence_kml' => $kml]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertInertia(fn ($page) => $page
            ->has('restaurants.0.geofence_coordinates', 3)
        );
    });

    test('handles malformed KML gracefully', function () {
        $malformedKML = '<kml><coordinates>invalid xml';

        Restaurant::factory()->create(['geofence_kml' => $malformedKML]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('restaurants.0.geofence_coordinates', [])
        );
    });

    test('handles empty KML string', function () {
        Restaurant::factory()->create(['geofence_kml' => '']);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertInertia(fn ($page) => $page
            ->where('restaurants.0.has_geofence', false)
            ->where('restaurants.0.geofence_coordinates', [])
        );
    });

    test('handles KML without coordinate tags', function () {
        $kml = '<?xml version="1.0"?><kml><Document><name>Test</name></Document></kml>';

        Restaurant::factory()->create(['geofence_kml' => $kml]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertInertia(fn ($page) => $page
            ->where('restaurants.0.geofence_coordinates', [])
        );
    });

    test('handles invalid coordinate format', function () {
        $kml = '<kml><coordinates>invalid,format</coordinates></kml>';

        Restaurant::factory()->create(['geofence_kml' => $kml]);

        $response = $this->get(route('restaurants.geofences'));

        // Should not crash
        $response->assertSuccessful();
    });
});

describe('Security', function () {
    test('handles XXE injection attempt safely', function () {
        $xxeKML = '<?xml version="1.0"?>
<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<kml><coordinates>&xxe;</coordinates></kml>';

        Restaurant::factory()->create(['geofence_kml' => $xxeKML]);

        $response = $this->get(route('restaurants.geofences'));

        // Should not crash or expose file contents
        $response->assertSuccessful();
    });

    test('handles extremely large KML without timeout', function () {
        $hugeCoordinates = str_repeat('-90.5,14.6,0 ', 5000);
        $largeKML = "<kml><coordinates>{$hugeCoordinates}</coordinates></kml>";

        Restaurant::factory()->create(['geofence_kml' => $largeKML]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertSuccessful();
    });
});

describe('Permissions', function () {
    test('user without restaurants.view permission is redirected', function () {
        $userWithoutPermission = createTestUserWithPermissions([]);
        $this->actingAs($userWithoutPermission);

        $response = $this->get(route('restaurants.geofences'));

        // System redirects to no-access page
        $response->assertRedirect(route('no-access'));
    });

    test('guest user is redirected to login', function () {
        auth()->logout();

        $response = $this->get(route('restaurants.geofences'));

        $response->assertRedirect(route('login'));
    });

    test('unverified user is redirected to no-access', function () {
        $unverifiedUser = \App\Models\User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($unverifiedUser);

        $response = $this->get(route('restaurants.geofences'));

        // System redirects to no-access, not verification notice
        $response->assertRedirect(route('no-access'));
    });
});

describe('Edge Cases', function () {
    test('handles empty database', function () {
        $response = $this->get(route('restaurants.geofences'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('restaurants', 0)
            ->where('stats.total_restaurants', 0)
        );
    });

    test('handles mix of active and inactive restaurants', function () {
        Restaurant::factory(3)->create(['is_active' => true]);
        Restaurant::factory(2)->create(['is_active' => false]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('restaurants', 5)
            ->where('stats.inactive_restaurants', 2)
        );
    });

    test('handles coordinates at extreme valid ranges', function () {
        $extremeKML = '<kml><coordinates>-180.0,90.0,0 180.0,-90.0,0</coordinates></kml>';

        Restaurant::factory()->create(['geofence_kml' => $extremeKML]);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('restaurants.0.geofence_coordinates', 2)
        );
    });

    test('handles multiple restaurants with mixed geofence states', function () {
        Restaurant::factory()->create(['geofence_kml' => null]);
        Restaurant::factory()->create(['geofence_kml' => '<kml><coordinates>-90,14,0</coordinates></kml>']);
        Restaurant::factory()->create(['geofence_kml' => '']);

        $response = $this->get(route('restaurants.geofences'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('restaurants', 3)
            ->where('stats.restaurants_with_geofence', 1)
        );
    });
});
