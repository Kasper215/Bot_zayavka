<script setup>

import {Head} from '@inertiajs/vue3'
import GlobalAlert from "@/Components/GlobalAlert.vue";
import GlobalConfirmModal from "@/Components/GlobalConfirmModal.vue";
import UserProfileCard from "@/Components/Users/UserProfileCard.vue";

import PrimaryForm from "@/Components/Users/Forms/PrimaryForm.vue";
</script>
<template>

    <Head>
        <title>Новые люди: видео</title>
        <meta name="description" content="система зарузки видео"/>
    </Head>


    <GlobalAlert></GlobalAlert>
    <GlobalConfirmModal></GlobalConfirmModal>
    <slot/>


    <footer class="text-body-secondary" style="padding:0px 0px 90px 0px;">

        <div class="container d-flex justify-content-center flex-column align-items-center">
            <p class="d-flex justify-content-center my-3">
                <a href="javascript:void(0)" @click="scrollTop"><i class="fa-solid fa-arrow-up mr-2"></i>Вернуться
                    наверх</a>
            </p>
        </div>
    </footer>


</template>
<script>


export default {
    data() {
        return {

            currentTheme: '',
            themes: []
        }
    },
    watch: {},

    computed: {
        tg() {
            return window.Telegram.WebApp;
        },


    },

    mounted() {
        this.tg.expand()

        this.tg.BackButton.hide()
    },
    methods: {

        goTo(name) {
            this.$router.push({name: name})
        },

        scrollTop() {
            window.scrollTo(0, 80);
        },
        openLink(url) {
            this.tg.openLink(url, {
                try_instant_view: true
            })
        },


    },


}
</script>

<style>
.fixed-top-menu {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #ffffff;
}
</style>
