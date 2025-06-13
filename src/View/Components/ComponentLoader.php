<?php

namespace Littleboy130491\Sumimasen\View\Components;

use Illuminate\View\Component;
use Littleboy130491\Sumimasen\Models\Component as ComponentModel;

/**
 * Class ComponentLoader
 *
 * This Blade component dynamically fetches component data from the database
 * using the provided name, and passes the data to another Blade component for rendering.
 *
 * Typical usage:
 * <x-component-loader name="slide" />
 *
 * This will:
 * - Query the 'components' table for a record with name 'slide'
 * - Retrieve the associated data
 * - Pass the data to a dynamic component for rendering (e.g., <x-dynamic.slide :componentData="$componentData" />)
 * - In the blade view, you can access the data using $componentData->blocks, see Littleboy130491\Sumimasen\Models\Component getBlocksAttribute method.
 *
 * @package Littleboy130491\Sumimasen\View\Components
 */
class ComponentLoader extends Component
{
    /**
     * The name identifier for the component to load.
     *
     * @var string
     */
    public $name;

    /**
     * The data fetched from the database for the given component.
     *
     * @var mixed|null
     */
    public $componentData;

    /**
     * Create a new component instance.
     *
     * @param string $name The slug of the component to load.
     */
    public function __construct($name)
    {
        $this->name = $name;
        // Fetch the first component with the given slug and retrieve its 'data' property, or null if not found.
        $this->componentData = ComponentModel::where('name', $name)->first() ?? null;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        // Render the dynamic component loader Blade view.
        return view('components.component-loader');
    }
}
