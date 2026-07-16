@extends('layouts.app')

@section('content')
@php
    $providers = [
        'bkash' => [
            'label' => 'bKash',
            'color' => 'bkash',
            'hex' => '#D12053',
            'hover' => '#c81d4c',
            'logo' => 'https://www.logo.wine/a/logo/BKash/BKash-Logo.wine.svg',
            'description' => 'দ্রুত ও নিরাপদ মোবাইল পেমেন্ট bKash এর মাধ্যমে। নিচের নম্বরে টাকা পাঠান।',
            'submit' => 'bKash দিয়ে সাহায্য করুন',
            'txn_maxlength' => 10,
            'txn_placeholder' => 'e.g. 9C87654321',
            'delay' => 'delay-100',
        ],
        'nagad' => [
            'label' => 'Nagad',
            'color' => 'nagad',
            'hex' => '#F7921E',
            'hover' => '#d47d11',
            'logo' => 'https://download.logo.wine/logo/Nagad/Nagad-Logo.wine.png',
            'description' => 'Nagad ওয়ালেটের মাধ্যমে সহজ এবং সুবিধাজনক সাহায্য ২৪/৭ সুবিধা।',
            'submit' => 'Nagad দিয়ে সাহায্য করুন',
            'txn_maxlength' => 12,
            'txn_placeholder' => 'e.g. 72N8K9M2',
            'delay' => 'delay-200',
        ],
        'rocket' => [
            'label' => 'Rocket',
            'color' => 'rocket',
            'hex' => '#8C3494',
            'hover' => '#7a2c7d',
            'logo' => 'https://searchvectorlogo.com/wp-content/uploads/2020/05/dutch-bangla-rocket-logo-vector.png',
            'description' => 'Rocket (Dutch-Bangla ব্যাংক) মোবাইল ব্যাংকিং সার্ভিসের মাধ্যমে আমাদের সমর্থন করুন।',
            'submit' => 'Rocket দিয়ে সাহায্য করুন',
            'txn_maxlength' => 10,
            'txn_placeholder' => 'e.g. 1234567890',
            'delay' => 'delay-300',
        ],
    ];
@endphp

