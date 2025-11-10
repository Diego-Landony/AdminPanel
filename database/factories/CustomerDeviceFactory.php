<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerDevice>
 */
class CustomerDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $loginCount = fake()->numberBetween(1, 50);
        $daysSinceCreation = fake()->numberBetween(1, 365);
        $isRecent = fake()->boolean(70);

        return [
            'customer_id' => Customer::factory(),
            'fcm_token' => fake()->randomFloat() < 0.8 ? fake()->unique()->sha256() : null,
            'device_identifier' => fake()->unique()->uuid(),
            'device_fingerprint' => fake()->randomFloat() < 0.9 ? fake()->sha256() : null,
            'device_type' => fake()->randomElement(['ios', 'android', 'web']),
            'device_name' => fake()->optional()->randomElement([
                'iPhone de Juan',
                'iPad Pro',
                'Samsung Galaxy',
                'Xiaomi Mi 11',
                'Google Pixel',
                'Navegador Chrome',
                'Navegador Safari',
            ]),
            'device_model' => fake()->optional()->randomElement([
                'iPhone 14 Pro',
                'iPhone 13',
                'iPad Pro 12.9',
                'Samsung Galaxy S23',
                'Xiaomi Mi 11',
                'Google Pixel 7',
                'Chrome on Windows',
                'Safari on macOS',
            ]),
            'app_version' => fake()->optional(0.8)->randomElement(['1.0.0', '1.0.5', '1.1.0', '1.2.0', '2.0.0']),
            'os_version' => fake()->optional(0.8)->randomElement([
                'iOS 17.2',
                'iOS 16.5',
                'Android 14',
                'Android 13',
                'macOS 14.0',
                'Windows 11',
            ]),
            'last_used_at' => $isRecent
                ? fake()->dateTimeBetween('-30 days', 'now')
                : fake()->optional(0.7)->dateTimeBetween('-90 days', '-31 days'),
            'is_active' => true,
            'login_count' => $loginCount,
            'trust_score' => $this->calculateInitialTrustScore($loginCount, $daysSinceCreation),
        ];
    }

    /**
     * Calculate initial trust score based on login count and device age
     */
    protected function calculateInitialTrustScore(int $loginCount, int $daysSinceCreation): int
    {
        $score = 50; // Base score

        // More logins = more trust (max +30)
        $score += min(30, $loginCount * 2);

        // Older device = more trust (max +20)
        $score += min(20, floor($daysSinceCreation / 7));

        return min(100, $score);
    }

    /**
     * Indicate that the device is for iOS.
     */
    public function ios(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'ios',
            'device_name' => fake()->randomElement(['iPhone de Juan', 'iPad Pro', 'iPhone de María']),
            'device_model' => fake()->randomElement(['iPhone 14 Pro', 'iPhone 13', 'iPad Pro 12.9', 'iPhone 15']),
        ]);
    }

    /**
     * Indicate that the device is for Android.
     */
    public function android(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'android',
            'device_name' => fake()->randomElement(['Samsung Galaxy', 'Xiaomi Mi 11', 'Google Pixel']),
            'device_model' => fake()->randomElement(['Samsung Galaxy S23', 'Xiaomi Mi 11', 'Google Pixel 7', 'OnePlus 11']),
        ]);
    }

    /**
     * Indicate that the device is web.
     */
    public function web(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'web',
            'device_name' => fake()->randomElement(['Navegador Chrome', 'Navegador Safari', 'Navegador Firefox']),
            'device_model' => fake()->randomElement(['Chrome on Windows', 'Safari on macOS', 'Firefox on Linux']),
        ]);
    }

    /**
     * Indicate that the device is active (used recently).
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $loginCount = fake()->numberBetween(5, 50);

            return [
                'fcm_token' => $attributes['fcm_token'] ?? (fake()->randomFloat() < 0.9 ? fake()->unique()->sha256() : null),
                'device_fingerprint' => $attributes['device_fingerprint'] ?? fake()->sha256(),
                'last_used_at' => fake()->dateTimeBetween('-7 days', 'now'),
                'is_active' => true,
                'login_count' => $loginCount,
                'trust_score' => min(100, 50 + ($loginCount * 2)),
            ];
        });
    }

    /**
     * Indicate that the device is inactive (not used recently).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => fake()->optional(0.7)->dateTimeBetween('-90 days', '-31 days'),
            'is_active' => false,
            'login_count' => fake()->numberBetween(1, 10),
            'trust_score' => fake()->numberBetween(20, 60),
        ]);
    }

    /**
     * Indicate that this is a new device (first login).
     */
    public function newDevice(): static
    {
        return $this->state(fn (array $attributes) => [
            'login_count' => 1,
            'trust_score' => 50,
            'last_used_at' => now(),
            'is_active' => true,
            'device_fingerprint' => fake()->sha256(),
        ]);
    }

    /**
     * Indicate that this is a trusted device (many logins).
     */
    public function trustedDevice(): static
    {
        return $this->state(fn (array $attributes) => [
            'fcm_token' => $attributes['fcm_token'] ?? fake()->unique()->sha256(),
            'device_fingerprint' => $attributes['device_fingerprint'] ?? fake()->sha256(),
            'login_count' => fake()->numberBetween(50, 200),
            'trust_score' => fake()->numberBetween(85, 100),
            'last_used_at' => fake()->dateTimeBetween('-3 days', 'now'),
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that this device has low trust score.
     */
    public function lowTrust(): static
    {
        return $this->state(fn (array $attributes) => [
            'login_count' => fake()->numberBetween(1, 5),
            'trust_score' => fake()->numberBetween(20, 40),
            'device_fingerprint' => null,
        ]);
    }

    /**
     * Indicate that this device has high trust score.
     */
    public function highTrust(): static
    {
        return $this->state(fn (array $attributes) => [
            'fcm_token' => $attributes['fcm_token'] ?? fake()->unique()->sha256(),
            'device_fingerprint' => $attributes['device_fingerprint'] ?? fake()->sha256(),
            'login_count' => fake()->numberBetween(30, 100),
            'trust_score' => fake()->numberBetween(80, 100),
        ]);
    }
}
