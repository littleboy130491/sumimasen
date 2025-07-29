<?php

namespace Littleboy130491\Sumimasen\View\Components;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

/**
 * LoopGrid Component
 *
 * A responsive grid component that accepts queries/collections and renders items
 * in a customizable Tailwind CSS grid layout with support for different breakpoints.
 *
 * Basic Usage:
 *   <x-sumimasen-cms-loop-grid :query="$posts">
 *       <div class="bg-white p-4 rounded">
 *           <h3>{{ $item->title }}</h3>
 *           <p>{{ $item->excerpt }}</p>
 *       </div>
 *   </x-sumimasen-cms-loop-grid>
 *
 * Advanced Usage:
 *   <x-sumimasen-cms-loop-grid
 *       :query="$posts"
 *       sm="2"
 *       md="3"
 *       lg="6"
 *       gap="4"
 *       :pagination="true"
 *       :attributes="['id' => 'posts-grid', 'class' => 'my-custom-class']">
 *
 *       <article class="bg-gray-50 rounded-lg">
 *           <h2>{{ $item->title }}</h2>
 *           <p>{{ $item->excerpt }}</p>
 *       </article>
 *   </x-sumimasen-cms-loop-grid>
 *
 * Parameters:
 *   - query: Eloquent Builder, Collection, LengthAwarePaginator, or array of items
 *   - sm: Number of columns on small screens (default: "1")
 *   - md: Number of columns on medium screens (default: "2")
 *   - lg: Number of columns on large screens (default: "4")
 *   - gap: Tailwind gap spacing (default: "7")
 *   - pagination: Whether to show pagination links (default: false)
 *   - attributes: Additional HTML attributes as array (default: [])
 *
 * Generated CSS classes follow this pattern:
 *   "grid lg:grid-cols-{lg} md:grid-cols-{md} sm:grid-cols-{sm} grid-cols-1 gap-{gap}"
 *
 * The $item variable is available in the slot content for each iteration.
 */
class LoopGrid extends Component
{
    public string $sm;

    public string $md;

    public string $lg;

    public string $gap;

    public bool $pagination;

    public $query;

    public array $attributes;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $query,
        string $sm = '1',
        string $md = '2',
        string $lg = '4',
        string $gap = '7',
        bool $pagination = false,
        array $attributes = []
    ) {
        $this->query = $query;
        $this->sm = $sm;
        $this->md = $md;
        $this->lg = $lg;
        $this->gap = $gap;
        $this->pagination = $pagination;
        $this->attributes = $attributes;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('sumimasen-cms::components.loop-grid');
    }

    /**
     * Get the grid CSS classes.
     */
    public function getGridClasses(): string
    {
        return "grid lg:grid-cols-{$this->lg} md:grid-cols-{$this->md} sm:grid-cols-{$this->sm} grid-cols-1 gap-{$this->gap}";
    }

    /**
     * Get additional attributes as string.
     */
    public function getAttributesString(): string
    {
        $attributeStrings = [];

        foreach ($this->attributes as $key => $value) {
            if ($key === 'class') {
                continue; // Handle class separately
            }
            $attributeStrings[] = "{$key}=\"{$value}\"";
        }

        return implode(' ', $attributeStrings);
    }

    /**
     * Get combined CSS classes including user-provided classes.
     */
    public function getCombinedClasses(): string
    {
        $gridClasses = $this->getGridClasses();
        $userClasses = $this->attributes['class'] ?? '';

        return trim("{$gridClasses} {$userClasses}");
    }

    /**
     * Execute the query and get results.
     */
    public function getResults()
    {
        if ($this->query instanceof Builder) {
            return $this->pagination ? $this->query->paginate() : $this->query->get();
        }

        if ($this->query instanceof Collection || $this->query instanceof LengthAwarePaginator) {
            return $this->query;
        }

        return collect($this->query);
    }
}
