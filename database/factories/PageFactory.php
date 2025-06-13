<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Models\Page;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Littleboy130491\Sumimasen\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Page::class;

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
            'section' => [
                'en' => [],
                'id' => [],
            ],
            'template' => fake()->randomElement(['default', 'page', 'landing']),
            'menu_order' => fake()->numberBetween(0, 100),
            'custom_fields' => [],
            'published_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the page should be published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the page should be a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the page should be scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Scheduled,
            'published_at' => fake()->dateTimeBetween('now', '+1 year'),
        ]);
    }

    /**
     * Set specific translations for the page.
     */
    public function withTranslations(array $translations): static
    {
        return $this->state(fn (array $attributes) => $translations);
    }

    /**
     * Set the page as a child of another page.
     */
    public function childOf(Page $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }

    /**
     * Set custom fields for the page.
     */
    public function withCustomFields(array $customFields): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_fields' => $customFields,
        ]);
    }

    /**
     * Set section data for the page.
     */
    public function withSections(array $sections): static
    {
        return $this->state(fn (array $attributes) => [
            'section' => $sections,
        ]);
    }

    /**
     * Create page without required relationships for testing.
     */
    public function withoutAuthor(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_id' => null,
        ]);
    }
}
