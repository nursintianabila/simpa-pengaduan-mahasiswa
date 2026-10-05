import {
  createRouter,
  createWebHistory,
} from 'vue-router'

import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'

import DashboardView from '../views/DashboardView.vue'
import ComplaintsView from '../views/ComplaintsView.vue'
import CreateComplaintView from '../views/CreateComplaintView.vue'
import ComplaintDetailView from '../views/ComplaintDetailView.vue'
import EditComplaintView from '../views/EditComplaintView.vue'

import AdminDashboardView from '../views/admin/AdminDashboardView.vue'
import AdminComplaintsView from '../views/admin/AdminComplaintsView.vue'
import AdminComplaintDetailView from '../views/admin/AdminComplaintDetailView.vue'

const routes = [
  /*
  |--------------------------------------------------------------------------
  | ROOT
  |--------------------------------------------------------------------------
  */

  {
    path: '/',
    redirect: '/dashboard',
  },

  /*
  |--------------------------------------------------------------------------
  | AUTH
  |--------------------------------------------------------------------------
  */

  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: {
      guestOnly: true,
    },
  },

  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: {
      guestOnly: true,
    },
  },

  /*
  |--------------------------------------------------------------------------
  | MAHASISWA
  |--------------------------------------------------------------------------
  */

  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: {
      requiresAuth: true,
      requiresMahasiswa: true,
    },
  },

  {
    path: '/complaints',
    name: 'complaints',
    component: ComplaintsView,
    meta: {
      requiresAuth: true,
      requiresMahasiswa: true,
    },
  },

  {
    path: '/complaints/create',
    name: 'complaints.create',
    component: CreateComplaintView,
    meta: {
      requiresAuth: true,
      requiresMahasiswa: true,
    },
  },

  {
    path: '/complaints/:id/edit',
    name: 'complaints.edit',
    component: EditComplaintView,
    meta: {
      requiresAuth: true,
      requiresMahasiswa: true,
    },
  },

  {
    path: '/complaints/:id',
    name: 'complaints.show',
    component: ComplaintDetailView,
    meta: {
      requiresAuth: true,
      requiresMahasiswa: true,
    },
  },

  /*
  |--------------------------------------------------------------------------
  | ADMIN
  |--------------------------------------------------------------------------
  */

  {
    path: '/admin/dashboard',
    name: 'admin.dashboard',
    component: AdminDashboardView,
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
    },
  },

  {
    path: '/admin/complaints',
    name: 'admin.complaints',
    component: AdminComplaintsView,
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
    },
  },

  {
    path: '/admin/complaints/:id',
    name: 'admin.complaints.detail',
    component: AdminComplaintDetailView,
    meta: {
      requiresAuth: true,
      requiresAdmin: true,
    },
  },

  /*
  |--------------------------------------------------------------------------
  | FALLBACK
  |--------------------------------------------------------------------------
  */

  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

/*
|--------------------------------------------------------------------------
| ROUTE GUARD
|--------------------------------------------------------------------------
*/

router.beforeEach((to) => {
  const token =
    localStorage.getItem('token')

  const storedUser =
    localStorage.getItem('user')

  let user = null

  if (storedUser) {
    try {
      user = JSON.parse(storedUser)
    } catch {
      localStorage.removeItem('user')
    }
  }

  /*
  |--------------------------------------------------------------------------
  | BELUM LOGIN
  |--------------------------------------------------------------------------
  */

  if (
    to.meta.requiresAuth &&
    !token
  ) {
    return {
      name: 'login',
    }
  }

  /*
  |--------------------------------------------------------------------------
  | ROUTE KHUSUS ADMIN
  |--------------------------------------------------------------------------
  */

  if (
    to.meta.requiresAdmin &&
    user?.role !== 'admin'
  ) {
    return {
      name: 'dashboard',
    }
  }

  /*
  |--------------------------------------------------------------------------
  | ROUTE KHUSUS MAHASISWA
  |--------------------------------------------------------------------------
  */

  if (
    to.meta.requiresMahasiswa &&
    user?.role === 'admin'
  ) {
    return {
      name: 'admin.dashboard',
    }
  }

  /*
  |--------------------------------------------------------------------------
  | USER SUDAH LOGIN
  | Jangan buka login/register lagi
  |--------------------------------------------------------------------------
  */

  if (
    to.meta.guestOnly &&
    token
  ) {
    if (user?.role === 'admin') {
      return {
        name: 'admin.dashboard',
      }
    }

    return {
      name: 'dashboard',
    }
  }

  return true
})

export default router