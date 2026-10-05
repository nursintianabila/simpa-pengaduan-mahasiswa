<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import Sidebar from '../components/Sidebar.vue'

const router = useRouter()

const complaints = ref([])
const loading = ref(true)
const error = ref('')

const search = ref('')
const statusFilter = ref('')
const typeFilter = ref('')
const priorityFilter = ref('')

/*
|--------------------------------------------------------------------------
| Load Pengaduan
|--------------------------------------------------------------------------
*/

const loadComplaints = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/complaints')

    complaints.value = response.data.data || []
  } catch (err) {
    console.error(
      'Gagal mengambil data pengaduan:',
      err
    )

    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')

      router.push('/login')
      return
    }

    error.value =
      err.response?.data?.message ||
      'Data pengaduan gagal dimuat.'
  } finally {
    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/

const filteredComplaints = computed(() => {
  return complaints.value.filter((complaint) => {
    const keyword =
      search.value.toLowerCase().trim()

    const title =
      complaint.title?.toLowerCase() || ''

    const category =
      complaint.category?.name?.toLowerCase() || ''

    const matchesSearch =
      !keyword ||
      title.includes(keyword) ||
      category.includes(keyword)

    const matchesStatus =
      !statusFilter.value ||
      complaint.status === statusFilter.value

    const matchesType =
      !typeFilter.value ||
      complaint.type === typeFilter.value

    const matchesPriority =
      !priorityFilter.value ||
      complaint.ai_priority === priorityFilter.value

    return (
      matchesSearch &&
      matchesStatus &&
      matchesType &&
      matchesPriority
    )
  })
})

const resetFilter = () => {
  search.value = ''
  statusFilter.value = ''
  typeFilter.value = ''
  priorityFilter.value = ''
}

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

const formatDate = (date) => {
  if (!date) return '-'

  return new Date(date).toLocaleDateString(
    'id-ID',
    {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    }
  )
}

const statusLabel = (status) => {
  const labels = {
    menunggu: 'Menunggu',
    diproses: 'Diproses',
    selesai: 'Selesai',
    ditolak: 'Ditolak',
  }

  return labels[status] || status
}

const typeLabel = (type) => {
  return type === 'aspirasi'
    ? 'Aspirasi'
    : 'Pengaduan'
}

const priorityLabel = (priority) => {
  const labels = {
    rendah: 'Rendah',
    sedang: 'Sedang',
    tinggi: 'Tinggi',
  }

  return labels[priority] || 'Belum dianalisis'
}

const openDetail = (id) => {
  router.push(`/complaints/${id}`)
}

onMounted(() => {
  loadComplaints()
})
</script>

<template>
  <div class="layout">
    <Sidebar />

    <main class="main-content">
      <div class="page">

        <!-- HEADER -->
        <div class="page-header">
          <div>
            <h1>Pengaduan & Aspirasi</h1>

            <p>
              Kelola pengaduan dan aspirasi yang telah Anda kirim.
            </p>
          </div>

          <button
            class="add-button"
            @click="router.push('/complaints/create')"
          >
            + Buat Pengaduan
          </button>
        </div>

        <!-- FILTER -->
        <div class="filter-card">

          <div class="filter-grid">

            <div class="filter-group search-group">
              <label>Pencarian</label>

              <input
                v-model="search"
                type="text"
                placeholder="Cari judul atau kategori..."
              />
            </div>

            <div class="filter-group">
              <label>Status</label>

              <select v-model="statusFilter">
                <option value="">
                  Semua Status
                </option>

                <option value="menunggu">
                  Menunggu
                </option>

                <option value="diproses">
                  Diproses
                </option>

                <option value="selesai">
                  Selesai
                </option>

                <option value="ditolak">
                  Ditolak
                </option>
              </select>
            </div>

            <div class="filter-group">
              <label>Jenis</label>

              <select v-model="typeFilter">
                <option value="">
                  Semua Jenis
                </option>

                <option value="pengaduan">
                  Pengaduan
                </option>

                <option value="aspirasi">
                  Aspirasi
                </option>
              </select>
            </div>

            <div class="filter-group">
              <label>Prioritas AI</label>

              <select v-model="priorityFilter">
                <option value="">
                  Semua Prioritas
                </option>

                <option value="tinggi">
                  Tinggi
                </option>

                <option value="sedang">
                  Sedang
                </option>

                <option value="rendah">
                  Rendah
                </option>
              </select>
            </div>

            <div class="reset-wrapper">
              <button
                class="reset-button"
                @click="resetFilter"
              >
                Reset
              </button>
            </div>

          </div>

          <div class="filter-result">
            Menampilkan
            <strong>{{ filteredComplaints.length }}</strong>
            dari
            <strong>{{ complaints.length }}</strong>
            data.
          </div>

        </div>

        <!-- LOADING -->
        <div
          v-if="loading"
          class="message"
        >
          Memuat data...
        </div>

        <!-- ERROR -->
        <div
          v-else-if="error"
          class="error-message"
        >
          {{ error }}
        </div>

        <!-- EMPTY -->
        <div
          v-else-if="filteredComplaints.length === 0"
          class="empty"
        >
          <h3>Data tidak ditemukan</h3>

          <p>
            Tidak ada pengaduan atau aspirasi yang sesuai
            dengan pencarian atau filter.
          </p>

          <button
            class="reset-empty-button"
            @click="resetFilter"
          >
            Reset Filter
          </button>
        </div>

        <!-- TABLE -->
        <div
          v-else
          class="table-container"
        >
          <table>

            <thead>
              <tr>
                <th>No</th>
                <th>Judul</th>
                <th>Jenis</th>
                <th>Kategori</th>
                <th>Prioritas AI</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
              </tr>
            </thead>

            <tbody>

              <tr
                v-for="(complaint, index) in filteredComplaints"
                :key="complaint.id"
              >

                <td>
                  {{ index + 1 }}
                </td>

                <td>
                  <div class="title-cell">
                    <strong>
                      {{ complaint.title }}
                    </strong>

                    <span
                      v-if="complaint.ai_summary"
                      class="summary-preview"
                    >
                      {{ complaint.ai_summary }}
                    </span>
                  </div>
                </td>

                <td>
                  <span
                    class="type-badge"
                    :class="complaint.type"
                  >
                    {{ typeLabel(complaint.type) }}
                  </span>
                </td>

                <td>
                  {{ complaint.category?.name || '-' }}
                </td>

                <td>
                  <span
                    v-if="complaint.ai_priority"
                    class="priority-badge"
                    :class="complaint.ai_priority"
                  >
                    <span class="priority-dot"></span>

                    {{
                      priorityLabel(
                        complaint.ai_priority
                      )
                    }}
                  </span>

                  <span
                    v-else
                    class="priority-badge unavailable"
                  >
                    Belum dianalisis
                  </span>
                </td>

                <td>
                  <span
                    class="status"
                    :class="complaint.status"
                  >
                    {{ statusLabel(complaint.status) }}
                  </span>
                </td>

                <td>
                  {{ formatDate(complaint.created_at) }}
                </td>

                <td>
                  <button
                    class="detail-button"
                    @click="openDetail(complaint.id)"
                  >
                    Detail
                  </button>
                </td>

              </tr>

            </tbody>

          </table>
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
  background: #f1f5f9;
}

