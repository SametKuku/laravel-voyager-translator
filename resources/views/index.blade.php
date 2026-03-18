<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Voyager Translator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <script>
        window.VT_ROUTES = {
            loadDb:         "{{ route('voyager-translator.load-db') }}",
            uploadSql:      "{{ route('voyager-translator.upload-sql') }}",
            translateBatch: "{{ route('voyager-translator.translate-batch') }}",
            save:           "{{ route('voyager-translator.save') }}",
            exportSql:      "{{ route('voyager-translator.export-sql') }}",
            exportJson:     "{{ route('voyager-translator.export-json') }}",
        };
    </script>
</head>
<body class="bg-slate-50 min-h-screen font-sans" x-data="vtApp()" x-cloak>

    {{-- Header --}}
    <header class="bg-gradient-to-r from-indigo-700 to-indigo-600 text-white px-6 py-4 shadow-lg">
        <div class="max-w-4xl mx-auto flex items-center gap-3">
            <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center ring-1 ring-white/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
            </div>
            <div>
                <h1 class="font-bold text-lg leading-tight">Voyager Translator</h1>
                <p class="text-xs text-indigo-200">Auto-translate Voyager CMS content — Gemini AI or Google Translate</p>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 py-8 space-y-5">

        {{-- ── STEP 1: Load Data ───────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden fade-in">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                <h2 class="font-semibold text-slate-800">Load Data</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- From DB --}}
                    <div class="rounded-xl border-2 p-5 transition-colors"
                        :class="dataSource === 'db' ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:border-indigo-300'">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">From Database</p>
                                <p class="text-xs text-slate-500 mt-0.5">Read directly from your Laravel DB connection</p>
                            </div>
                        </div>
                        <button @click="loadFromDb()" :disabled="loading"
                            class="w-full px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                            <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="loading && dataSource === 'db' ? 'Connecting...' : 'Connect & Load'"></span>
                        </button>
                    </div>

                    {{-- Upload SQL --}}
                    <div class="rounded-xl border-2 p-5 transition-colors"
                        :class="dataSource === 'sql' ? 'border-slate-700 bg-slate-50' : 'border-slate-200 hover:border-slate-400'">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-800 text-sm">Upload SQL Dump</p>
                                <p class="text-xs text-slate-500 mt-0.5">Upload a <code class="bg-slate-100 px-1 rounded">.sql</code> file from your backup</p>
                            </div>
                        </div>
                        <label class="w-full px-4 py-2.5 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-900 transition-colors cursor-pointer flex items-center justify-center gap-2"
                            :class="loading ? 'opacity-50 pointer-events-none' : ''">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span x-text="loading && dataSource === 'sql' ? 'Parsing...' : 'Choose .sql file'"></span>
                            <input type="file" class="hidden" accept=".sql,.txt" @change="uploadSql($event)">
                        </label>
                    </div>
                </div>

                {{-- Loaded stats --}}
                <template x-if="loaded">
                    <div class="mt-5 bg-emerald-50 border border-emerald-200 rounded-xl p-4 fade-in">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>
                            <span class="text-sm font-semibold text-emerald-800">
                                Loaded <span x-text="totalGroups.toLocaleString()"></span> translation groups
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(count, locale) in localeStats" :key="locale">
                                <div class="flex items-center gap-1.5 px-2.5 py-1 bg-white border border-emerald-200 rounded-full text-xs">
                                    <span class="font-mono font-bold text-slate-700" x-text="locale.toUpperCase()"></span>
                                    <span class="text-slate-400" x-text="count.toLocaleString()"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="loadError">
                    <div class="mt-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 fade-in" x-text="loadError"></div>
                </template>
            </div>
        </div>

        {{-- ── STEP 2: Languages ───────────────────────────────────────── --}}
        <template x-if="loaded">
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden fade-in">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                    <h2 class="font-semibold text-slate-800">Languages</h2>
                </div>
                <div class="p-6 space-y-5">
                    {{-- Source --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Source Language</label>
                        <div class="flex items-center gap-3">
                            <select x-model="sourceLang"
                                class="border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-400 min-w-[220px]">
                                <template x-for="lang in langs" :key="lang.code">
                                    <option :value="lang.code" x-text="lang.flag + '  ' + lang.name + ' (' + lang.code + ')'"></option>
                                </template>
                            </select>
                            <span class="text-xs text-slate-400">Auto-detected: <span class="font-semibold text-slate-600" x-text="detectedLang.toUpperCase()"></span></span>
                        </div>
                    </div>
                    {{-- Targets --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Target Languages</label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="lang in langs" :key="lang.code">
                                <button @click="toggleTarget(lang.code)"
                                    :disabled="lang.code === sourceLang"
                                    :class="targetLangs.includes(lang.code)
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                        : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-400'"
                                    class="px-3.5 py-1.5 text-sm border rounded-full transition-all disabled:opacity-25 disabled:cursor-not-allowed">
                                    <span x-text="lang.flag + ' ' + lang.name"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="targetLangs.length === 0" class="text-xs text-amber-600 mt-2">Select at least one target language</p>
                    </div>
                </div>
            </div>
        </template>

        {{-- ── STEP 3: Engine ──────────────────────────────────────────── --}}
        <template x-if="loaded">
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden fade-in">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">3</span>
                    <h2 class="font-semibold text-slate-800">Translation Engine</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <button @click="engine = 'gtx'"
                            :class="engine === 'gtx' ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-300' : 'border-slate-200 hover:border-slate-300'"
                            class="p-4 border-2 rounded-xl text-left transition-all">
                            <div class="text-sm font-semibold text-slate-800 mb-0.5">Google Translate</div>
                            <div class="text-xs text-slate-500">Free — no API key required</div>
                        </button>
                        <button @click="engine = 'gemini'"
                            :class="engine === 'gemini' ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-300' : 'border-slate-200 hover:border-slate-300'"
                            class="p-4 border-2 rounded-xl text-left transition-all">
                            <div class="text-sm font-semibold text-slate-800 mb-0.5">Gemini AI ✨</div>
                            <div class="text-xs text-slate-500">Fast & accurate — requires API key</div>
                        </button>
                    </div>
                    <template x-if="engine === 'gemini'">
                        <div class="flex gap-2 fade-in">
                            <input x-model="geminiKey" type="password"
                                placeholder="Paste Gemini API Key (or set GEMINI_API_KEY in .env)"
                                class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <button @click="saveKey()"
                                class="px-4 py-2 bg-slate-700 text-white text-sm rounded-lg hover:bg-slate-800 transition-colors whitespace-nowrap">
                                Save Key
                            </button>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank"
                                class="px-3 py-2 border border-slate-200 text-slate-500 text-sm rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Get Key
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- ── STEP 4: Translate ───────────────────────────────────────── --}}
        <template x-if="loaded && targetLangs.length > 0">
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden fade-in">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">4</span>
                    <h2 class="font-semibold text-slate-800">Translate</h2>
                </div>
                <div class="p-6">

                    {{-- Per-locale progress --}}
                    <template x-if="translating || completedLocales.length > 0">
                        <div class="space-y-3 mb-5">
                            <template x-for="locale in targetLangs" :key="locale">
                                <div class="fade-in">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-sm font-medium text-slate-700" x-text="langName(locale)"></span>
                                        <span class="text-xs tabular-nums"
                                            :class="progressMap[locale] === 100 ? 'text-emerald-600 font-semibold' : 'text-slate-400'"
                                            x-text="progressMap[locale] === 100 ? '✓ Done' : ((progressMap[locale] || 0) + '%')"></span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full transition-all duration-500"
                                            :class="progressMap[locale] === 100 ? 'bg-emerald-500' : 'bg-indigo-500'"
                                            :style="'width:' + (progressMap[locale] || 0) + '%'"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Status --}}
                    <template x-if="statusMsg">
                        <p class="text-sm text-slate-600 mb-4 bg-slate-50 rounded-lg px-4 py-2.5" x-text="statusMsg"></p>
                    </template>

                    {{-- Error --}}
                    <template x-if="txError">
                        <div class="text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-4 py-3 mb-4 fade-in" x-text="txError"></div>
                    </template>

                    <div class="flex items-center gap-3">
                        <button @click="startTranslation()"
                            :disabled="translating || (completedLocales.length === targetLangs.length && targetLangs.length > 0)"
                            class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-40 transition-colors flex items-center gap-2 shadow-sm">
                            <svg x-show="!translating" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                            </svg>
                            <svg x-show="translating" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="translating ? 'Translating…' : 'Start Translation'"></span>
                        </button>
                        <button @click="resetAll()" :disabled="translating"
                            class="px-4 py-2.5 border border-slate-200 text-slate-500 text-sm rounded-lg hover:bg-slate-50 disabled:opacity-40 transition-colors">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- ── STEP 5: Save & Export ───────────────────────────────────── --}}
        <template x-if="completedLocales.length > 0">
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden fade-in">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-emerald-500 text-white text-xs font-bold flex items-center justify-center">✓</span>
                    <h2 class="font-semibold text-slate-800">Save & Export</h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap gap-2 mb-5">
                        <template x-for="locale in completedLocales" :key="locale">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-full text-sm text-emerald-700 font-medium">
                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span x-text="langName(locale)"></span>
                            </span>
                        </template>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        {{-- Save to DB --}}
                        <button @click="saveToDb()" :disabled="saving"
                            class="px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition-colors flex items-center gap-2 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            <span x-text="saving ? 'Saving…' : (savedCount ? 'Saved ' + savedCount.toLocaleString() + ' rows ✓' : 'Save to Database')"></span>
                        </button>

                        {{-- Download SQL --}}
                        <button @click="exportSql()"
                            class="px-5 py-2.5 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download SQL
                        </button>

                        {{-- Download JSON --}}
                        <button @click="exportJson()"
                            class="px-5 py-2.5 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download JSON
                        </button>
                    </div>

                    <template x-if="saveError">
                        <p class="text-sm text-red-600 mt-3" x-text="saveError"></p>
                    </template>
                </div>
            </div>
        </template>

    </div>{{-- /container --}}

    <script>
    function vtApp() {
        return {
            // Data state
            sessionId:        null,
            loaded:           false,
            loading:          false,
            dataSource:       null,
            totalGroups:      0,
            localeStats:      {},
            detectedLang:     'en',
            loadError:        '',

            // Language config
            sourceLang:  'en',
            targetLangs: [],
            engine:      'gtx',
            geminiKey:   localStorage.getItem('vt_gemini_key') || '',

            // Translation state
            translating:      false,
            progressMap:      {},
            completedLocales: [],
            statusMsg:        '',
            txError:          '',

            // Save state
            saving:     false,
            savedCount: 0,
            saveError:  '',

            // Supported languages
            langs: [
                { code: 'tr', name: 'Turkish',    flag: '🇹🇷' },
                { code: 'en', name: 'English',    flag: '🇬🇧' },
                { code: 'es', name: 'Spanish',    flag: '🇪🇸' },
                { code: 'ru', name: 'Russian',    flag: '🇷🇺' },
                { code: 'de', name: 'German',     flag: '🇩🇪' },
                { code: 'fr', name: 'French',     flag: '🇫🇷' },
                { code: 'ar', name: 'Arabic',     flag: '🇸🇦' },
                { code: 'zh', name: 'Chinese',    flag: '🇨🇳' },
                { code: 'pt', name: 'Portuguese', flag: '🇵🇹' },
                { code: 'it', name: 'Italian',    flag: '🇮🇹' },
                { code: 'ja', name: 'Japanese',   flag: '🇯🇵' },
                { code: 'ko', name: 'Korean',     flag: '🇰🇷' },
                { code: 'nl', name: 'Dutch',      flag: '🇳🇱' },
                { code: 'pl', name: 'Polish',     flag: '🇵🇱' },
                { code: 'uk', name: 'Ukrainian',  flag: '🇺🇦' },
            ],

            langName(code) {
                const l = this.langs.find(x => x.code === code);
                return l ? l.flag + ' ' + l.name : code.toUpperCase();
            },

            toggleTarget(code) {
                if (this.targetLangs.includes(code)) {
                    this.targetLangs = this.targetLangs.filter(l => l !== code);
                } else {
                    this.targetLangs = [...this.targetLangs, code];
                }
            },

            saveKey() {
                localStorage.setItem('vt_gemini_key', this.geminiKey);
            },

            // ── Load from DB ────────────────────────────────────────────────
            async loadFromDb() {
                this.loading    = true;
                this.dataSource = 'db';
                this.loadError  = '';
                try {
                    const res = await this.post(window.VT_ROUTES.loadDb, {});
                    this._applyLoadResult(res);
                } catch (e) {
                    this.loadError = 'Error: ' + e.message;
                } finally {
                    this.loading = false;
                }
            },

            // ── Upload SQL ──────────────────────────────────────────────────
            async uploadSql(event) {
                const file = event.target.files[0];
                if (!file) return;

                this.loading    = true;
                this.dataSource = 'sql';
                this.loadError  = '';

                try {
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

                    const r = await fetch(window.VT_ROUTES.uploadSql, { method: 'POST', body: fd });
                    const res = await r.json();
                    this._applyLoadResult(res);
                } catch (e) {
                    this.loadError = 'Upload error: ' + e.message;
                } finally {
                    this.loading = false;
                }
            },

            _applyLoadResult(res) {
                if (!res.success) throw new Error(res.error || 'Unknown error');
                this.sessionId    = res.id;
                this.totalGroups  = res.total;
                this.localeStats  = res.locale_stats;
                this.detectedLang = res.detected_lang;
                this.sourceLang   = res.detected_lang;
                this.loaded       = true;
                // Pre-select all locales except source
                this.targetLangs  = Object.keys(res.locale_stats).filter(l => l !== res.detected_lang);
            },

            // ── Translate ───────────────────────────────────────────────────
            async startTranslation() {
                if (!this.sessionId || this.targetLangs.length === 0) return;

                this.translating      = true;
                this.completedLocales = [];
                this.progressMap      = {};
                this.txError          = '';
                this.savedCount       = 0;

                const batchSize = this.engine === 'gemini' ? 40 : 15;

                for (const locale of this.targetLangs) {
                    this.statusMsg  = 'Translating → ' + this.langName(locale) + '…';
                    this.progressMap = { ...this.progressMap, [locale]: 0 };

                    let batchIdx = 0;
                    let done     = false;
                    let retries  = 0;

                    while (!done) {
                        try {
                            const res = await this.post(window.VT_ROUTES.translateBatch, {
                                id:          this.sessionId,
                                source_lang: this.sourceLang,
                                locale,
                                batch_index: batchIdx,
                                batch_size:  batchSize,
                                engine:      this.engine,
                                gemini_key:  this.geminiKey || null,
                            });

                            if (!res.success) throw new Error(res.error);

                            if (res.done) {
                                done = true;
                                this.progressMap = { ...this.progressMap, [locale]: 100 };
                            } else {
                                this.progressMap = { ...this.progressMap, [locale]: res.progress };
                                batchIdx++;
                                retries = 0;
                            }
                        } catch (e) {
                            retries++;
                            if (retries >= 3) {
                                this.txError = 'Failed on ' + locale + ': ' + e.message;
                                done = true;
                            } else {
                                await new Promise(r => setTimeout(r, 2000 * retries));
                            }
                        }
                    }

                    if (this.progressMap[locale] === 100) {
                        this.completedLocales = [...this.completedLocales, locale];
                    }
                }

                this.translating = false;
                this.statusMsg   = this.txError ? '' : '✓ All translations complete!';
            },

            // ── Save to DB ──────────────────────────────────────────────────
            async saveToDb() {
                this.saving    = true;
                this.saveError = '';
                try {
                    const res = await this.post(window.VT_ROUTES.save, {
                        id:      this.sessionId,
                        locales: this.completedLocales,
                    });
                    if (!res.success) throw new Error(res.error);
                    this.savedCount = res.saved;
                } catch (e) {
                    this.saveError = 'Save error: ' + e.message;
                } finally {
                    this.saving = false;
                }
            },

            // ── Exports ─────────────────────────────────────────────────────
            exportSql() {
                const loc = this.completedLocales.join(',');
                window.location.href = window.VT_ROUTES.exportSql + '?id=' + this.sessionId + '&locales=' + loc;
            },
            exportJson() {
                const loc = this.completedLocales.join(',');
                window.location.href = window.VT_ROUTES.exportJson + '?id=' + this.sessionId + '&locales=' + loc;
            },

            // ── Reset ────────────────────────────────────────────────────────
            resetAll() {
                Object.assign(this, {
                    sessionId: null, loaded: false, dataSource: null,
                    totalGroups: 0, localeStats: {}, loadError: '',
                    targetLangs: [], completedLocales: [],
                    progressMap: {}, statusMsg: '', txError: '',
                    saving: false, savedCount: 0, saveError: '',
                });
            },

            // ── HTTP helper ──────────────────────────────────────────────────
            async post(url, data) {
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(data),
                });
                const json = await r.json();
                if (!r.ok && r.status >= 500) throw new Error(json.error || 'Server error ' + r.status);
                return json;
            },
        };
    }
    </script>
</body>
</html>
