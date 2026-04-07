<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    examples: {
        type: Array,
        default: () => []
    }
});

// Fallback to default if no examples provided from server
const books = computed(() => {
    if (props.examples && props.examples.length > 0) {
        return props.examples.map(ex => ({
            title: ex.title,
            description: ex.description,
            image: ex.cover_path,
            pdf_file: ex.pdf_path,
            tag: ex.tag
        }));
    }
    
    // Default fallback
    return [
        {
            title: 'Лес, где живут чудеса',
            description: 'История, которая оживает на страницах вашей личной книги.',
            image: '/images/les s chydesami.png',
            pdf_file: '/Лес, где живут чудеса.pdf',
            tag: 'Семейная история'
        }
    ];
});

const openBook = (pdf) => {
    if (pdf) {
        window.open(pdf, '_blank');
    }
};

const activeIndex = ref(0);
</script>

<template>
    <div class="book-gallery-container mb-12">
        <div class="gallery-header mb-6">
            <h3 class="gallery-title">Наши работы</h3>
            <p class="gallery-subtitle">Посмотрите, как может выглядеть ваша будущая книга</p>
        </div>

        <div class="gallery-scroll">
            <div class="gallery-track">
                <div 
                    v-for="(book, index) in books" 
                    :key="index"
                    class="book-card-lux"
                >
                    <div class="book-cover-wrapper">
                        <img :src="book.image" :alt="book.title" class="book-img" />
                        <div class="book-tag">{{ book.tag }}</div>
                        <div class="book-overlay">
                            <button class="view-btn" @click="openBook(book.pdf_file)">Показать книгу</button>
                        </div>
                    </div>
                    <div class="book-info">
                        <h4 class="book-title">{{ book.title }}</h4>
                        <p class="book-desc">{{ book.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.book-gallery-container {
    padding: 0 10px;
    animation: fadeIn 0.8s ease-out;
}

.gallery-header {
    text-align: center;
    border: none;
    padding: 0;
    margin-top: 20px;
}

.gallery-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #fff;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.gallery-subtitle {
    color: #94a3b8;
    font-size: 0.9rem;
    margin-top: 4px;
}

.gallery-scroll {
    overflow-x: auto;
    padding: 20px 0;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none;  /* IE and Edge */
}

.gallery-scroll::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

.gallery-track {
    display: flex;
    justify-content: center;
    gap: 25px;
    padding-bottom: 10px;
}

.book-card-lux {
    flex: 0 0 300px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.book-card-lux:hover {
    transform: translateY(-10px);
    border-color: rgba(96, 165, 250, 0.3);
    background: rgba(255, 255, 255, 0.05);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.book-cover-wrapper {
    position: relative;
    height: 440px;
    background: rgba(255, 255, 255, 0.05); /* Совсем легкая подложка */
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.book-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: transform 0.6s ease;
}

.book-card-lux:hover .book-img {
    transform: scale(1.05);
}

.book-tag {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(8px);
    color: #60a5fa;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    border: 1px solid rgba(96, 165, 250, 0.3);
}

.book-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(15, 23, 42, 0.9), transparent);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.book-card-lux:hover .book-overlay {
    opacity: 1;
}

.view-btn {
    background: #fff;
    color: #0f172a;
    border: none;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transform: translateY(20px);
    transition: all 0.4s ease;
}

.book-card-lux:hover .view-btn {
    transform: translateY(0);
}

.book-info {
    padding: 20px;
}

.book-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}

.book-desc {
    font-size: 0.85rem;
    color: #94a3b8;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 480px) {
    .book-card-lux {
        flex: 0 0 240px;
    }
    .book-cover-wrapper {
        height: 380px;
    }
}
</style>
