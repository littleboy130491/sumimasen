<?php

namespace Littleboy130491\Sumimasen\Tests\Models;

use Awcodes\Curator\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Models\Component;
use Littleboy130491\Sumimasen\Tests\TestCase;

class ComponentModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_component()
    {
        $component = Component::create([
            'name' => 'Test Component',
            'data' => ['key' => 'value'],
            'notes' => 'Test notes',
        ]);

        $this->assertDatabaseHas('components', [
            'id' => $component->id,
            'name' => 'Test Component',
        ]);
    }

    /** @test */
    public function it_casts_data_to_array()
    {
        $data = ['title' => 'Test Title', 'content' => 'Test Content'];

        $component = Component::create([
            'name' => 'Test Component',
            'data' => $data,
        ]);

        $this->assertIsArray($component->data);
        $this->assertEquals($data, $component->data);
    }

    /** @test */
    public function it_has_translatable_data()
    {
        $component = Component::create([
            'name' => 'Test Component',
            'data' => [
                'en' => ['title' => 'English Title'],
                'id' => ['title' => 'Indonesian Title'],
            ],
        ]);

        $this->assertEquals(['title' => 'English Title'], $component->getTranslation('data', 'en'));
        $this->assertEquals(['title' => 'Indonesian Title'], $component->getTranslation('data', 'id'));
    }

    /** @test */
    public function it_processes_blocks_attribute_correctly()
    {
        $media = Media::factory()->create();

        $data = [
            [
                'type' => 'text',
                'data' => [
                    'content' => 'Some text',
                    'media_id' => $media->id,
                ],
            ],
            [
                'type' => 'image',
                'data' => [
                    'caption' => 'Image caption',
                ],
            ],
        ];

        $component = Component::create([
            'name' => 'Test Component',
            'data' => $data,
        ]);

        $blocks = $component->blocks;

        $this->assertIsArray($blocks);
        $this->assertCount(2, $blocks);
        $this->assertEquals('Some text', $blocks[0]['data']['content']);
        $this->assertEquals($media->url, $blocks[0]['data']['media_url']);
        $this->assertEquals('Image caption', $blocks[1]['data']['caption']);
        $this->assertArrayNotHasKey('media_url', $blocks[1]['data']);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $component = Component::create([
            'name' => 'Test Component',
            'data' => ['key' => 'value'],
        ]);

        $component->delete();

        $this->assertSoftDeleted('components', ['id' => $component->id]);
        $this->assertNotNull($component->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_store_complex_data_structures()
    {
        $complexData = [
            'sections' => [
                [
                    'type' => 'hero',
                    'settings' => [
                        'background_color' => '#ffffff',
                        'text_align' => 'center',
                    ],
                    'content' => [
                        'title' => 'Hero Title',
                        'subtitle' => 'Hero Subtitle',
                    ],
                ],
                [
                    'type' => 'gallery',
                    'settings' => [
                        'columns' => 3,
                        'spacing' => 'large',
                    ],
                    'images' => [1, 2, 3],
                ],
            ],
        ];

        $component = Component::create([
            'name' => 'Complex Component',
            'data' => $complexData,
        ]);

        $this->assertEquals($complexData, $component->data);
        $this->assertEquals('Hero Title', $component->data['sections'][0]['content']['title']);
        $this->assertEquals(3, $component->data['sections'][1]['settings']['columns']);
    }
}
