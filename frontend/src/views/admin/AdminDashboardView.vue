<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/axios'

const router = useRouter()

const user = ref(null)
const complaints = ref([])
const loading = ref(true)
const error = ref('')

const loadDashboard = async () => {
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
      'Gagal memuat dashboard admin:',
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
      'Dashboard admin gagal dimuat.'
  } finally {
    loading.value = false
  }
}

const totalComplaints = computed(() => {
  return complaints.value.length
})

const countStatus = (status) => {
  return complaints.value.filter(
    (complaint) =>
      complaint.status === status
  ).length
}

const countPriority = (priority) => {
  return complaints.value.filter(
    (complaint) =>
      complaint.ai_priority === priority
  ).length
}

const highPriorityComplaints = computed(() => {
  return complaints.value
    .filter(
      (complaint) =>
        complaint.ai_priority === 'tinggi'
    )
    .slice(0, 5)
})

const recentComplaints = computed(() => {
  return complaints.value.slice(0, 5)
})

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

const priorityLabel = (priority) => {
  const labels = {
    rendah: 'Rendah',
    sedang: 'Sedang',
    tinggi: 'Tinggi',
  }

  return labels[priority] || 'Belum dianalisis'
}

const openDetail = (id) => {
  router.push(`/admin/complaints/${id}`)
}

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (err) {
    console.error('Logout error:', err)
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')

    router.push('/login')
  }
}

