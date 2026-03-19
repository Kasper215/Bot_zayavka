<script setup>
import { ref, reactive, computed } from 'vue';
import { useUsersStore } from "@/stores/users";
import BookQuiz from "@/Components/Users/Forms/BookQuiz.vue";
import PriceCalculator from "@/Components/Users/Forms/PriceCalculator.vue";
import AIBookStarter from "@/Components/Users/Forms/AIBookStarter.vue";

const userStore = useUsersStore();
const currentStep = ref('quiz'); // 'quiz' -> 'ai' -> 'calc' -> 'contacts' -> 'success'

const form = reactive({
    client_name: '',
    service_type: '',
    volume_stage: '',
    contacts: '',
    extra: '',
    calc_data: '',
    calc_price: '',
});

const selectedFiles = ref([]);

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

const tg = window.Telegram?.WebApp || null;

const applyQuizResult = (genre) => {
    form.service_type = genre;
    currentStep.value = 'ai';
};

const handleAIBack = () => {
    currentStep.value = 'quiz';
};

const handleAIAfter = (text) => {
    if (text) {
        form.extra = `Идея вступления от ИИ: \n"${text}"\n\n` + form.extra;
    }
    currentStep.value = 'calc';
};

const handleCalcBack = () => {
    currentStep.value = 'ai';
};

const applyCalc = (data) => {
    console.log('Got calculation data:', data);
    form.volume_stage = data.calc_data || 'Данные расчета не получены';
    form.calc_price = data.calc_price || '0 ₽';
    form.calc_data = data.calc_data || 'Данные расчета не получены';
    currentStep.value = 'contacts';
};

const isCompleted = (step) => {
    const order = ['quiz', 'ai', 'calc', 'contacts', 'success'];
    return order.indexOf(order.find(s => s === currentStep.value)) > order.indexOf(step);
};

const submitForm = async () => {
    const formData = { ...form };
    await userStore.uploadAnonymousForm(formData, selectedFiles.value);
    currentStep.value = 'success';
}

const resetToStart = () => {
    if (tg) {
        tg.close();
    } else {
        // Reset form
        Object.keys(form).forEach(key => form[key] = '');
        selectedFiles.value = [];
        currentStep.value = 'quiz';
    }
};

const steps = {
    quiz: 'Жанр',
    ai: 'Идея',
    calc: 'Расчет',
    contacts: 'Контакты'
};
</script>

<template>
  <div class="lead-form-wrapper">
    <div class="form-card glass-panel shadow-2xl">
        <!-- Progress Steps -->
        <div class="steps-indicator mb-6 d-flex justify-content-center gap-3">
            <div 
                v-for="(stepName, key) in steps" 
                :key="key" 
                class="step-dot"
                :class="{'active': currentStep === key, 'completed': isCompleted(key)}"
            ></div>
         </div>

        <form @submit.prevent="submitForm">
            <!-- Step 1: Квиз по жанрам -->
            <transition name="slide">
                <div v-if="currentStep === 'quiz'" class="form-section transition-item">
                    <h1 class="biobook-title text-center mb-2">BioBook</h1>
                    <h2 class="section-title text-center mb-4">Выберите жанр вашей будущей книги</h2>
                    <BookQuiz @select="applyQuizResult" />
                </div>
            </transition>

            <!-- Step 2: ИИ Генератор Вступления -->
            <transition name="slide">
                <div v-if="currentStep === 'ai'" class="form-section transition-item">
                    <h1 class="biobook-title text-center mb-2">BioBook ✨</h1>
                    <h2 class="section-title text-center">Давайте оживим вашу идею</h2>
                    <AIBookStarter 
                        :genre="form.service_type" 
                        @back="handleAIBack" 
                        @next="handleAIAfter" 
                    />
                </div>
            </transition>

            <!-- Step 3: Калькулятор -->
            <transition name="slide">
                <div v-if="currentStep === 'calc'" class="form-section transition-item">
                    <h1 class="biobook-title text-center mb-2">BioBook💰</h1>
                    <h2 class="section-title text-center">Оценка стоимости и объема</h2>
                    <PriceCalculator 
                        @back="handleCalcBack" 
                        @apply="applyCalc"
                    />
                </div>
            </transition>

            <!-- Step 4: Контакты -->
            <transition name="slide">
              <div v-if="currentStep === 'contacts'" class="form-section transition-item" id="section-contacts">
                 <h1 class="biobook-title text-center mb-2">BioBook</h1>
                 <h2 class="section-title justify-content-center">Почти готово! Оставьте контакты</h2>
                 <div class="input-container mt-4">
                    <input type="text" v-model="form.client_name" class="modern-input" placeholder="Как к вам обращаться?" required id="input-name">
                    <input type="text" v-model="form.contacts" class="modern-input" placeholder="Номер телефона или @telegram" required id="input-phone">
                    <textarea v-model="form.extra" class="modern-input modern-textarea" placeholder="Коротко о проекте или дополнительные требования (по желанию)"></textarea>
                    
                    <!-- Загрузка файлов -->
                    <div class="file-upload-section mt-2">
                       <label class="file-upload-label">
                           <input type="file" multiple @change="handleFiles" class="hidden-input" id="file-input">
                           <div class="upload-btn-glass" style="cursor: pointer;">
                               <span class="u-icon">📎</span>
                               <span class="u-text">Прикрепить материалы (черновики, фото, записи)</span>
                           </div>
                       </label>
                       
                       <div v-if="selectedFiles.length > 0" class="selected-files-list mt-3">
                           <div v-for="(file, index) in selectedFiles" :key="index" class="file-item">
                               <span class="f-icon">📄</span>
                               <span class="f-name text-truncate" :title="file.name">{{ file.name }}</span>
                               <span class="f-size">({{ formatSize(file.size) }})</span>
                               <button type="button" @click="removeFile(index)" class="f-remove">✕</button>
                           </div>
                       </div>
                    </div>
                 </div>

                 <div v-if="form.service_type" class="form-summary mt-4 p-3 rounded-20 bg-white-05 border-white-08 text-white">
                    <div class="small text-muted mb-2">Вы выбрали:</div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                       <span class="fw-bold text-white">{{ form.service_type }}</span>
                       <span class="text-muted">•</span>
                       <span class="small text-white opacity-90">{{ form.volume_stage }}</span>
                    </div>
                    <div v-if="form.calc_price" class="text-blue fw-bold">{{ form.calc_price }}</div>
                 </div>

                 <div class="nav-btns mt-4 d-flex flex-column flex-sm-row justify-content-between gap-3">
                   <button type="button" @click="currentStep = 'calc'" class="nav-btn-outline w-100 w-sm-auto">← Назад к расчету</button>
                   <button type="submit" class="submit-btn w-100 w-sm-auto px-5" :disabled="userStore.loading">
                      <span v-if="userStore.loading">Отправляем...</span>
                      <span v-else>Отправить заявку 🚀</span>
                   </button>
                 </div>
              </div>
            </transition>

            <!-- Success Screen -->
            <transition name="zoom">
              <div v-if="currentStep === 'success'" class="success-screen py-5 text-center">
                <div class="success-animation mb-4">
                  <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                  </svg>
                </div>
                <h2 class="biobook-title mb-2">Заявка принята!</h2>
                <p class="text-slate-300 mb-5">Наш менеджер свяжется с вами в ближайшее время для обсуждения деталей вашего проекта.</p>
                <button type="button" @click="resetToStart" class="submit-btn">
                   Вернуться в меню 🏠
                </button>
              </div>
            </transition>
        </form>
    </div>
  </div>
