<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/axios'

const router = useRouter()

const user = ref(null)
const complaints = ref([])

const loading = ref(true)
const error = ref('')

const search = ref('')
const statusFilter = ref('')
const typeFilter = ref('')
const priorityFilter = ref('')

/*
|--------------------------------------------------------------------------
| Load Data
|--------------------------------------------------------------------------
*/

const loadComplaints = async () => {
  loading.value = true
  error.value = ''

  try {
    const [userResponse, complaintResponse] =
      await Promise.all([
        api.get('/me'),
        api.get('/complaints'),
      ])

    user.value = userResponse.data.data

    if (user.value.role !== 'admin') {
      router.push('/dashboard')
      return
    }

    complaints.value =
      complaintResponse.data.data || []

    localStorage.setItem(
      'user',
      JSON.stringify(user.value)
    )
  } catch (err) {
    console.error(
      'Gagal mengambil pengaduan:',
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
| Filter Data
|--------------------------------------------------------------------------
*/

const filteredComplaints = computed(() => {
  return complaints.value.filter((complaint) => {
    const keyword =
      search.value
        .toLowerCase()
        .trim()

    const title =
      complaint.title?.toLowerCase() || ''

    const studentName =
      complaint.user?.name?.toLowerCase() || ''

    const nim =
      complaint.user?.nim?.toLowerCase() || ''

    const category =
      complaint.category?.name?.toLowerCase() || ''

    const matchesSearch =
      !keyword ||
      title.includes(keyword) ||
      studentName.includes(keyword) ||
      nim.includes(keyword) ||
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

/*
|--------------------------------------------------------------------------
| Reset Filter
|--------------------------------------------------------------------------
*/

const resetFilter = () => {
  search.value = ''
  statusFilter.value = ''
  typeFilter.value = ''
  priorityFilter.value = ''
}

/*
|--------------------------------------------------------------------------
| Format
|--------------------------------------------------------------------------
*/

const formatDate = (date) => {
  if (!date) return '-'

  return new Date(date).toLocaleDateString(
    'id-ID',
    {
      day: '2-digit',
      month: 'short',
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

/*
|--------------------------------------------------------------------------
| Detail
|--------------------------------------------------------------------------
*/

const openDetail = (id) => {
  router.push(`/admin/complaints/${id}`)
}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (err) {
    console.error(
      'Logout error:',
      err
    )
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')

    router.push('/login')
  }
}

onMounted(() => {
  loadComplaints()
})
</script>

<template>
  <div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

      <div>
        <div class="brand">

          <div class="brand-logo">
            PA
          </div>

          <div>
            <h2>SIMPA</h2>
            <span>Administrator</span>
          </div>

        </div>

        <nav>

          <RouterLink
            to="/admin/dashboard"
            class="menu"
          >
            Dashboard
          </RouterLink>

          <RouterLink
            to="/admin/complaints"
            class="menu active"
          >
            Semua Pengaduan
          </RouterLink>

        </nav>
      </div>

      <div class="sidebar-bottom">

        <div class="admin-user">
          <strong>
            {{ user?.name || 'Administrator' }}
          </strong>

          <span>
            {{ user?.email || '-' }}
          </span>
        </div>

        <button
          class="logout-button"
          @click="logout"
        >
          Logout
        </button>

      </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

      <!-- HEADER -->
      <div class="page-header">

        <div>
          <h1>
            Semua Pengaduan
          </h1>

          <p>
            Kelola seluruh pengaduan dan aspirasi
            yang dikirim oleh mahasiswa.
          </p>
        </div>

        <div class="total-badge">
          Total {{ complaints.length }} Data
        </div>

      </div>

      <!-- FILTER -->
      <div class="filter-card">

        <div class="filter-grid">

          <!-- SEARCH -->
          <div class="filter-group search-group">
            <label>
              Pencarian
            </label>

            <input
              v-model="search"
              type="text"
              placeholder="Cari judul, mahasiswa, NIM, atau kategori..."
            />
          </div>

          <!-- STATUS -->
          <div class="filter-group">
            <label>
              Status
            </label>

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

          <!-- JENIS -->
          <div class="filter-group">
            <label>
              Jenis
            </label>

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

          <!-- PRIORITAS AI -->
          <div class="filter-group">
            <label>
              Prioritas AI
            </label>

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

          <!-- RESET -->
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
          <strong>
            {{ filteredComplaints.length }}
          </strong>
          dari
          <strong>
            {{ complaints.length }}
          </strong>
          data.
        </div>

      </div>

      <!-- LOADING -->
      <div
        v-if="loading"
        class="state-card"
      >
        Memuat data pengaduan...
      </div>

      <!-- ERROR -->
      <div
        v-else-if="error"
        class="error-card"
      >
        {{ error }}
      </div>

      <!-- EMPTY -->
      <div
        v-else-if="
          filteredComplaints.length === 0
        "
        class="empty-card"
      >
        <h3>
          Data tidak ditemukan
        </h3>

        <p>
          Tidak ada pengaduan yang sesuai
          dengan pencarian atau filter.
        </p>

        <button
          @click="resetFilter"
        >
          Reset Filter
        </button>
      </div>

      <!-- TABLE -->
      <div
        v-else
        class="table-card"
      >

        <div class="table-container">

          <table>

            <thead>
              <tr>
                <th>No</th>
                <th>Mahasiswa</th>
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
                v-for="(
                  complaint,
                  index
                ) in filteredComplaints"
                :key="complaint.id"
              >

                <!-- NO -->
                <td>
                  {{ index + 1 }}
                </td>

                <!-- MAHASISWA -->
                <td>
                  <div class="student-info">

                    <strong>
                      {{
                        complaint.user?.name ||
                        '-'
                      }}
                    </strong>

                    <span>
                      NIM:
                      {{
                        complaint.user?.nim ||
                        '-'
                      }}
                    </span>

                  </div>
                </td>

                <!-- JUDUL -->
                <td>
                  <div class="title-cell">
                    <strong>
                      {{ complaint.title }}
                    </strong>
                  </div>
                </td>

                <!-- JENIS -->
                <td>
                  <span
                    class="type-badge"
                    :class="complaint.type"
                  >
                    {{
                      typeLabel(
                        complaint.type
                      )
                    }}
                  </span>
                </td>

                <!-- KATEGORI -->
                <td>
                  {{
                    complaint.category?.name ||
                    '-'
                  }}
                </td>

                <!-- PRIORITAS AI -->
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

                <!-- STATUS -->
                <td>
                  <span
                    class="status"
                    :class="complaint.status"
                  >
                    {{
                      statusLabel(
                        complaint.status
                      )
                    }}
                  </span>
                </td>

                <!-- TANGGAL -->
                <td>
                  {{
                    formatDate(
                      complaint.created_at
                    )
                  }}
                </td>

                <!-- AKSI -->
                <td>
                  <button
                    class="detail-button"
                    @click="
                      openDetail(
                        complaint.id
                      )
                    "
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
* {
  box-sizing: border-box;
}

.admin-layout {
  min-height: 100vh;
  background: #f1f5f9;
}

/* ==========================
   SIDEBAR
========================== */

.sidebar {
  position: fixed;
  top: 0;
  left: 0;

  width: 260px;
  height: 100vh;

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

.brand-logo {
  width: 44px;
  height: 44px;

  display: flex;
  align-items: center;
  justify-content: center;

  background: #2563eb;

  border-radius: 10px;

  font-weight: 700;
}

.brand h2 {
  margin: 0;
}

.brand span {
  color: #94a3b8;
  font-size: 12px;
}

nav {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.menu {
  padding: 12px 14px;

  color: #cbd5e1;

  text-decoration: none;

  border-radius: 8px;
}

.menu:hover,
.menu.active {
  background: #2563eb;
  color: white;
}

.sidebar-bottom {
  padding-top: 20px;

  border-top:
    1px solid #334155;
}

.admin-user {
  display: flex;
  flex-direction: column;

  margin-bottom: 15px;
}

.admin-user span {
  margin-top: 4px;

  color: #94a3b8;

  font-size: 11px;

  word-break: break-all;
}

.logout-button {
  width: 100%;

  padding: 10px;

  border: none;
  border-radius: 7px;

  background: #dc2626;
  color: white;

  cursor: pointer;
}

/* ==========================
   MAIN
========================== */

.main-content {
  margin-left: 260px;

  min-height: 100vh;

  padding: 35px;
}

/* ==========================
   HEADER
========================== */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  gap: 20px;

  margin-bottom: 25px;
}

.page-header h1 {
  margin: 0 0 7px;

  color: #0f172a;
}

.page-header p {
  margin: 0;

  color: #64748b;
}

.total-badge {
  padding: 9px 14px;

  background: #dbeafe;
  color: #1d4ed8;

  border-radius: 20px;

  font-size: 13px;
  font-weight: 700;
}

/* ==========================
   FILTER
========================== */

.filter-card {
  padding: 22px;

  margin-bottom: 20px;

  background: white;

  border-radius: 12px;

  box-shadow:
    0 3px 12px rgba(0, 0, 0, 0.04);
}

.filter-grid {
  display: grid;

  grid-template-columns:
    2fr 1fr 1fr 1fr auto;

  gap: 15px;

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
  padding: 11px 18px;

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
  margin-top: 15px;

  color: #64748b;

  font-size: 13px;
}

/* ==========================
   TABLE
========================== */

.table-card {
  background: white;

  border-radius: 12px;

  overflow: hidden;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.05);
}

.table-container {
  overflow-x: auto;
}

table {
  width: 100%;

  border-collapse: collapse;
}

th,
td {
  padding: 14px;

  text-align: left;

  border-bottom:
    1px solid #e2e8f0;

  font-size: 13px;

  vertical-align: middle;
}

th {
  background: #f8fafc;

  color: #475569;

  font-weight: 700;

  white-space: nowrap;
}

td {
  color: #334155;
}

tbody tr:hover {
  background: #f8fafc;
}

/* ==========================
   STUDENT
========================== */

.student-info {
  display: flex;
  flex-direction: column;

  min-width: 130px;
}

.student-info strong {
  color: #1e293b;
}

.student-info span {
  margin-top: 4px;

  color: #64748b;

  font-size: 11px;
}

/* ==========================
   TITLE
========================== */

.title-cell {
  min-width: 180px;
  max-width: 300px;
}

.title-cell strong {
  color: #1e293b;
}

/* ==========================
   TYPE
========================== */

.type-badge {
  display: inline-block;

  padding: 6px 9px;

  border-radius: 6px;

  font-size: 11px;
  font-weight: 700;
}

.type-badge.pengaduan {
  background: #fee2e2;
  color: #b91c1c;
}

.type-badge.aspirasi {
  background: #e0e7ff;
  color: #4338ca;
}

/* ==========================
   PRIORITAS AI
========================== */

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

/* ==========================
   STATUS
========================== */

.status {
  display: inline-block;

  padding: 6px 10px;

  border-radius: 20px;

  font-size: 11px;
  font-weight: 700;

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

/* ==========================
   BUTTON
========================== */

.detail-button {
  padding: 8px 13px;

  border: none;

  border-radius: 6px;

  background: #2563eb;
  color: white;

  cursor: pointer;

  font-size: 12px;
  font-weight: 600;
}

.detail-button:hover {
  background: #1d4ed8;
}

/* ==========================
   STATE
========================== */

.state-card,
.error-card,
.empty-card {
  padding: 30px;

  background: white;

  border-radius: 12px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.04);
}

.state-card {
  color: #64748b;
}

.error-card {
  color: #b91c1c;
}

.empty-card {
  text-align: center;
}

.empty-card h3 {
  margin-top: 0;

  color: #334155;
}

.empty-card p {
  color: #64748b;
}

.empty-card button {
  padding: 9px 15px;

  border: none;

  border-radius: 7px;

  background: #2563eb;
  color: white;

  cursor: pointer;
}

/* ==========================
   RESPONSIVE
========================== */

@media (max-width: 1000px) {
  .filter-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .reset-wrapper {
    align-items: flex-end;
  }
}

@media (max-width: 768px) {
  .sidebar {
    display: none;
  }

  .main-content {
    margin-left: 0;

    padding: 20px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .filter-grid {
    grid-template-columns: 1fr;
  }

  .reset-button {
    width: 100%;
  }
}
</style>