<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable
        class="border-e border-slate-200 bg-slate-300 dark:border-slate-800 dark:bg-slate-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        @if(auth()->user()->role_id == 3)
        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Gestión')" class="grid">
                <x-nav-link :href="route('dashboard.manager')" icon="cog-6-tooth" :active="request()->routeIs('dashboard.manager')">
                    {{ __('Panel de Gestión') }}
                </x-nav-link>
            </flux:navlist.group>
        </flux:navlist>
        <flux:navlist variant="outline">
            <flux:navlist.group heading="{{ __('Administrador') }}" expandable
                :expanded="request()->routeIs('customers.index') || request()->routeIs('users.*')">
                <x-nav-link :href="route('users.index')" icon="user-group" :active="request()->routeIs('users.*')">
                    {{ __('Adm. Usuarios') }}
                </x-nav-link>
            </flux:navlist.group>
        </flux:navlist>
        @elseif(auth()->user()->role_id == 5)
        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Auditoría')" class="grid">
                <x-nav-link :href="route('dashboard.auditor')" icon="clipboard-document-check"
                    :active="request()->routeIs('dashboard.auditor')">
                    {{ __('Panel de Auditor') }}
                </x-nav-link>
            </flux:navlist.group>
        </flux:navlist>
        @else
        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Menu')" class="grid">
                <x-nav-link :href="route('dashboard')" icon="chart-pie" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-nav-link>

                {{-- Nuevo Dashboard KPI --}}
                <x-nav-link :href="route('dashboard.principal')" icon="presentation-chart-bar" :active="request()->routeIs('dashboard.principal')">
                    {{ __('Dashboard Calidad') }}
                </x-nav-link>
            </flux:navlist.group>
        </flux:navlist>
        <flux:navlist variant="outline">
            <flux:navlist.group heading="{{ __('Administrador') }}" expandable
                :expanded="request()->routeIs('customers.index') || request()->routeIs('users.*')">
                <x-nav-link :href="route('users.index')" icon="user-group" :active="request()->routeIs('users.*')">
                    {{ __('Adm. Usuarios') }}
                </x-nav-link>
            </flux:navlist.group>
        </flux:navlist>
        @endif
        <flux:navlist.group heading="{{ __('Registros') }}" expandable :expanded="request()->routeIs('calidad.*')">
            <x-nav-link :href="route('calidad.inspeccion')" icon="clipboard-document-list"
                :active="request()->routeIs('calidad.inspeccion')">
                {{ __('Inspección de Tela') }}
            </x-nav-link>

            <x-nav-link :href="route('calidad.auditoria')" icon="clipboard-document-check"
                :active="request()->routeIs('calidad.auditoria')">
                {{ __('Auditoría a Materia Prima') }}
            </x-nav-link>
        </flux:navlist.group>

        <flux:navlist.group heading="Reportes" expandable :expanded="request()->routeIs('reportes.*')">
            <x-nav-link :href="route('reportes.inspeccion')" icon="presentation-chart-line"
                :active="request()->routeIs('reportes.inspeccion')">
                {{ __('Reporte Inspección') }}
            </x-nav-link>
        </flux:navlist.group>

        <flux:spacer />

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down" />

            <flux:menu class="w-[220px]">
                {{-- User Info Header --}}
                <div class="px-1 py-1.5">
                    <div class="flex items-center gap-2 text-start text-sm">
                        <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                            <span
                                class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                {{ auth()->user()->initials() }}
                            </span>
                        </span>
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </div>

                <flux:menu.separator />

                <div class="px-2 py-4">
                    <div class="flex flex-col gap-1 w-full mb-4 items-start opacity-60" x-data>
                        <flux:button class="w-full justify-start text-left pl-3" icon="sun" x-on:click.prevent
                            variant="ghost" disabled aria-disabled="true"
                            x-bind:class="$flux.appearance === 'light' ? 'bg-white dark:bg-slate-600 text-zinc-900 dark:text-white shadow-sm ring-1 ring-zinc-900/5 dark:ring-white/10' : ''">
                            {{ __('Light') }}
                        </flux:button>

                        <flux:button class="w-full justify-start text-left pl-3" icon="moon" x-on:click.prevent
                            variant="ghost" disabled aria-disabled="true"
                            x-bind:class="$flux.appearance === 'dark' ? 'bg-white dark:bg-slate-600 text-zinc-900 dark:text-white shadow-sm ring-1 ring-zinc-900/5 dark:ring-white/10' : ''">
                            {{ __('Dark') }}
                        </flux:button>

                        <flux:button class="w-full justify-start text-left pl-3" icon="computer-desktop" x-on:click.prevent
                            variant="ghost" disabled aria-disabled="true"
                            x-bind:class="$flux.appearance === 'system' ? 'bg-white dark:bg-slate-600 text-zinc-900 dark:text-white shadow-sm ring-1 ring-zinc-900/5 dark:ring-white/10' : ''">
                            {{ __('Sistema') }}
                        </flux:button>
                    </div>
                </div>

                <flux:menu.separator />

                {{-- Logout Form --}}
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Cerrar Sesion') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>
                <div class="px-2 py-4">
                    <div class="flex flex-col gap-1 w-full mb-4 items-start opacity-60" x-data>
                        <flux:button class="w-full justify-start text-left pl-3" icon="sun" x-on:click.prevent
                            variant="ghost" disabled aria-disabled="true"
                            x-bind:class="$flux.appearance === 'light' ? 'bg-white dark:bg-slate-600 text-zinc-900 dark:text-white shadow-sm ring-1 ring-zinc-900/5 dark:ring-white/10' : ''">
                            {{ __('Light') }}
                        </flux:button>

                        <flux:button class="w-full justify-start text-left pl-3" icon="moon" x-on:click.prevent
                            variant="ghost" disabled aria-disabled="true"
                            x-bind:class="$flux.appearance === 'dark' ? 'bg-white dark:bg-slate-600 text-zinc-900 dark:text-white shadow-sm ring-1 ring-zinc-900/5 dark:ring-white/10' : ''">
                            {{ __('Dark') }}
                        </flux:button>

                        <flux:button class="w-full justify-start text-left pl-3" icon="computer-desktop" x-on:click.prevent
                            variant="ghost" disabled aria-disabled="true"
                            x-bind:class="$flux.appearance === 'system' ? 'bg-white dark:bg-slate-600 text-zinc-900 dark:text-white shadow-sm ring-1 ring-zinc-900/5 dark:ring-white/10' : ''">
                            {{ __('Sistema') }}
                        </flux:button>
                    </div>
                </div>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>

</html>