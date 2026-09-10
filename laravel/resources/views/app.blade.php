<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#FFFFFF">
    <meta name="description" content="Vayla — la plateforme de locations meublées vérifiées à Madagascar. Réservez en confiance, sans mauvaise surprise.">

    <link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2048%2048'%3E%3Cpath%20d='M2.18,4.81%20L19.79,45.70%20Q38.12,20.37%2045.82,2.30%20L38.58,4.12%20Q27.42,20.47%2021.66,26.12%20L9.01,7.47%20Z'%20fill='%23C9452A'/%3E%3Cpath%20d='M38.91,15.91%20Q41.29,11.40%2045.82,2.30%20L38.58,4.12%20Q37.07,6.03%2032.88,12.19%20Z'%20fill='%230E9080'/%3E%3C/svg%3E">
    <title inertia>{{ config('app.name', 'Vayla') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

    @vite('resources/js/app.js')
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