</template>

<style scoped>
.lead-form-wrapper {
    padding: 10px;
    max-width: 600px;
    margin: 0 auto;
}

.form-card {
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 32px;
    padding: 2.5rem 1.5rem;
}

.biobook-title {
    font-size: 3.5rem;
    font-weight: 900;
    letter-spacing: -0.05em;
    background: linear-gradient(135deg, #fff 0%, #60a5fa 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
}

.section-title {
    font-size: 1.1rem;
    color: #cbd5e1;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.text-muted {
    color: #94a3b8;
}

.step-dot {
    width: 10px;
    height: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.step-dot.active {
    width: 30px;
    background: #60a5fa;
    box-shadow: 0 0 15px rgba(96, 165, 250, 0.5);
}

.step-dot.completed {
    background: #10b981;
}

.modern-input {
    width: 100%;
    padding: 1rem 1.25rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    color: white;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.modern-input:focus {
    background: rgba(255, 255, 255, 0.05);
    border-color: #60a5fa;
    outline: none;
    box-shadow: 0 0 20px rgba(96, 165, 250, 0.15);
}

.modern-textarea {
    min-height: 120px;
    resize: none;
}

.submit-btn {
    width: 100%;
    padding: 1.1rem;
    background: #60a5fa;
    border: none;
    border-radius: 18px;
    color: white;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 25px rgba(96, 165, 250, 0.4);
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-3px);
    background: #3b82f6;
    box-shadow: 0 15px 30px rgba(96, 165, 250, 0.5);
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    box-shadow: none;
}

.nav-btn-outline {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #94a3b8;
    padding: 1rem 1.5rem;
    border-radius: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.nav-btn-outline:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: #60a5fa;
    color: white;
}

.bg-white-05 { background: rgba(255, 255, 255, 0.02); }
.border-white-08 { border: 1px solid rgba(255, 255, 255, 0.05); }
.rounded-20 { border-radius: 20px; }
.text-blue { color: #60a5fa; }

/* Transitions */
.slide-enter-active, .slide-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-enter-from { opacity: 0; transform: translateX(30px); }
.slide-leave-to { opacity: 0; transform: translateX(-30px); }

.zoom-enter-active {
    animation: zoomIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

@media (max-width: 480px) {
    .form-card {
        padding: 2rem 1.25rem;
        border-radius: 24px;
    }
    .biobook-title {
        font-size: 2.8rem;
    }
}

.file-upload-section {
    position: relative;
    width: 100%;
}

.hidden-input {
    display: none;
}

.upload-btn-glass {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 1rem 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px dashed rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: #cbd5e1;
}

.upload-btn-glass:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: #60a5fa;
    color: #fff;
}

.selected-files-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.file-item {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    font-size: 0.9rem;
    animation: slideIn 0.3s ease-out;
}

.f-icon { margin-right: 10px; }
.f-name { flex: 1; color: #f1f5f9; }
.f-size { color: #64748b; font-size: 0.8rem; margin: 0 10px; }
.f-remove {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 6px;
}
.f-remove:hover {
    background: rgba(239, 68, 68, 0.1);
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Success Animation */
.checkmark__circle {
  stroke-dasharray: 166;
  stroke-dashoffset: 166;
  stroke-width: 2;
  stroke-miterlimit: 10;
  stroke: #10b981;
  fill: none;
  animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: block;
  stroke-width: 2;
  stroke: #fff;
  stroke-miterlimit: 10;
  margin: 10% auto;
  box-shadow: inset 0px 0px 0px #10b981;
  animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
}

.checkmark__check {
  transform-origin: 50% 50%;
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
}

@keyframes stroke {
  100% { stroke-dashoffset: 0; }
}
@keyframes scale {
  0%, 100% { transform: none; }
  50% { transform: scale3d(1.1, 1.1, 1); }
}
@keyframes fill {
  100% { box-shadow: inset 0px 0px 0px 40px #10b981; }
}
</style>
