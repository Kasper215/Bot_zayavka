<template>
    <button
        @click="goBack"
        class="btn btn-light text-secondary mb-3" style="position: sticky; top:80px; z-index: 100;">Назад</button>

</template>
<script>
export default {
    methods:{
        goBack(){
            this.$router.push({ name: 'MenuPage' })
        },
    }
}
</script>
