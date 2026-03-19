<template>
  <div class="calculator-card">
    <div class="header-container mb-4">
      <h2 class="section-title">Калькулятор стоимости</h2>
      <p class="text-muted small">Предварительный расчет вашего проекта</p>
    </div>

    <!-- Объем страниц -->
    <div class="calc-section">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="label">Объем: <strong>{{ form.pages }} стр.</strong></span>
        <span class="badge-price">+{{ formatPrice(prices.writing) }} ₽</span>
      </div>
      <input type="range" v-model="form.pages" min="20" max="500" step="4" class="modern-range">
    </div>

    <!-- Формат -->
    <div class="calc-section">
      <span class="label d-block mb-3">Формат издания:</span>
      <div class="display-grid-2">
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
      <span class="label d-block mb-3">Тип работы:</span>
      <div class="display-grid-2">
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
      <span class="label d-block mb-3">Вид издания:</span>
      <div class="display-grid-3">
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
    <div class="total-card mt-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span>Ориентировочная стоимость:</span>
      </div>
      <div class="total-price">от {{ formatPrice(totalPrice) }} до {{ formatPrice(totalMaxPrice) }} ₽</div>
      <p class="small text-muted mt-2">Включает обложку (500 ₽) и верстку.</p>
      
      <button type="button" @click="apply" class="apply-btn mt-3">
        Зафиксировать и заказать
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue';

const emit = defineEmits(['apply']);

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

const apply = () => {
  emit('apply', summary.value);
};
</script>

<style scoped>
.calculator-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 1.5rem;
  margin-top: 2rem;
}

.calc-section {
  margin-bottom: 1.5rem;
}

.calculator-card h2 {
  color: #fff;
}

.label {
  font-size: 0.9rem;
  color: #cbd5e1;
}

.text-muted {
  color: #94a3b8;
}

.badge-price {
  font-size: 0.8rem;
  background: rgba(96, 165, 250, 0.15);
  color: #60a5fa;
  padding: 2px 8px;
  border-radius: 6px;
}

.modern-range {
  width: 100%;
  height: 6px;
  background: rgba(255,255,255,0.1);
  border-radius: 5px;
  outline: none;
  -webkit-appearance: none;
  appearance: none;
}

.modern-range::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  background: #60a5fa;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 0 10px rgba(96, 165, 250, 0.5);
}

.display-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.display-grid-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 10px;
}

.mini-card {
  padding: 10px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.mini-card.active {
  background: rgba(96, 165, 250, 0.1);
  border-color: #60a5fa;
  color: #fff;
}

.m-icon {
  display: block;
  font-size: 1.2rem;
  margin-bottom: 4px;
}

.total-card {
  background: rgba(96, 165, 250, 0.08);
  border-radius: 16px;
  padding: 1.5rem;
  border: 1px solid rgba(96, 165, 250, 0.2);
}

.total-price {
  font-size: 1.4rem;
  font-weight: 700;
  color: #60a5fa;
}

.apply-btn {
  width: 100%;
  padding: 12px;
  border: none;
  border-radius: 12px;
  background: #60a5fa;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
}

.apply-btn:hover {
  background: #3b82f6;
  transform: translateY(-2px);
}

@media (max-width: 480px) {
  .display-grid-3 {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .mini-card {
    padding: 8px;
    font-size: 0.8rem;
  }
  
  .total-card {
    padding: 1rem;
  }
  
  .total-price {
    font-size: 1.2rem;
  }
}

@media (max-width: 360px) {
  .display-grid-3, .display-grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>
