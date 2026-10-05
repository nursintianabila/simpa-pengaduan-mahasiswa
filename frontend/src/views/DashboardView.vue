<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import Sidebar from '../components/Sidebar.vue'

const router = useRouter()

const user = ref(null)
const complaints = ref([])
const loading = ref(true)
const error = ref('')

/*
|--------------------------------------------------------------------------
| Load Dashboard
|--------------------------------------------------------------------------
*/

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
    complaints.value =
      complaintResponse.data.data || []

    localStorage.setItem(
      'user',
      JSON.stringify(user.value)
    )
  } catch (err) {
    console.error(
      'Gagal memuat dashboard:',
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
      'Dashboard gagal dimuat.'
  } finally {
    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Statistik
|--------------------------------------------------------------------------
*/

const totalComplaints = computed(() => {
  return complaints.value.length
})

const countStatus = (status) => {
  return complaints.value.filter(
    (item) => item.status === status
  ).length
}

const countPriority = (priority) => {
  return complaints.value.filter(
    (item) => item.ai_priority === priority
  ).length
}

const recentComplaints = computed(() => {
  return [...complaints.value]
    .sort((a, b) => {
      return new Date(b.created_at) -
        new Date(a.created_at)
    })
    .slice(0, 5)
})

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

const openDetail = (id) => {
  router.push(`/complaints/${id}`)
}

onMounted(loadDashboard)
</script>

<template>
  <div class="layout">
    <Sidebar />

    <main class="main-content">
      <div class="page">

        <!-- LOADING -->
        <div
          v-if="loading"
          class="state-card"
        >
          Memuat dashboard...
        </div>

        <!-- ERROR -->
        <div
          v-else-if="error"
          class="error-card"
        >
          {{ error }}
        </div>

        <template v-else>

          <!-- WELCOME -->
          <div class="welcome-card">

            <div>
              <span class="welcome-kicker">
                Dashboard Mahasiswa
              </span>

              <h1>
                Selamat Datang,
                {{ user?.name }}
              </h1>

              <p>
                Pantau pengaduan dan aspirasi Anda
                serta lihat perkembangan tindak lanjutnya.
              </p>
            </div>

            <button
              class="create-button"
              @click="
                router.push(
                  '/complaints/create'
                )
              "
            >
              + Buat Pengaduan
            </button>

          </div>

          <!-- STATISTIK STATUS -->
          <div class="stats">

            <div class="stat-card total">
              <span>Total Pengaduan</span>

              <strong>
                {{ totalComplaints }}
              </strong>

              <small>
                Seluruh laporan Anda
              </small>
            </div>

            <div class="stat-card waiting">
              <span>Menunggu</span>

              <strong>
                {{ countStatus('menunggu') }}
              </strong>

              <small>
                Belum diproses admin
              </small>
            </div>

            <div class="stat-card process">
              <span>Diproses</span>

              <strong>
                {{ countStatus('diproses') }}
              </strong>

              <small>
                Sedang ditindaklanjuti
              </small>
            </div>

            <div class="stat-card done">
              <span>Selesai</span>

              <strong>
                {{ countStatus('selesai') }}
              </strong>

              <small>
                Telah diselesaikan
              </small>
            </div>

            <div class="stat-card rejected">
              <span>Ditolak</span>

              <strong>
                {{ countStatus('ditolak') }}
              </strong>

              <small>
                Tidak dapat ditindaklanjuti
              </small>
            </div>

          </div>

          <!-- RINGKASAN AI -->
          <div class="ai-card">

            <div class="section-header">

              <div>
                <span class="section-kicker">
                  Analisis AI
                </span>

                <h2>
                  Ringkasan Prioritas Laporan
                </h2>

                <p>
                  Distribusi tingkat prioritas hasil
                  analisis AI pada laporan Anda.
                </p>
              </div>

              <div class="ai-badge">
                DeepSeek
              </div>

            </div>

            <div class="ai-grid">

              <div class="ai-stat high">
                <div class="ai-stat-top">
                  <span>Prioritas Tinggi</span>

                  <span class="priority-circle high"></span>
                </div>

                <strong>
                  {{ countPriority('tinggi') }}
                </strong>

                <small>
                  Membutuhkan perhatian lebih cepat
                </small>
              </div>

              <div class="ai-stat medium">
                <div class="ai-stat-top">
                  <span>Prioritas Sedang</span>

                  <span class="priority-circle medium"></span>
                </div>

                <strong>
                  {{ countPriority('sedang') }}
                </strong>

                <small>
                  Perlu ditindaklanjuti
                </small>
              </div>

              <div class="ai-stat low">
                <div class="ai-stat-top">
                  <span>Prioritas Rendah</span>

                  <span class="priority-circle low"></span>
                </div>

                <strong>
                  {{ countPriority('rendah') }}
                </strong>

                <small>
                  Tingkat urgensi relatif rendah
                </small>
              </div>

            </div>

            <div class="ai-note">
              Hasil AI bersifat informasi pendukung.
              Keputusan dan tindak lanjut tetap dilakukan
              oleh administrator.
            </div>

          </div>

          <!-- PENGADUAN TERBARU -->
          <div class="recent-card">

            <div class="section-header">

              <div>
                <span class="section-kicker">
                  Aktivitas Terbaru
                </span>

                <h2>
                  Pengaduan Terbaru
                </h2>

                <p>
                  Lima pengaduan atau aspirasi terbaru
                  yang Anda kirim.
                </p>
              </div>

              <button
                class="view-all-button"
                @click="
                  router.push(
                    '/complaints'
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
              <h3>Belum ada pengaduan</h3>

              <p>
                Anda belum pernah mengirim pengaduan
                atau aspirasi.
              </p>
            </div>

            <div
              v-else
              class="table-container"
            >
              <table>

                <thead>
                  <tr>
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
                        :class="complaint.status"
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

          <!-- PROFILE -->
          <div class="profile">

            <div class="section-header">
              <div>
                <span class="section-kicker">
                  Profil
                </span>

                <h2>
                  Informasi Mahasiswa
                </h2>

                <p>
                  Informasi akun yang sedang digunakan.
                </p>
              </div>
            </div>

            <div class="profile-grid">

              <div>
                <span>Nama</span>

                <strong>
                  {{ user?.name || '-' }}
                </strong>
              </div>

              <div>
                <span>NIM</span>

                <strong>
                  {{ user?.nim || '-' }}
                </strong>
              </div>

              <div>
                <span>Email</span>

                <strong>
                  {{ user?.email || '-' }}
                </strong>
              </div>

              <div>
                <span>Role</span>

                <strong class="role-text">
                  {{ user?.role || '-' }}
                </strong>
              </div>

            </div>

          </div>

        </template>

      </div>
    </main>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

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
  padding: 35px;
}

/* =========================================================
   WELCOME
========================================================= */

.welcome-card {
  display: flex;
  justify-content: space-between;
  align-items: center;

  gap: 20px;

  padding: 25px;

  background: white;

  border-radius: 14px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.04);
}

.welcome-kicker,
.section-kicker {
  display: block;

  margin-bottom: 6px;

  color: #2563eb;

  font-size: 11px;
  font-weight: 800;

  text-transform: uppercase;
  letter-spacing: 0.6px;
}

.welcome-card h1 {
  margin: 0;

  color: #0f172a;
}

.welcome-card p {
  margin: 8px 0 0;

  color: #64748b;

  line-height: 1.6;
}

.create-button {
  padding: 12px 18px;

  border: none;
  border-radius: 8px;

  background: #2563eb;
  color: white;

  font-size: 13px;
  font-weight: 600;

  cursor: pointer;

  white-space: nowrap;
}

.create-button:hover {
  background: #1d4ed8;
}

/* =========================================================
   STATUS STATISTICS
========================================================= */

.stats {
  margin-top: 25px;

  display: grid;

  grid-template-columns:
    repeat(5, 1fr);

  gap: 15px;
}

.stat-card {
  padding: 20px;

  background: white;

  border-radius: 12px;

  border-left: 4px solid #475569;

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

  margin: 9px 0 4px;

  color: #0f172a;

  font-size: 29px;
}

.stat-card small {
  color: #94a3b8;

  font-size: 11px;
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

/* =========================================================
   SECTION HEADER
========================================================= */

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;

  gap: 20px;

  margin-bottom: 20px;
}

.section-header h2 {
  margin: 0 0 5px;

  color: #0f172a;

  font-size: 19px;
}

.section-header p {
  margin: 0;

  color: #64748b;

  font-size: 13px;
  line-height: 1.6;
}

/* =========================================================
   AI
========================================================= */

.ai-card {
  margin-top: 25px;
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

.ai-badge {
  padding: 6px 11px;

  border: 1px solid #c4b5fd;
  border-radius: 20px;

  background: #f5f3ff;
  color: #6d28d9;

  font-size: 10px;
  font-weight: 700;

  text-transform: uppercase;
}

.ai-grid {
  display: grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap: 14px;
}

.ai-stat {
  padding: 17px;

  border: 1px solid #e2e8f0;
  border-radius: 10px;

  background: white;
}

.ai-stat.high {
  border-color: #fecaca;
  background: #fffafa;
}

.ai-stat.medium {
  border-color: #fde68a;
  background: #fffdf7;
}

.ai-stat.low {
  border-color: #bbf7d0;
  background: #fafffb;
}

.ai-stat-top {
  display: flex;
  justify-content: space-between;
  align-items: center;

  gap: 10px;
}

.ai-stat-top > span:first-child {
  color: #64748b;

  font-size: 12px;
  font-weight: 600;
}

.priority-circle {
  width: 9px;
  height: 9px;

  border-radius: 50%;
}

.priority-circle.high {
  background: #dc2626;
}

.priority-circle.medium {
  background: #f59e0b;
}

.priority-circle.low {
  background: #16a34a;
}

.ai-stat strong {
  display: block;

  margin: 9px 0 4px;

  color: #0f172a;

  font-size: 28px;
}

.ai-stat small {
  color: #94a3b8;

  font-size: 11px;
}

.ai-note {
  margin-top: 14px;

  padding: 12px 14px;

  border-radius: 8px;

  background: #f8fafc;

  color: #64748b;

  font-size: 11px;
  line-height: 1.6;
}

/* =========================================================
   RECENT
========================================================= */

.recent-card,
.profile {
  margin-top: 25px;

  padding: 25px;

  background: white;

  border-radius: 12px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.05);
}

.view-all-button {
  padding: 9px 14px;

  border: none;
  border-radius: 7px;

  background: #2563eb;
  color: white;

  cursor: pointer;

  font-size: 12px;
  font-weight: 600;

  white-space: nowrap;
}

.view-all-button:hover {
  background: #1d4ed8;
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
  padding: 13px;

  text-align: left;

  border-bottom: 1px solid #e2e8f0;

  vertical-align: middle;
}

th {
  background: #f8fafc;

  color: #475569;

  font-size: 12px;
  font-weight: 700;

  white-space: nowrap;
}

td {
  color: #334155;

  font-size: 12px;
}

tbody tr:hover {
  background: #f8fafc;
}

.title-cell {
  display: flex;
  flex-direction: column;

  gap: 5px;

  min-width: 200px;
  max-width: 300px;
}

.title-cell strong {
  color: #1e293b;
}

.summary-preview {
  display: -webkit-box;

  overflow: hidden;

  color: #94a3b8;

  font-size: 10px;
  line-height: 1.4;

  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.type-badge {
  display: inline-block;

  padding: 5px 8px;

  border-radius: 6px;

  font-size: 10px;
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

.priority-badge {
  display: inline-flex;
  align-items: center;

  gap: 6px;

  padding: 5px 9px;

  border-radius: 20px;

  font-size: 10px;
  font-weight: 700;

  white-space: nowrap;
}

.priority-dot {
  width: 6px;
  height: 6px;

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

.status {
  display: inline-block;

  padding: 5px 9px;

  border-radius: 20px;

  font-size: 10px;
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

.detail-button {
  padding: 7px 11px;

  border: none;
  border-radius: 6px;

  background: #0f172a;
  color: white;

  cursor: pointer;

  font-size: 11px;
  font-weight: 600;
}

.detail-button:hover {
  background: #334155;
}

/* =========================================================
   PROFILE
========================================================= */

.profile-grid {
  display: grid;

  grid-template-columns:
    repeat(2, 1fr);

  gap: 15px;
}

.profile-grid > div {
  padding: 15px;

  background: #f8fafc;

  border: 1px solid #f1f5f9;
  border-radius: 8px;

  display: flex;
  flex-direction: column;

  gap: 5px;
}

.profile-grid span {
  color: #64748b;

  font-size: 12px;
}

.profile-grid strong {
  color: #1e293b;

  font-size: 13px;
}

.role-text {
  text-transform: capitalize;
}

/* =========================================================
   STATE
========================================================= */

.state-card,
.error-card {
  padding: 25px;

  background: white;

  border-radius: 12px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.05);
}

.state-card {
  color: #64748b;
}

.error-card {
  color: #b91c1c;
}

.empty {
  padding: 25px;

  background: #f8fafc;

  border-radius: 8px;

  text-align: center;
}

.empty h3 {
  margin: 0 0 6px;

  color: #334155;
}

.empty p {
  margin: 0;

  color: #64748b;

  font-size: 12px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {
  .stats {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .ai-grid {
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

  .welcome-card,
  .section-header {
    flex-direction: column;
    align-items: stretch;
  }

  .create-button,
  .view-all-button {
    width: 100%;
  }

  .stats,
  .ai-grid,
  .profile-grid {
    grid-template-columns: 1fr;
  }
}
</style>
