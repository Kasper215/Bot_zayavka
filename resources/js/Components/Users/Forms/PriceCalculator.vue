<template>
  <div class="calculator-card animate-slide-up">
    
    <!-- Попап уведомление -->
    <Transition name="toast">
      <div v-if="showToast" class="fixed-toast">
        <div class="toast-content">
          <span class="icon">✨</span>
          <div class="text-group">
            <div class="toast-title">Цена зафиксирована!</div>
            <div class="toast-sub">Данные добавлены в форму</div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Объем страниц -->
    <div class="calc-section">
      <div class="header-flex">
        <span class="label">Объем: <strong>{{ form.pages }} стр.</strong></span>
        <Transition name="fade" mode="out-in">
           <span :key="prices.writing" class="badge-price">+{{ formatPrice(prices.writing) }} ₽</span>
        </Transition>
      </div>
      <div class="range-container">
        <input type="range" v-model="form.pages" min="20" max="500" step="4" class="modern-range">
      </div>
    </div>

    <!-- Формат -->
    <div class="calc-section">
      <span class="label mb-3">Формат издания:</span>
      <div class="grid-2">
        <div class="mini-card" :class="{active: form.format === 'A5'}" @click="form.format = 'A5'">
          <span class="m-icon">📄</span> A5
        </div>
        <div class="mini-card" :class="{active: form.format === 'A4'}" @click="form.format = 'A4'">
          <span class="m-icon">📋</span> A4
        </div>
      </div>
    </div>

    <!-- Тип работы -->
    <div class="calc-section">
      <span class="label mb-3">Тип работы:</span>
      <div class="grid-2">
        <div class="mini-card" :class="{active: form.work_type === 'writing'}" @click="form.work_type = 'writing'">
          <span class="m-icon">✍️</span> С нуля
        </div>
        <div class="mini-card" :class="{active: form.work_type === 'editing'}" @click="form.work_type = 'editing'">
          <span class="m-icon">🔍</span> Редактура
        </div>
      </div>
    </div>

    <!-- Тип издания -->
    <div class="calc-section">
      <span class="label mb-3">Вид издания:</span>
      <div class="grid-3 sm-grid-2">
        <div class="mini-card" :class="{active: form.print === 'pdf'}" @click="form.print = 'pdf'">
          <span class="m-icon">💻</span> PDF
        </div>
        <div class="mini-card" :class="{active: form.print === 'bw'}" @click="form.print = 'bw'">
          <span class="m-icon">⬛</span> Ч/Б
        </div>
        <div class="mini-card" :class="{active: form.print === 'color'}" @click="form.print = 'color'">
          <span class="m-icon">🎨</span> Цвет
        </div>
      </div>
    </div>

    <!-- Итого -->
    <div class="total-card mt-5">
      <div class="price-header">Ориентировочная стоимость:</div>
      <div class="price-row">
        <Transition name="fade" mode="out-in">
           <div :key="totalPrice" class="total-price"> от {{ formatPrice(totalPrice) }} ₽ </div>
        </Transition>
        <span class="price-dash">—</span>
        <Transition name="fade" mode="out-in">
           <div :key="totalMaxPrice" class="total-price"> {{ formatPrice(totalMaxPrice) }} ₽ </div>
        </Transition>
      </div>
      <p class="small-info mt-2">Включает обложку (500 ₽) и верстку.</p>
      
      <button type="button" @click="apply" class="apply-btn" :class="{ 'btn-success': showToast }">
        <span v-if="!showToast">Зафиксировать цену</span>
        <span v-else>Готово ✅</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, watch, onMounted, ref } from 'vue';

const emit = defineEmits(['apply']);
const showToast = ref(false);

const form = reactive({
  pages: 100,
  format: 'A5',
  work_type: 'writing',
  print: 'pdf'
});

const prices = computed(() => {
  let basePerPage = 150;
  if (form.format === 'A4') basePerPage = 225;
  if (form.work_type === 'editing') basePerPage = (form.format === 'A4' ? 70 : 45);

  const writing = form.pages * basePerPage;
  
  let print = 0;
  let binding = 0;
  if (form.print === 'bw') {
    print = (form.pages / 20) * 800;
    binding = 700;
  } else if (form.print === 'color') {
    print = (form.pages / 20) * 1200;
    binding = 700;
  }

  return { writing, print, binding, cover: 500 };
});

const totalPrice = computed(() => {
  return Object.values(prices.value).reduce((a, b) => a + b, 0);
});

const totalMaxPrice = computed(() => Math.round(totalPrice.value * 1.15));

const formatPrice = (val) => new Intl.NumberFormat('ru-RU').format(Math.round(val));

