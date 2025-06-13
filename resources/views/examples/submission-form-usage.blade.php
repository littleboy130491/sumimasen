{{-- Example 1: Basic usage in any Blade view --}}
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Contact Us</h1>

    {{-- Include the Livewire component --}}
    <livewire:submission-form />
</div>

{{-- Example 2: Usage within a layout --}}
@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Left column: Information --}}
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Get in Touch</h1>
                <p class="text-gray-600 mb-6">
                    We'd love to hear from you. Send us a message and we'll respond as soon as possible.
                </p>

                <div class="space-y-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                        </svg>
                        <span class="text-gray-700">contact@example.com</span>
                    </div>

                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">123 Main St, City, State 12345</span>
                    </div>

                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z">
                            </path>
                        </svg>
                        <span class="text-gray-700">(555) 123-4567</span>
                    </div>
                </div>
            </div>

            {{-- Right column: Form --}}
            <div>
                <livewire:submission-form />
            </div>
        </div>
    </div>
@endsection

{{-- Example 3: Usage in a modal or popup --}}
<div x-data="{ showModal: false }">
    <button @click="showModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        Contact Us
    </button>

    <div x-show="showModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" @click="showModal = false"></div>

            <div class="relative bg-white rounded-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="flex justify-between items-center p-4 border-b">
                    <h3 class="text-lg font-semibold">Contact Us</h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-4">
                    <livewire:submission-form />
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Example 4: Usage with custom styling --}}
<div class="bg-gradient-to-r from-blue-500 to-purple-600 py-16">
    <div class="max-w-4xl mx-auto px-4">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-white mb-4">Ready to Get Started?</h2>
            <p class="text-blue-100">Contact us today and let's discuss your project.</p>
        </div>

        <div class="bg-white rounded-lg shadow-xl p-8">
            <livewire:submission-form />
        </div>
    </div>
</div>
