<template>
    <div class="min-h-screen bg-gray-50 dark:bg-dark-900">
        <!-- Header -->
        <header class="bg-white dark:bg-dark-800 border-b border-gray-200 dark:border-dark-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                            ⚡ NexusEcom
                        </h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-500">Dashboard Inertia</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Card 1 -->
                    <div class="bg-white dark:bg-dark-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Vendas Hoje</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            R$ 0,00
                        </div>
                    </div>
                    
                    <!-- Card 2 -->
                    <div class="bg-white dark:bg-dark-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Pedidos Hoje</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            0
                        </div>
                    </div>
                    
                    <!-- Card 3 -->
                    <div class="bg-white dark:bg-dark-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">NF-es Recebidas</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ nfesCount }}
                        </div>
                    </div>
                    
                    <!-- Card 4 -->
                    <div class="bg-white dark:bg-dark-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500">Anúncios Ativos</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ adsCount }}
                        </div>
                    </div>
                </div>

                <div class="mt-8 bg-white dark:bg-dark-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        🎯 Inertia.js Migrado com Sucesso!
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Esta página está funcionando com Inertia + Vue 3. 
                        O app está mais rápido e leve que Livewire!
                    </p>
                    <div class="mt-4 flex gap-4">
                        <Link 
                            href="/roadmap" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            Ver Roadmap
                        </Link>
                        <Link 
                            href="/dashboard" 
                            class="px-4 py-2 bg-gray-200 dark:bg-dark-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-dark-600"
                        >
                            Voltar ao Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    nfesCount: {
        type: Number,
        default: 0
    },
    adsCount: {
        type: Number,
        default: 0
    }
});
</script>
