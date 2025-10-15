@php
    $filePathName = 'dynamic';
    $componentName = $filePathName . '.' . $name;
@endphp
@if ($componentData)
    @props(['attributes' => $attributes])
    <x-dynamic-component :component="$componentName" :componentData="$componentData"  {{ $attributes }}/>
@else
    <!-- Component {{ $componentName }} not found-->
@endif