onMounted(() => {
  loadDashboard()
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
            class="menu active"
          >
            Dashboard
          </RouterLink>

          <RouterLink
            to="/admin/complaints"
            class="menu"
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

    <!-- MAIN -->
    <main class="main-content">

      <div
        v-if="loading"
        class="state-card"
      >
        Memuat dashboard admin...
      </div>

      <div
        v-else-if="error"
        class="error-card"
      >
        {{ error }}
      </div>

      <template v-else>

        <!-- HEADER -->
        <div class="page-header">
          <div>
            <h1>Dashboard Admin</h1>

            <p>
              Selamat datang,
              {{ user?.name }}.
              Kelola pengaduan dan aspirasi mahasiswa.
            </p>
          </div>

          <div class="admin-badge">
            Administrator
          </div>
        </div>

        <!-- STATISTIK -->
        <div class="stats-grid">

          <div class="stat-card total">
            <span>
              Total Pengaduan
            </span>

            <strong>
              {{ totalComplaints }}
            </strong>

            <small>
              Seluruh pengaduan dan aspirasi
            </small>
          </div>

          <div class="stat-card waiting">
            <span>
              Menunggu
            </span>

            <strong>
              {{ countStatus('menunggu') }}
            </strong>

            <small>
              Belum diproses
            </small>
          </div>

          <div class="stat-card process">
            <span>
              Diproses
            </span>

            <strong>
              {{ countStatus('diproses') }}
            </strong>

            <small>
              Sedang ditindaklanjuti
            </small>
          </div>

          <div class="stat-card done">
            <span>
              Selesai
            </span>

            <strong>
              {{ countStatus('selesai') }}
            </strong>

            <small>
              Telah diselesaikan
            </small>
          </div>

          <div class="stat-card rejected">
            <span>
              Ditolak
            </span>

            <strong>
              {{ countStatus('ditolak') }}
            </strong>

            <small>
              Pengaduan ditolak
            </small>
          </div>

        </div>

        <!-- STATISTIK AI -->
        <div class="ai-section">

          <div class="ai-section-header">
            <div>
              <span class="ai-kicker">
                Analisis AI
              </span>

              <h2>
                Ringkasan Prioritas AI
              </h2>

              <p>
                Distribusi tingkat prioritas hasil analisis AI
                pada seluruh pengaduan dan aspirasi mahasiswa.
              </p>
            </div>

            <div class="ai-model-badge">
              DeepSeek V4.1 Flash
            </div>
          </div>

          <div class="ai-stats-grid">

            <div class="ai-stat-card high">
              <div class="ai-stat-top">
                <span>Prioritas Tinggi</span>
                <div class="priority-indicator high-dot"></div>
              </div>

              <strong>
                {{ countPriority('tinggi') }}
              </strong>

              <small>
                Perlu perhatian lebih cepat
              </small>
            </div>

            <div class="ai-stat-card medium">
              <div class="ai-stat-top">
                <span>Prioritas Sedang</span>
                <div class="priority-indicator medium-dot"></div>
              </div>

              <strong>
                {{ countPriority('sedang') }}
              </strong>

              <small>
                Perlu ditindaklanjuti
              </small>
            </div>

            <div class="ai-stat-card low">
              <div class="ai-stat-top">
                <span>Prioritas Rendah</span>
                <div class="priority-indicator low-dot"></div>
              </div>

              <strong>
                {{ countPriority('rendah') }}
              </strong>

              <small>
                Dampak dan urgensi relatif rendah
              </small>
            </div>

          </div>

        </div>

        <!-- PRIORITAS TINGGI -->
        <div class="recent-card priority-card">

          <div class="section-header">
            <div>
              <div class="section-title-row">
                <h2>
                  Pengaduan Prioritas Tinggi
                </h2>

                <span class="danger-badge">
                  {{ countPriority('tinggi') }} Data
                </span>
              </div>

              <p>
                Pengaduan yang dinilai AI memiliki
                tingkat urgensi tinggi.
              </p>
            </div>

            <button
              class="view-all-button danger-button"
              @click="
                router.push(
                  '/admin/complaints'
                )
              "
            >
              Lihat Semua
            </button>
          </div>

          <div
            v-if="highPriorityComplaints.length === 0"
            class="empty priority-empty"
          >
            Tidak ada pengaduan prioritas tinggi.
          </div>

          <div
            v-else
            class="priority-list"
          >

            <div
              v-for="complaint in highPriorityComplaints"
              :key="complaint.id"
              class="priority-item"
            >

              <div class="priority-main">

                <div class="priority-item-header">

                  <span
                    class="priority-badge tinggi"
                  >
                    <span class="priority-dot"></span>
                    Tinggi
                  </span>

                  <span class="priority-date">
                    {{ formatDate(complaint.created_at) }}
                  </span>

                </div>

                <h3>
                  {{ complaint.title }}
                </h3>

                <p class="priority-summary">
                  {{
                    complaint.ai_summary ||
                    complaint.description
                  }}
                </p>

                <div class="priority-meta">
                  <span>
                    {{
                      complaint.user?.name ||
                      '-'
                    }}
                  </span>

                  <span>
                    {{
                      complaint.category?.name ||
                      'Tanpa kategori'
                    }}
                  </span>

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
                </div>

              </div>

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

            </div>

          </div>

        </div>

        <!-- PENGADUAN TERBARU -->
        <div class="recent-card">

          <div class="section-header">
            <div>
              <h2>
                Pengaduan Terbaru
              </h2>

              <p>
                Pengaduan dan aspirasi terbaru
                dari mahasiswa.
              </p>
            </div>

            <button
              class="view-all-button"
              @click="
                router.push(
                  '/admin/complaints'
                )
              "
            >
              Lihat Semua
            </button>
          </div>

          <div
            v-if="recentComplaints.length === 0"
            class="empty"
          >
            Belum ada pengaduan.
          </div>

          <div
            v-else
            class="table-container"
          >
            <table>

              <thead>
                <tr>
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
                  v-for="complaint in recentComplaints"
                  :key="complaint.id"
                >

                  <td>
                    <div class="student">
                      <strong>
                        {{
                          complaint.user?.name ||
                          '-'
                        }}
                      </strong>

                      <span>
                        {{
                          complaint.user?.nim ||
                          '-'
                        }}
                      </span>
                    </div>
                  </td>

                  <td>
                    {{ complaint.title }}
                  </td>

                  <td class="capitalize">
                    {{ complaint.type }}
                  </td>

                  <td>
                    {{
                      complaint.category?.name ||
                      '-'
                    }}
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
                      :class="
                        complaint.status
                      "
                    >
                      {{
                        statusLabel(
                          complaint.status
                        )
                      }}
                    </span>
                  </td>

                  <td>
                    {{
                      formatDate(
                        complaint.created_at
                      )
                    }}
                  </td>

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

      </template>

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
  border-top: 1px solid #334155;
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

.main-content {
  margin-left: 260px;
  padding: 35px;

  min-height: 100vh;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 30px;
}

.page-header h1 {
  margin: 0 0 7px;

  color: #0f172a;
}

.page-header p {
  margin: 0;

  color: #64748b;
}

.admin-badge {
  padding: 8px 14px;

  background: #dbeafe;
  color: #1d4ed8;

  border-radius: 20px;

  font-size: 13px;
  font-weight: 700;
}

.stats-grid {
  display: grid;

  grid-template-columns:
    repeat(5, 1fr);

  gap: 15px;

  margin-bottom: 25px;
}

.stat-card {
  padding: 22px;

  background: white;

  border-radius: 12px;

  border-left:
    4px solid #475569;

  box-shadow:
    0 3px 12px rgba(0, 0, 0, 0.04);
}

.stat-card span {
  display: block;

  color: #64748b;

  font-size: 13px;
}

.stat-card strong {
  display: block;

  margin: 10px 0 5px;

  color: #0f172a;

  font-size: 30px;
}

.stat-card small {
  color: #94a3b8;
}

.total {
  border-left-color: #475569;
}

.waiting {
  border-left-color: #f59e0b;
}

.process {
  border-left-color: #2563eb;
}

.done {
  border-left-color: #16a34a;
}

.rejected {
  border-left-color: #dc2626;
}

/* ==========================
   AI STATISTICS
========================== */

.ai-section {
  margin-bottom: 25px;
  padding: 25px;

  background: linear-gradient(
    135deg,
    #ffffff,
    #f8fbff
  );

  border: 1px solid #dbeafe;
  border-radius: 14px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.04);
}

.ai-section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;

  gap: 20px;

  margin-bottom: 20px;
}

