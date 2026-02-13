<script setup>
import Layout from "@/Layouts/Layout.vue";

</script>
<template>
    <Layout>
        <template #default>

            <router-view/>

        </template>
    </Layout>


</template>

<script>



export default {
    data() {
        return {

        }
    },

    computed: {
        tg() {
            return window.Telegram.WebApp;
        },

        tgUser() {
            const urlParams = new URLSearchParams(this.tg.initData);
            return JSON.parse(urlParams.get('user'));
        },
    },
    mounted() {


    },
    methods: {
        open(url) {
            this.tg.openLink(url)
        },
        goTo(name) {
            this.$router.push({name: name})
        },
    }

}
</script>


