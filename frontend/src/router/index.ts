import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import LoginView from '../views/login.vue'

const routes: RouteRecordRaw[] = [
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guest: true }
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, _from, next) => {
    const token = localStorage.getItem('token')

    if (to.meta.requiresAuth && !token) {
        next('/login')
    } else if (to.meta.guest && token) {
        next('/')
    } else {
        next()
    }
})

export default router