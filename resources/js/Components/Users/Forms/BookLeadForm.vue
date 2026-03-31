<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useUsersStore } from "@/stores/users";
import BookQuiz from "@/Components/Users/Forms/BookQuiz.vue";
import PriceCalculator from "@/Components/Users/Forms/PriceCalculator.vue";
import AIBookStarter from "@/Components/Users/Forms/AIBookStarter.vue";

const userStore = useUsersStore();
const currentStep = ref('form'); // 'form' or 'success'
const showQuiz = ref(false);
const activeSection = ref('section-genre');

const form = reactive({
    client_name: '',
    service_type: '',
    volume_stage: 'Стандарт (100 стр.)',
    contacts: '',
    extra: '',
    calc_data: '100 стр, A5, Написание с нуля, Только PDF',
    calc_price: '0 ₽',
});

const leadId = ref(null);
const selectedFiles = ref([]);
const payment_method = ref('card');
const payment_screenshot = ref(null);
const payment_screenshot_preview = ref(null);

const handlePaymentScreenshot = (event) => {
    const file = event.target.files[0];
    if (file) {
        payment_screenshot.value = file;
        payment_screenshot_preview.value = URL.createObjectURL(file);
    }
};

const prepaymentAmount = computed(() => {
    const leftPart = form.calc_price.split('до')[0];
    const numericPrice = parseFloat(leftPart.replace(/[^0-9]/g, ''));
    if (isNaN(numericPrice) || numericPrice === 0) return '0 ₽';
    return (numericPrice * 0.5).toLocaleString('ru-RU') + ' ₽';
});

const payment_id = ref(null);
const payment_status = ref('pending');
let statusInterval = null;

const submitPayment = async () => {
    if (!payment_screenshot.value) {
        alert('Пожалуйста, прикрепите скриншот оплаты');
        return;
    }
    try {
        const response = await userStore.submitPayment({
            lead_id: form.lead_id,
            method: payment_method.value,
            screenshot: payment_screenshot.value
        });
        if (response && response.status === 'ok') {
            payment_id.value = response.payment_id;
            currentStep.value = 'success';
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            statusInterval = setInterval(checkPaymentStatus, 5000);
        }
    } catch (e) {
        console.error("Payment error:", e);
    }
};

const checkPaymentStatus = async () => {
    if (!payment_id.value) return;
    try {
        const res = await fetch(`/api/public/payments/${payment_id.value}/status`);
        if (res.ok) {
            const data = await res.json();
            payment_status.value = data.status;
            if (data.status !== 'pending') {
                clearInterval(statusInterval);
            }
        }
    } catch(e) {}
};

const genres = [
    { id: 'memoirs', name: 'Мемуары / Биография', icon: '📜', color: '#60a5fa' },
    { id: 'family', name: 'История семьи', icon: '👨‍👩‍👧‍👦', color: '#10b981' },
    { id: 'business', name: 'История компании', icon: '🏢', color: '#f59e0b' },
    { id: 'expert', name: 'Экспертная книга', icon: '💼', color: '#a855f7' },
    { id: 'editing', name: 'Редактура текста', icon: '✍️', color: '#ec4899' }
];

const selectGenre = (name) => {
    form.service_type = name;
    showQuiz.value = false;
    scrollToSection('section-ai');
};

const handleFiles = (event) => {
    const files = Array.from(event.target.files);
    selectedFiles.value = [...selectedFiles.value, ...files];
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
};

const formatSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const handleAIAfter = (text) => {
    if (text) {
        form.extra = `Идея от ИИ: \n"${text}"\n\n` + form.extra;
    }
    scrollToSection('section-calc');
};

const applyCalc = (data) => {
    form.volume_stage = data.calc_data || 'Данные не получены';
    form.calc_price = data.calc_price || '0 ₽';
    form.calc_data = data.calc_data || 'Данные не получены';
    scrollToSection('section-contacts');
};

