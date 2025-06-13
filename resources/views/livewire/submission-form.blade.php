<div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-lg">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ __('submission-form.title') }}</h2>
        <p class="text-gray-600">{{ __('submission-form.description') }}</p>
    </div>


    <form wire:submit="submit" class="space-y-6 mb-6">
        {{-- Name Field --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('submission-form.name') }} <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" wire:model.live="name"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror @if ($formSubmitted) bg-gray-100 cursor-not-allowed @endif"
                placeholder="{{ __('submission-form.name_placeholder') }}"
                @if ($formSubmitted) disabled @endif>
            @error('name')
                <p class="mt-1 text-sm text-red-600 flex items-center">
                    <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Email Field --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('submission-form.email') }} <span class="text-red-500">*</span>
            </label>
            <input type="email" id="email" wire:model.live="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('email') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror @if ($formSubmitted) bg-gray-100 cursor-not-allowed @endif"
                placeholder="{{ __('submission-form.email_placeholder') }}"
                @if ($formSubmitted) disabled @endif>
            @error('email')
                <p class="mt-1 text-sm text-red-600 flex items-center">
                    <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Phone Field --}}
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('submission-form.phone') }}
            </label>
            <input type="tel" id="phone" wire:model.live="phone"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('phone') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror @if ($formSubmitted) bg-gray-100 cursor-not-allowed @endif"
                placeholder="{{ __('submission-form.phone_placeholder') }}"
                @if ($formSubmitted) disabled @endif>
            @error('phone')
                <p class="mt-1 text-sm text-red-600 flex items-center">
                    <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Subject Field --}}
        <div>
            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('submission-form.subject') }}
            </label>
            <input type="text" id="subject" wire:model.live="subject"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('subject') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror @if ($formSubmitted) bg-gray-100 cursor-not-allowed @endif"
                placeholder="{{ __('submission-form.subject_placeholder') }}"
                @if ($formSubmitted) disabled @endif>
            @error('subject')
                <p class="mt-1 text-sm text-red-600 flex items-center">
                    <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Message Field --}}
        <div>
            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">
                {{ __('submission-form.message') }} <span class="text-red-500">*</span>
            </label>
            <textarea id="message" wire:model.live="message" rows="5"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 resize-vertical @error('message') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror @if ($formSubmitted) bg-gray-100 cursor-not-allowed @endif"
                placeholder="{{ __('submission-form.message_placeholder') }}" @if ($formSubmitted) disabled @endif></textarea>
            @error('message')
                <p class="mt-1 text-sm text-red-600 flex items-center">
                    <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- CAPTCHA Field - Only show if enabled --}}
        {{-- CAPTCHA Field - Only show if enabled --}}
        @if (!empty(config('captcha.sitekey')) && !empty(config('captcha.secret')))
            <div>
                <div class="@if ($formSubmitted) opacity-50 pointer-events-none @endif">
                    <!-- Simple reCAPTCHA implementation -->
                    <div class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}"
                        data-callback="onCaptchaSuccess" data-expired-callback="onCaptchaExpired">
                    </div>

                    {{-- Fallback for when JavaScript is disabled --}}
                    <noscript>
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-sm text-yellow-800">
                                {{ __('submission-form.captcha_js_required') }}
                            </p>
                        </div>
                    </noscript>
                </div>

                <!-- Load reCAPTCHA script -->
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>

                <script>
                    function onCaptchaSuccess(response) {
                        @this.set('captcha', response);
                    }

                    function onCaptchaExpired() {
                        @this.set('captcha', '');
                    }
                </script>

                @error('captcha')
                    <p class="mt-1 text-sm text-red-600 flex items-center">
                        <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ $errors->first('captcha') }}
                    </p>
                @enderror
            </div>
        @endif

        {{-- Submit Button --}}
        <div>
            <button type="submit" wire:loading.attr="disabled"
                class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 @if ($formSubmitted) !bg-gray-400 !cursor-not-allowed @endif"
                @if ($formSubmitted) disabled @endif>
                <span wire:loading.remove>
                    @if ($formSubmitted)
                        {{ __('submission-form.submitted') }}
                    @else
                        {{ __('submission-form.submit') }}
                    @endif
                </span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    {{ __('submission-form.submitting') }}
                </span>
            </button>
        </div>
    </form>
    {{-- Success Message --}}
    @if ($showSuccess)
        <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-90"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-90"
            class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">{{ __('submission-form.success_title') }}</h3>
                    <p class="text-sm text-green-700 mt-1">{{ __('submission-form.success_message') }}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button wire:click="hideSuccess"
                        class="inline-flex text-green-400 hover:text-green-600 focus:outline-none focus:text-green-600">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Error Message --}}
    @error('form')
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">{{ __('submission-form.error_title') }}</h3>
                    <p class="text-sm text-red-700 mt-1">{{ $errors->first('form') }}</p>
                </div>
            </div>
        </div>
    @enderror
    {{-- Required Fields Note / Form Submitted Note --}}
    <div class="mt-4 text-sm text-gray-500">
        @if ($formSubmitted)
            <div class="flex items-center text-green-600">
                <svg class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                {{ __('submission-form.form_submitted_note') }}
            </div>
        @else
            <span class="text-red-500">*</span> {{ __('submission-form.required_fields') }}
        @endif
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('hide-success-after-delay', () => {
            setTimeout(() => {
                Livewire.dispatch('hideSuccess');
            }, 5000);
        });
    });
</script>