.ai-kicker {
  display: block;

  margin-bottom: 5px;

  color: #2563eb;

  font-size: 11px;
  font-weight: 800;

  text-transform: uppercase;
  letter-spacing: 0.8px;
}

.ai-section-header h2 {
  margin: 0 0 6px;

  color: #0f172a;

  font-size: 19px;
}

.ai-section-header p {
  margin: 0;

  color: #64748b;

  font-size: 13px;
  line-height: 1.6;
}

.ai-model-badge {
  padding: 7px 12px;

  border: 1px solid #c4b5fd;
  border-radius: 20px;

  background: #f5f3ff;
  color: #6d28d9;

  font-size: 11px;
  font-weight: 700;

  white-space: nowrap;
}

.ai-stats-grid {
  display: grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap: 15px;
}

.ai-stat-card {
  padding: 18px;

  background: white;

  border: 1px solid #e2e8f0;
  border-radius: 11px;
}

.ai-stat-card.high {
  border-color: #fecaca;
  background: #fffafa;
}

.ai-stat-card.medium {
  border-color: #fde68a;
  background: #fffdf7;
}

.ai-stat-card.low {
  border-color: #bbf7d0;
  background: #fafffb;
}

.ai-stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 10px;
}

.ai-stat-top span {
  color: #64748b;

  font-size: 12px;
  font-weight: 600;
}

.priority-indicator {
  width: 9px;
  height: 9px;

  border-radius: 50%;
}

.high-dot {
  background: #dc2626;
}

.medium-dot {
  background: #f59e0b;
}

