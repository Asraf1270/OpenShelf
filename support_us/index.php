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
                },
                fontFamily: {
                    sans: ['Inter', 'Roboto', 'sans-serif'],
                },
            }
        }
    }
</script>

<style>
    /* Prevent Tailwind's reset from affecting the project header/footer too much */
    /* Usually not needed if using standard classes, but just in case */
    .support-us-page {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    
    /* Animation for the cards */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }

    /* Custom scrollbar for a more SaaS look */
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<main class="support-us-page bg-[#f8fafc]">
    <!-- Hero Section -->
    <div class="relative overflow-hidden pt-16 pb-12 lg:pt-24 lg:pb-20">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-600 text-sm font-semibold mb-6 animate-fadeInUp">
                <span class="flex h-2 w-2 rounded-full bg-indigo-600 mr-2"></span>
                Support Our Mission
            </div>
            <h1 class="text-4xl md:text-6xl font-extrabold text-slate-900 tracking-tight mb-6 animate-fadeInUp delay-100">
                Support Our Work
            </h1>
            <p class="max-w-2xl mx-auto text-lg md:text-xl text-slate-600 leading-relaxed animate-fadeInUp delay-200">
                Help us keep OpenShelf free and accessible for everyone. Your contribution helps us cover server costs, domain fees, and continuous development of new features.
            </p>
        </div>
        
        <!-- Subtle background elements -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full pointer-events-none -z-10">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-100/40 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-pink-100/30 rounded-full blur-[120px]"></div>
        </div>
    </div>

    <!-- Payment Methods Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
            
            <!-- bKash Card -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 flex flex-col transition-all duration-500 hover:shadow-[0_20px_50px_rgba(209,32,83,0.1)] hover:-translate-y-2 group animate-fadeInUp delay-100">
                <div class="flex items-center justify-between mb-10">
                    <div class="w-16 h-16 rounded-2xl bg-bkash/10 flex items-center justify-center transition-transform duration-500 group-hover:scale-110">
                        <!-- We use a placeholder icon if external image fails, but bKash is iconic -->
                        <img src="https://www.logo.wine/a/logo/BKash/BKash-Logo.wine.svg" alt="bKash" class="w-12 h-auto">
                    </div>
                    <span class="text-[10px] font-bold text-bkash uppercase tracking-[0.2em] bg-bkash/5 px-4 py-1.5 rounded-full border border-bkash/10">Personal</span>
                </div>
                
                <h3 class="text-2xl font-bold text-slate-900 mb-2">bKash</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-8">Fast and secure mobile payments via bKash. Send money to the number below.</p>
                
                <div class="mt-auto space-y-6">
                    <div class="relative overflow-hidden p-4 bg-slate-50 rounded-2xl border border-slate-100 group/item">
                        <div class="flex items-center justify-between relative z-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Number</span>
                                <span class="font-mono text-lg text-slate-700 font-bold tracking-wider" id="bkash-num">01700000000</span>
                            </div>
                            <button onclick="copyToClipboard('01700000000', 'bkash-btn')" id="bkash-btn" class="flex items-center gap-2 px-4 py-2 bg-white text-bkash text-sm font-bold rounded-xl shadow-sm border border-slate-100 hover:bg-bkash hover:text-white transition-all duration-300">
                                <i class="far fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-r from-bkash/0 via-bkash/[0.02] to-bkash/0 translate-x-[-100%] group-hover/item:translate-x-[100%] transition-transform duration-1000"></div>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-[0.15em] ml-1">Transaction ID</label>
                        <div class="relative">
                            <input type="text" 
                                   maxlength="10"
                                   placeholder="e.g. 9C87654321" 
                                   class="w-full pl-11 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-bkash/5 focus:border-bkash outline-none transition-all duration-300 text-slate-700 font-medium placeholder:text-slate-300"
                            >
                            <i class="fas fa-receipt absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 transition-colors duration-300"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 ml-1">Must be exactly 10 characters (alphanumeric)</p>
                    </div>
                </div>
            </div>

            <!-- Nagad Card -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 flex flex-col transition-all duration-500 hover:shadow-[0_20px_50px_rgba(247,146,30,0.1)] hover:-translate-y-2 group animate-fadeInUp delay-200">
                <div class="flex items-center justify-between mb-10">
                    <div class="w-16 h-16 rounded-2xl bg-nagad/10 flex items-center justify-center transition-transform duration-500 group-hover:scale-110">
                        <img src="https://download.logo.wine/logo/Nagad/Nagad-Logo.wine.png" alt="Nagad" class="w-12 h-auto">
                    </div>
                    <span class="text-[10px] font-bold text-nagad uppercase tracking-[0.2em] bg-nagad/5 px-4 py-1.5 rounded-full border border-nagad/10">Personal</span>
                </div>
                
                <h3 class="text-2xl font-bold text-slate-900 mb-2">Nagad</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-8">Easy and convenient donation through Nagad wallet. Available 24/7.</p>
                
                <div class="mt-auto space-y-6">
                    <div class="relative overflow-hidden p-4 bg-slate-50 rounded-2xl border border-slate-100 group/item">
                        <div class="flex items-center justify-between relative z-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Number</span>
                                <span class="font-mono text-lg text-slate-700 font-bold tracking-wider" id="nagad-num">01700000000</span>
                            </div>
                            <button onclick="copyToClipboard('01700000000', 'nagad-btn')" id="nagad-btn" class="flex items-center gap-2 px-4 py-2 bg-white text-nagad text-sm font-bold rounded-xl shadow-sm border border-slate-100 hover:bg-nagad hover:text-white transition-all duration-300">
                                <i class="far fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-r from-nagad/0 via-nagad/[0.02] to-nagad/0 translate-x-[-100%] group-hover/item:translate-x-[100%] transition-transform duration-1000"></div>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-[0.15em] ml-1">Transaction ID</label>
                        <div class="relative">
                            <input type="text" 
                                   minlength="8"
                                   maxlength="12"
                                   placeholder="e.g. 72N8K9M2" 
                                   class="w-full pl-11 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-nagad/5 focus:border-nagad outline-none transition-all duration-300 text-slate-700 font-medium placeholder:text-slate-300"
                            >
                            <i class="fas fa-receipt absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 ml-1">Between 8 to 12 alphanumeric characters</p>
                    </div>
                </div>
            </div>

            <!-- Rocket Card -->
            <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 flex flex-col transition-all duration-500 hover:shadow-[0_20px_50px_rgba(140,52,148,0.1)] hover:-translate-y-2 group animate-fadeInUp delay-300">
                <div class="flex items-center justify-between mb-10">
                    <div class="w-16 h-16 rounded-2xl bg-rocket/10 flex items-center justify-center transition-transform duration-500 group-hover:scale-110">
                        <img src="https://searchvectorlogo.com/wp-content/uploads/2020/05/dutch-bangla-rocket-logo-vector.png" alt="Rocket" class="w-12 h-auto">
                    </div>
                    <span class="text-[10px] font-bold text-rocket uppercase tracking-[0.2em] bg-rocket/5 px-4 py-1.5 rounded-full border border-rocket/10">Personal</span>
                </div>
                
                <h3 class="text-2xl font-bold text-slate-900 mb-2">Rocket</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-8">Support us via Rocket (Dutch-Bangla Bank) mobile banking service.</p>
                
                <div class="mt-auto space-y-6">
                    <div class="relative overflow-hidden p-4 bg-slate-50 rounded-2xl border border-slate-100 group/item">
                        <div class="flex items-center justify-between relative z-10">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Account Number</span>
                                <span class="font-mono text-lg text-slate-700 font-bold tracking-wider" id="rocket-num">01700000000-0</span>
                            </div>
                            <button onclick="copyToClipboard('01700000000-0', 'rocket-btn')" id="rocket-btn" class="flex items-center gap-2 px-4 py-2 bg-white text-rocket text-sm font-bold rounded-xl shadow-sm border border-slate-100 hover:bg-rocket hover:text-white transition-all duration-300">
                                <i class="far fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-r from-rocket/0 via-rocket/[0.02] to-rocket/0 translate-x-[-100%] group-hover/item:translate-x-[100%] transition-transform duration-1000"></div>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-[0.15em] ml-1">Transaction ID</label>
                        <div class="relative">
                            <input type="text" 
                                   maxlength="10"
                                   placeholder="e.g. 1234567890" 
                                   class="w-full pl-11 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-rocket/5 focus:border-rocket outline-none transition-all duration-300 text-slate-700 font-medium placeholder:text-slate-300"
                            >
                            <i class="fas fa-receipt absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                        </div>
                        <p class="text-[10px] text-slate-400 ml-1">Must be exactly 10 digits (numeric only)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Message -->
        <div class="mt-20 text-center animate-fadeInUp delay-300">
            <div class="inline-flex flex-col items-center">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-px w-12 bg-slate-200"></div>
                    <div class="text-indigo-600">
                        <i class="fas fa-heart text-xl animate-pulse"></i>
                    </div>
                    <div class="h-px w-12 bg-slate-200"></div>
                </div>
                <h4 class="text-xl font-bold text-slate-900 mb-2">Thank you for your generosity!</h4>
                <p class="text-slate-500 max-w-lg mx-auto leading-relaxed">
                    Every contribution, no matter how small, helps us maintain this platform for students. We deeply appreciate your support in making knowledge more accessible.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <a href="/books/" class="px-6 py-3 bg-white text-slate-700 font-semibold rounded-2xl border border-slate-200 hover:bg-slate-50 transition-all duration-300">
                        Back to Library
                    </a>
                    <a href="/contact.php" class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-2xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all duration-300">
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
            const container = this.closest('.space-y-3');
            const icon = container.querySelector('.fa-receipt');
            
            // Just some subtle visual feedback
            if (val.length > 0) {
                icon.classList.remove('text-slate-300');
                icon.classList.add('text-indigo-400');
            } else {
                icon.classList.remove('text-indigo-400');
                icon.classList.add('text-slate-300');
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
