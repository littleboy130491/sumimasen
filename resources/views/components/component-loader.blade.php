@php
    $filePathName = 'dynamic';
    $componentName = $filePathName . '.' . $name;
@endphp
@if ($componentData)
    <x-dynamic-component :component="$componentName" :componentData="$componentData" {{ $attributes }}/>
@else
    <!-- Component {{ $componentName }} not found-->
@endif
