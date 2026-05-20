<?php
/**
 * OpenShelf - Support Us Page
 * High-fidelity, modern payment gateway UI for donations
 */

session_start();
include '../includes/header.php';
?>

<!-- Tailwind CSS via CDN -->
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
                    brand: {
                        indigo: '#2C3E50',
                        teal: '#4C9F8A',
                    }
                },
                fontFamily: {
                    sans: ['Outfit', 'Inter', 'sans-serif'],
                },
            }
        }
    }
</script>

<style>
    /* Prevent Tailwind's reset from affecting the project header/footer too much */
    .support-us-page {
        font-family: 'Outfit', sans-serif;
    }
    
    /* Animation for the cards */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fadeInUp {
        animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: var(--secondary);
    }
</style>

<main class="support-us-page bg-[#f8fafc]">
    <!-- Hero Section -->
    <div class="relative overflow-hidden pt-20 pb-12 lg:pt-28 lg:pb-20">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-4 py-1.5 rounded-full bg-teal-50 text-secondary text-sm font-bold mb-8 animate-fadeInUp">
                <span class="flex h-2.5 w-2.5 rounded-full bg-secondary mr-3 animate-pulse"></span>
                Support Our Mission
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold text-primary tracking-tight mb-8 animate-fadeInUp delay-100 leading-[1.1]">
                Support <span class="text-secondary">Our Work</span>
            </h1>
            <p class="max-w-2xl mx-auto text-lg md:text-xl text-slate-600 leading-relaxed animate-fadeInUp delay-200 font-medium">
                Help us keep OpenShelf free and accessible for everyone. Your contribution helps us cover server costs, domain fees, and continuous development of new features.
            </p>
        </div>
        
        <!-- Subtle background elements -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full pointer-events-none -z-10">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-teal-100/40 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/10 rounded-full blur-[120px]"></div>
        </div>
    </div>

    <!-- Payment Methods Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
            
            <!-- bKash Card -->
            <div class="bg-white rounded-[2.5rem] shadow-[0_8px_40px_rgba(0,0,0,0.04)] border border-slate-100 p-10 flex flex-col transition-all duration-500 hover:shadow-[0_25px_60px_rgba(209,32,83,0.12)] hover:-translate-y-3 group animate-fadeInUp delay-100">
                <div class="flex items-center justify-between mb-12">
                    <div class="w-18 h-18 rounded-3xl bg-bkash/10 flex items-center justify-center transition-transform duration-500 group-hover:scale-110">
                        <img src="https://www.logo.wine/a/logo/BKash/BKash-Logo.wine.svg" alt="bKash" class="w-14 h-auto">
                    </div>
                    <span class="text-[11px] font-extrabold text-bkash uppercase tracking-[0.2em] bg-bkash/5 px-5 py-2 rounded-full border border-bkash/10">Personal</span>
                </div>
                
                <h3 class="text-3xl font-black text-slate-900 mb-3 tracking-tight">bKash</h3>
                <p class="text-slate-500 text-base leading-relaxed mb-10 font-medium">Fast and secure mobile payments via bKash. Send money to the number below.</p>
                
                <div class="mt-auto space-y-8">
                    <div class="relative overflow-hidden p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 group/item">
                        <div class="flex items-center justify-between relative z-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Number</span>
                                <span class="font-mono text-xl text-slate-700 font-black tracking-wider" id="bkash-num">01700000000</span>
                            </div>
                            <button onclick="copyToClipboard('01700000000', 'bkash-btn')" id="bkash-btn" class="flex items-center gap-2 px-5 py-2.5 bg-white text-bkash text-sm font-bold rounded-2xl shadow-sm border border-slate-100 hover:bg-bkash hover:text-white transition-all duration-300">
                                <i class="far fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <label class="text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Transaction ID</label>
                        <div class="relative">
                            <input type="text" 
                                   maxlength="10"
                                   placeholder="e.g. 9C87654321" 
                                   class="w-full pl-12 pr-4 py-5 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-bkash/5 focus:border-bkash outline-none transition-all duration-300 text-slate-800 font-bold placeholder:text-slate-300 text-lg"
                            >
                            <i class="fas fa-receipt absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 transition-colors duration-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nagad Card -->
            <div class="bg-white rounded-[2.5rem] shadow-[0_8px_40px_rgba(0,0,0,0.04)] border border-slate-100 p-10 flex flex-col transition-all duration-500 hover:shadow-[0_25px_60px_rgba(247,146,30,0.12)] hover:-translate-y-3 group animate-fadeInUp delay-200">
                <div class="flex items-center justify-between mb-12">
                    <div class="w-18 h-18 rounded-3xl bg-nagad/10 flex items-center justify-center transition-transform duration-500 group-hover:scale-110">
                        <img src="https://download.logo.wine/logo/Nagad/Nagad-Logo.wine.png" alt="Nagad" class="w-14 h-auto">
                    </div>
                    <span class="text-[11px] font-extrabold text-nagad uppercase tracking-[0.2em] bg-nagad/5 px-5 py-2 rounded-full border border-nagad/10">Personal</span>
                </div>
                
                <h3 class="text-3xl font-black text-slate-900 mb-3 tracking-tight">Nagad</h3>
                <p class="text-slate-500 text-base leading-relaxed mb-10 font-medium">Easy and convenient donation through Nagad wallet. Available 24/7.</p>
                
                <div class="mt-auto space-y-8">
                    <div class="relative overflow-hidden p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 group/item">
                        <div class="flex items-center justify-between relative z-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Number</span>
                                <span class="font-mono text-xl text-slate-700 font-black tracking-wider" id="nagad-num">01700000000</span>
                            </div>
                            <button onclick="copyToClipboard('01700000000', 'nagad-btn')" id="nagad-btn" class="flex items-center gap-2 px-5 py-2.5 bg-white text-nagad text-sm font-bold rounded-2xl shadow-sm border border-slate-100 hover:bg-nagad hover:text-white transition-all duration-300">
                                <i class="far fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <label class="text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Transaction ID</label>
                        <div class="relative">
                            <input type="text" 
                                   minlength="8"
                                   maxlength="12"
                                   placeholder="e.g. 72N8K9M2" 
                                   class="w-full pl-12 pr-4 py-5 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-nagad/5 focus:border-nagad outline-none transition-all duration-300 text-slate-800 font-bold placeholder:text-slate-300 text-lg"
                            >
                            <i class="fas fa-receipt absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rocket Card -->
            <div class="bg-white rounded-[2.5rem] shadow-[0_8px_40px_rgba(0,0,0,0.04)] border border-slate-100 p-10 flex flex-col transition-all duration-500 hover:shadow-[0_25px_60px_rgba(140,52,148,0.12)] hover:-translate-y-3 group animate-fadeInUp delay-300">
                <div class="flex items-center justify-between mb-12">
                    <div class="w-18 h-18 rounded-3xl bg-rocket/10 flex items-center justify-center transition-transform duration-500 group-hover:scale-110">
                        <img src="https://searchvectorlogo.com/wp-content/uploads/2020/05/dutch-bangla-rocket-logo-vector.png" alt="Rocket" class="w-14 h-auto">
                    </div>
                    <span class="text-[11px] font-extrabold text-rocket uppercase tracking-[0.2em] bg-rocket/5 px-5 py-2 rounded-full border border-rocket/10">Personal</span>
                </div>
                
                <h3 class="text-3xl font-black text-slate-900 mb-3 tracking-tight">Rocket</h3>
                <p class="text-slate-500 text-base leading-relaxed mb-10 font-medium">Support us via Rocket (Dutch-Bangla Bank) mobile banking service.</p>
                
                <div class="mt-auto space-y-8">
                    <div class="relative overflow-hidden p-5 bg-slate-50 rounded-[1.5rem] border border-slate-100 group/item">
                        <div class="flex items-center justify-between relative z-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Number</span>
                                <span class="font-mono text-xl text-slate-700 font-black tracking-wider" id="rocket-num">01700000000-0</span>
                            </div>
                            <button onclick="copyToClipboard('01700000000-0', 'rocket-btn')" id="rocket-btn" class="flex items-center gap-2 px-5 py-2.5 bg-white text-rocket text-sm font-bold rounded-2xl shadow-sm border border-slate-100 hover:bg-rocket hover:text-white transition-all duration-300">
                                <i class="far fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <label class="text-[11px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Transaction ID</label>
                        <div class="relative">
                            <input type="text" 
                                   maxlength="10"
                                   placeholder="e.g. 1234567890" 
                                   class="w-full pl-12 pr-4 py-5 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-rocket/5 focus:border-rocket outline-none transition-all duration-300 text-slate-800 font-bold placeholder:text-slate-300 text-lg"
                            >
                            <i class="fas fa-receipt absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Message -->
        <div class="mt-24 text-center animate-fadeInUp delay-300">
            <div class="inline-flex flex-col items-center">
                <div class="flex items-center gap-6 mb-10">
                    <div class="h-[1.5px] w-16 bg-slate-200"></div>
                    <div class="text-secondary">
                        <i class="fas fa-heart text-2xl animate-pulse"></i>
                    </div>
                    <div class="h-[1.5px] w-16 bg-slate-200"></div>
                </div>
                <h4 class="text-2xl font-black text-primary mb-4 tracking-tight">Thank you for your generosity!</h4>
                <p class="text-slate-500 max-w-lg mx-auto leading-relaxed font-medium text-lg">
                    Every contribution, no matter how small, helps us maintain this platform for students. We deeply appreciate your support in making knowledge more accessible.
                </p>
                <div class="mt-12 flex flex-wrap justify-center gap-5">
                    <a href="/books/" class="px-8 py-4 bg-white text-primary font-extrabold rounded-2xl border border-slate-200 hover:bg-slate-50 transition-all duration-400 shadow-sm">
                        Back to Library
                    </a>
                    <a href="/contact.php" class="px-8 py-4 bg-secondary text-white font-extrabold rounded-2xl shadow-xl shadow-teal-200 hover:bg-primary hover:-translate-y-1 transition-all duration-400">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    /**
     * Copy text to clipboard and provide visual feedback
     */
    function copyToClipboard(text, btnId) {
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.getElementById(btnId);
            const originalContent = btn.innerHTML;
            
            // Add success state
            btn.innerHTML = '<i class="fas fa-check"></i> Copied';
            btn.classList.add('!bg-green-500', '!text-white', '!border-green-500');
            
            // Revert after delay
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.classList.remove('!bg-green-500', '!text-white', '!border-green-500');
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }

    // Input validation hints/logic (Client-side visual feedback)
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            const val = this.value;
            const container = this.closest('.space-y-4');
            const icon = container.querySelector('.fa-receipt');
            
            // Just some subtle visual feedback
            if (val.length > 0) {
                icon.classList.remove('text-slate-300');
                icon.classList.add('text-secondary');
            } else {
                icon.classList.remove('text-secondary');
                icon.classList.add('text-slate-300');
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
