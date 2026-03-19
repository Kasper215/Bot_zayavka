<template>
  <div class="quiz-container shadow-glow">
    <div v-if="!result" class="quiz-active">
      <div class="quiz-header">
        <div class="step-counter">Вопрос {{ currentStep + 1 }} из {{ quizSteps.length }}</div>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: ((currentStep + 1) / quizSteps.length) * 100 + '%' }"></div>
        </div>
      </div>

      <transition name="quiz-fade" mode="out-in">
        <div :key="currentStep" class="question-box mt-4">
          <h3 class="question-text">{{ quizSteps[currentStep].question }}</h3>
          <div class="quiz-options mt-3">
            <button 
              v-for="(option, idx) in quizSteps[currentStep].options" 
              :key="idx"
              type="button"
              @click="nextStep(option.scores)"
              class="quiz-option-btn"
            >
              {{ option.text }}
            </button>
          </div>
        </div>
      </transition>
    </div>

    <div v-else class="quiz-result slide-in">
      <div class="result-header text-center">
        <span class="result-icon">✨</span>
        <h3 class="result-title">Мы подобрали идеальный жанр!</h3>
        <p class="result-desc">Основываясь на ваших ответах, вам лучше всего подойдет:</p>
      </div>

      <div class="recommended-card mt-3">
        <div class="r-icon">{{ result.icon }}</div>
        <div class="r-content">
          <h4>{{ result.title }}</h4>
          <p class="small text-muted">{{ result.description }}</p>
        </div>
      </div>

      <button type="button" @click="$emit('select', result.title)" class="apply-quiz-btn mt-4">
        Применить этот жанр
      </button>
      <button type="button" @click="resetQuiz" class="text-btn d-block mx-auto mt-2">Пройти заново</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';

const emit = defineEmits(['select']);

const currentStep = ref(0);
const quizScores = reactive({
  memoirs: 0,
  family: 0,
  business: 0,
  expert: 0,
  editing: 0
});

const result = ref(null);

const quizSteps = [
  {
    question: "Какова основная цель книги?",
    options: [
      { text: "🌟 Личная история и самовыражение", scores: { memoirs: 3, bio: 1 } },
      { text: "👨‍👩‍👧‍👦 Сохранение памяти для семьи", scores: { family: 3 } },
      { text: "🏢 История успеха и репутация бренда", scores: { business: 3 } },
      { text: "🎓 Передача экспертного опыта", scores: { expert: 3 } }
    ]
  },
  {
    question: "Кто является главным героем?",
    options: [
      { text: "👤 Я сам(а)", scores: { memoirs: 2, bio: 1 } },
      { text: "👵 Мои предки и родные", scores: { family: 2 } },
      { text: "👥 Наша команда или компания", scores: { business: 2 } },
      { text: "💡 Моя авторская методика", scores: { expert: 2 } }
    ]
  },
  {
    question: "На каком этапе проект?",
    options: [
      { text: "🆕 Начинаем с нуля (только идея)", scores: { memoirs: 1, family: 1, business: 1, expert: 1 } },
      { text: "🔍 Есть готовая рукопись (нужна правка)", scores: { editing: 5 } }
    ]
  }
];

const resultsData = {
  memoirs: { title: "Мемуары / Биография", icon: "📜", description: "Ваша жизнь — это уникальный роман. Мы поможем упаковать её в книгу." },
  family: { title: "История семьи", icon: "👨‍👩‍👧‍👦", description: "Создайте генеалогическую книгу, которую будут хранить поколения." },
  business: { title: "История компании", icon: "🏢", description: "Расскажите историю успеха вашего бренда и его ценностей." },
  expert: { title: "Экспертная книга", icon: "💼", description: "Упакуйте ваши знания в мощный инструмент для продвижения личного бренда." },
  editing: { title: "Редактура текста", icon: "✍️", description: "Доведем ваш готовый текст до совершенства и подготовим к печати." }
};

const nextStep = (scores) => {
  Object.keys(scores).forEach(key => {
    if (quizScores[key] !== undefined) quizScores[key] += scores[key];
  });

  if (currentStep.value < quizSteps.length - 1) {
    currentStep.value++;
  } else {
    calculateResult();
  }
};

const calculateResult = () => {
  let highest = 'memoirs';
  Object.keys(quizScores).forEach(key => {
    if (quizScores[key] > quizScores[highest]) highest = key;
  });
  result.value = resultsData[highest];
};

const resetQuiz = () => {
  currentStep.value = 0;
  result.value = null;
  Object.keys(quizScores).forEach(key => quizScores[key] = 0);
};
</script>

<style scoped>
.quiz-container {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.step-counter {
  font-size: 0.8rem;
  color: #60a5fa;
  margin-bottom: 8px;
  font-weight: 600;
}

.progress-bar {
  height: 4px;
  background: rgba(255,255,255,0.05);
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #60a5fa;
  transition: width 0.4s ease;
}

.question-text {
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 1.2rem;
  color: #fff;
}

.quiz-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.quiz-option-btn {
  padding: 14px 18px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 14px;
  color: #f1f5f9;
  text-align: left;
  cursor: pointer;
  transition: all 0.3s ease;
}

.quiz-option-btn:hover {
  background: rgba(96, 165, 250, 0.1);
  border-color: #60a5fa;
  transform: translateX(5px);
}

.result-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 10px;
}

.recommended-card {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 1.25rem;
  background: rgba(96, 165, 250, 0.1);
  border: 1px solid #60a5fa;
  border-radius: 16px;
}

.r-icon {
  font-size: 2.2rem;
}

.r-content h4 {
  margin: 0 0 4px 0;
  color: #fff;
}

.apply-quiz-btn {
  width: 100%;
  padding: 14px;
  background: #60a5fa;
  border: none;
  border-radius: 14px;
  color: #fff;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 10px 20px rgba(96, 165, 250, 0.3);
}

.result-title {
  color: #fff;
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.result-desc {
  color: #94a3b8;
  margin-bottom: 1.5rem;
}

.r-content p {
  color: #94a3b8;
  margin: 0;
  line-height: 1.4;
}

.text-btn {
  background: none;
  border: none;
  color: #64748b;
  font-size: 0.9rem;
  cursor: pointer;
  transition: color 0.3s ease;
}

.text-btn:hover {
  color: #fff;
}

/* Animations */
.quiz-fade-enter-active, .quiz-fade-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}
.quiz-fade-enter-from { opacity: 0; transform: translateX(20px); }
.quiz-fade-leave-to { opacity: 0; transform: translateX(-20px); }
</style>
