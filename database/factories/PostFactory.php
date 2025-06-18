<?php

namespace Littleboy130491\Sumimasen\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Models\Post;
use Littleboy130491\Sumimasen\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Littleboy130491\Sumimasen\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);
        $slug = Str::slug($title);

        return [
            'author_id' => User::factory(),
            'status' => fake()->randomElement([
                ContentStatus::Published,
                ContentStatus::Draft,
                ContentStatus::Scheduled,
            ]),
            'title' => [
                'en' => $title,
                'id' => fake()->sentence(3),
            ],
            'slug' => [
                'en' => $slug,
                'id' => Str::slug(fake()->sentence(3)),
            ],
            'content' => [
                'en' => fake()->paragraphs(3, true),
                'id' => fake()->paragraphs(3, true),
            ],
            'excerpt' => [
                'en' => fake()->paragraph(),
                'id' => fake()->paragraph(),
            ],
            'published_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the post should be published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the post should be a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post should be scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Scheduled,
            'published_at' => fake()->dateTimeBetween('now', '+1 year'),
        ]);
    }

    /**
     * Set specific translations for the post.
     */
    public function withTranslations(array $translations): static
    {
        return $this->state(fn (array $attributes) => $translations);
    }

    /**
     * Create post without required relationships for testing.
     */
    public function withoutAuthor(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => null,
        ]);
    }
}
