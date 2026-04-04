<?php

declare(strict_types=1);

?>
<!DOCTYPE html>
<html lang="en" class="h-full dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evergreen — Yard Consultation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'system-ui', 'sans-serif'],
                        display: ['Outfit', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        moss: {
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            950: '#052e16',
                        },
                    },
                    keyframes: {
                        'fade-up': {
                            '0%': { opacity: '0', transform: 'translateY(8px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        'pulse-ring': {
                            '0%': { transform: 'scale(1)', opacity: '0.6' },
                            '100%': { transform: 'scale(1.6)', opacity: '0' },
                        },
                    },
                    animation: {
                        'fade-up': 'fade-up 0.35s ease-out forwards',
                        'pulse-ring': 'pulse-ring 1.2s ease-out infinite',
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked@12.0.0/marked.min.js"></script>
    <style>
        @media (min-width: 1024px) {
            html.ev-landing, html.ev-landing body { height: 100dvh; max-height: 100dvh; overflow: hidden; }
        }

        /* Consultation markdown */
        #consultation-body { color: #cbd5e1; font-size: 0.875rem; line-height: 1.7; }
        #consultation-body h1 { font-size: 1.2rem; font-weight: 700; color: #f1f5f9; margin: 0 0 0.75rem; font-family: Outfit, system-ui, sans-serif; }
        #consultation-body h2 { font-size: 0.9375rem; font-weight: 600; color: #4ade80; margin: 1.5rem 0 0.5rem; font-family: Outfit, system-ui, sans-serif; padding-bottom: 0.25rem; border-bottom: 1px solid rgba(51,65,85,0.6); }
        #consultation-body h2:first-child { margin-top: 0; }
        #consultation-body h3 { font-size: 0.875rem; font-weight: 600; color: #e2e8f0; margin: 0.875rem 0 0.25rem; }
        #consultation-body p { margin: 0 0 0.65rem; }
        #consultation-body ul, #consultation-body ol { margin: 0 0 0.65rem 1.25rem; }
        #consultation-body ul { list-style: disc; }
        #consultation-body ol { list-style: decimal; }
        #consultation-body li { margin-bottom: 0.2rem; }
        #consultation-body strong { color: #e2e8f0; font-weight: 600; }
        #consultation-body a { color: #4ade80; text-decoration: underline; text-underline-offset: 2px; }
        #consultation-body blockquote { border-left: 3px solid #16a34a; padding-left: 0.875rem; color: #94a3b8; font-style: italic; margin: 0 0 0.65rem; }

        /* Upload drop zone hover */
        .drop-zone-active { border-color: rgba(34, 197, 94, 0.6) !important; background-color: rgba(22, 163, 74, 0.06) !important; }

        /* Smooth step transitions */
        .step-panel { transition: opacity 0.2s ease; }

        /* Care grid label */
        .care-label { font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; }
        .care-value { font-size: 0.8125rem; color: #cbd5e1; line-height: 1.4; margin-top: 0.1rem; }

        /* Plant card number badge */
        .plant-num { display: inline-flex; align-items: center; justify-content: center; width: 1.375rem; height: 1.375rem; border-radius: 9999px; background: rgba(22,163,74,0.15); color: #4ade80; font-size: 0.6875rem; font-weight: 700; flex-shrink: 0; }

        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(51,65,85,0.7); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(71,85,105,0.9); }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased">

<div class="flex min-h-[100dvh] flex-col lg:min-h-0 lg:h-[100dvh] lg:flex-row lg:overflow-hidden">

    <!-- ── Brand sidebar ─────────────────────────────────────────────── -->
    <aside class="relative z-10 flex shrink-0 flex-col justify-between border-b border-slate-800 bg-slate-900/60 backdrop-blur-sm px-5 py-5 sm:px-6 lg:w-[min(22rem,30vw)] lg:border-b-0 lg:border-r lg:border-slate-800 lg:py-10">
        <div>
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-moss-600/20 ring-1 ring-moss-500/30" aria-hidden="true">
                    <svg class="h-5 w-5 text-moss-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c4 4 6 8 6 12a6 6 0 01-12 0c0-4 2-8 6-12z" />
                    </svg>
                </div>
                <div>
                    <p class="font-display text-lg font-semibold tracking-tight text-white leading-none">Evergreen</p>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-500 mt-0.5">Yard Consultation</p>
                </div>
            </div>
            <!-- Feed link in sidebar -->
            <a href="feed.php" class="mt-5 flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2 text-xs font-semibold text-slate-400 transition hover:bg-slate-900 hover:text-white hover:border-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500/50">
                <svg class="h-4 w-4 shrink-0 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Community feed</span>
                <svg class="h-3 w-3 ml-auto text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <!-- Tagline -->
            <p class="mt-5 text-sm leading-relaxed text-slate-400">One photo, your location, your goals — get a personalised planting plan in minutes.</p>

            <!-- Feature bullets -->
            <ul class="mt-5 space-y-2.5" aria-label="What you get">
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-moss-600/20 ring-1 ring-moss-500/25" aria-hidden="true">
                        <svg class="h-3 w-3 text-moss-400" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                    </span>
                    <span class="text-xs text-slate-400 leading-snug">Climate-appropriate species for your exact region</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-moss-600/20 ring-1 ring-moss-500/25" aria-hidden="true">
                        <svg class="h-3 w-3 text-moss-400" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                    </span>
                    <span class="text-xs text-slate-400 leading-snug">Ornamental, edible, or a balanced mix — your call</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-moss-600/20 ring-1 ring-moss-500/25" aria-hidden="true">
                        <svg class="h-3 w-3 text-moss-400" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6l3 3 5-5"/></svg>
                    </span>
                    <span class="text-xs text-slate-400 leading-snug">Detailed care guide plus an optional concept render</span>
                </li>
            </ul>

            <!-- How it works -->
            <div class="mt-6 rounded-xl border border-slate-800 bg-slate-950/50 p-4 hidden lg:block">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-3">How it works</p>
                <ol class="space-y-2.5" aria-label="Steps">
                    <li class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full bg-slate-800 text-[10px] font-bold text-slate-400">1</span>
                        <span class="text-xs text-slate-500 leading-snug">Upload a photo of your yard or garden area</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full bg-slate-800 text-[10px] font-bold text-slate-400">2</span>
                        <span class="text-xs text-slate-500 leading-snug">Tell us your location and planting priority</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-full bg-slate-800 text-[10px] font-bold text-slate-400">3</span>
                        <span class="text-xs text-slate-500 leading-snug">Get a personalised plan with plant recommendations</span>
                    </li>
                </ol>
            </div>
        </div>

        <p class="mt-6 hidden text-[10px] leading-relaxed text-slate-600 lg:block">Informational only. Verify HOA rules, utilities, and local codes before planting.</p>
    </aside>

    <!-- ── Main work area ─────────────────────────────────────────────── -->
    <main class="relative flex min-h-[50vh] flex-1 flex-col lg:min-h-0 lg:overflow-hidden">
        <h1 class="sr-only">Evergreen yard consultation</h1>

        <!-- ── Step 1: Upload form ───────────────────────────── -->
        <section id="panel-upload" class="step-panel flex min-h-0 flex-1 flex-col justify-center px-4 py-6 sm:px-8 lg:overflow-hidden lg:py-8">
            <form id="form-upload" class="mx-auto w-full max-w-md" enctype="multipart/form-data" novalidate>

                <!-- Form card -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-2xl shadow-black/50 ring-1 ring-white/[0.04]">

                    <!-- Card header -->
                    <div class="px-5 pt-5 pb-4 border-b border-slate-800/80 sm:px-6">
                        <p class="font-display text-base font-semibold text-white">Start your consultation</p>
                        <p class="mt-0.5 text-xs text-slate-500">All fields required · typically 1–3 min</p>
                    </div>

                    <div class="px-5 py-5 space-y-5 sm:px-6">

                        <!-- Location -->
                        <div>
                            <label for="location" class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Your location
                                <span class="ml-1 text-slate-600 font-normal">(city, region, or ZIP)</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3" aria-hidden="true">
                                    <svg class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <input type="text" name="location" id="location" required maxlength="280" autocomplete="address-level2"
                                    placeholder="e.g. Austin TX, London UK, 90210"
                                    class="w-full rounded-lg border border-slate-700 bg-slate-950/80 pl-9 pr-3 py-2.5 text-sm text-white placeholder:text-slate-600 transition-all duration-150 focus:border-moss-500/70 focus:outline-none focus:ring-2 focus:ring-moss-500/20 hover:border-slate-600">
                            </div>
                        </div>

                        <!-- Priority toggle -->
                        <div>
                            <p id="goal-label" class="text-xs font-semibold text-slate-300 mb-1.5">Planting priority</p>
                            <div class="grid grid-cols-3 gap-1.5 rounded-xl bg-slate-950/60 p-1 ring-1 ring-slate-800" role="group" aria-labelledby="goal-label">
                                <label class="relative flex cursor-pointer flex-col items-center justify-center rounded-lg py-2.5 px-2 text-center transition-all duration-150 has-[input:checked]:bg-moss-600 has-[input:checked]:shadow-lg has-[input:checked]:shadow-moss-950/30 hover:bg-slate-800/60">
                                    <input type="radio" name="plant_goal" value="looks" class="peer sr-only" checked>
                                    <svg class="h-4 w-4 mb-1 text-slate-500 peer-checked:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <span class="text-[11px] font-semibold text-slate-500 peer-checked:text-white transition-colors">Looks</span>
                                </label>
                                <label class="relative flex cursor-pointer flex-col items-center justify-center rounded-lg py-2.5 px-2 text-center transition-all duration-150 has-[input:checked]:bg-moss-600 has-[input:checked]:shadow-lg has-[input:checked]:shadow-moss-950/30 hover:bg-slate-800/60">
                                    <input type="radio" name="plant_goal" value="food" class="peer sr-only">
                                    <svg class="h-4 w-4 mb-1 text-slate-500 peer-checked:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                                    </svg>
                                    <span class="text-[11px] font-semibold text-slate-500 peer-checked:text-white transition-colors">Food</span>
                                </label>
                                <label class="relative flex cursor-pointer flex-col items-center justify-center rounded-lg py-2.5 px-2 text-center transition-all duration-150 has-[input:checked]:bg-moss-600 has-[input:checked]:shadow-lg has-[input:checked]:shadow-moss-950/30 hover:bg-slate-800/60">
                                    <input type="radio" name="plant_goal" value="mixed" class="peer sr-only">
                                    <svg class="h-4 w-4 mb-1 text-slate-500 peer-checked:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                    <span class="text-[11px] font-semibold text-slate-500 peer-checked:text-white transition-colors">Both</span>
                                </label>
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-600">Ornamental · Edible garden · Balanced mix</p>
                        </div>

                        <!-- Photo upload -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Yard photo</label>
                            <label id="drop-zone" class="group relative flex min-h-[7rem] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-700 bg-slate-950/50 px-4 py-4 text-center transition-all duration-200 hover:border-moss-600/50 hover:bg-slate-900/40" aria-label="Upload yard photo">
                                <input type="file" name="photo" id="input-photo" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" required aria-required="true">
                                <div id="drop-zone-icon" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-800 ring-1 ring-slate-700 transition-all duration-200 group-hover:ring-moss-600/40">
                                    <svg class="h-5 w-5 text-slate-500 group-hover:text-moss-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-300 group-hover:text-white transition-colors">Drop photo or click to browse</p>
                                    <p id="file-label" class="mt-0.5 text-[11px] text-slate-600">JPG · PNG · WebP · GIF · up to 12 MB</p>
                                </div>
                            </label>
                        </div>

                    </div>

                    <!-- Submit -->
                    <div class="px-5 pb-5 sm:px-6">
                        <button type="submit" id="btn-submit"
                            class="w-full rounded-xl bg-moss-600 py-3 text-sm font-semibold text-white shadow-lg shadow-moss-950/30 transition-all duration-150 hover:bg-moss-500 hover:shadow-moss-950/40 focus:outline-none focus:ring-2 focus:ring-moss-400 focus:ring-offset-2 focus:ring-offset-slate-950 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer">
                            Get my consultation →
                        </button>
                    </div>
                </div>

                <!-- Error message -->
                <div id="error-global" class="hidden mt-3 rounded-xl border border-red-900/50 bg-red-950/30 px-4 py-3" role="alert" aria-live="polite">
                    <div class="flex items-start gap-2.5">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p id="error-text" class="text-xs text-red-300 leading-relaxed"></p>
                    </div>
                </div>

                <p class="mt-4 text-center text-[10px] leading-relaxed text-slate-600 lg:hidden">Informational only. Verify HOA, utilities, and local codes before planting.</p>
            </form>
        </section>

        <!-- ── Step 2: Processing ────────────────────────────── -->
        <section id="panel-processing" class="step-panel absolute inset-0 z-30 hidden flex-col items-center justify-center bg-slate-950/92 px-6 backdrop-blur-sm" aria-live="polite" aria-busy="true">
            <div class="w-full max-w-sm rounded-2xl border border-slate-800 bg-slate-900/95 p-8 text-center shadow-2xl shadow-black/60">
                <!-- Spinner -->
                <div class="relative mx-auto h-14 w-14 mb-6">
                    <span class="absolute inset-0 rounded-full border-2 border-moss-500/20 animate-pulse-ring"></span>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="h-10 w-10 animate-spin rounded-full border-2 border-slate-700 border-t-moss-500" aria-hidden="true"></div>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="h-5 w-5 text-moss-500/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c4 4 6 8 6 12a6 6 0 01-12 0c0-4 2-8 6-12z" />
                        </svg>
                    </div>
                </div>

                <p class="font-display text-base font-semibold text-white">Analysing your yard</p>
                <p id="processing-step" class="mt-1.5 text-xs text-slate-500 leading-relaxed transition-all duration-300">Reading sunlight, soil, and space…</p>

                <!-- Progress steps -->
                <div class="mt-6 space-y-2 text-left" aria-hidden="true">
                    <div id="step-1" class="flex items-center gap-2.5">
                        <div class="h-1.5 w-1.5 rounded-full bg-moss-500 shrink-0"></div>
                        <span class="text-[11px] text-slate-400">Analysing photo</span>
                    </div>
                    <div id="step-2" class="flex items-center gap-2.5 opacity-40">
                        <div class="h-1.5 w-1.5 rounded-full bg-slate-600 shrink-0"></div>
                        <span class="text-[11px] text-slate-500">Matching plants to your climate</span>
                    </div>
                    <div id="step-3" class="flex items-center gap-2.5 opacity-40">
                        <div class="h-1.5 w-1.5 rounded-full bg-slate-600 shrink-0"></div>
                        <span class="text-[11px] text-slate-500">Writing your personalised plan</span>
                    </div>
                    <div id="step-4" class="flex items-center gap-2.5 opacity-40">
                        <div class="h-1.5 w-1.5 rounded-full bg-slate-600 shrink-0"></div>
                        <span class="text-[11px] text-slate-500">Generating concept image</span>
                    </div>
                </div>

                <p class="mt-6 text-[10px] text-slate-600">Usually 1–3 minutes. Keep this window open.</p>
            </div>
        </section>

        <!-- ── Step 3: Results ───────────────────────────────── -->
        <section id="panel-result" class="step-panel hidden min-h-0 flex-1 flex-col overflow-hidden" aria-live="polite">

            <!-- Results header -->
            <header class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-slate-800 bg-slate-900/50 px-4 py-3 sm:px-6 backdrop-blur-sm">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-moss-600/20 ring-1 ring-moss-500/30">
                        <svg class="h-3.5 w-3.5 text-moss-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-display text-sm font-semibold text-white leading-none">Consultation ready</p>
                        <p id="result-context" class="hidden mt-0.5 truncate text-xs text-slate-500"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a id="btn-view-feed" href="feed.php" target="_blank" rel="noopener"
                       class="hidden rounded-lg border border-slate-700 bg-slate-900/60 px-3.5 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:bg-slate-800 hover:text-white hover:border-slate-600 focus:outline-none focus:ring-2 focus:ring-moss-500/40">
                        View in feed ↗
                    </a>
                    <button type="button" id="btn-reset"
                        class="rounded-lg border border-slate-700 bg-slate-900/60 px-3.5 py-1.5 text-xs font-semibold text-slate-300 transition-all duration-150 hover:bg-slate-800 hover:text-white hover:border-slate-600 cursor-pointer focus:outline-none focus:ring-2 focus:ring-moss-500/40">
                        ← New consultation
                    </button>
                </div>
            </header>

            <!-- Scrollable content -->
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-6 sm:px-6 sm:py-8">
                <div class="mx-auto max-w-3xl space-y-8 pb-10">

                    <!-- Before / Concept images -->
                    <div class="animate-fade-up">
                        <div class="flex items-baseline justify-between mb-3">
                            <h2 class="font-display text-sm font-semibold text-white">Before &amp; Concept</h2>
                            <p class="text-[11px] text-slate-600">Concept is illustrative — not a build spec</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <!-- Before -->
                            <div class="group overflow-hidden rounded-xl border border-slate-800 bg-black/40">
                                <div class="flex items-center justify-between bg-slate-900/80 px-3 py-2">
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Before</span>
                                    <span class="text-[10px] text-slate-600">Your photo</span>
                                </div>
                                <img id="img-before" src="" alt="Your yard before" class="ev-photo-lightbox block w-full cursor-zoom-in object-contain max-h-60 sm:max-h-72 bg-black/20">
                            </div>
                            <!-- Concept -->
                            <div class="group overflow-hidden rounded-xl border border-slate-800 bg-black/40">
                                <div class="flex items-center justify-between bg-slate-900/80 px-3 py-2">
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Concept</span>
                                    <span class="text-[10px] text-slate-600">AI render</span>
                                </div>
                                <img id="img-after" src="" alt="Concept render" class="ev-photo-lightbox hidden w-full cursor-zoom-in object-contain max-h-60 sm:max-h-72 bg-black/20">
                                <div id="img-after-placeholder" class="flex min-h-[8rem] flex-col items-center justify-center gap-2 px-4 py-8 text-center">
                                    <svg class="h-8 w-8 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-xs text-slate-500">No concept image</p>
                                    <p id="img-after-reason" class="max-w-[14rem] text-[11px] leading-snug text-slate-600"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plant recommendations cards -->
                    <div id="plants-section" class="hidden animate-fade-up" style="animation-delay:0.05s">
                        <div class="flex items-baseline justify-between mb-3">
                            <h2 class="font-display text-sm font-semibold text-white">Recommended Plants</h2>
                            <p id="plants-count" class="text-[11px] text-slate-600"></p>
                        </div>
                        <div id="plants-grid" class="grid gap-3 sm:grid-cols-2"></div>
                    </div>

                    <!-- Full consultation plan -->
                    <div class="animate-fade-up" style="animation-delay:0.1s">
                        <div class="flex items-baseline justify-between mb-3">
                            <h2 class="font-display text-sm font-semibold text-white">Your Full Plan</h2>
                            <p class="text-[11px] text-slate-600">Cross-check with a local nursery</p>
                        </div>
                        <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-5 sm:p-6 ring-1 ring-white/[0.03]">
                            <div id="consultation-body" class="mx-auto max-w-prose"></div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </main>
</div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);

    const panelUpload     = $('panel-upload');
    const panelProcessing = $('panel-processing');
    const panelResult     = $('panel-result');
    const form            = $('form-upload');
    const inputPhoto      = $('input-photo');
    const fileLabel       = $('file-label');
    const btnSubmit       = $('btn-submit');
    const btnReset        = $('btn-reset');
    const btnViewFeed     = $('btn-view-feed');
    const errGlobal       = $('error-global');
    const errText         = $('error-text');
    const dropZone        = $('drop-zone');
    const imgBefore       = $('img-before');
    const imgAfter        = $('img-after');
    const imgAfterPh      = $('img-after-placeholder');
    const imgAfterReason  = $('img-after-reason');
    const consultationBody = $('consultation-body');
    const resultContext   = $('result-context');
    const plantsSection   = $('plants-section');
    const plantsGrid      = $('plants-grid');
    const plantsCount     = $('plants-count');
    const processingStep  = $('processing-step');

    // ── Processing step ticker ──────────────────────────────────────────
    const stepMessages = [
        { el: 'step-1', label: 'Reading sunlight, soil, and space…' },
        { el: 'step-2', label: 'Matching plants to your climate…' },
        { el: 'step-3', label: 'Writing your personalised plan…' },
        { el: 'step-4', label: 'Generating concept image…' },
    ];
    let stepTimer = null;

    function startStepTicker() {
        let idx = 0;
        stepMessages.forEach((s, i) => {
            const el = $(s.el);
            if (el) {
                el.classList.toggle('opacity-40', i !== 0);
                el.querySelector('div').className = i === 0
                    ? 'h-1.5 w-1.5 rounded-full bg-moss-500 shrink-0'
                    : 'h-1.5 w-1.5 rounded-full bg-slate-600 shrink-0';
            }
        });
        processingStep.textContent = stepMessages[0].label;

        stepTimer = setInterval(() => {
            idx++;
            if (idx >= stepMessages.length) { clearInterval(stepTimer); return; }
            const s = stepMessages[idx];
            processingStep.textContent = s.label;
            const el = $(s.el);
            if (el) {
                el.classList.remove('opacity-40');
                el.querySelector('div').className = 'h-1.5 w-1.5 rounded-full bg-moss-500 shrink-0';
            }
        }, 28000);
    }

    function stopStepTicker() {
        if (stepTimer) { clearInterval(stepTimer); stepTimer = null; }
    }

    // ── Error helpers ───────────────────────────────────────────────────
    function showError(msg) {
        errText.textContent = msg;
        errGlobal.classList.remove('hidden');
    }
    function clearError() {
        errText.textContent = '';
        errGlobal.classList.add('hidden');
    }

    // ── Panel switching ─────────────────────────────────────────────────
    function setStep(step) {
        panelUpload.classList.toggle('hidden', step !== 'upload');
        panelProcessing.classList.toggle('hidden', step !== 'processing');
        panelProcessing.classList.toggle('flex', step === 'processing');
        panelResult.classList.toggle('hidden', step !== 'result');
        panelResult.classList.toggle('flex', step === 'result');
        document.documentElement.classList.toggle('ev-landing', step === 'upload' || step === 'processing');
        if (step === 'processing') {
            startStepTicker();
        } else {
            stopStepTicker();
        }
    }

    // ── Markdown rendering ──────────────────────────────────────────────
    function renderConsultationMarkdown(text) {
        const raw = (text || '').trim();
        if (!raw) {
            consultationBody.innerHTML = '<p class="text-slate-500 text-sm">No written plan returned. Try again.</p>';
            return;
        }
        if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
            consultationBody.innerHTML = DOMPurify.sanitize(marked.parse(raw, { breaks: true }));
        } else {
            consultationBody.textContent = raw;
        }
    }

    // ── Care icon map ───────────────────────────────────────────────────
    const careIconMap = {
        sun: '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="12" r="5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>',
        water: '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/></svg>',
        soil: '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10v11M20 10v11M8 10v11M16 10v11M12 10v11"/></svg>',
        maintenance: '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        spacing: '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>',
        seasonal: '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        pests_tips: '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    };
    const careLabels = {
        sun: 'Sunlight', water: 'Watering', soil: 'Soil',
        maintenance: 'Maintenance', spacing: 'Spacing',
        seasonal: 'Seasonal', pests_tips: 'Tips',
    };

    // ── Plant cards renderer ────────────────────────────────────────────
    function renderPlantCards(suggestions) {
        if (!suggestions || !suggestions.length) {
            plantsSection.classList.add('hidden');
            return;
        }
        plantsSection.classList.remove('hidden');
        plantsCount.textContent = suggestions.length + ' plant' + (suggestions.length !== 1 ? 's' : '') + ' recommended';
        plantsGrid.innerHTML = '';

        suggestions.forEach((s, i) => {
            const plant = (s.plant || 'Plant').trim();
            const location = (s.location || '').trim();
            const reason = (s.reason || '').trim();
            const care = s.care || {};

            const careEntries = Object.entries(careLabels)
                .map(([k, label]) => {
                    const val = (care[k] || '').trim();
                    if (!val) return '';
                    const icon = careIconMap[k] || '';
                    return `<div class="flex flex-col gap-0.5">
                        <div class="flex items-center gap-1 text-slate-500">${icon}<span class="care-label">${label}</span></div>
                        <p class="care-value">${escapeHtml(val)}</p>
                    </div>`;
                })
                .filter(Boolean)
                .join('');

            const card = document.createElement('div');
            card.className = 'rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden ring-1 ring-white/[0.03]';
            card.innerHTML = `
                <div class="px-4 pt-4 pb-3 border-b border-slate-800/80">
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <span class="plant-num" aria-hidden="true">${i + 1}</span>
                        <h3 class="font-display text-sm font-semibold text-white leading-tight">${escapeHtml(plant)}</h3>
                    </div>
                    ${location ? `<p class="text-[11px] text-moss-400 font-medium mb-1.5 flex items-center gap-1.5">
                        <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        ${escapeHtml(location)}
                    </p>` : ''}
                    ${reason ? `<p class="text-xs text-slate-400 leading-snug">${escapeHtml(reason)}</p>` : ''}
                </div>
                ${careEntries ? `<div class="px-4 py-3 grid grid-cols-2 gap-x-4 gap-y-3">${careEntries}</div>` : ''}
            `;
            plantsGrid.appendChild(card);
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Apply results ───────────────────────────────────────────────────
    function applyResultView(data) {
        const bust = '?t=' + Date.now();
        imgBefore.src = (data.image || '') + bust;

        const loc   = (data.location_label || '').trim();
        const focus = (data.focus_label || '').trim();
        const ctxLine = loc && focus ? loc + ' · ' + focus : (loc || focus || '');
        resultContext.textContent = ctxLine;
        resultContext.classList.toggle('hidden', !ctxLine);

        if (data.rendered_image) {
            imgAfter.src = data.rendered_image + bust;
            imgAfter.classList.remove('hidden');
            imgAfterPh.classList.add('hidden');
            imgAfterReason.textContent = '';
        } else {
            imgAfter.removeAttribute('src');
            imgAfter.classList.add('hidden');
            imgAfterPh.classList.remove('hidden');
            imgAfterReason.textContent = (data.preview_note || '').trim();
        }

        renderPlantCards(data.layout && data.layout.suggestions);
        renderConsultationMarkdown(data.consultation_full);

        // Wire up feed link
        if (data.upload_id) {
            btnViewFeed.href = 'consultation.php?id=' + data.upload_id;
            btnViewFeed.classList.remove('hidden');
        }
    }

    // ── Drag & drop ─────────────────────────────────────────────────────
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drop-zone-active');
    });
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drop-zone-active');
    });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drop-zone-active');
        const files = e.dataTransfer && e.dataTransfer.files;
        if (files && files[0]) {
            // Manually assign to input for FormData
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            inputPhoto.files = dt.files;
            updateFileLabel(files[0]);
        }
    });

    function updateFileLabel(f) {
        if (f) {
            const kb = Math.round(f.size / 1024);
            const size = kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : kb + ' KB';
            fileLabel.textContent = f.name + ' · ' + size;
            fileLabel.classList.add('text-moss-400');
            fileLabel.classList.remove('text-slate-600');
        } else {
            fileLabel.textContent = 'JPG · PNG · WebP · GIF · up to 12 MB';
            fileLabel.classList.remove('text-moss-400');
            fileLabel.classList.add('text-slate-600');
        }
    }

    inputPhoto.addEventListener('change', () => {
        updateFileLabel(inputPhoto.files && inputPhoto.files[0]);
    });

    // ── Form submit ─────────────────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearError();
        const fd = new FormData(form);
        if (!inputPhoto.files || !inputPhoto.files[0]) {
            showError('Please add a yard photo to continue.');
            return;
        }
        if (!(fd.get('location') || '').toString().trim()) {
            showError('Please enter your location so we can recommend suitable plants.');
            return;
        }
        btnSubmit.disabled = true;
        setStep('processing');
        try {
            const res = await fetch('upload.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.ok) {
                showError(data.error || 'Something went wrong. Please try again.');
                setStep('upload');
                return;
            }
            applyResultView(data);
            setStep('result');
        } catch (err) {
            showError('Check your connection and try again.');
            setStep('upload');
        } finally {
            btnSubmit.disabled = false;
        }
    });

    // ── Reset ───────────────────────────────────────────────────────────
    btnReset.addEventListener('click', () => {
        form.reset();
        const looks = form.querySelector('input[name="plant_goal"][value="looks"]');
        if (looks) looks.checked = true;
        updateFileLabel(null);
        imgBefore.removeAttribute('src');
        imgAfter.removeAttribute('src');
        imgAfter.classList.add('hidden');
        imgAfterPh.classList.remove('hidden');
        imgAfterReason.textContent = '';
        resultContext.textContent = '';
        resultContext.classList.add('hidden');
        consultationBody.innerHTML = '';
        plantsGrid.innerHTML = '';
        plantsSection.classList.add('hidden');
        btnViewFeed.classList.add('hidden');
        btnViewFeed.href = 'feed.php';
        clearError();
        setStep('upload');
    });

    setStep('upload');
})();
</script>
<script src="lightbox.js"></script>
</body>
</html>
