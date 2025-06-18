<?php

namespace Littleboy130491\Sumimasen\Tests\Filament;

use Littleboy130491\Sumimasen\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Enums\ContentStatus;
use Littleboy130491\Sumimasen\Filament\Resources\PageResource\Pages\CreatePage;
use Littleboy130491\Sumimasen\Filament\Resources\PageResource\Pages\EditPage;
use Littleboy130491\Sumimasen\Filament\Resources\PageResource\Pages\ListPages;
use Littleboy130491\Sumimasen\Models\Page;
use Littleboy130491\Sumimasen\Tests\TestCase;
use Livewire\Livewire;

class PageResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    /** @test */
    public function it_can_render_list_pages()
    {
        Page::factory()->count(3)->create();

        Livewire::test(ListPages::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_list_pages()
    {
        $pages = Page::factory()->count(3)->create();

        Livewire::test(ListPages::class)
            ->assertCanSeeTableRecords($pages);
    }

    /** @test */
    public function it_can_search_pages_by_title()
    {
        $pages = Page::factory()->count(3)->create();
        $searchPage = $pages->first();
        $searchPage->update(['title' => ['en' => 'Unique Search Title']]);

        Livewire::test(ListPages::class)
            ->searchTable('Unique Search Title')
            ->assertCanSeeTableRecords([$searchPage])
            ->assertCanNotSeeTableRecords($pages->skip(1));
    }

    /** @test */
    public function it_can_filter_pages_by_status()
    {
        $publishedPage = Page::factory()->create(['status' => ContentStatus::Published]);
        $draftPage = Page::factory()->create(['status' => ContentStatus::Draft]);

        Livewire::test(ListPages::class)
            ->filterTable('status', ContentStatus::Published->value)
            ->assertCanSeeTableRecords([$publishedPage])
            ->assertCanNotSeeTableRecords([$draftPage]);
    }

    /** @test */
    public function it_can_render_create_page()
    {
        Livewire::test(CreatePage::class)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_create_a_page()
    {
        $newData = [
            'title.en' => 'New Page Title',
            'slug.en' => 'new-page-title',
            'content.en' => 'New page content',
            'status' => ContentStatus::Published,
            'author_id' => $this->admin->id,
        ];

        Livewire::test(CreatePage::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', [
            'status' => ContentStatus::Published,
            'author_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating()
    {
        Livewire::test(CreatePage::class)
            ->fillForm([
                'title.en' => '',
                'slug.en' => '',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'title.en' => 'required',
                'slug.en' => 'required',
            ]);
    }

    /** @test */
    public function it_can_render_edit_page()
    {
        $page = Page::factory()->create();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_retrieve_data_for_editing()
    {
        $page = Page::factory()->create([
            'title' => ['en' => 'Edit Test Page'],
            'slug' => ['en' => 'edit-test-page'],
            'content' => ['en' => 'Edit test content'],
        ]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormSet([
                'title.en' => 'Edit Test Page',
                'slug.en' => 'edit-test-page',
                'content.en' => 'Edit test content',
            ]);
    }

    /** @test */
    public function it_can_save_edited_page()
    {
        $page = Page::factory()->create();

        $newData = [
            'title.en' => 'Updated Page Title',
            'slug.en' => 'updated-page-title',
            'content.en' => 'Updated page content',
        ];

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $page->refresh();
        $this->assertEquals('Updated Page Title', $page->getTranslation('title', 'en'));
        $this->assertEquals('updated-page-title', $page->getTranslation('slug', 'en'));
        $this->assertEquals('Updated page content', $page->getTranslation('content', 'en'));
    }

    /** @test */
    public function it_validates_required_fields_when_editing()
    {
        $page = Page::factory()->create();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm([
                'title.en' => '',
                'slug.en' => '',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'title.en' => 'required',
                'slug.en' => 'required',
            ]);
    }

    /** @test */
    public function it_can_delete_a_page()
    {
        $page = Page::factory()->create();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('pages', ['id' => $page->id]);
    }

    /** @test */
    public function it_can_bulk_delete_pages()
    {
        $pages = Page::factory()->count(3)->create();

        Livewire::test(ListPages::class)
            ->callTableBulkAction(BulkAction::make('delete'), $pages);

        foreach ($pages as $page) {
            $this->assertSoftDeleted('pages', ['id' => $page->id]);
        }
    }

    /** @test */
    public function it_can_create_page_with_parent()
    {
        $parentPage = Page::factory()->create();

        $newData = [
            'title.en' => 'Child Page',
            'slug.en' => 'child-page',
            'content.en' => 'Child page content',
            'parent_id' => $parentPage->id,
            'status' => ContentStatus::Published,
            'author_id' => $this->admin->id,
        ];

        Livewire::test(CreatePage::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', [
            'parent_id' => $parentPage->id,
        ]);
    }

    /** @test */
    public function it_can_create_page_with_custom_fields()
    {
        $customFields = [
            'seo_keywords' => 'test, page, cms',
            'custom_meta' => 'Custom meta description',
            'additional_css' => 'body { background: red; }',
        ];

        $newData = [
            'title.en' => 'Page with Custom Fields',
            'slug.en' => 'page-with-custom-fields',
            'status' => ContentStatus::Published,
            'author_id' => $this->admin->id,
            'custom_fields' => $customFields,
        ];

        Livewire::test(CreatePage::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::where('slug->en', 'page-with-custom-fields')->first();
        $this->assertEquals($customFields, $page->custom_fields);
    }

    /** @test */
    public function it_can_create_page_with_sections()
    {
        $sections = [
            [
                'type' => 'text',
                'data' => [
                    'content' => 'This is a text section',
                    'alignment' => 'center',
                ],
            ],
            [
                'type' => 'image',
                'data' => [
                    'caption' => 'Image caption',
                    'alt_text' => 'Alt text for image',
                ],
            ],
        ];

        $newData = [
            'title.en' => 'Page with Sections',
            'slug.en' => 'page-with-sections',
            'status' => ContentStatus::Published,
            'author_id' => $this->admin->id,
            'section' => $sections,
        ];

        Livewire::test(CreatePage::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::where('slug->en', 'page-with-sections')->first();
        $this->assertEquals($sections, $page->section);
    }
}
