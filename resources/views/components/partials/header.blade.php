@pushOnce('before_head_close')
    <!-- Alpine JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpushOnce

<!--Start Header Menu-->
<div x-data="{ open: false, openSubMenu: null }">
    <header x-data="{ open: false }" class=" absolute top-0 left-1/2 -translate-x-1/2 lg:w-[1200px] z-99999">
        <div class="lg:max-w-[1200px] mx-auto  flex pt-5 justify-between">

            <!--Logo-->
            <div class=" flex items-center ">
                <a href="#"><img class="w-20 filter brightness-0 invert" src="{{ asset('media/logo.png') }}"
                        alt="logo"></a>
            </div>

            <!--Main Menu - Using the navigation-menu component-->
            <x-partials.navigation-menu location="header" />

            <!--Button-->
            <div class="self-center hidden lg:block sm:block">
                <a href="#"
                    class="text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md">
                    Unduh Brosur
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <div class="sm:hidden">
                <button @click="open = !open" class="text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Off-canvas Mobile Menu -->
            <div x-show="open" class="fixed inset-0 bg-black bg-opacity-50 z-40 sm:hidden" @click="open = false"></div>

            <div x-show="open"
                class="fixed top-0 right-0 w-[90%] h-full bg-cover shadow-lg z-50 transform transition-transform duration-300 ease-in-out sm:hidden"
                style="background-image: linear-gradient(90deg, rgba(255, 255, 255, 0.95) 10%, rgba(255, 255, 255, 0.45) 100%), url({{ asset('images/top-view-of-a-truck-driving-along-a-highway-road-i-2023-11-27-05-27-13-utc-111-scaled.jpg') }});"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

                <div class="px-6 mt-5">
                    <button @click="open = false" class="text-gray-700 float-right">
                        ✕
                    </button>

                    <div class="pt-10">

                        <!--Logo-->
                        <div class=" flex items-center ">
                            <a href="#"><img class="w-25" src="{{ asset('media/logo.png') }}"
                                    alt="logo"></a>
                        </div>

                        <!--Mobile Menu - Using the mobile-menu component-->
                        <x-partials.mobile-menu location="header" />

                        <!--Button-->
                        <div class="flex items-center lg:block sm:block mt-7">
                            <a href="#"
                                class="text-sm font-medium text-white bg-[var(--color-blue)] hover:bg-blue-700 px-4 py-2 rounded-md">
                                Sign In
                            </a>
                        </div>

                        <!-- Icon -->
                        <div class="flex flex-col gap-4 mt-10 ">
                            <a href="#" class="flex flex-row gap-2 ">
                                <i aria-hidden="true" class="fas fa-phone-alt text-[var(--color-blue)]"></i>
                                <p class="!text-[var(--color-heading)]">Telephone : +62 21 227 831 98</p>
                            </a>

                            <a href="#" class="flex flex-row gap-2">
                                <i aria-hidden="true" class="fab fa-whatsapp text-[var(--color-blue)]"></i>
                                <p class="!text-[var(--color-heading)]">Whatsapp : +62 8521 1881 421</p>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
    </header>
</div>
<!--End Header Menu-->