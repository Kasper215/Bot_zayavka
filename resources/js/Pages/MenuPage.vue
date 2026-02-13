<script setup>

import MenuUser from "@/Components/MenuUser.vue";

</script>

<template>

    <div class="container-fluid p-3">
        <MenuUser></MenuUser>
    </div>

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

    },
    methods: {}

}
</script>
