<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Supply FE')</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #172033;
            --muted: #62748b;
            --line: #d8e1ee;
            --brand: #0f766e;
            --brand-soft: #dff7f3;
            --panel: #ffffff;
            --backdrop: linear-gradient(135deg, #f4f8fb 0%, #ecfdf5 52%, #fdf7ed 100%);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: var(--backdrop);
            color: var(--ink);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .auth-shell {
            width: min(1080px, 100%);
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            background: rgba(255,255,255,0.72);
            border: 1px solid rgba(216,225,238,0.9);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 28px 80px rgba(23, 32, 51, 0.08);
            backdrop-filter: blur(18px);
        }
        .auth-brand {
            padding: 48px;
            background:
                radial-gradient(circle at top left, rgba(15,118,110,0.22), transparent 42%),
                radial-gradient(circle at bottom right, rgba(245,158,11,0.16), transparent 34%);
        }
        .auth-panel {
            padding: 48px;
            background: var(--panel);
        }
        .kicker {
            font-size: 12px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--brand);
            font-weight: 700;
        }
        h1, h2 { margin: 0; }
        p { line-height: 1.6; color: var(--muted); }
        .brand-title { font-size: clamp(2rem, 4vw, 3.5rem); line-height: 1.05; margin-top: 12px; margin-bottom: 18px; }
        .brand-footer { margin-top: 32px; font-size: 14px; }
        .panel-title { font-size: 2rem; margin-bottom: 10px; }
        .cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border-radius: 16px;
            padding: 14px 18px;
            background: var(--ink);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }
        .notice {
            padding: 16px;
            border-radius: 16px;
            border: 1px solid rgba(15, 118, 110, 0.18);
            background: var(--brand-soft);
            color: var(--ink);
            margin: 24px 0;
        }
        .alert {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        @media (max-width: 900px) {
            .auth-shell { grid-template-columns: 1fr; }
            .auth-brand, .auth-panel { padding: 32px 24px; }
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <section class="auth-brand">
            <div class="kicker">@yield('auth_kicker', 'Supply Workspace')</div>
            <h1 class="brand-title">@yield('auth_brand_title')</h1>
            <p>@yield('auth_brand_copy')</p>
            <p class="brand-footer">@yield('auth_brand_footer')</p>
        </section>

        <section class="auth-panel">
            <div class="kicker">SSO Access</div>
            <h2 class="panel-title">@yield('auth_card_title', 'Masuk')</h2>
            <p>@yield('auth_card_copy')</p>

            @if (session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @yield('auth_form')
            @yield('auth_footer')
        </section>
    </main>
</body>
</html>
