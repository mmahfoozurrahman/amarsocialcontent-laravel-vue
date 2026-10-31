<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AmarSocialContent - Social Media Knowledge Aggregator & Second Brain</title>
    
    <!-- Google Fonts for Bengali & English -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script>
        window.AMAR_CONFIG = {
            isDemoMode: {{ config('auth.demo_otp') ? 'true' : 'false' }},
            appEnv: '{{ app()->environment() }}'
        };
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>

