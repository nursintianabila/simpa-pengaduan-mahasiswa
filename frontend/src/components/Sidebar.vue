<script setup>
import { useRoute, useRouter } from 'vue-router'
import api from '../api/axios'

const route = useRoute()
const router = useRouter()

const user = JSON.parse(
  localStorage.getItem('user') || '{}'
)

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (error) {
    console.error(error)
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')

    router.push('/login')
  }
}
</script>

<template>
  <aside class="sidebar">
    <div>
      <div class="brand">
        <div class="brand-icon">PA</div>

        <div>
          <h2>SIMPA</h2>
          <span>Pengaduan Mahasiswa</span>
        </div>
      </div>

      <nav>
        <RouterLink
          to="/dashboard"
          class="menu"
          :class="{ active: route.path === '/dashboard' }"
        >
          Dashboard
        </RouterLink>

        <RouterLink
          to="/complaints"
          class="menu"
          :class="{ active: route.path.startsWith('/complaints') }"
        >
          Pengaduan & Aspirasi
        </RouterLink>
      </nav>
    </div>

    <div class="bottom">
      <div class="user">
        <strong>{{ user.name || 'Mahasiswa' }}</strong>

        <span>
          {{ user.nim || user.role }}
        </span>
      </div>

      <button @click="logout">
        Logout
      </button>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  width: 250px;
  height: 100vh;
  box-sizing: border-box;

  padding: 25px 18px;

  background: #0f172a;
  color: white;

  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 40px;
}

.brand-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: #2563eb;

  display: flex;
  align-items: center;
  justify-content: center;

  font-weight: bold;
}

.brand h2 {
  margin: 0;
  font-size: 20px;
}

.brand span {
  font-size: 11px;
  color: #94a3b8;
}

nav {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.menu {
  color: #cbd5e1;
  text-decoration: none;

  padding: 12px 14px;
  border-radius: 8px;
}

.menu:hover {
  background: #1e293b;
  color: white;
}

.menu.active {
  background: #2563eb;
  color: white;
}

.bottom {
  border-top: 1px solid #334155;
  padding-top: 20px;
}

.user {
  display: flex;
  flex-direction: column;
  margin-bottom: 15px;
}

.user span {
  margin-top: 4px;
  color: #94a3b8;
  font-size: 12px;
}

button {
  width: 100%;
  border: none;
  padding: 10px;

  background: #dc2626;
  color: white;

  border-radius: 7px;
  cursor: pointer;
}
</style>