@php
    $filePathName = 'dynamic';
    $componentName = $filePathName . '.' . $name;
@endphp
@if ($componentData)
    <x-dynamic-component 
        :component="$componentName" 
        :componentData="$componentData"
        :class="$attributes->get('class')"
        {{ $attributes->except('class') }}
    />
@else
    <!-- Component {{ $componentName }} not found-->
@endif