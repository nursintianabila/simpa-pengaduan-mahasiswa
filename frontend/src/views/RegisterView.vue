<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'

const router = useRouter()

const name = ref('')
const nim = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const loading = ref(false)
const error = ref('')
const success = ref('')

const goToLogin = () => {
  router.replace({
    name: 'login',
  })
}

const register = async () => {
  loading.value = true
  error.value = ''
  success.value = ''

  if (
    password.value !==
    passwordConfirmation.value
  ) {
    error.value =
      'Konfirmasi password tidak sama dengan password.'

    loading.value = false
    return
  }

  try {
    const response = await api.post(
      '/register',
      {
        name: name.value,
        nim: nim.value,
        email: email.value,
        password: password.value,
        password_confirmation:
          passwordConfirmation.value,
      }
    )

    success.value =
      response.data?.message ||
      'Pendaftaran berhasil. Silakan login.'

    name.value = ''
    nim.value = ''
    email.value = ''
    password.value = ''
    passwordConfirmation.value = ''

    setTimeout(() => {
      goToLogin()
    }, 1200)
  } catch (err) {
    console.error(
      'Register error:',
      err
    )

    if (err.response?.status === 422) {
      const validationErrors =
        err.response.data.errors

      if (validationErrors) {
        const firstError =
          Object.values(
            validationErrors
          )[0]

        error.value =
          Array.isArray(firstError)
            ? firstError[0]
            : firstError

        return
      }
    }

    error.value =
      err.response?.data?.message ||
      'Pendaftaran gagal. Silakan periksa data Anda.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="register-page">

    <div class="register-card">

      <!-- BRAND -->
      <div class="brand">

        <div class="brand-logo">
          SIMPA
        </div>

        <div>

          <h1>
            Daftar Akun
          </h1>

          <p>
            Sistem Informasi Pengaduan
            & Aspirasi Mahasiswa
          </p>

        </div>

      </div>

      <!-- INFORMASI -->
      <div class="information">
        Silakan lengkapi data mahasiswa
        untuk membuat akun.
      </div>

      <!-- FORM -->
      <form
        @submit.prevent="register"
      >

        <!-- NAMA -->
        <div class="form-group">

          <label>
            Nama Lengkap
          </label>

          <input
            v-model="name"
            type="text"
            placeholder="Masukkan nama lengkap"
            autocomplete="name"
            required
          />

        </div>

        <!-- NIM -->
        <div class="form-group">

          <label>
            NIM
          </label>

          <input
            v-model="nim"
            type="text"
            placeholder="Masukkan NIM"
            required
          />

        </div>

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
            autocomplete="new-password"
            required
          />

        </div>

        <!-- KONFIRMASI PASSWORD -->
        <div class="form-group">

          <label>
            Konfirmasi Password
          </label>

          <input
            v-model="passwordConfirmation"
            type="password"
            placeholder="Ulangi password"
            autocomplete="new-password"
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

        <!-- SUCCESS -->
        <div
          v-if="success"
          class="alert success"
        >
          {{ success }}
        </div>

        <!-- BUTTON REGISTER -->
        <button
          type="submit"
          class="register-button"
          :disabled="loading"
        >
          {{
            loading
              ? 'Mendaftarkan...'
              : 'Daftar'
          }}
        </button>

      </form>

      <!-- LOGIN LINK -->
      <div class="login-link">

        <span>
          Sudah punya akun?
        </span>

        <a
          href="/login"
          class="login-link-button"
        >
          Login di sini
        </a>

      </div>

    </div>

  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.register-page {
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

.register-card {
  width: 100%;
  max-width: 470px;

  padding: 35px;

  background: white;

  border-radius: 16px;

  box-shadow:
    0 10px 30px
    rgba(15, 23, 42, 0.08);

  position: relative;
  z-index: 1;
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

.alert.success {
  background: #dcfce7;
  color: #15803d;
}

/* =========================================================
   REGISTER BUTTON
========================================================= */

.register-button {
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

.register-button:hover {
  background: #1d4ed8;
}

.register-button:disabled {
  opacity: 0.6;

  cursor: not-allowed;
}

/* =========================================================
   LOGIN LINK
========================================================= */

.login-link {
  position: relative;
  z-index: 10;

  margin-top: 22px;

  padding-top: 20px;

  border-top:
    1px solid #e2e8f0;

  text-align: center;

  color: #64748b;

  font-size: 13px;
}

.login-link-button {
  position: relative;
  z-index: 11;

  display: inline-block;

  margin-left: 4px;

  color: #2563eb;

  font-size: 13px;
  font-weight: 600;

  text-decoration: none;

  cursor: pointer;

  pointer-events: auto;
}

.login-link-button:hover {
  color: #1d4ed8;

  text-decoration: underline;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 520px) {
  .register-card {
    padding: 25px 20px;
  }

  .brand {
    align-items: flex-start;
  }
}
</style>