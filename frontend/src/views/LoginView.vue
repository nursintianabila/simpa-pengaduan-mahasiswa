<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'

const router = useRouter()

const email = ref('')
const password = ref('')

const loading = ref(false)
const error = ref('')

const login = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.post(
      '/login',
      {
        email: email.value,
        password: password.value,
      }
    )

    const data = response.data.data

    localStorage.setItem(
      'token',
      data.token
    )

    localStorage.setItem(
      'user',
      JSON.stringify(data.user)
    )

    if (data.user.role === 'admin') {
      router.push('/admin/dashboard')
    } else {
      router.push('/dashboard')
    }
  } catch (err) {
    console.error(
      'Login error:',
      err
    )

    error.value =
      err.response?.data?.message ||
      'Login gagal. Periksa email dan password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">

    <div class="login-card">

      <!-- BRAND -->
      <div class="brand">

        <div class="brand-logo">
          SIMPA
        </div>

        <div>
          <h1>Login</h1>

          <p>
            Sistem Informasi Pengaduan
            & Aspirasi Mahasiswa
          </p>
        </div>

      </div>

      <!-- INFO -->
      <div class="information">
        Masuk menggunakan akun yang telah
        terdaftar pada sistem.
      </div>

      <!-- FORM LOGIN -->
      <form
        @submit.prevent="login"
      >

        <!-- EMAIL -->
        <div class="form-group">
          <label>
            Email
          </label>

          <input
            v-model="email"
            type="email"
            placeholder="Masukkan email"
            autocomplete="email"
            required
          />
        </div>

        <!-- PASSWORD -->
        <div class="form-group">
          <label>
            Password
          </label>

          <input
            v-model="password"
            type="password"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
          />
        </div>

        <!-- ERROR -->
        <div
          v-if="error"
          class="alert error"
        >
          {{ error }}
        </div>

        <!-- BUTTON LOGIN -->
        <button
          type="submit"
          class="login-button"
          :disabled="loading"
        >
          {{
            loading
              ? 'Memproses...'
              : 'Login'
          }}
        </button>

      </form>

      <!-- REGISTER -->
      <div class="register-link">

        <span>
          Belum punya akun?
        </span>

        <button
          type="button"
          @click="router.push('/register')"
        >
          Daftar sekarang
        </button>

      </div>

    </div>

  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.login-page {
  min-height: 100vh;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 30px 20px;

  background: #f1f5f9;

  font-family:
    Arial,
    sans-serif;
}

.login-card {
  width: 100%;
  max-width: 430px;

  padding: 35px;

  background: white;

  border-radius: 16px;

  box-shadow:
    0 10px 30px
    rgba(15, 23, 42, 0.08);
}

/* =========================================================
   BRAND
========================================================= */

.brand {
  display: flex;
  align-items: center;

  gap: 14px;

  margin-bottom: 22px;
}

.brand-logo {
  width: 55px;
  height: 55px;

  display: flex;
  align-items: center;
  justify-content: center;

  flex-shrink: 0;

  background: #2563eb;
  color: white;

  border-radius: 12px;

  font-size: 12px;
  font-weight: 700;
}

.brand h1 {
  margin: 0 0 5px;

  color: #0f172a;

  font-size: 24px;
}

.brand p {
  margin: 0;

  color: #64748b;

  font-size: 12px;
  line-height: 1.5;
}

/* =========================================================
   INFORMATION
========================================================= */

.information {
  margin-bottom: 24px;

  padding: 12px 14px;

  background: #eff6ff;
  color: #1d4ed8;

  border-radius: 8px;

  font-size: 13px;
  line-height: 1.5;
}

/* =========================================================
   FORM
========================================================= */

.form-group {
  margin-bottom: 17px;
}

.form-group label {
  display: block;

  margin-bottom: 7px;

  color: #334155;

  font-size: 13px;
  font-weight: 600;
}

.form-group input {
  width: 100%;

  padding: 12px 13px;

  border:
    1px solid #cbd5e1;

  border-radius: 8px;

  background: white;

  color: #0f172a;

  font-size: 14px;

  transition: 0.2s;
}

.form-group input::placeholder {
  color: #94a3b8;
}

.form-group input:focus {
  outline: none;

  border-color: #2563eb;

  box-shadow:
    0 0 0 3px
    rgba(37, 99, 235, 0.08);
}

/* =========================================================
   ALERT
========================================================= */

.alert {
  margin-bottom: 17px;

  padding: 11px 13px;

  border-radius: 7px;

  font-size: 13px;
  line-height: 1.5;
}

.alert.error {
  background: #fee2e2;
  color: #b91c1c;
}

/* =========================================================
   LOGIN BUTTON
========================================================= */

.login-button {
  width: 100%;

  padding: 12px;

  border: none;
  border-radius: 8px;

  background: #2563eb;
  color: white;

  font-size: 14px;
  font-weight: 600;

  cursor: pointer;

  transition: 0.2s;
}

.login-button:hover {
  background: #1d4ed8;
}

.login-button:disabled {
  opacity: 0.6;

  cursor: not-allowed;
}

/* =========================================================
   REGISTER LINK
========================================================= */

.register-link {
  margin-top: 22px;

  padding-top: 20px;

  border-top:
    1px solid #e2e8f0;

  text-align: center;

  color: #64748b;

  font-size: 13px;
}

.register-link button {
  margin-left: 4px;

  padding: 0;

  border: none;

  background: transparent;
  color: #2563eb;

  font-size: 13px;
  font-weight: 600;

  cursor: pointer;
}

.register-link button:hover {
  color: #1d4ed8;

  text-decoration: underline;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 480px) {
  .login-card {
    padding: 25px 20px;
  }

  .brand {
    align-items: flex-start;
  }
}
</style>