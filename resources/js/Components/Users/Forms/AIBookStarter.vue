<template>
  <div class="ai-generator-card shadow-glow">
    <div v-if="!generatedText" class="generator-input">
      <div class="ai-header text-center mb-5">
        <span class="ai-badge mb-3">AI Assistant</span>
        <h3 class="mt-2 text-white fw-semibold">Давайте напишем начало вашей книги вместе! ✨</h3>
        <p class="text-blue-light small opacity-90">Опишите кратко вашу идею, и наш ИИ предложит захватывающее вступление.</p>
      </div>

      <div class="input-group-modern mb-5">
        <label class="label small mb-3 d-block text-center text-slate-200">О чем ваша книга? (коротко)</label>
        <div class="textarea-wrapper">
          <textarea 
            v-model="prompt" 
            class="modern-textarea ai-textarea" 
            placeholder="Опишите вашу идею..."
            maxlength="300"
          ></textarea>
          <div class="char-count">{{ prompt.length }}/300</div>
        </div>
      </div>

      <div class="style-selector mb-4">
        <label class="label small mb-3 d-block text-slate-200">Выберите стиль повествования:</label>
        <div class="style-grid">
          <button 
            v-for="style in styles" 
            :key="style.id"
            type="button"
            class="style-btn"
            :class="{active: selectedStyle === style.id}"
            @click="selectedStyle = style.id"
          >
            <span class="s-icon">{{ style.icon }}</span>
            <span class="s-name">{{ style.name }}</span>
          </button>
        </div>
      </div>

      <button 
        type="button" 
        @click="generateIntro" 
        class="ai-generate-btn" 
        :disabled="!prompt || loading"
      >
        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
        {{ loading ? 'Магия ИИ в процессе...' : 'Сгенерировать вступление ✨' }}
      </button>
    </div>

    <!-- Результат генерации -->
    <div v-else class="ai-result-view slide-in">
      <div class="ai-header mb-3">
        <span class="ai-badge success">Готово!</span>
        <h4 class="mt-2">Ваше уникальное вступление:</h4>
      </div>
      
      <div class="result-text-box">
        <p class="generated-text">{{ displayedText }}<span v-if="isTyping" class="typing-cursor">|</span></p>
      </div>

      <div class="action-btns mt-4">
        <button type="button" @click="$emit('next', generatedText)" class="ai-apply-btn">
          Применить и продолжить 🚀
        </button>
        <button type="button" @click="resetGenerator" class="text-btn d-block mx-auto mt-3">
          Попробовать другой вариант
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['back', 'next']);

const prompt = ref('');
const selectedStyle = ref('dramatic');
const loading = ref(false);
const generatedText = ref('');
const displayedText = ref('');
const isTyping = ref(false);

const styles = [
  { id: 'dramatic', name: 'Драматичный', icon: '🎭' },
  { id: 'lyrical', name: 'Лирический', icon: '🍃' },
  { id: 'business', name: 'Деловой', icon: '💼' },
  { id: 'mystery', name: 'Загадочный', icon: '🔮' }
];

const typeText = (text) => {
  displayedText.value = '';
  isTyping.value = true;
  let i = 0;
  const interval = setInterval(() => {
    if (i < text.length) {
      displayedText.value += text.charAt(i);
      i++;
    } else {
      clearInterval(interval);
      isTyping.value = false;
    }
  }, 30); // Скорость печати
};

const generateIntro = async () => {
  if (!prompt.value) return;
  loading.value = true;
  generatedText.value = '';
  displayedText.value = '';
  
  try {
    const { data } = await axios.post('/api/public/ai/generate-intro', {
        prompt: prompt.value,
        style: selectedStyle.value
    });

    if (data.status === 'ok') {
        generatedText.value = data.text;
        typeText(data.text);
    }
  } catch (error) {
    console.error('AI Generation error:', error);
    alert('Не удалось сгенерировать текст. Попробуйте еще раз.');
  } finally {
    loading.value = false;
  }
};

const resetGenerator = () => {
  generatedText.value = '';
  displayedText.value = '';
  prompt.value = '';
};
</script>

<style scoped>
.ai-generator-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
}

.ai-badge {
  background: linear-gradient(135deg, #60a5fa 0%, #c084fc 100%);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #fff;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.ai-header h3, .ai-header h4 {
  color: #fff;
}

.ai-header p {
  color: #94a3b8;
}

.ai-badge.success {
  background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
}

.ai-textarea {
  width: 100%;
  min-height: 120px;
  background: rgba(15, 23, 42, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 18px;
  padding: 1.2rem;
  color: #fff;
  font-family: inherit;
  font-size: 1rem;
  resize: none;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.ai-textarea:focus {
  outline: none;
  border-color: #60a5fa;
  background: rgba(15, 23, 42, 0.6);
  box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.15);
}

.textarea-wrapper {
  position: relative;
}

.typing-cursor {
  display: inline-block;
  font-weight: 100;
  animation: blink 1s step-end infinite;
}

@keyframes blink {
  from, to { color: transparent }
  50% { color: #60a5fa }
}

.char-count {
  text-align: right;
  font-size: 0.7rem;
  color: #94a3b8;
  margin-top: 5px;
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

.style-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.style-btn {
  padding: 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px;
  color: #e2e8f0;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  gap: 12px;
  font-family: inherit;
}

.style-btn:hover {
  background: rgba(255,255,255,0.08);
  border-color: rgba(255,255,255,0.2);
  transform: translateY(-2px);
}

.style-btn.active {
  background: rgba(96, 165, 250, 0.1);
  border-color: #60a5fa;
  box-shadow: 0 0 15px rgba(96, 165, 250, 0.2);
}

.s-icon { font-size: 1.25rem; }
.s-name { font-size: 0.9rem; font-weight: 500; }

.ai-generate-btn {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
  border: none;
  border-radius: 16px;
  color: #fff;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
  transition: all 0.3s ease;
}

.ai-generate-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 30px rgba(99, 102, 241, 0.4);
}

.ai-generate-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.text-blue-light {
    color: #93c5fd;
}

.text-slate-200 {
    color: #e2e8f0;
}

.fw-semibold {
    font-weight: 600;
}

.result-text-box {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(96, 165, 250, 0.4);
  border-radius: 20px;
  padding: 1.75rem;
  position: relative;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.generated-text {
  font-size: 1.15rem;
  line-height: 1.8;
  color: #f8fafc;
  font-style: italic;
  font-weight: 300;
}

.ai-apply-btn {
  width: 100%;
  padding: 14px;
  background: #60a5fa;
  border: none;
  border-radius: 14px;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}

/* Mobile Adaptive */
@media (max-width: 480px) {
  .ai-generator-card {
    padding: 1rem;
  }
  
  .style-grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .ai-header h3 {
    font-size: 1.25rem;
  }
  
  .generated-text {
    font-size: 1rem;
    line-height: 1.6;
  }
  
  .result-text-box {
    padding: 1.25rem;
  }
  
  .ai-apply-btn, .ai-generate-btn {
    padding: 14px;
    font-size: 0.95rem;
  }
}
</style>
