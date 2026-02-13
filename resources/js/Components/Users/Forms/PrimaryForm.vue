<template>
    <form v-on:submit.prevent="submitForm">
        <p class="alert alert-primary">
            Необходимо заполнить информацию о себе!
        </p>
        <div class="form-floating mb-2">
            <input type="text"
                   required
                   v-model="form.name" class="form-control" placeholder="Имя">
            <label>Имя</label>
        </div>

        <div class="form-floating mb-2">
            <input type="text"
                   required
                   v-mask="'+7(###) ###-##-##'"
                   v-model="form.phone" class="form-control" placeholder="Телефон">
            <label>Телефон</label>
        </div>


        <div class="form-floating mb-2">
            <input type="email" v-model="form.email" class="form-control" placeholder="Email">
            <label>Email</label>
        </div>

        <div class="form-floating mb-2">
            <input
                required
                type="date" v-model="form.birthday" class="form-control">
            <label>Дата рождения</label>
        </div>


        <!-- AGENT BLOCK -->
        <template v-if="selectedRole === 1" >
            <div class="form-floating mb-2">
                <input type="text" v-model="form.agent.region" class="form-control" placeholder="Регион">
                <label>Регион</label>
            </div>
        </template>



        <!-- SUBMIT -->
        <button
            type="submit"
            class="btn btn-primary w-100 p-3">
            Сохранить
        </button>

    </form>
</template>

<script>
import {useUsersStore} from "@/stores/users";

export default {
    name: "PrimaryForm",
    props:{
        initialData: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            userStore: useUsersStore(),
            selectedRole: useUsersStore().self.role || 0,
            form: {
                name: "",
                email: "",
                password: "",
                phone: "",
                region: "",
            }
        }
    },
    created() {
        if (this.initialData) {
            this.form = {...this.initialData}
            this.isEdit = true
        }
    },
    methods: {
        async submitForm() {
            try {
                await this.userStore
                    .createPrimary(this.form)
                    .then(()=>{
                        this.$emit("callback")
                })
            } catch (e) {
                console.error(e)
            }
        }
    }
}
</script>
