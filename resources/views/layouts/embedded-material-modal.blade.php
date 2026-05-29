<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Material' }}</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ @filemtime(public_path('css/global.css')) }}">
    <script src="{{ asset('js/number-helper-client.js') }}"></script>
    <style>
        html, body {
            min-height: 0;
            height: auto;
            overflow-x: hidden;
            overflow-y: auto;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        body {
            background: #fff;
            padding: 18px 18px 10px;
        }

        body.modal-open-embedded {
            overflow-y: auto;
        }

        body > .card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            margin: 0 !important;
            background: #fff !important;
            min-height: auto !important;
            width: 100% !important;
        }

        body > .card .form-container {
            max-width: none !important;
            width: 100% !important;
        }

        body > .card .image-section {
            padding-bottom: 0 !important;
            justify-content: flex-start !important;
            gap: 18px;
        }

        body > .card .image-section > div:last-child {
            margin-top: auto;
        }

        body > .card .left-column,
        body > .card .image-section {
            min-height: auto !important;
        }
    </style>
</head>
<body class="modal-open-embedded">
    {!! $content ?? '' !!}
    <script>
        (function () {
            function getPrimaryHeight() {
                const primary = document.body.firstElementChild;
                if (primary) {
                    const rect = primary.getBoundingClientRect();
                    const computed = window.getComputedStyle(document.body);
                    const paddingTop = Number.parseFloat(computed.paddingTop || '0') || 0;
                    const paddingBottom = Number.parseFloat(computed.paddingBottom || '0') || 0;

                    return Math.ceil(rect.height + paddingTop + paddingBottom);
                }

                return Math.max(
                    document.body ? document.body.scrollHeight : 0,
                    document.documentElement ? document.documentElement.scrollHeight : 0
                );
            }

            function reportEmbeddedHeight() {
                const height = getPrimaryHeight();

                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({
                        type: 'supply-material-embedded-height',
                        height: height,
                    }, '*');
                }
            }

            window.addEventListener('load', reportEmbeddedHeight);
            window.addEventListener('resize', reportEmbeddedHeight);
            document.addEventListener('DOMContentLoaded', reportEmbeddedHeight);

            const observer = new MutationObserver(() => {
                window.requestAnimationFrame(reportEmbeddedHeight);
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
            });

            window.setTimeout(reportEmbeddedHeight, 120);
            window.setTimeout(reportEmbeddedHeight, 360);
        })();
    </script>
</body>
</html>
