<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api/axios'
import Sidebar from '../components/Sidebar.vue'

const route = useRoute()
const router = useRouter()

const categories = ref([])

const form = ref({
  type: 'pengaduan',
  category_id: '',
  title: '',
  description: '',
})

const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const validationErrors = ref({})

const loadData = async () => {
  loading.value = true
  error.value = ''

  try {
    const [complaintResponse, categoryResponse] =
      await Promise.all([
        api.get(`/complaints/${route.params.id}`),
        api.get('/categories'),
      ])

    const complaint = complaintResponse.data.data

    // Mahasiswa hanya boleh edit ketika masih menunggu
    if (complaint.status !== 'menunggu') {
      error.value =
        'Pengaduan yang sudah diproses tidak dapat diubah.'

      return
    }

    categories.value = categoryResponse.data.data

    form.value = {
      type: complaint.type,
      category_id: complaint.category_id || '',
      title: complaint.title,
      description: complaint.description,
    }
  } catch (err) {
    console.error('Gagal memuat data edit:', err)

    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')

      router.push('/login')
      return
    }

    if (err.response?.status === 403) {
      error.value =
        err.response.data.message ||
        'Anda tidak memiliki akses ke pengaduan ini.'

      return
    }

    if (err.response?.status === 404) {
      error.value = 'Pengaduan tidak ditemukan.'
      return
    }

    error.value = 'Data pengaduan gagal dimuat.'
  } finally {
    loading.value = false
  }
}

const updateComplaint = async () => {
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

    await api.put(
      `/complaints/${route.params.id}`,
      payload
    )

    router.push(
      `/complaints/${route.params.id}`
    )
  } catch (err) {
    console.error('Gagal mengubah pengaduan:', err)

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
        'Pengaduan ini tidak dapat diubah.'

      return
    }

    if (err.response?.status === 404) {
      error.value = 'Pengaduan tidak ditemukan.'
      return
    }

    error.value =
      err.response?.data?.message ||
      'Pengaduan gagal diperbarui.'
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="layout">
    <Sidebar />

    <main class="main-content">
      <div class="page">

        <div class="page-header">
          <div>
            <h1>Edit Pengaduan / Aspirasi</h1>

            <p>
              Perbarui pengaduan atau aspirasi yang
              sebelumnya telah Anda kirim.
            </p>
          </div>

          <button
            class="back-button"
            type="button"
            @click="
              router.push(
                `/complaints/${route.params.id}`
              )
            "
          >
            ← Kembali
          </button>
        </div>

        <!-- Loading -->
        <div
          v-if="loading"
          class="state-card"
        >
          Memuat data pengaduan...
        </div>

        <!-- Error -->
        <div
          v-else-if="error && !form.title"
          class="error-card"
        >
          <h3>Pengaduan Tidak Dapat Diedit</h3>

          <p>
            {{ error }}
          </p>

          <button
            @click="router.push('/complaints')"
          >
            Kembali ke Pengaduan
          </button>
        </div>

        <!-- Form -->
        <div
          v-else
          class="form-card"
        >
          <div
            v-if="error"
            class="error-alert"
          >
            {{ error }}
          </div>

          <form @submit.prevent="updateComplaint">

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
                      Laporkan masalah atau kendala
                      yang Anda alami.
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
                      Sampaikan saran atau masukan
                      untuk kampus.
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
              >
                <option value="">
                  Pilih kategori
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
                placeholder="Masukkan judul"
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
                placeholder="Jelaskan pengaduan atau aspirasi..."
              ></textarea>

              <small
                v-if="validationErrors.description"
                class="validation-error"
              >
                {{ validationErrors.description[0] }}
              </small>
            </div>

            <div class="information">
              <strong>Perhatian</strong>

              <p>
                Pengaduan hanya dapat diubah selama
                statusnya masih Menunggu.
              </p>
            </div>

            <div class="form-actions">
              <button
                type="button"
                class="cancel-button"
                @click="
                  router.push(
                    `/complaints/${route.params.id}`
                  )
                "
              >
                Batal
              </button>

              <button
                type="submit"
                class="submit-button"
                :disabled="submitting"
              >
                {{
                  submitting
                    ? 'Menyimpan...'
                    : 'Simpan Perubahan'
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

.form-card,
.state-card,
.error-card {
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
  background: #fef3c7;
  border-left: 4px solid #f59e0b;
  border-radius: 7px;
}

.information strong {
  color: #92400e;
}

.information p {
  margin: 5px 0 0;
  color: #78350f;
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

.error-card {
  color: #b91c1c;
}

.error-card h3 {
  margin-top: 0;
}

.error-card button {
  margin-top: 10px;
  padding: 10px 15px;
  border: none;
  background: #2563eb;
  color: white;
  border-radius: 7px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }

  .page {
    padding: 20px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .type-options {
    grid-template-columns: 1fr;
  }
}
</style>