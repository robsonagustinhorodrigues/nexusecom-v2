<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NexusEcom') }} ⚡</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,600,800,900" rel="stylesheet" />

        <!-- Font Awesome (ícones) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body { font-family: 'Inter', sans-serif; background-color: #020617; }
            input:-webkit-autofill,
            input:-webkit-autofill:hover, 
            input:-webkit-autofill:focus {
                -webkit-text-fill-color: white !important;
                -webkit-box-shadow: 0 0 0px 1000px #0f172a inset !important;
                transition: background-color 5000s ease-in-out 0s;
            }
        </style>
    </head>
    <body class="h-full bg-dark-950 text-slate-200 antialiased">
        {{ $slot }}
    </body>
</html>
