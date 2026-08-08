<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>{{ config('app.name', 'FleetManager') }} - Authentication</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-on-surface antialiased font-body-md min-h-screen flex items-center justify-center p-4 md:p-8" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background-color: #f9f9ff;">
    
    <div class="w-full max-w-5xl min-h-[600px] bg-surface-container-lowest border border-outline-variant rounded-2xl shadow-xl overflow-hidden" style="display: flex; flex-direction: row; width: 100%; max-width: 1000px; min-height: 580px; background-color: #ffffff; border: 1px solid #c3c6d7; border-radius: 16px;">
        
        <!-- Left Branding & Telematics Hero Panel -->
        <div class="hidden md:flex bg-surface-container-low p-8 lg:p-10 flex-col justify-between relative overflow-hidden border-r border-outline-variant" style="flex: 1 1 50%; min-width: 0; box-sizing: border-box; background-color: #f0f3ff; border-right: 1px solid #c3c6d7; display: flex; flex-direction: column; justify-content: space-between; padding: 40px;">
            <!-- Background Accent Blur -->
            <div class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full bg-primary/10 blur-3xl pointer-events-none"></div>

            <!-- Top Logo -->
            <div class="relative z-10 flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-primary flex items-center justify-center text-on-primary shadow-xs shrink-0" style="width: 44px; height: 44px; background-color: #004ac6; color: #ffffff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined text-[26px]" data-weight="fill">local_shipping</span>
                </div>
                <div>
                    <h1 class="text-headline-md font-headline-md font-black text-on-surface leading-tight" style="font-size: 20px; font-weight: 900; color: #111c2d;">FleetManager</h1>
                    <p class="text-label-md font-label-md text-on-surface-variant" style="font-size: 12px; color: #434655;">Enterprise Logistics</p>
                </div>
            </div>

            <!-- Middle Content -->
            <div class="relative z-10 my-8 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary font-label-md text-label-md" style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 9999px; background-color: rgba(0, 74, 198, 0.1); color: #004ac6; font-size: 12px; font-weight: 600;">
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse" style="width: 8px; height: 8px; border-radius: 50%; background-color: #004ac6;"></span> Telematics & Asset Control
                </div>
                <h2 class="font-display text-display text-on-surface text-3xl font-extrabold leading-tight" style="font-size: 28px; font-weight: 800; color: #111c2d; line-height: 1.2;">
                    Real-time asset tracking & precision fleet diagnostics.
                </h2>
                <p class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed" style="font-size: 15px; color: #434655; line-height: 1.5;">
                    Monitor active loads, define geofence boundaries, and inspect engine trouble codes in sub-second telemetry streams.
                </p>

                <div class="grid grid-cols-2 gap-4 pt-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                    <div class="p-4 bg-surface-container-lowest/90 backdrop-blur-xs rounded-xl border border-outline-variant/60 shadow-xs" style="padding: 16px; background-color: #ffffff; border: 1px solid #c3c6d7; border-radius: 12px;">
                        <span class="font-headline-lg text-headline-lg font-bold text-primary block" style="font-size: 24px; font-weight: 700; color: #004ac6; display: block;">1,248</span>
                        <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider" style="font-size: 11px; font-weight: 600; color: #434655; text-transform: uppercase;">Active Assets</span>
                    </div>
                    <div class="p-4 bg-surface-container-lowest/90 backdrop-blur-xs rounded-xl border border-outline-variant/60 shadow-xs" style="padding: 16px; background-color: #ffffff; border: 1px solid #c3c6d7; border-radius: 12px;">
                        <span class="font-headline-lg text-headline-lg font-bold text-[#059669] block" style="font-size: 24px; font-weight: 700; color: #059669; display: block;">99.8%</span>
                        <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider" style="font-size: 11px; font-weight: 600; color: #434655; text-transform: uppercase;">Stream Accuracy</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative z-10 border-t border-outline-variant/50 pt-4 text-body-sm text-on-surface-variant" style="border-top: 1px solid #c3c6d7; padding-top: 16px; font-size: 13px; color: #434655;">
                &copy; {{ date('Y') }} FleetManager Enterprise. All rights reserved.
            </div>
        </div>

        <!-- Right Authentication Form Panel -->
        <div class="w-full md:w-1/2 p-8 lg:p-12 flex flex-col justify-center items-center bg-surface-container-lowest" style="flex: 1 1 50%; min-width: 0; box-sizing: border-box; background-color: #ffffff; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px;">
            <div style="width: 100%; max-width: 380px; box-sizing: border-box;">
                {{ $slot }}
            </div>
        </div>
    </div>

</body>
</html>
