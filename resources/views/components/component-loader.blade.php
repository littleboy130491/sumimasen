@php
    $filePathName = 'dynamic';
    $componentName = $filePathName . '.' . $name;
@endphp
@if ($componentData)
    <x-sumimasen-cms::dynamic-component :component="$componentName" :componentData="$componentData" />
@else
    <!-- Component {{ $componentName }} not found-->
@endif