const scrollToSection = (id) => {
    const el = document.getElementById(id);
    if (el) {
        const offset = 100;
        const bodyRect = document.body.getBoundingClientRect().top;
        const elementRect = el.getBoundingClientRect().top;
        const elementPosition = elementRect - bodyRect;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
};

const submitForm = async () => {
    if (!form.client_name || !form.contacts || !form.service_type) {
        alert('Пожалуйста, укажите Жанр, ваше Имя и Контакты для связи');
        return;
    }
    
    // Ensure volume_stage is populated before sending
    if (!form.volume_stage) {
        form.volume_stage = form.calc_data || "Параметры не выбраны";
    }

    try {
        const response = await userStore.uploadAnonymousForm({ ...form }, selectedFiles.value);
        if (response && response.lead_id) {
            form.lead_id = response.lead_id;
        }
        
        if (form.calc_price && form.calc_price !== '0 ₽') {
            currentStep.value = 'payment';
        } else {
            currentStep.value = 'success';
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (e) {
        console.error("Submission error:", e);
    }
}

const resetToStart = () => {
    if (statusInterval) clearInterval(statusInterval);
    Object.keys(form).forEach(key => form[key] = '');
    selectedFiles.value = [];
    payment_screenshot.value = null;
    payment_screenshot_preview.value = null;
    payment_id.value = null;
    payment_status.value = 'pending';
    currentStep.value = 'form';
};

const isSectionFilled = (section) => {
    if (section === 'genre') return !!form.service_type;
    if (section === 'ai') return form.extra.length > 10;
    if (section === 'calc') return !!form.calc_data;
    if (section === 'contacts') return !!form.client_name && !!form.contacts;
    return false;
};

const paymentSettings = ref({
    card_number: '0000 0000 0000 0000',
    phone_number: '+7 (000) 000-00-00',
    recipient_name: 'Иван И. (Сбербанк/Тинькофф)'
});

onMounted(async () => {
    try {
        const response = await fetch('/api/public/payment-settings');
        if (response.ok) {
            paymentSettings.value = await response.json();
        }
    } catch(e) {}

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                activeSection.value = entry.target.id;
            }
        });
    }, { threshold: 0.3 });

    document.querySelectorAll('.form-section').forEach(section => {
        observer.observe(section);
    });
});
</script>