.page {
  padding: 30px;
}

/* =========================================================
   HEADER
========================================================= */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  gap: 20px;

  margin-bottom: 25px;
}

.page-header h1 {
  margin: 0 0 8px;

  color: #1e293b;
}

.page-header p {
  margin: 0;

  color: #64748b;
}

.add-button {
  padding: 12px 18px;

  border: none;
  border-radius: 8px;

  background: #2563eb;
  color: white;

  font-size: 14px;
  font-weight: 600;

  cursor: pointer;

  white-space: nowrap;
}

.add-button:hover {
  background: #1d4ed8;
}

/* =========================================================
   FILTER
========================================================= */

.filter-card {
  padding: 20px;

  margin-bottom: 20px;

  background: white;

  border-radius: 12px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.04);
}

.filter-grid {
  display: grid;

  grid-template-columns:
    2fr 1fr 1fr 1fr auto;

  gap: 14px;

  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;

  gap: 7px;
}

.filter-group label {
  color: #334155;

  font-size: 13px;
  font-weight: 600;
}

.filter-group input,
.filter-group select {
  width: 100%;

  padding: 11px 12px;

  border: 1px solid #cbd5e1;
  border-radius: 8px;

  background: white;

  font-family: inherit;
}

.filter-group input:focus,
.filter-group select:focus {
  outline: none;

  border-color: #2563eb;
}

