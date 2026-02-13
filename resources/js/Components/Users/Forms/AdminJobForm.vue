<template>
    <form @submit.prevent="submitForm" class="">
        <!-- ФИО -->
        <div class="form-floating mb-2">
            <input
                type="text"
                class="form-control"
                id="fioInput"
                v-model="form.fio"
                placeholder="ФИО"
                required
            />
            <label for="fioInput">ФИО</label>
        </div>

        <!-- Email -->
        <div class="form-floating mb-2">
            <input
                type="email"
                class="form-control"
                id="emailInput"
                v-model="form.email"
                placeholder="Email"
                required
            />
            <label for="emailInput">Email</label>
        </div>

        <!-- Телефон -->
        <div class="form-floating mb-2">
            <input
                type="tel"
                class="form-control"
                id="phoneInput"
                v-model="form.phone"
                placeholder="Телефон"
                v-mask="'+7(###) ###-##-##'"
                required
            />
            <label for="phoneInput">Телефон</label>
        </div>

        <!-- Возраст -->
        <div class="form-floating mb-2">
            <input
                type="number"
                class="form-control"
                id="ageInput"
                v-model="form.age"
                placeholder="Возраст"
                min="18"
                required
            />
            <label for="ageInput">Возраст</label>
        </div>

        <!-- Зарплатные ожидания -->
        <div class="form-floating mb-2">
            <input
                type="number"
                class="form-control"
                id="salaryInput"
                v-model="form.salary"
                placeholder="Зарплатные ожидания"
                min="0"
                required
            />
            <label for="salaryInput">Зарплатные ожидания (₽\мес)</label>
        </div>

        <!-- Опыт работы -->
        <div class="form-floating mb-2">
      <textarea
          class="form-control"
          id="experienceInput"
          v-model="form.experience"
          placeholder="Опыт работы"
          style="height: 100px"
      ></textarea>
            <label for="experienceInput">Опыт работы</label>
        </div>

        <!-- Причина выбора позиции -->
        <div class="form-floating mb-2">
      <textarea
          class="form-control"
          id="reasonInput"
          v-model="form.reason"
          placeholder="Почему хотите стать администратором"
          style="height: 100px"
      ></textarea>
            <label for="reasonInput">Почему хотите стать администратором</label>
        </div>

        <!-- Согласие на обработку данных -->
        <div class="form-check mb-2">
            <input
                class="form-check-input"
                type="checkbox"
                id="consentCheck"
                v-model="form.consent"
                required
            />
            <label class="form-check-label" for="consentCheck">
                Я даю согласие на обработку персональных данных согласно
                <a href="https://www.consultant.ru/document/cons_doc_LAW_61801/" target="_blank">
                    Федеральному закону №152-ФЗ
                </a>
            </label>
        </div>

        <!-- Кнопка отправки -->
        <button type="submit"
                :disabled="!form.consent"
                class="btn btn-primary w-100 p-3">Отправить заявку
        </button>
    </form>
</template>

<script>
import {useJobStore} from "@/stores/useJobStore";

export default {
    name: 'AdminJobForm',
    data() {
        return {
            jobStore: useJobStore(),
            form: {
                fio: '',
                email: '',
                phone: '',
                age: null,
                salary: null,
                experience: '',
                reason: '',
                consent: false
            }
        }
    },
    methods: {
        submitForm() {
            if (!this.form.consent) {
                alert('Необходимо дать согласие на обработку данных.')
                return
            }
            this.jobStore.submitAdminForm(this.form)
            this.$emit('submit-application', this.form)

            const form = {
                fio: '',
                email: '',
                phone: '',
                age: null,
                salary: null,
                experience: '',
                reason: '',
                consent: false
            }

            this.form = form

        }
    }
}
</script>