<template>
  <div class="lead-form-wrapper">
    <transition name="fade" mode="out-in">
        <div v-if="currentStep === 'form'" class="main-form-container">
            
            <div class="background-glows">
                <div class="glow glow-1"></div>
                <div class="glow glow-2"></div>
            </div>

            <!-- Header -->
            <header class="form-header text-center mb-5">
                <div class="header-content">
                    <h1 class="biobook-title">BioBook</h1>
                    <p class="subtitle">Ваша история заслуживает стать книгой</p>
                    <div class="header-divider"></div>
                </div>
            </header>

            <!-- Sticky Nav -->
            <nav class="sticky-nav">
                <div class="scroll-indicators">
                    <button @click="scrollToSection('section-genre')" :class="{active: activeSection === 'section-genre', filled: isSectionFilled('genre')}">
                        <span class="nav-dot"></span>
                        <span class="nav-text">Жанр</span>
                    </button>
                    <button @click="scrollToSection('section-ai')" :class="{active: activeSection === 'section-ai', filled: isSectionFilled('ai')}">
                        <span class="nav-dot"></span>
                        <span class="nav-text">Идея</span>
                    </button>
                    <button @click="scrollToSection('section-calc')" :class="{active: activeSection === 'section-calc', filled: isSectionFilled('calc')}">
                        <span class="nav-dot"></span>
                        <span class="nav-text">Расчет</span>
                    </button>
                    <button @click="scrollToSection('section-files')" :class="{active: activeSection === 'section-files', filled: selectedFiles.length > 0}">
                        <span class="nav-dot"></span>
                        <span class="nav-text">Файлы</span>
                    </button>
                    <button @click="scrollToSection('section-contacts')" :class="{active: activeSection === 'section-contacts', filled: isSectionFilled('contacts')}">
                        <span class="nav-dot"></span>
                        <span class="nav-text">Контакты</span>
                    </button>
                </div>
            </nav>

            <form @submit.prevent="submitForm" class="content-wrapper">
                
                <!-- SECTION 1: GENRE -->
                <section id="section-genre" class="form-section">
                    <div class="section-card glass-panel">
                        <div class="section-header">
                            <span class="section-number">01</span>
                            <div>
                                <h2 class="section-title">Определимся с жанром</h2>
                                <p class="section-desc">Выберите подходящее направление для вашего проекта</p>
                            </div>
                        </div>
                        
                        <div class="genres-grid mt-4">
                            <div 
                                v-for="genre in genres" 
                                :key="genre.id"
                                class="genre-card-modern"
                                :class="{selected: form.service_type === genre.name}"
                                :style="{'--accent-color': genre.color}"
                                @click="selectGenre(genre.name)"
                            >
                                <div class="g-inner">
                                    <span class="g-icon">{{ genre.icon }}</span>
                                    <span class="g-name">{{ genre.name }}</span>
                                </div>
                                <div class="g-glow"></div>
                            </div>
                        </div>

                        <div class="quiz-trigger mt-4">
                            <p>Затрудняетесь с выбором?</p>
                            <button type="button" @click="showQuiz = !showQuiz" class="btn-secondary">
                                {{ showQuiz ? 'Скрыть помощник' : 'Пройти быстрый тест ✨' }}
                            </button>
                        </div>

                        <transition name="expand">
                            <div v-if="showQuiz" class="quiz-embed mt-4">
                                <BookQuiz @select="selectGenre" />
                            </div>
                        </transition>
                    </div>
                </section>

                <!-- SECTION 2: AI & DESCRIPTION -->
                <section id="section-ai" class="form-section">
                    <div class="section-card glass-panel">
                        <div class="section-header">
                            <span class="section-number">02</span>
                            <div>
                                <h2 class="section-title">О чем будет книга?</h2>
                                <p class="section-desc">Опишите вашу задумку, сюжет или главных героев</p>
                            </div>
                        </div>

                        <div class="input-container-modern mt-4">
                            <div class="textarea-wrapper-glow">
                                <textarea 
                                    v-model="form.extra" 
                                    class="modern-textarea-premium" 
                                    placeholder="Напишите здесь всё, что считаете важным..."
                                ></textarea>
                                <div class="corner-accents"></div>
                            </div>

                            <div class="ai-helper-box mt-4">
                                <details class="ai-dropdown">
                                    <summary class="ai-summary">
                                        <div class="ai-btn-content">
                                            <div class="ai-icon-sparkle">✨</div>
                                            <span>Разработать идею с помощью ИИ</span>
                                        </div>
                                    </summary>
                                    <div class="ai-panel-body mt-3">
                                        <AIBookStarter 
                                            :genre="form.service_type" 
                                            @next="handleAIAfter" 
                                        />
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 3: CALC -->
                <section id="section-calc" class="form-section">
                    <div class="section-card glass-panel">
                        <div class="section-header">
                            <span class="section-number">03</span>
                            <div>
                                <h2 class="section-title">Объем и бюджет</h2>
                                <p class="section-desc">Предварительный расчет стоимости реализации</p>
                            </div>
                        </div>
                        
                        <div class="calc-wrapper mt-4">
                            <PriceCalculator @apply="applyCalc" />
                            
                            <div v-if="form.calc_price" class="summary-card-mini slide-in mt-4">
                                <div class="s-left">
                                    <span class="s-label">Ориентировочно:</span>
                                    <span class="s-value">{{ form.calc_price }}</span>
                                </div>
                                <div class="s-right">
                                    <div class="s-check">✓ Зафиксировано</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 4: FILES -->
                <section id="section-files" class="form-section">
                    <div class="section-card glass-panel">
                        <div class="section-header">
                            <span class="section-number">04</span>
                            <div>
                                <h2 class="section-title">Прикрепите материалы</h2>
                                <p class="section-desc">Любые файлы, которые помогут нам в работе</p>
                            </div>
                        </div>

                        <div class="upload-container-modern mt-4">
                            <label class="premium-drop-zone">
                                <input type="file" multiple @change="handleFiles" class="hidden-input">
                                <div class="dz-content">
                                    <div class="dz-icon">📎</div>
                                    <div class="dz-text">Перетащите файлы сюда</div>
                                    <div class="dz-hint">Макс. 10МБ на файл (PDF, DOCX, JPG, MP3)</div>
                                </div>
                            </label>

                            <div v-if="selectedFiles.length > 0" class="modern-file-list mt-4">
                                <transition-group name="list">
                                    <div v-for="(file, index) in selectedFiles" :key="file.name + index" class="file-card-lux">
                                        <div class="f-icon-box">📄</div>
                                        <div class="f-details">
                                            <span class="f-name">{{ file.name }}</span>
                                            <span class="f-meta">{{ formatSize(file.size) }}</span>
                                        </div>
                                        <button type="button" @click="removeFile(index)" class="f-delete-btn">✕</button>
                                    </div>
                                </transition-group>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECTION 5: CONTACTS -->
                <section id="section-contacts" class="form-section">
                    <div class="section-card glass-panel highlight-border">
                        <div class="section-header">
                            <span class="section-number">05</span>
                            <div>
                                <h2 class="section-title">Оставьте контакты</h2>
                                <p class="section-desc">Последний шаг к началу вашего проекта</p>
                            </div>
                        </div>

                        <div class="contacts-form-grid mt-4">
                            <div class="modern-input-group">
                                <input type="text" v-model="form.client_name" class="lux-input" placeholder=" " required id="c_name">
                                <label for="c_name">Как вас зовут?</label>
                                <div class="input-line"></div>
                            </div>
                            <div class="modern-input-group">
                                <input type="text" v-model="form.contacts" class="lux-input" placeholder=" " required id="c_phone">
                                <label for="c_phone">Телефон или Telegram</label>
                                <div class="input-line"></div>
                            </div>
                        </div>

                        <div class="submit-action-area mt-5">
                            <button type="submit" class="premium-submit-btn" :disabled="userStore.loading">
                                <div class="btn-shine"></div>
                                <span v-if="userStore.loading">ОТПРАВЛЯЕМ...</span>
                                <span v-else>ОТПРАВИТЬ ЗАЯВКУ 🚀</span>
                            </button>
                            <p class="privacy-note mt-3">Ваши данные в безопасности и не передаются третьим лицам</p>
                        </div>
                    </div>
                </section>
            </form>
        </div>
        <div v-else-if="currentStep === 'payment'" class="payment-screen-lux py-1 md:py-5">
            <div class="success-content text-center glass-panel p-4 md:p-8 max-w-lg mx-auto overflow-hidden shadow-2xl relative">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 pointer-events-none"></div>
                <h1 class="text-3xl md:text-4xl font-black text-white mb-3">Оплата</h1>
                <p class="text-sm md:text-base text-indigo-200 opacity-90 mb-6">
                    Для начала работы необходимо внести предоплату 50% от расчетной стоимости.
                </p>
                
                <div class="payment-amount-box mb-6 p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 shadow-[0_0_20px_rgba(99,102,241,0.15)] transform transition hover:scale-[1.02]">
                    <span class="block text-indigo-300 text-sm font-medium mb-1 uppercase tracking-wider">Сумма к оплате:</span>
                    <span class="block text-3xl md:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400">{{ prepaymentAmount }}</span>
                </div>

                <div class="payment-methods mb-6">
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <label class="flex-1 p-4 rounded-2xl border cursor-pointer transition-all duration-300 flex items-center justify-center gap-3 sm:flex-col sm:gap-2"
                               :class="payment_method === 'card' ? 'bg-indigo-500/20 border-indigo-500 shadow-[0_0_15px_rgba(99,102,241,0.3)]' : 'bg-white/5 border-white/10 hover:bg-white/10'">
                            <input type="radio" v-model="payment_method" value="card" class="hidden">
                            <span class="text-3xl">💳</span>
                            <span class="text-white font-semibold">По карте</span>
                        </label>
                        <label class="flex-1 p-4 rounded-2xl border cursor-pointer transition-all duration-300 flex items-center justify-center gap-3 sm:flex-col sm:gap-2"
                               :class="payment_method === 'phone' ? 'bg-indigo-500/20 border-indigo-500 shadow-[0_0_15px_rgba(99,102,241,0.3)]' : 'bg-white/5 border-white/10 hover:bg-white/10'">
                            <input type="radio" v-model="payment_method" value="phone" class="hidden">
                            <span class="text-3xl">📱</span>
                            <span class="text-white font-semibold">По номеру</span>
                        </label>
                    </div>
                </div>

                <div class="payment-details mb-6 text-left">
                    <transition name="fade" mode="out-in">
                        <div v-if="payment_method === 'card'" key="card" class="p-4 md:p-5 rounded-2xl bg-white/5 border border-white/10 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-indigo-500"></div>
                            <span class="block text-slate-400 text-xs uppercase tracking-wider mb-2">Номер карты (Сбербанк):</span>
                            <strong class="block text-white text-xl md:text-2xl tracking-[2px] md:tracking-[4px] font-mono break-words">{{ paymentSettings.card_number }}</strong>
                        </div>
                        <div v-else key="phone" class="p-4 md:p-5 rounded-2xl bg-white/5 border border-white/10 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-indigo-500"></div>
                            <span class="block text-slate-400 text-xs uppercase tracking-wider mb-2">Номер телефона (СБП):</span>
                            <strong class="block text-white text-xl md:text-2xl tracking-[1px] md:tracking-[2px] font-mono break-words mb-2">{{ paymentSettings.phone_number }}</strong>
                            <div class="inline-block px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-sm border border-indigo-500/30">
                                Владелец: {{ paymentSettings.recipient_name }}
                            </div>
                        </div>
                    </transition>
                </div>

                <div class="payment-screenshot mb-8">
                    <label class="block p-6 rounded-2xl border-2 border-dashed cursor-pointer transition-all group"
                           :class="payment_screenshot ? 'bg-indigo-500/10 border-indigo-500/50' : 'bg-white/5 border-white/20 hover:border-indigo-400 hover:bg-white/10'">
                        <input type="file" accept="image/*" @change="handlePaymentScreenshot" class="hidden">
                        <div v-if="!payment_screenshot" class="pointer-events-none flex flex-col items-center">
                            <div class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                <span class="text-2xl">📸</span>
                            </div>
                            <span class="text-white font-medium mb-1">Загрузить скриншот чека</span>
                            <span class="text-rose-400 text-xs uppercase tracking-widest font-bold">Обязательное подтверждение</span>
                        </div>
                        <div v-else class="text-center relative">
                            <div class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center shadow-lg transform rotate-12">
                                <span class="text-white text-sm">✓</span>
                            </div>
                            <img :src="payment_screenshot_preview" alt="Скриншот" class="max-h-[150px] mx-auto rounded-xl object-contain shadow-lg border border-white/10">
                            <div class="mt-3 text-sm text-indigo-300 truncate max-w-[200px] mx-auto opacity-70">{{ payment_screenshot.name }}</div>
                            <div class="mt-2 text-xs text-white/50 hover:text-white transition-colors underline decoration-dotted underline-offset-4">Выбрать другой чек</div>
                        </div>
                    </label>
                </div>

                <div class="submit-action-area">
                    <button @click="submitPayment" class="w-full relative px-6 py-4 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-black text-lg shadow-[0_10px_30px_rgba(99,102,241,0.4)] hover:shadow-[0_10px_40px_rgba(99,102,241,0.6)] hover:-translate-y-1 transition-all overflow-hidden" :disabled="userStore.loading">
                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:animate-shine"></div>
                        <span v-if="userStore.loading" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            ОТПРАВЛЯЕМ...
                        </span>
                        <span v-else class="tracking-wide">ПОДТВЕРДИТЬ ОПЛАТУ ✅</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Success Screen -->
        <div v-else-if="currentStep === 'success'" class="success-screen-lux py-1 md:py-5">
            <div class="success-content text-center glass-panel p-5 md:p-10 max-w-lg mx-auto overflow-hidden relative shadow-2xl">
                <!-- Celebration Particles -->
                <div class="confetti-container absolute inset-0 pointer-events-none">
                    <div v-for="i in 20" :key="i" class="confetti-piece" :style="`--d: ${i*18}deg; --delay: ${i*0.1}s; --color: ${i % 2 === 0 ? '#60a5fa' : '#a855f7'}`"></div>
                </div>

                <div class="success-animation-lux mb-6">
                    <div class="checkmark-wrapper w-24 h-24 mx-auto bg-green-500/10 rounded-full flex items-center justify-center border border-green-500/20 shadow-[0_0_30px_rgba(34,197,94,0.2)]">
                        <span class="text-5xl">🎉</span>
                    </div>
                </div>
                
                <h1 class="text-3xl md:text-4xl font-black text-white mb-4">Заявка принята!</h1>
                <p class="text-indigo-200 mb-6 text-sm md:text-base leading-relaxed">
                    Ваша история уже в надежных руках.<br> 
                    <span v-if="payment_id" class="text-white/70 text-sm mt-3 inline-block">Ожидайте подтверждения оплаты администратором.</span>
                    <span v-else>Скоро мы свяжемся с вами!</span>
                </p>
                
                <!-- Status polling UI element -->
                <div v-if="payment_id" class="mb-8 p-4 rounded-2xl border transition-all duration-500"
                     :class="payment_status === 'approved' ? 'bg-emerald-500/10 border-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.2)]' : 
                             payment_status === 'rejected' ? 'bg-rose-500/10 border-rose-500/30' : 
                             'bg-indigo-500/10 border-indigo-500/30'">
                     
                    <div v-if="payment_status === 'pending'" class="flex items-center gap-3 justify-center text-indigo-300 text-sm md:text-base">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <strong>Статус:</strong> Оплата проверяется...
                    </div>
                    <div v-else-if="payment_status === 'approved'" class="text-emerald-400 font-bold text-lg flex items-center justify-center gap-2">
                        <span class="text-2xl">✅</span> Успешно оплачено!
                    </div>
                    <div v-else-if="payment_status === 'rejected'" class="text-rose-400 font-bold text-lg flex items-center justify-center gap-2">
                        <span class="text-2xl">❌</span> Оплата отклонена
                        <p class="text-xs text-white/50 block w-full mt-2 font-normal">Пожалуйста, свяжитесь с поддержкой.</p>
                    </div>
                </div>

                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 mb-6 text-left space-y-3">
                    <div class="flex justify-between items-center text-sm md:text-base border-b border-white/10 pb-3">
                        <span class="text-slate-400">Жанр:</span>
                        <span class="text-indigo-300 font-bold text-right">{{ form.service_type }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm md:text-base border-b border-white/10 pb-3" v-if="form.calc_price">
                        <span class="text-slate-400">Сумма чека:</span>
                        <span class="text-white font-bold bg-white/10 px-2 py-1 rounded">{{ prepaymentAmount }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm md:text-base pt-1">
                        <span class="text-slate-400">ID Заявки:</span>
                        <span class="text-slate-300 font-mono tracking-wider">#{{ form.lead_id || 'PRO_BOOK' }}</span>
                    </div>
                </div>

                <button @click="resetToStart" class="w-full px-5 py-3 rounded-xl bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white transition-all border border-white/10 text-sm md:text-base font-medium">
                    Сделать новую заявку 📝
                </button>
            </div>
        </div>
    </transition>
  </div>
</template>

<style scoped>
.lead-form-wrapper {
    max-width: 860px;
    margin: 0 auto;
    padding: 20px 15px 100px;
    font-family: system-ui, -apple-system, sans-serif;
    color: #fff;
    position: relative;
    z-index: 1;
}

/* Background Atmosphere */
.background-glows {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -1;
    overflow: hidden;
    pointer-events: none;
}

.glow {
    position: absolute;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    filter: blur(120px);
    opacity: 0.15;
}

.glow-1 { top: -200px; left: -100px; background: #60a5fa; }
.glow-2 { bottom: -100px; right: -100px; background: #a855f7; }

/* Header */
.biobook-title {
    font-size: 5rem;
    font-weight: 800;
    letter-spacing: -0.05em;
    margin-bottom: 0;
    background: linear-gradient(135deg, #fff 30%, #60a5fa 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));
}

.subtitle {
    font-size: 1.25rem;
    color: #94a3b8;
    font-weight: 300;
    margin-top: 5px;
}

.header-divider {
    width: 80px;
    height: 6px;
    background: linear-gradient(90deg, #60a5fa, #a855f7);
    margin: 30px auto;
    border-radius: 10px;
}

/* Sticky Nav Lux */
.sticky-nav {
    position: sticky;
    top: 20px;
    z-index: 1000;
    margin-bottom: 60px;
    display: flex;
    justify-content: center;
}

.scroll-indicators {
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(25px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 40px;
    padding: 6px;
    display: flex;
    gap: 5px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}

.scroll-indicators button {
    background: transparent;
    border: none;
    padding: 10px 20px;
    border-radius: 35px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.nav-dot {
    width: 6px;
    height: 6px;
    background: currentColor;
    border-radius: 50%;
    transition: all 0.4s;
}

.nav-text {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: none;
}

.scroll-indicators button.active {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.scroll-indicators button.active .nav-text {
    display: block;
}

.scroll-indicators button.active .nav-dot {
    background: #60a5fa;
    transform: scale(1.5);
    box-shadow: 0 0 10px #60a5fa;
}

.scroll-indicators button.filled:not(.active) .nav-dot {
    background: #10b981;
    opacity: 1;
}

/* Glass Panel Styling */
.glass-panel {
    background: rgba(30, 41, 59, 0.4);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 35px;
    padding: 40px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    margin-bottom: 40px;
    transition: transform 0.3s ease, border-color 0.3s ease;
}

.glass-panel:hover {
    border-color: rgba(96, 165, 250, 0.2);
}

.section-header {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 30px;
}

.section-number {
    font-size: 2rem;
    font-weight: 800;
    color: rgba(96, 165, 250, 0.3);
    line-height: 1;
    font-family: serif;
}

.section-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
    color: #fff;
}

.section-desc {
    font-size: 1rem;
    color: #94a3b8;
    margin-top: 5px;
}

/* Genre Cards Premium */
.genres-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
}

.genre-card-modern {
    position: relative;
    background: rgba(15, 23, 42, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 25px;
    padding: 25px 15px;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.g-inner {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.g-icon {
    font-size: 2.5rem;
    margin-bottom: 12px;
    display: block;
}

.g-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #cbd5e1;
}

.g-glow {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at center, var(--accent-color), transparent 70%);
    opacity: 0;
    transition: opacity 0.4s;
    z-index: 1;
}

.genre-card-modern:hover {
    transform: translateY(-8px);
    border-color: var(--accent-color);
}

.genre-card-modern:hover .g-glow { opacity: 0.1; }

.genre-card-modern.selected {
    border-color: var(--accent-color);
    background: rgba(255,255,255,0.03);
    box-shadow: 0 0 30px rgba(0,0,0,0.3), 0 0 15px var(--accent-color);
}

.genre-card-modern.selected .g-name { color: #fff; }
.genre-card-modern.selected .g-glow { opacity: 0.2; }

/* Premium Input Area */
.textarea-wrapper-glow {
    position: relative;
    border-radius: 25px;
    background: rgba(15, 23, 42, 0.5);
    padding: 2px;
    overflow: hidden;
}

.modern-textarea-premium {
    width: 100%;
    min-height: 200px;
    background: transparent;
    border: none;
    color: #fff;
    padding: 25px;
    font-size: 1.1rem;
    line-height: 1.7;
    resize: none;
    outline: none;
}

.textarea-wrapper-glow::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 25px;
    padding: 1px;
    background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent, rgba(96, 165, 250, 0.2));
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
}

/* AI Dropdown LUX */
.ai-summary {
    list-style: none;
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
    border: 1px solid rgba(139, 92, 246, 0.3);
    padding: 18px 25px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.ai-summary:hover {
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2));
    transform: scale(1.01);
}

.ai-btn-content {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
    color: #c084fc;
}

.ai-icon-sparkle { font-size: 1.2rem; }

/* Premium Dropzone */
.premium-drop-zone {
    display: block;
    border: 2px dashed rgba(255, 255, 255, 0.1);
    border-radius: 30px;
    padding: 50px 20px;
    text-align: center;
    cursor: pointer;
    background: rgba(255,255,255,0.02);
    transition: all 0.3s;
}

.premium-drop-zone:hover {
    border-color: #60a5fa;
    background: rgba(96, 165, 250, 0.05);
}

.dz-icon { font-size: 3.5rem; margin-bottom: 20px; opacity: 0.6; }
.dz-text { font-size: 1.25rem; font-weight: 600; margin-bottom: 10px; }
.dz-hint { font-size: 0.85rem; color: #64748b; }

/* Lux Input Group */
.modern-input-group {
    position: relative;
    margin-bottom: 30px;
}

.lux-input {
    width: 100%;
    background: transparent;
    border: none;
    border-bottom: 2px solid rgba(255,255,255,0.1);
    padding: 15px 5px;
    color: #fff;
    font-size: 1.2rem;
    outline: none;
    transition: all 0.3s;
}

.modern-input-group label {
    position: absolute;
    top: 15px;
    left: 5px;
    color: #64748b;
    pointer-events: none;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    font-size: 1.1rem;
}

.lux-input:focus ~ label,
.lux-input:not(:placeholder-shown) ~ label {
    top: -15px;
    font-size: 0.85rem;
    color: #60a5fa;
    font-weight: 700;
}

.input-line {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: #60a5fa;
    transition: width 0.4s ease;
}

.lux-input:focus ~ .input-line {
    width: 100%;
}

/* Premium Submit Button */
.premium-submit-btn {
    width: 100%;
    padding: 24px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6, #3b82f6);
    background-size: 200% auto;
    border: none;
    border-radius: 25px;
    color: #fff;
    font-size: 1.4rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    cursor: pointer;
    box-shadow: 0 20px 40px rgba(59, 130, 246, 0.4);
    transition: all 0.5s;
    position: relative;
    overflow: hidden;
}

.premium-submit-btn:hover {
    background-position: right center;
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 25px 50px rgba(59, 130, 246, 0.5);
}

.btn-shine {
    position: absolute;
    top: 0; left: -100%;
    width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    animation: shine 3s infinite;
}

@keyframes shine {
    to { left: 200%; }
}

/* Success Lux */
.success-screen-lux {
    max-width: 700px;
    margin: 60px auto;
}

.lux-title { font-size: 3.5rem; font-weight: 800; }
.lux-p { font-size: 1.2rem; }

.summary-box-lux {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 24px;
    max-width: 480px;
    text-align: left;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.sb-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.2), transparent);
    margin: 15px 0;
}

.sb-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    font-size: 1.1rem;
    gap: 30px;
}

.sb-item:last-child { margin-bottom: 0; }

.sb-label {
    color: #94a3b8;
    font-size: 0.95rem;
    white-space: nowrap;
}

.sb-value {
    font-weight: 500;
    color: #fff;
    text-align: right;
    line-height: 1.3;
}

.sb-value.highlight {
    color: #60a5fa;
    font-size: 1.2rem;
    font-weight: 800;
}

/* Animations and Misc */
.fade-enter-active, .fade-leave-active { transition: opacity 0.5s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.expand-enter-active, .expand-leave-active { transition: all 0.4s ease; max-height: 1000px; }
.expand-enter-from, .expand-leave-to { opacity: 0; max-height: 0; transform: translateY(-20px); }

.slide-in { animation: slideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.list-enter-active, .list-leave-active { transition: all 0.4s; }
.list-enter-from, .list-leave-to { opacity: 0; transform: scale(0.9); }

/* Custom Checkmark LUX */
.checkmark-lux {
    width: 50px; height: 50px;
    display: block;
    stroke-width: 5;
    stroke: #fff;
    stroke-miterlimit: 10;
    filter: drop-shadow(0 0 5px rgba(255,255,255,0.3));
}

.checkmark-wrapper {
    background: linear-gradient(135deg, #60a5fa, #3b82f6);
    width: 100px; height: 100px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto;
    box-shadow: 0 0 30px rgba(96, 165, 250, 0.4), inset 0 0 15px rgba(255,255,255,0.3);
    animation: scale-up 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes stroke {
    100% { stroke-dashoffset: 0; }
}

@keyframes scale-up { 
    0% { transform: scale(0.5); opacity: 0; } 
    100% { transform: scale(1); opacity: 1; } 
}

.checkmark__circle-lux {
    stroke-dasharray: 166; stroke-dashoffset: 166; stroke-width: 3; stroke-miterlimit: 10;
    stroke: rgba(255,255,255,0.4); fill: none; 
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark__check-lux {
    transform-origin: 50% 50%; stroke-dasharray: 48; stroke-dashoffset: 48;
    stroke: #fff; stroke-width: 5;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
}

@media (max-width: 768px) {
    .biobook-title { font-size: 3rem; }
    .subtitle { font-size: 1rem; }
    .glass-panel { padding: 25px 15px; border-radius: 24px; }
    .section-title { font-size: 1.5rem; }
    .section-number { font-size: 1.5rem; }
    
    .sticky-nav { 
        position: fixed;
        bottom: 20px;
        top: auto;
        left: 50%;
        transform: translateX(-50%);
        width: 95%;
        margin-bottom: 0;
        margin-top: 0;
    }
    .scroll-indicators { 
        padding: 4px; 
        width: 100%;
        justify-content: space-around;
        gap: 2px;
        background: rgba(15, 23, 42, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .scroll-indicators button { padding: 8px 12px; }
    .scroll-indicators button .nav-text { font-size: 0.65rem; }
    
    .genre-card-modern { padding: 20px 10px; border-radius: 20px; }
    .g-icon { font-size: 2rem; }
    .genres-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    
    .summary-card-mini { padding: 15px; flex-direction: column; gap: 10px; text-align: center; }
    .s-value { font-size: 1.2rem; }
    .s-label { margin-right: 0; }
    
    .lux-title { font-size: 2.2rem; }
    .summary-box-lux { padding: 20px; }
    .sb-item { font-size: 0.9rem; gap: 10px; }
}

@media (max-width: 480px) {
    .biobook-title { font-size: 2.5rem; }
    .genres-grid { grid-template-columns: repeat(2, 1fr); }
    .premium-submit-btn { font-size: 1.1rem; padding: 20px; }
    .nav-text { display: none !important; }
    .scroll-indicators button.active .nav-dot { transform: scale(2); }
}

/* Entry Animations */
.form-section {
    opacity: 0;
    transform: translateY(30px);
    animation: revealSection 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

#section-genre { animation-delay: 0.1s; }
#section-ai { animation-delay: 0.2s; }
#section-calc { animation-delay: 0.3s; }
#section-files { animation-delay: 0.4s; }
#section-contacts { animation-delay: 0.5s; }

@keyframes revealSection {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hidden-input { display: none; }
.btn-secondary {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    padding: 10px 20px;
    border-radius: 12px;
    color: #60a5fa;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

/* Fix browser default markers */
summary {
    list-style: none;
}
summary::-webkit-details-marker {
    display: none;
}
summary::marker {
    content: none;
}

.summary-card-mini {
    background: rgba(96, 165, 250, 0.1);
    border: 1px solid rgba(96, 165, 250, 0.3);
    padding: 20px 30px;
    border-radius: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.s-label { color: #94a3b8; font-size: 0.9rem; margin-right: 15px; }
.s-value { font-size: 1.4rem; font-weight: 800; color: #fff; }
.s-check { color: #10b981; font-weight: 700; font-size: 0.9rem; }

.file-card-lux {
    display: flex; align-items: center; background: rgba(255,255,255,0.03);
    padding: 15px 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);
    margin-bottom: 10px;
}

.f-icon-box { font-size: 1.5rem; margin-right: 15px; }
.f-details { flex: 1; display: flex; flex-direction: column; }
.f-name { font-weight: 600; color: #e2e8f0; }
.f-meta { font-size: 0.8rem; color: #64748b; }
.f-delete-btn {
    background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none;
    width: 32px; height: 32px; border-radius: 50%; cursor: pointer;
}
</style>