.reset-wrapper {
  display: flex;
}

.reset-button {
  padding: 11px 17px;

  border: 1px solid #cbd5e1;
  border-radius: 8px;

  background: white;
  color: #475569;

  cursor: pointer;
}

.reset-button:hover {
  background: #f8fafc;
}

.filter-result {
  margin-top: 14px;

  color: #64748b;

  font-size: 13px;
}

/* =========================================================
   TABLE
========================================================= */

.table-container {
  overflow-x: auto;

  background: white;

  border-radius: 12px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.05);
}

table {
  width: 100%;

  border-collapse: collapse;
}

th,
td {
  padding: 15px;

  text-align: left;

  border-bottom: 1px solid #e2e8f0;

  vertical-align: middle;
}

th {
  background: #f8fafc;

  color: #475569;

  font-size: 13px;
  font-weight: 700;

  white-space: nowrap;
}

td {
  color: #334155;

  font-size: 13px;
}

tbody tr:hover {
  background: #f8fafc;
}

/* =========================================================
   TITLE
========================================================= */

.title-cell {
  display: flex;
  flex-direction: column;

  gap: 6px;

  min-width: 210px;
  max-width: 330px;
}

.title-cell strong {
  color: #1e293b;
}

.summary-preview {
  display: -webkit-box;

  overflow: hidden;

  color: #94a3b8;

  font-size: 11px;
  line-height: 1.45;

  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

/* =========================================================
   TYPE
========================================================= */

.type-badge {
  display: inline-block;

  padding: 6px 9px;

  border-radius: 6px;

  font-size: 11px;
  font-weight: 700;

  white-space: nowrap;
}

.type-badge.pengaduan {
  background: #fee2e2;
  color: #b91c1c;
}

.type-badge.aspirasi {
  background: #e0e7ff;
  color: #4338ca;
}

/* =========================================================
   PRIORITAS AI
========================================================= */

.priority-badge {
  display: inline-flex;
  align-items: center;

  gap: 7px;

  padding: 6px 10px;

  border-radius: 20px;

  font-size: 11px;
  font-weight: 700;

  white-space: nowrap;
}

.priority-dot {
  width: 7px;
  height: 7px;

  border-radius: 50%;

  background: currentColor;
}

.priority-badge.rendah {
  background: #dcfce7;
  color: #15803d;
}

.priority-badge.sedang {
  background: #fef3c7;
  color: #b45309;
}

.priority-badge.tinggi {
  background: #fee2e2;
  color: #b91c1c;
}

.priority-badge.unavailable {
  background: #e2e8f0;
  color: #64748b;
}

/* =========================================================
   STATUS
========================================================= */

.status {
  display: inline-block;

  padding: 6px 10px;

  border-radius: 20px;

  font-size: 12px;
  font-weight: 600;

  white-space: nowrap;
}

.status.menunggu {
  background: #fef3c7;
  color: #92400e;
}

.status.diproses {
  background: #dbeafe;
  color: #1d4ed8;
}

.status.selesai {
  background: #dcfce7;
  color: #15803d;
}

.status.ditolak {
  background: #fee2e2;
  color: #b91c1c;
}

/* =========================================================
   BUTTON
========================================================= */

.detail-button {
  padding: 8px 13px;

  border: none;
  border-radius: 6px;

  background: #0f172a;
  color: white;

  cursor: pointer;

  font-size: 12px;
  font-weight: 600;
}

.detail-button:hover {
  background: #334155;
}

/* =========================================================
   STATE
========================================================= */

.message,
.empty,
.error-message {
  padding: 30px;

  background: white;

  border-radius: 12px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.04);
}

.message {
  color: #64748b;
}

.empty {
  text-align: center;
}

.empty h3 {
  margin-top: 0;
  margin-bottom: 8px;

  color: #1e293b;
}

.empty p {
  margin: 0 0 15px;

  color: #64748b;
}

.reset-empty-button {
  padding: 9px 15px;

  border: none;
  border-radius: 7px;

  background: #2563eb;
  color: white;

  cursor: pointer;
}

.error-message {
  color: #b91c1c;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {
  .filter-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }
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

  .add-button {
    width: 100%;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .reset-button {
    width: 100%;
  }
}
</style>
