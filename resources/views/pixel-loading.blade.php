<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connecting...</title>
    
    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Meta / Facebook Pixel -->
    @if(!empty($pixelMetaId))
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $pixelMetaId }}');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $pixelMetaId }}&ev=PageView&noscript=1" /></noscript>
    @endif

    <!-- Google Analytics / GTM -->
    @if(!empty($pixelGoogleId))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $pixelGoogleId }}"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $pixelGoogleId }}');
    </script>
    @endif

    <!-- LinkedIn Insight -->
    @if(!empty($pixelLinkedinId))
    <script type="text/javascript">
    _linkedin_data_partner_id = "{{ $pixelLinkedinId }}";
    window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
    window._linkedin_data_partner_ids.push(_linkedin_data_partner_id);
    (function(l) {
    if (!l){window.lintrk = function(a,b){window.lintrk.q.push([a,b])};
    window.lintrk.q=[]}
    var s = document.getElementsByTagName("script")[0];
    var b = document.createElement("script");
    b.type = "text/javascript";b.async = true;
    b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
    s.parentNode.insertBefore(b, s);})(window.lintrk);
    </script>
    <noscript><img height="1" width="1" style="display:none;" alt="" src="https://px.ads.linkedin.com/collect/?pid={{ $pixelLinkedinId }}&fmt=gif" /></noscript>
    @endif

    <!-- Premium Styling -->
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f1f5f9;
            font-family: 'Outfit', -apple-system, sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            text-align: center;
            padding: 2.5rem;
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            width: 90%;
            animation: fadeIn 0.6s ease-out;
        }

        .spinner {
            position: relative;
            width: 72px;
            height: 72px;
            margin: 0 auto 2rem;
        }

        .spinner-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }

        .spinner-ring:nth-child(2) {
            animation-delay: -0.3s;
            border-top-color: #a855f7;
        }

        .spinner-ring:nth-child(3) {
            animation-delay: -0.6s;
            border-top-color: #ec4899;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
            background: linear-gradient(to right, #818cf8, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.025em;
        }

        p {
            font-size: 0.95rem;
            color: #94a3b8;
            margin: 0;
            font-weight: 300;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
        </div>
        <h1>Connecting Safely</h1>
        <p>Securing connection & forwarding you now...</p>
    </div>

    <!-- Async non-blocking redirect -->
    <script>
        setTimeout(function() {
            window.location.replace("{!! addslashes($destination) !!}");
        }, 250);
    </script>
</body>
</html>