.low-dot {
  background: #16a34a;
}

.ai-stat-card strong {
  display: block;

  margin: 9px 0 4px;

  color: #0f172a;

  font-size: 29px;
}

.ai-stat-card small {
  color: #94a3b8;
}

/* ==========================
   PRIORITY HIGH LIST
========================== */

.priority-card {
  margin-bottom: 25px;
}

.section-title-row {
  display: flex;
  align-items: center;

  gap: 10px;
}

.danger-badge {
  padding: 4px 9px;

  border-radius: 20px;

  background: #fee2e2;
  color: #b91c1c;

  font-size: 10px;
  font-weight: 700;
}

.danger-button {
  background: #dc2626;
}

.danger-button:hover {
  background: #b91c1c;
}

.priority-list {
  display: flex;
  flex-direction: column;

  gap: 12px;
}

.priority-item {
  display: flex;
  justify-content: space-between;
  align-items: center;

  gap: 20px;

  padding: 17px;

  border: 1px solid #fecaca;
  border-left: 4px solid #dc2626;
  border-radius: 9px;

  background: #fffafa;
}

.priority-main {
  min-width: 0;
  flex: 1;
}

.priority-item-header {
  display: flex;
  align-items: center;

  gap: 10px;

  margin-bottom: 8px;
}

.priority-date {
  color: #94a3b8;

  font-size: 11px;
}

.priority-item h3 {
  margin: 0 0 7px;

  color: #1e293b;

  font-size: 15px;
}

.priority-summary {
  margin: 0 0 10px;

  color: #64748b;

  font-size: 12px;
  line-height: 1.6;
}

.priority-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;

  gap: 8px;
}

.priority-meta > span:not(.status) {
  padding-right: 8px;

  border-right: 1px solid #e2e8f0;

  color: #64748b;

  font-size: 11px;
}

.priority-empty {
  border: 1px dashed #cbd5e1;
}

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

.detail-button {
  padding: 8px 13px;

  border: none;
  border-radius: 7px;

  background: #2563eb;
  color: white;

  cursor: pointer;

  font-size: 12px;
  font-weight: 600;

  white-space: nowrap;
}

.detail-button:hover {
  background: #1d4ed8;
}

.recent-card {
  padding: 25px;

  background: white;

  border-radius: 12px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.05);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  margin-bottom: 20px;
}

.section-header h2 {
  margin: 0 0 5px;

  color: #0f172a;
}

.section-header p {
  margin: 0;

  color: #64748b;

  font-size: 14px;
}

.view-all-button {
  padding: 10px 15px;

  border: none;
  border-radius: 7px;

  background: #2563eb;
  color: white;

  cursor: pointer;
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

  font-size: 14px;
}

th {
  background: #f8fafc;

  color: #475569;
}

td {
  color: #334155;
}

.student {
  display: flex;
  flex-direction: column;
}

.student span {
  margin-top: 3px;

  color: #64748b;

  font-size: 12px;
}

.capitalize {
  text-transform: capitalize;
}

.status {
  display: inline-block;

  padding: 6px 10px;

  border-radius: 20px;

  font-size: 12px;
  font-weight: 600;
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

.empty,
.state-card,
.error-card {
  padding: 25px;

  background: white;

  border-radius: 10px;
}

.empty {
  text-align: center;
  color: #64748b;
}

.error-card {
  color: #b91c1c;
}

@media (max-width: 1100px) {
  .stats-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .ai-stats-grid {
    grid-template-columns:
      repeat(2, 1fr);
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

  .stats-grid,
  .ai-stats-grid {
    grid-template-columns: 1fr;
  }

  .ai-section-header {
    flex-direction: column;
  }

  .priority-item {
    flex-direction: column;
    align-items: stretch;
  }

  .priority-item .detail-button {
    width: 100%;
  }

  .page-header,
  .section-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 15px;
  }
}
</style>