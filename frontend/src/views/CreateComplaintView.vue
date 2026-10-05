<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import Sidebar from '../components/Sidebar.vue'

const router = useRouter()

const categories = ref([])

const form = ref({
  type: 'pengaduan',
  category_id: '',
  title: '',
  description: '',
})

const loadingCategories = ref(true)
const submitting = ref(false)

const error = ref('')
const validationErrors = ref({})

const loadCategories = async () => {
  loadingCategories.value = true

  try {
    const response = await api.get('/categories')

    categories.value = response.data.data
  } catch (err) {
    console.error('Gagal mengambil kategori:', err)

    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')

      router.push('/login')
      return
    }

    error.value = 'Kategori gagal dimuat.'
  } finally {
    loadingCategories.value = false
  }
}

const submitComplaint = async () => {
  submitting.value = true
  error.value = ''
  validationErrors.value = {}

  try {
    const payload = {
      type: form.value.type,

      category_id: form.value.category_id
        ? Number(form.value.category_id)
        : null,

      title: form.value.title,
      description: form.value.description,
    }

    await api.post('/complaints', payload)

    router.push('/complaints')
  } catch (err) {
    console.error('Gagal membuat pengaduan:', err)

    if (err.response?.status === 422) {
      validationErrors.value =
        err.response.data.errors || {}

      error.value =
        'Periksa kembali data yang Anda masukkan.'

      return
    }

    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')

      router.push('/login')
      return
    }

    if (err.response?.status === 403) {
      error.value =
        err.response.data.message ||
        'Anda tidak memiliki izin untuk membuat pengaduan.'

      return
    }

    error.value =
      err.response?.data?.message ||
      'Pengaduan gagal dikirim.'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadCategories()
})
</script>

<template>
  <div class="layout">
    <Sidebar />

    <main class="main-content">
      <div class="page">

        <div class="page-header">
          <div>
            <h1>Buat Pengaduan / Aspirasi</h1>

            <p>
              Sampaikan pengaduan atau aspirasi Anda melalui
              formulir berikut.
            </p>
          </div>

          <button
            class="back-button"
            type="button"
            @click="router.push('/complaints')"
          >
            ← Kembali
          </button>
        </div>

        <div
          v-if="error"
          class="error-alert"
        >
          {{ error }}
        </div>

        <div class="form-card">
          <form @submit.prevent="submitComplaint">

            <!-- Jenis -->
            <div class="form-group">
              <label>
                Jenis
                <span class="required">*</span>
              </label>

              <div class="type-options">

                <label
                  class="type-card"
                  :class="{
                    selected:
                      form.type === 'pengaduan'
                  }"
                >
                  <input
                    v-model="form.type"
                    type="radio"
                    value="pengaduan"
                  />

                  <div>
                    <strong>Pengaduan</strong>

                    <span>
                      Laporkan masalah atau kendala yang Anda alami.
                    </span>
                  </div>
                </label>

                <label
                  class="type-card"
                  :class="{
                    selected:
                      form.type === 'aspirasi'
                  }"
                >
                  <input
                    v-model="form.type"
                    type="radio"
                    value="aspirasi"
                  />

                  <div>
                    <strong>Aspirasi</strong>

                    <span>
                      Sampaikan saran atau masukan untuk kampus.
                    </span>
                  </div>
                </label>

              </div>

              <small
                v-if="validationErrors.type"
                class="validation-error"
              >
                {{ validationErrors.type[0] }}
              </small>
            </div>

            <!-- Kategori -->
            <div class="form-group">
              <label for="category">
                Kategori
              </label>

              <select
                id="category"
                v-model="form.category_id"
                :disabled="loadingCategories"
              >
                <option value="">
                  {{
                    loadingCategories
                      ? 'Memuat kategori...'
                      : 'Pilih kategori'
                  }}
                </option>

                <option
                  v-for="category in categories"
                  :key="category.id"
                  :value="category.id"
                >
                  {{ category.name }}
                </option>
              </select>

              <small
                v-if="validationErrors.category_id"
                class="validation-error"
              >
                {{ validationErrors.category_id[0] }}
              </small>
            </div>

            <!-- Judul -->
            <div class="form-group">
              <label for="title">
                Judul
                <span class="required">*</span>
              </label>

              <input
                id="title"
                v-model="form.title"
                type="text"
                placeholder="Contoh: AC Lab Komputer Tidak Berfungsi"
              />

              <small
                v-if="validationErrors.title"
                class="validation-error"
              >
                {{ validationErrors.title[0] }}
              </small>
            </div>

            <!-- Deskripsi -->
            <div class="form-group">
              <label for="description">
                Deskripsi
                <span class="required">*</span>
              </label>

              <textarea
                id="description"
                v-model="form.description"
                rows="8"
                placeholder="Jelaskan pengaduan atau aspirasi Anda secara lengkap..."
              ></textarea>

              <small
                v-if="validationErrors.description"
                class="validation-error"
              >
                {{ validationErrors.description[0] }}
              </small>
            </div>

            <div class="information">
              <strong>Informasi</strong>

              <p>
                Pengaduan atau aspirasi akan dikirim menggunakan
                akun mahasiswa yang sedang login.
              </p>
            </div>

            <div class="form-actions">
              <button
                class="cancel-button"
                type="button"
                @click="router.push('/complaints')"
              >
                Batal
              </button>

              <button
                class="submit-button"
                type="submit"
                :disabled="submitting"
              >
                {{
                  submitting
                    ? 'Mengirim...'
                    : 'Kirim Pengaduan'
                }}
              </button>
            </div>

          </form>
        </div>

      </div>
    </main>
  </div>
