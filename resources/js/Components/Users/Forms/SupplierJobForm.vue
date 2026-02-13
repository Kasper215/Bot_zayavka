<template>
    <form @submit.prevent="submitForm">
        <!-- Название компании -->
        <div class="form-floating mb-2">
            <input
                type="text"
                class="form-control"
                id="companyNameInput"
                v-model="form.company_name"
                placeholder="Название компании"
                required
            />
            <label for="companyNameInput">Название компании</label>
        </div>

        <!-- ИНН / регистрационный номер -->
        <div class="form-floating mb-2">
            <input
                type="text"
                class="form-control"
                id="regNumberInput"
                v-model="form.reg_number"
                placeholder="ИНН / регистрационный номер"
                required
            />
            <label for="regNumberInput">ИНН / регистрационный номер</label>
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
                required
                v-mask="'+7(###) ###-##-##'"
            />
            <label for="phoneInput">Телефон</label>
        </div>

        <!-- Категории товаров -->

        <div class="form-floating">
            <input
                type="text"
                class="form-control"
                id="categoriesInput"
                v-model="form.categories"
                placeholder="Категории товаров"
            />
            <label for="categoriesInput">Категории товаров </label>
        </div>
        <p class="small fst-italic  mb-2">(например: продукты, техника)</p>

        <!-- Описание товаров -->
        <div class="form-floating mb-2">
      <textarea
          class="form-control"
          id="productsInput"
          v-model="form.products"
          placeholder="Описание товаров"
          style="height: 100px"
      ></textarea>
            <label for="productsInput">Описание товаров</label>
        </div>

        <!-- Объём поставок -->
        <div class="form-floating mb-2">
            <input
                type="number"
                class="form-control"
                id="volumeInput"
                v-model="form.volume"
                placeholder="Объём поставок"
                min="0"
            />
            <label for="volumeInput">Средний объём поставок (шт./мес.)</label>
        </div>

        <!-- Условия сотрудничества -->
        <div class="form-floating mb-2">
      <textarea
          class="form-control"
          id="termsInput"
          v-model="form.terms"
          placeholder="Условия сотрудничества"
          style="height: 100px"
      ></textarea>
            <label for="termsInput">Условия сотрудничества (сроки, оплата)</label>
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
        <button
            :disabled="!form.consent"
            type="submit" class="btn btn-primary w-100 p-3">Стать поставщиком</button>
    </form>
</template>

<script>
import {useJobStore} from "@/stores/useJobStore";

export default {
    name: 'SupplierJobForm',
    data() {
        return {
            jobStore:useJobStore(),
            form: {
                company_name: '',
                reg_number: '',
                email: '',
                phone: '',
                categories: '',
                products: '',
                volume: null,
                terms: '',
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
            this.jobStore.submitSupplierForm(this.form)
            this.$emit('submit-application', this.form)
        }
    }
}
</script>
