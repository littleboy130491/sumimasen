<?php

namespace Database\Factories;

use Awcodes\Curator\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Awcodes\Curator\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->word() . '.' . fake()->randomElement(['jpg', 'png', 'gif', 'webp']);
        
        return [
            'disk' => 'public',
            'directory' => 'media',
            'filename' => $filename,
            'extension' => pathinfo($filename, PATHINFO_EXTENSION),
            'mime_type' => 'image/' . pathinfo($filename, PATHINFO_EXTENSION),
            'size' => fake()->numberBetween(1000, 1000000),
            'alt' => fake()->sentence(3),
            'title' => fake()->sentence(2),
            'description' => fake()->optional()->paragraph(),
            'caption' => fake()->optional()->sentence(),
            'exif' => [],
            'path' => 'media/' . $filename,
            'width' => fake()->numberBetween(100, 2000),
            'height' => fake()->numberBetween(100, 2000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create an image media file.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => fake()->randomElement(['jpg', 'png', 'gif', 'webp']),
            'mime_type' => 'image/' . fake()->randomElement(['jpeg', 'png', 'gif', 'webp']),
            'width' => fake()->numberBetween(100, 2000),
            'height' => fake()->numberBetween(100, 2000),
        ]);
    }

    /**
     * Create a document media file.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'extension' => fake()->randomElement(['pdf', 'doc', 'docx', 'txt']),
            'mime_type' => 'application/' . fake()->randomElement(['pdf', 'msword', 'vnd.openxmlformats-officedocument.wordprocessingml.document', 'plain']),
            'width' => null,
            'height' => null,
        ]);
    }
}