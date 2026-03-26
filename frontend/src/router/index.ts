import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from "vue-router";
import RegisterView from "../views/register.vue";
import LoginView from "../views/login.vue";
import RegisterSuccessView from "../views/registerSuccess.vue";
import DashboardView from "../views/dashboard.vue";
import BoardView from "../views/board.vue";
import EpicDetailView from "../views/epicDetail.vue";
import ItemDetailView from "../views/taskDetail.vue";
import WorkflowSettings from "@/views/WorkflowSettings.vue";

const routes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: LoginView,
    meta: { guest: true },
  },
  {
    path: "/register",
    name: "register",
    component: RegisterView,
    meta: { guest: true },
  },
  {
    path: "/register/success",
    name: "register-success",
    component: RegisterSuccessView,
    meta: { guest: true },
  },
  {
    path: "/",
    name: "dashboard",
    component: DashboardView,
    meta: { requiresAuth: true },
    children: [
      {
        path: "/board/:boardId",
        name: "board",
        component: BoardView,
        meta: { requiresAuth: true },
      },
      {
        path: "/board/:boardId/epic/:epicId",
        name: "epic-detail",
        component: EpicDetailView,
        meta: { requiresAuth: true },
      },
      {
        path: "/board/:boardId/item/:itemId",
        name: "item-detail",
        component: ItemDetailView,
        meta: { requiresAuth: true },
      },
      {
        path: "/workflow-settings",
        name: "workflow",
        component: WorkflowSettings,
        meta: { requiresAuth: true },
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem("token");
  if (to.meta.requiresAuth && !token) {
    next("/login");
  } else if (to.meta.guest && token) {
    next("/");
  } else {
    next();
  }
});

export default router;
