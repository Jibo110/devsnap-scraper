{{-- resources/views/scraper/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>DEVSNAP · Ticket Intelligence</title>

    {{-- Tailwind CSS CDN (swap for compiled asset in production) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Google Fonts: Syne + DM Mono --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Syne', 'sans-serif'],
                        mono:    ['DM Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50:  '#f0fdf9',
                            100: '#ccfbef',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            900: '#064e3b',
                        },
                        surface: {
                            950: '#050d0a',
                            900: '#0a1a14',
                            800: '#0f261d',
                            700: '#163322',
                            600: '#1e4433',
                        },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.5s ease both',
                        'pulse-dot': 'pulseDot 1.4s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeUp: {
                            '0%':   { opacity: 0, transform: 'translateY(14px)' },
                            '100%': { opacity: 1, transform: 'translateY(0)' },
                        },
                        pulseDot: {
                            '0%, 100%': { opacity: 0.3, transform: 'scale(0.8)' },
                            '50%':      { opacity: 1,   transform: 'scale(1.2)' },
                        },
                    },
                }
            }
        }
    </script>

    <style>
        body { background-color: #050d0a; }

        /* Scanline overlay */
        body::before {
            content: '';
            position: fixed; inset: 0; pointer-events: none; z-index: 999;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(0,0,0,0.06) 2px,
                rgba(0,0,0,0.06) 4px
            );
        }

        /* Glowing grid background */
        .grid-bg {
            background-image:
                linear-gradient(rgba(16,185,129,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16,185,129,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Green neon glow on focus */
        .neon-input:focus {
            box-shadow: 0 0 0 2px #10b981, 0 0 20px rgba(16,185,129,0.25);
            outline: none;
        }

        /* Glowing button */
        .glow-btn {
            box-shadow: 0 0 16px rgba(16,185,129,0.35);
            transition: box-shadow 0.2s, transform 0.15s;
        }
        .glow-btn:hover:not(:disabled) {
            box-shadow: 0 0 28px rgba(16,185,129,0.6);
            transform: translateY(-1px);
        }
        .glow-btn:active:not(:disabled) { transform: translateY(0); }

        /* Table row hover */
        .result-row:hover td { background-color: rgba(16,185,129,0.05); }

        /* Stagger animation for rows */
        .result-row { animation: fadeUp 0.35s ease both; }

        /* Price badge pulse */
        @keyframes priceGlow {
            0%, 100% { box-shadow: 0 0 6px rgba(52,211,153,0.3); }
            50%       { box-shadow: 0 0 14px rgba(52,211,153,0.7); }
        }
        .price-badge { animation: priceGlow 2.5s ease-in-out infinite; }

        /* Spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { animation: spin 0.75s linear infinite; }

        /* Dot loader */
        .dot-1 { animation-delay: 0s; }
        .dot-2 { animation-delay: 0.2s; }
        .dot-3 { animation-delay: 0.4s; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0a1a14; }
        ::-webkit-scrollbar-thumb { background: #10b981; border-radius: 3px; }
    </style>
</head>

<body class="font-display text-brand-100 min-h-full grid-bg" x-data="ticketScraper()" x-init="init()">

    {{-- ── TOP BAR ──────────────────────────────────────────────────────── --}}
    <header class="border-b border-surface-700 bg-surface-950/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                {{-- Logo mark --}}
                <div class="w-8 h-8 rounded-md bg-brand-500 flex items-center justify-center">
                    <svg class="w-4 h-4 text-surface-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight text-white">DEVSNAP</span>
                <span class="text-surface-600 font-mono text-xs hidden sm:block">/ ticket-intel</span>
            </div>

            <div class="flex items-center gap-3">
                {{-- Live status dot --}}
                <span class="flex items-center gap-2 text-xs font-mono text-brand-400">
                    <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse-dot dot-1 inline-block"></span>
                    SYSTEM ONLINE
                </span>
            </div>
        </div>
    </header>

    {{-- ── HERO SECTION ─────────────────────────────────────────────────── --}}
    <main class="max-w-7xl mx-auto px-6 py-12">

        <div class="mb-10 animate-fade-up">
            <p class="font-mono text-brand-500 text-sm tracking-widest uppercase mb-2">Live Market Intelligence</p>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-white leading-tight">
                Ticket Price<br/>
                <span class="text-brand-400">Recon Engine</span>
            </h1>
            <p class="mt-3 text-surface-600 text-sm max-w-lg">
                Search across live sources for real-time ticket pricing. Enter an event or team name to begin.
            </p>
        </div>

        {{-- ── SEARCH PANEL ──────────────────────────────────────────────── --}}
        <div class="bg-surface-900 border border-surface-700 rounded-2xl p-6 mb-8 animate-fade-up" style="animation-delay:0.1s">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-surface-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                        </svg>
                    </div>
                    <input
                        x-model="query"
                        @keydown.enter="run()"
                        type="text"
                        placeholder='e.g. "Lakers", "Taylor Swift", "Coachella 2025"'
                        class="neon-input w-full pl-11 pr-4 py-3.5 rounded-xl
                               bg-surface-800 border border-surface-600
                               text-white placeholder-surface-600 font-mono text-sm
                               transition-colors focus:border-brand-500"
                        :disabled="loading"
                    />
                </div>

                <button
                    @click="run()"
                    :disabled="loading || !query.trim()"
                    class="glow-btn px-6 py-3.5 rounded-xl font-bold text-sm
                           bg-brand-500 text-surface-950
                           disabled:opacity-40 disabled:cursor-not-allowed disabled:box-shadow-none
                           flex items-center gap-2 whitespace-nowrap"
                >
                    {{-- Spinner --}}
                    <template x-if="loading">
                        <svg class="spinner w-4 h-4" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="3">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83
                                     M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"
                                  stroke-linecap="round"/>
                        </svg>
                    </template>
                    {{-- Icon --}}
                    <template x-if="!loading">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </template>
                    <span x-text="loading ? 'Scanning…' : 'Run Scraper'"></span>
                </button>
            </div>

            {{-- Loading status bar --}}
            <div x-show="loading" x-transition class="mt-4">
                <div class="flex items-center gap-3 text-xs font-mono text-brand-400">
                    <span class="flex gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-400 animate-pulse-dot dot-1 inline-block"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-400 animate-pulse-dot dot-2 inline-block"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-brand-400 animate-pulse-dot dot-3 inline-block"></span>
                    </span>
                    <span>Launching Playwright · Scraping live sources · Parsing prices…</span>
                </div>
                <div class="mt-2 h-0.5 bg-surface-700 rounded-full overflow-hidden">
                    <div class="h-full bg-brand-500 rounded-full animate-[scan_2s_ease-in-out_infinite]"
                         style="animation: indeterminate 1.8s ease-in-out infinite; width: 40%;"
                         x-ref="progressBar"></div>
                </div>
            </div>
        </div>

        {{-- ── ERROR ALERT ───────────────────────────────────────────────── --}}
        <div x-show="error" x-transition class="mb-6 bg-red-950/60 border border-red-800/60 rounded-xl p-4 flex gap-3">
            <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="text-red-300 font-semibold text-sm">Scraper Error</p>
                <p x-text="error" class="text-red-400/80 text-xs font-mono mt-1"></p>
            </div>
            <button @click="error = null" class="ml-auto text-red-500 hover:text-red-300">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- ── RESULTS TABLE ─────────────────────────────────────────────── --}}
        <div x-show="results.length > 0" x-transition class="animate-fade-up" style="animation-delay:0.15s">

            {{-- Meta bar --}}
            <div class="flex items-center justify-between mb-4">
                <div>
                    <span class="text-white font-bold text-lg" x-text="results.length + ' results'"></span>
                    <span class="text-surface-600 font-mono text-xs ml-2" x-text="'for «' + lastQuery + '»'"></span>
                </div>
                <button @click="results = []; lastQuery = ''"
                        class="text-xs font-mono text-surface-600 hover:text-brand-400 transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    CLEAR
                </button>
            </div>

            {{-- Table --}}
            <div class="rounded-2xl border border-surface-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-800 border-b border-surface-700">
                                <th class="text-left px-5 py-3.5 font-semibold text-surface-600 font-mono text-xs uppercase tracking-wider w-8">#</th>
                                <th class="text-left px-5 py-3.5 font-semibold text-surface-600 font-mono text-xs uppercase tracking-wider">Event</th>
                                <th class="text-left px-5 py-3.5 font-semibold text-surface-600 font-mono text-xs uppercase tracking-wider">Section</th>
                                <th class="text-left px-5 py-3.5 font-semibold text-surface-600 font-mono text-xs uppercase tracking-wider">Price</th>
                                <th class="text-left px-5 py-3.5 font-semibold text-surface-600 font-mono text-xs uppercase tracking-wider">Source</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-800">
                            <template x-for="(row, i) in results" :key="i">
                                <tr class="result-row bg-surface-900"
                                    :style="`animation-delay: ${i * 0.04}s`">

                                    {{-- Index --}}
                                    <td class="px-5 py-4 text-surface-600 font-mono text-xs"
                                        x-text="i + 1"></td>

                                    {{-- Event --}}
                                    <td class="px-5 py-4">
                                        <span class="text-white font-medium leading-snug line-clamp-2"
                                              x-text="row.event"></span>
                                    </td>

                                    {{-- Section --}}
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md
                                                     bg-surface-700 border border-surface-600
                                                     text-brand-300 font-mono text-xs"
                                              x-text="row.section"></span>
                                    </td>

                                    {{-- Price --}}
                                    <td class="px-5 py-4">
                                        <span class="price-badge inline-flex items-center px-3 py-1 rounded-lg
                                                     bg-brand-900/60 border border-brand-600/50
                                                     text-brand-400 font-mono font-semibold text-sm"
                                              x-text="row.price"></span>
                                    </td>

                                    {{-- Source --}}
                                    <td class="px-5 py-4">
                                        <span class="text-surface-600 font-mono text-xs truncate max-w-[180px] block"
                                              x-text="row.source"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer note --}}
            <p class="mt-3 text-xs font-mono text-surface-700 text-center">
                Data scraped live · Not affiliated with any ticketing platform · For research use only
            </p>
        </div>

        {{-- ── EMPTY STATE ───────────────────────────────────────────────── --}}
        <div x-show="!loading && results.length === 0 && !error" class="text-center py-20">
            <div class="w-16 h-16 rounded-2xl bg-surface-800 border border-surface-700 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-surface-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
            </div>
            <p class="text-surface-600 font-mono text-sm">No results yet · Run a search to begin</p>
        </div>

    </main>

    {{-- ── ALPINE CONTROLLER ─────────────────────────────────────────────── --}}
    <script>
        function ticketScraper() {
            return {
                query:      '',
                loading:    false,
                results:    [],
                error:      null,
                lastQuery:  '',

                init() {
                    // Animate indeterminate progress bar via JS since Tailwind JIT
                    // can't generate custom keyframes at runtime
                    const style = document.createElement('style');
                    style.textContent = `
                        @keyframes indeterminate {
                            0%   { transform: translateX(-100%); width: 45%; }
                            50%  { transform: translateX(120%);  width: 45%; }
                            100% { transform: translateX(300%);  width: 45%; }
                        }
                    `;
                    document.head.appendChild(style);
                },

                async run() {
                    if (!this.query.trim() || this.loading) return;

                    this.loading   = true;
                    this.error     = null;
                    this.results   = [];
                    this.lastQuery = this.query.trim();

                    try {
                        const response = await fetch('{{ route("scraper.run") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept':       'application/json',
                            },
                            body: JSON.stringify({ query: this.query.trim() }),
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            this.error = data.message || `Server error (HTTP ${response.status})`;
                        } else if (data.results.length === 0) {
                            this.error = `No ticket prices found for "${this.lastQuery}". Try a broader search.`;
                        } else {
                            this.results = data.results;
                        }

                    } catch (err) {
                        this.error = 'Network error: ' + (err.message || 'Could not reach the server.');
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>

</body>
</html>