</template>

<style scoped>
.layout {
  min-height: 100vh;
  background: #f1f5f9;
}

.main-content {
  margin-left: 250px;
  min-height: 100vh;
}

.page {
  max-width: 1000px;
  padding: 35px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

.page-header h1 {
  margin: 0 0 8px;
  color: #0f172a;
}

.page-header p {
  margin: 0;
  color: #64748b;
}

.back-button {
  border: 1px solid #cbd5e1;
  background: white;
  color: #334155;
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
}

.form-card {
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.form-group {
  margin-bottom: 25px;
}

.form-group > label {
  display: block;
  margin-bottom: 9px;
  color: #334155;
  font-weight: 600;
}

.required {
  color: #dc2626;
}

input[type='text'],
select,
textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 12px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-family: inherit;
  font-size: 14px;
}

input[type='text']:focus,
select:focus,
textarea:focus {
  outline: none;
  border-color: #2563eb;
}

textarea {
  resize: vertical;
}

.type-options {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.type-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 18px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
}

.type-card.selected {
  border-color: #2563eb;
  background: #eff6ff;
}

.type-card strong {
  display: block;
  margin-bottom: 5px;
  color: #1e293b;
}

.type-card span {
  display: block;
  color: #64748b;
  font-size: 13px;
}

.validation-error {
  display: block;
  margin-top: 7px;
  color: #dc2626;
}

.error-alert {
  margin-bottom: 20px;
  padding: 15px;
  background: #fee2e2;
  color: #b91c1c;
  border-radius: 8px;
}

.information {
  padding: 15px;
  margin-bottom: 25px;
  background: #eff6ff;
  border-left: 4px solid #2563eb;
  border-radius: 7px;
}

.information strong {
  color: #1e40af;
}

.information p {
  margin: 5px 0 0;
  color: #475569;
  font-size: 14px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.cancel-button,
.submit-button {
  padding: 11px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.cancel-button {
  border: 1px solid #cbd5e1;
  background: white;
  color: #475569;
}

.submit-button {
  border: none;
  background: #2563eb;
  color: white;
}

.submit-button:hover {
  background: #1d4ed8;
}

.submit-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }

  .page {
    padding: 20px;
  }

  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .type-options {
    grid-template-columns: 1fr;
  }
}
</style>