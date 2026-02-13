
<template>
    <div class="container py-4" v-if="self">
        <!-- ЛОГОТИП -->
        <div class="text-center mb-4">
            <img src="/пнглого.png" alt="logo" style="max-width: 160px"/>
        </div>

<!--        &lt;!&ndash; ТАБЫ &ndash;&gt;
        <ul class="nav nav-tabs mb-2">
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'form' }"
                    @click="activeTab = 'form'"
                >
                    Регистрация
                </button>
            </li>
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'upload', disabled: !formCompleted }"
                    @click="formCompleted && (activeTab = 'upload')"
                >
                    Загрузка видео
                </button>
            </li>
        </ul>-->

        <form @submit.prevent="submitForm">
            <!-- ТАБ 1: ФОРМА -->
            <template v-if="activeTab === 'form'">

                <!-- ФОРМА -->

                <div class="form-floating mb-2">
                    <input
                        type="text"
                        class="form-control"
                        id="surname"
                        placeholder="Фамилия"
                        v-model="form.surname"
                        required
                    />
                    <label for="surname">Фамилия</label>
                </div>

                <div class="form-floating mb-2">
                    <input
                        type="text"
                        class="form-control"
                        id="name"
                        placeholder="Имя"
                        v-model="form.name"
                        required
                    />
                    <label for="name">Имя</label>
                </div>

                <div class="form-floating mb-2">
                    <input
                        type="text"
                        class="form-control"
                        id="patronymic"
                        placeholder="Отчество"
                        v-model="form.patronymic"
                    />
                    <label for="patronymic">Отчество</label>
                </div>

                <div class="form-floating mb-2">
                    <input
                        type="date"
                        class="form-control"
                        id="birthday"
                        placeholder="Дата рождения"
                        v-model="form.birthday"
                        required
                    />
                    <label for="birthday">Дата рождения</label>
                </div>

                <div class="form-floating mb-2">
                    <input
                        type="text"
                        class="form-control"
                        id="region"
                        placeholder="Регион"
                        v-model="form.region"
                        required
                    />
                    <label for="region">Регион</label>
                </div>

                <div class="form-floating mb-2">
                    <input
                        type="text"
                        class="form-control"
                        id="city"
                        placeholder="Город"
                        v-model="form.city"
                        required
                    />
                    <label for="city">Город</label>
                </div>

                <!-- ЧЕКБОКС СОГЛАСИЯ -->
                <div class="form-check mb-2">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="agree"
                        v-model="form.agreeWithRules"
                        required
                    />
                    <label class="form-check-label" for="agree">
                        Я даю своё согласие на обработку персональных данных
                    </label>
                </div>

                <!-- ЧЕКБОКС СОГЛАСИЯ -->
                <div class="form-check mb-2">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="agree"
                        v-model="form.agreeUseVideo"
                        required
                    />
                    <label class="form-check-label" for="agree">
                        Я даю своё согласие на использование загруженных мною видео-роликов в целях реализации акции «Моё слово защитнику»
                    </label>
                </div>


                <!-- КНОПКА ПРОДОЛЖИТЬ -->
                <button
                    :disabled="!form.agreeWithRules || !form.agreeUseVideo"
                    class="btn btn-primary w-100 p-3" type="submit">
                    Продолжить
                </button>

            </template>

            <!-- ТАБ 2: ЗАГРУЗКА ВИДЕО -->
            <template v-if="activeTab === 'upload'">
                <div
                    class="upload-area border rounded p-4 text-center mb-2"
                    :class="{ 'upload-area--active': dragActive }"
                    @dragover.prevent="dragActive = true"
                    @dragleave.prevent="dragActive = false"
                    @drop.prevent="handleDrop"
                >
                    <p class="mb-2 fw-bold">Перетащите видео сюда</p>
                    <p class="text-muted">или нажмите, чтобы выбрать файл</p>

                    <input
                        type="file"
                        accept="video/*"
                        class="d-none"
                        ref="fileInput"
                        @change="handleFileSelect"
                    />

                    <button
                        :disabled="userStore.loading"
                        type="button"
                        class="btn btn-outline-secondary mt-2" @click="$refs.fileInput.click()">
                        Выбрать файл
                    </button>

                    <div v-if="videoFile" class="mt-3">
                        <p class="fw-semibold">Вы выбрали:</p>
                        <p class="text-break">{{ videoFile.name }}</p>
                    </div>
                </div>

                <template v-if="userStore.progress > 0">
                    <div
                        class="w-100 mb-2"
                        style=" background: #eee; height: 10px; border-radius: 4px;">
                        <div
                            :style="{ width: userStore.progress + '%', background: '#42b883', height: '10px', borderRadius: '4px' }"
                        ></div>
                    </div>
                    <p class="fst-italic">Уже загружено <span class="fw-bold">{{ userStore.progress }}%</span></p>
                </template>

                <button
                    @click="activeTab='form'"
                    :disabled="userStore.loading"
                    class="btn btn-outline-light w-100 p-3 mb-2 text-secondary" type="button">
                    Редактировать информацию
                </button>

                <button
                    type="submit"
                    class="btn btn-success w-100 p-3"
                    :disabled="!videoFile||userStore.loading"

                >
                    <template v-if="!videoFile">
                        <span>Выберите видео для отправки</span>
                    </template>
                    <template v-if="videoFile">
                        <span v-if="userStore.loading">Видео загружается...</span>
                        <span v-else>Отправить видео</span>
                    </template>

                </button>
            </template>

        </form>
    </div>
</template>

<script>
import {useUsersStore} from "@/stores/users";

export default {
    name: "RegistrationVideoForm",

    data() {
        return {
            userStore: useUsersStore(),
            activeTab: "form",
            formCompleted: false,
            dragActive: false,

            form: {
                surname: "",
                name: "",
                patronymic: "",
                city: "",
                region: "",
                birthday: "",
                agreeWithRules: false,
                agreeUseVideo: false,
            },

            videoFile: null,
        };
    },
    computed: {
        tg() {
            return window.Telegram.WebApp;
        },
        self() {
            return this.userStore.self
        },

    },
    created() {
        this.userStore.fetchSelf().then(() => {
            let userName = (this.self.name||'').split(" ")

            if (userName.length<=1)
                userName = this.self.fio_from_telegram.split(" ")

            this.form.name = userName[0] || ''
            this.form.patronymic = userName[1] || ''
            this.form.surname = userName[2] || ''
            this.form.city = this.self.city || ''
            this.form.region = this.self.region || ''
            this.form.birthday = this.self.birthday || ''
        })
    },
    methods: {
        openRules() {

        },

        submitForm() {
            this.userStore.uploadForm(this.form)
                .then(() => {
                    this.tg.close()
                })
        },

        handleDrop(e) {
            this.dragActive = false;
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith("video/")) {
                this.videoFile = file;
            }
        },

        handleFileSelect(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith("video/")) {
                this.videoFile = file;
            }
        },

        submitVideo() {

        },
    },
};
</script>

<style scoped>
.upload-area {
    background: #fafafa;
    transition: 0.2s;
    cursor: pointer;
}

.upload-area--active {
    background: #e8f0ff;
    border-color: #0d6efd;
}
</style>
