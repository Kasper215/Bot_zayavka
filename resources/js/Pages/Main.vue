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
        return {}
    },
    methods: {
        open(url) {
            window.open(url, '_blank');
        },
        goTo(name) {
            this.$router.push({name: name})
        },
    }
}
</script>