const summary = computed(() => {
  const printLabels = { pdf: 'Только PDF', bw: 'Ч/Б печать', color: 'Цветная печать' };
  const workLabels = { writing: 'Написание с нуля', editing: 'Редактура' };
  return {
    calc_data: `Расчет: ${form.pages} стр, ${form.format}, ${workLabels[form.work_type]}, ${printLabels[form.print]}`,
    calc_price: `от ${formatPrice(totalPrice.value)} до ${formatPrice(totalMaxPrice.value)} ₽`
  };
});

watch(summary, (newVal) => {
  emit('apply', newVal);
}, { deep: true });

onMounted(() => {
  emit('apply', summary.value);
});

const apply = () => {
  emit('apply', summary.value);
  showToast.value = true;
  setTimeout(() => {
    showToast.value = false;
  }, 2500);
};
</script>

<style scoped>
.calculator-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 28px;
  padding: 1.5rem;
  margin-top: 1.5rem;
  position: relative;
  backdrop-filter: blur(10px);
}

.calc-section {
  margin-bottom: 1.25rem;
}

.header-flex {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.label {
  font-size: 0.85rem;
  color: #94a3b8;
  display: block;
  font-weight: 500;
  letter-spacing: 0.02em;
}

.label strong {
  color: #fff;
  font-weight: 700;
}

.badge-price {
  font-size: 0.75rem;
  background: rgba(96, 165, 250, 0.1);
  color: #60a5fa;
  padding: 4px 10px;
  border-radius: 10px;
  font-weight: 700;
}

.range-container {
  padding: 10px 0;
}

.modern-range {
  width: 100%;
  height: 4px;
  background: rgba(255,255,255,0.08);
  border-radius: 10px;
  outline: none;
  -webkit-appearance: none;
}

.modern-range::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 22px;
  height: 22px;
  background: #fff;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 0 15px rgba(96, 165, 250, 0.4);
  border: 4px solid #60a5fa;
  transition: transform 0.2s;
}

.modern-range::-webkit-slider-thumb:active {
  transform: scale(1.2);
}

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }

.mini-card {
  padding: 12px 8px;
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.04);
  border-radius: 16px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  color: #64748b;
  font-weight: 600;
  font-size: 0.8rem;
}

.mini-card:hover { border-color: rgba(96, 165, 250, 0.3); background: rgba(255,255,255,0.04); }

.mini-card.active {
  background: rgba(96, 165, 250, 0.12);
  border-color: #60a5fa;
  color: #fff;
  box-shadow: 0 8px 16px -6px rgba(96, 165, 250, 0.3);
  transform: translateY(-2px);
}

.m-icon { display: block; font-size: 1.3rem; margin-bottom: 5px; filter: grayscale(0.2); }

.total-card {
  background: linear-gradient(135deg, rgba(96, 165, 250, 0.1), rgba(139, 92, 246, 0.1));
  border-radius: 22px;
  padding: 1.25rem;
  border: 1px solid rgba(96, 165, 250, 0.15);
  text-align: center;
}

.price-header { font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }

.price-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.total-price { font-size: 1.5rem; font-weight: 800; color: #60a5fa; text-shadow: 0 0 20px rgba(96, 165, 250, 0.2); }
.price-dash { color: #475569; font-weight: 300; font-size: 1.2rem; }

.small-info { font-size: 0.7rem; color: #64748b; margin-top: 4px; }

.apply-btn {
  width: 100%;
  padding: 14px;
  margin-top: 1.15rem;
  border: none;
  border-radius: 16px;
  background: #60a5fa;
  color: #fff;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 12px rgba(96, 165, 250, 0.25);
}

.apply-btn:hover { background: #3b82f6; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(96, 165, 250, 0.35); }
.apply-btn:active { transform: translateY(0); }
.btn-success { background: #10b981 !important; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); }

/* Toast Styles */
.fixed-toast {
  position: absolute;
  top: -20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 50;
  width: 90%;
  pointer-events: none;
}

.toast-content {
  background: #1e293b;
  border: 1px solid #10b981;
  padding: 12px 20px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 10px 25px -5px rgba(0,0,0,0.4), 0 0 20px rgba(16, 185, 129, 0.2);
}

.toast-title { color: #fff; font-size: 0.85rem; font-weight: 700; }
.toast-sub { color: #94a3b8; font-size: 0.7rem; }

/* Transitions */
.toast-enter-active, .toast-leave-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.toast-enter-from { opacity: 0; transform: translate(-50%, -10px) scale(0.9); }
.toast-leave-to { opacity: 0; transform: translate(-50%, -20px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 640px) {
  .calculator-card { padding: 1.25rem; }
  .total-price { font-size: 1.3rem; }
  .sm-grid-2 { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 380px) {
  .grid-2 { grid-template-columns: 1fr; }
  .mini-card { padding: 8px; font-size: 0.75rem; }
  .m-icon { font-size: 1.1rem; }
}
</style>