<div class="support-us-page bg-[#f8fafc]">
    <div class="relative overflow-hidden pt-20 pb-12 lg:pt-28 lg:pb-20">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-4 py-1.5 rounded-full bg-teal-50 text-secondary text-sm font-bold mb-8 animate-fadeInUp">
                <span class="flex h-2.5 w-2.5 rounded-full bg-secondary mr-3 animate-pulse"></span>
                আমাদের মিশনকে সমর্থন করুন
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold text-primary tracking-tight mb-8 animate-fadeInUp delay-100 leading-[1.1]">
                আমাদের কাজকে <span class="text-secondary">সমর্থন করুন</span>
            </h1>
            <p class="max-w-2xl mx-auto text-lg md:text-xl text-slate-600 leading-relaxed animate-fadeInUp delay-200 font-medium">
                OpenShelf কে সকলের জন্য ফ্রি এবং সহজলভ্য রাখার ক্ষেত্রে আমাদের সাহায্য করুন। আপনার অনুদান সার্ভার খরচ, ডোমেইন ফি, এবং নতুন ফিচারগুলোর ধারাবাহিক উন্নয়নে সহায়তা করে।
            </p>
        </div>
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full pointer-events-none -z-10">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-teal-100/40 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px]"></div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
        @if ($success || !empty($errors))
            <div class="mb-10 max-w-3xl mx-auto">
                @if ($success)
                    <div class="rounded-3xl bg-emerald-50 border border-emerald-200 p-6 text-emerald-800 shadow-sm mb-4">
                        <strong class="font-semibold">সাফল্য:</strong> {{ $success }}
                    </div>
                @endif
                @if (!empty($errors))
                    <div class="rounded-3xl bg-rose-50 border border-rose-200 p-6 text-rose-800 shadow-sm">
                        <strong class="font-semibold">অনুগ্রহ করে নিম্নলিখিত ঠিক করুন:</strong>
                        <ul class="mt-3 list-disc list-inside space-y-2">
                            @foreach ($errors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
            @foreach ($providers as $key => $provider)
                @php
                    $accountNumber = $accountNumbers[$key];
                    $values = $supportFormValues[$key];
                @endphp
                <form method="post" action="{{ route('support-us.store') }}" class="bg-white rounded-[2.5rem] shadow-[0_8px_40px_rgba(0,0,0,0.04)] border border-slate-100 p-10 flex flex-col transition-all duration-500 hover:-translate-y-3 group animate-fadeInUp {{ $provider['delay'] }}" style="hover:shadow-color: {{ $provider['hex'] }};">
                    @csrf
                    <input type="hidden" name="provider" value="{{ $key }}">
                    <div class="flex items-center justify-between mb-12">
                        <div class="w-18 h-18 rounded-3xl bg-{{ $provider['color'] }}/10 flex items-center justify-center transition-transform duration-500 group-hover:scale-110">
                            <img src="{{ $provider['logo'] }}" alt="{{ $provider['label'] }}" class="w-14 h-auto">
                        </div>
                        <span class="text-[11px] font-extrabold text-{{ $provider['color'] }} uppercase tracking-[0.2em] bg-{{ $provider['color'] }}/5 px-5 py-2 rounded-full border border-{{ $provider['color'] }}/10">ব্যক্তিগত</span>
                    </div>

                    <h3 class="text-3xl font-black text-slate-900 mb-3 tracking-tight">{{ $provider['label'] }}</h3>
                    <p class="text-slate-500 text-base leading-relaxed mb-10 font-medium">{{ $provider['description'] }}</p>

                    <div class="mt-auto space-y-8">
                        <div class="relative overflow-hidden p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 group/item">
                            <div class="flex items-center justify-between relative z-10">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">অ্যাকাউন্ট নম্বর</span>
                                    <span class="font-mono text-xl text-slate-700 font-black tracking-wider" id="{{ $key }}-num">{{ $accountNumber }}</span>
                                </div>
                                <button type="button" onclick="copyToClipboard('{{ $accountNumber }}', '{{ $key }}-btn')" id="{{ $key }}-btn" class="flex items-center gap-2 px-5 py-2.5 bg-white text-{{ $provider['color'] }} text-sm font-bold rounded-2xl shadow-sm border border-slate-100 transition-all duration-300" style="--provider-color: {{ $provider['hex'] }};" onmouseover="this.style.background='{{ $provider['hex'] }}';this.style.color='#fff';" onmouseout="this.style.background='#fff';this.style.color='{{ $provider['hex'] }}';">
                                    <i class="far fa-copy"></i> কপি করুন
                                </button>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Amount</label>
                            <div class="relative">
                                <input type="text" name="amount" value="{{ $values['amount'] }}" placeholder="e.g. 250.00" class="w-full pl-12 pr-4 py-5 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white outline-none transition-all duration-300 text-slate-800 font-bold placeholder:text-slate-300 text-lg support-input" style="--focus-color: {{ $provider['hex'] }};">
                                <i class="fas fa-dollar-sign absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 transition-colors duration-300"></i>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Transaction ID</label>
                            <div class="relative">
                                <input type="text" name="transaction_id" value="{{ $values['transaction_id'] }}" maxlength="{{ $provider['txn_maxlength'] }}" placeholder="{{ $provider['txn_placeholder'] }}" class="w-full pl-12 pr-4 py-5 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white outline-none transition-all duration-300 text-slate-800 font-bold placeholder:text-slate-300 text-lg support-input" style="--focus-color: {{ $provider['hex'] }};">
                                <i class="fas fa-receipt absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 transition-colors duration-300"></i>
                            </div>
                        </div>
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-2xl text-white font-bold py-4 px-5 shadow-lg border transition-all duration-300" style="background: {{ $provider['hex'] }}; border-color: {{ $provider['hex'] }};" onmouseover="this.style.background='{{ $provider['hover'] }}';" onmouseout="this.style.background='{{ $provider['hex'] }}';">
                            <i class="fas fa-heart"></i>
                            {{ $provider['submit'] }}
                        </button>
                    </div>
                </form>
            @endforeach
        </div>

        <div class="mt-24 text-center animate-fadeInUp delay-300">
            <div class="inline-flex flex-col items-center">
                <div class="flex items-center gap-6 mb-10">
                    <div class="h-[1.5px] w-16 bg-slate-200"></div>
                    <div class="text-secondary">
                        <i class="fas fa-heart text-2xl animate-pulse"></i>
                    </div>
                    <div class="h-[1.5px] w-16 bg-slate-200"></div>
                </div>
                <h4 class="text-2xl font-black text-primary mb-4 tracking-tight">আপনার উদারতার জন্য ধন্যবাদ!</h4>
                <p class="text-slate-500 max-w-lg mx-auto leading-relaxed font-medium text-lg">
                    প্রতিটি অনুদান, সেটা যতই ছোট হোক না কেন, আমাদেরকে এই প্ল্যাটফর্মটি শিক্ষার্থীদের জন্য বজায় রাখতে সাহায্য করে। জ্ঞানের আরো সহজলভ্যতা তৈরিতে আপনার সমর্থন আমরা আন্তরিকভাবে মূল্যায়ন করি।
                </p>
                <div class="mt-12 flex flex-wrap justify-center gap-5">
                    <a href="{{ route('books') }}" class="px-8 py-4 bg-white text-primary font-extrabold rounded-2xl border border-slate-200 hover:bg-slate-50 transition-all duration-400 shadow-sm">
                        লাইব্রেরিতে ফিরে যান
                    </a>
                    <a href="{{ route('contact') }}" class="px-8 py-4 bg-secondary text-white font-extrabold rounded-2xl shadow-xl shadow-teal-200 hover:bg-primary hover:-translate-y-1 transition-all duration-400">
                        সহায়তার সাথে যোগাযোগ করুন
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    bkash: '#D12053',
                    nagad: '#F7921E',
                    rocket: '#8C3494',
                    primary: '#2C3E50',
                    secondary: '#4C9F8A',
                    brand: { indigo: '#2C3E50', teal: '#4C9F8A' }
                },
                fontFamily: { sans: ['Outfit', 'Inter', 'sans-serif'] }
            }
        },
        safelist: [
            'bg-bkash/10', 'text-bkash', 'bg-bkash/5', 'border-bkash/10',
            'bg-nagad/10', 'text-nagad', 'bg-nagad/5', 'border-nagad/10',
            'bg-rocket/10', 'text-rocket', 'bg-rocket/5', 'border-rocket/10',
            'focus:ring-bkash/5', 'focus:border-bkash',
            'focus:ring-nagad/5', 'focus:border-nagad',
            'focus:ring-rocket/5', 'focus:border-rocket',
            'shadow-bkash/10', 'shadow-nagad/10', 'shadow-rocket/10',
            'hover:shadow-[0_25px_60px_rgba(209,32,83,0.12)]',
            'hover:shadow-[0_25px_60px_rgba(247,146,30,0.12)]',
            'hover:shadow-[0_25px_60px_rgba(140,52,148,0.12)]',
            'delay-100', 'delay-200', 'delay-300'
        ]
    }
</script>
<style>
    .support-us-page { font-family: 'Outfit', sans-serif; }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeInUp { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .support-input:focus {
        border-color: var(--focus-color) !important;
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--focus-color) 5%, transparent);
    }
</style>
@endpush

@push('scripts')
<script>
    function copyToClipboard(text, btnId) {
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.getElementById(btnId);
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> কপি হয়েছে';
            btn.style.background = '#22c55e';
            btn.style.color = '#fff';
            btn.style.borderColor = '#22c55e';
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.style.background = '';
                btn.style.color = '';
                btn.style.borderColor = '';
            }, 2000);
        }).catch(err => console.error('Failed to copy: ', err));
    }

    document.querySelectorAll('.support-input').forEach(input => {
        input.addEventListener('input', function() {
            const container = this.closest('.space-y-4');
            const icon = container?.querySelector('.fa-receipt, .fa-dollar-sign');
            if (!icon) return;
            icon.classList.toggle('text-secondary', this.value.length > 0);
            icon.classList.toggle('text-slate-300', this.value.length === 0);
        });
    });
</script>
@endpush
