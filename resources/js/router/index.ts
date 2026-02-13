import {createRouter, createWebHashHistory} from 'vue-router'

import { defineAsyncComponent } from 'vue'

const MenuPage = defineAsyncComponent(() => import('../Pages/MenuPage.vue'))

const HelpPage = defineAsyncComponent(() => import('../Pages/HelpPage.vue'))


const routes = [
    {
        path: '/',
        name: 'MenuPage',
        component: MenuPage,
    },
    {
        path: '/help',
        name: 'HelpPage',
        component: HelpPage,
    }
]


const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

export default router
