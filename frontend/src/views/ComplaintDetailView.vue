<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api/axios'
import Sidebar from '../components/Sidebar.vue'

const route = useRoute()
const router = useRouter()

const complaint = ref(null)

const loading = ref(true)
const deleting = ref(false)
const error = ref('')

/*
|--------------------------------------------------------------------------
| Mengambil Detail Pengaduan
|--------------------------------------------------------------------------
*/

const loadComplaint = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get(
      `/complaints/${route.params.id}`
    )

    complaint.value = response.data.data
  } catch (err) {
    console.error(
      'Gagal mengambil detail pengaduan:',
      err
    )

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

    error.value =
      err.response?.data?.message ||
      'Detail pengaduan gagal dimuat.'
  } finally {
    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Format Tanggal
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
      hour: '2-digit',
      minute: '2-digit',
    }
  )
}

/*
|--------------------------------------------------------------------------
| Label Status
|--------------------------------------------------------------------------
*/

const statusLabel = (status) => {
  const labels = {
    menunggu: 'Menunggu',
    diproses: 'Diproses',
    selesai: 'Selesai',
    ditolak: 'Ditolak',
  }

  return labels[status] || status
}

/*
|--------------------------------------------------------------------------
| Label Jenis
|--------------------------------------------------------------------------
*/

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

const sentimentLabel = (sentiment) => {
  const labels = {
    positif: 'Positif',
    netral: 'Netral',
    negatif: 'Negatif',
  }

  return labels[sentiment] || 'Belum dianalisis'
}

const hasAiSummary = () => {
  return Boolean(
    complaint.value?.ai_summary ||
    complaint.value?.ai_priority ||
    complaint.value?.ai_sentiment
  )
}

/*
|--------------------------------------------------------------------------
| Edit Pengaduan
|--------------------------------------------------------------------------
*/

const editComplaint = () => {
  if (!complaint.value) {
    return
  }

  router.push(
    `/complaints/${complaint.value.id}/edit`
  )
}

/*
|--------------------------------------------------------------------------
| Hapus Pengaduan
|--------------------------------------------------------------------------
*/

const deleteComplaint = async () => {
  if (!complaint.value) {
    return
  }

  if (complaint.value.status !== 'menunggu') {
    error.value =
      'Pengaduan yang sudah diproses tidak dapat dihapus.'

    return
  }

  const confirmed = window.confirm(
    'Apakah Anda yakin ingin menghapus pengaduan ini? Data yang sudah dihapus tidak dapat dikembalikan.'
  )

  if (!confirmed) {
    return
  }

  deleting.value = true
  error.value = ''

  try {
    await api.delete(
      `/complaints/${complaint.value.id}`
    )

    router.push('/complaints')
  } catch (err) {
    console.error(
      'Gagal menghapus pengaduan:',
      err
    )

    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')

      router.push('/login')
      return
    }

    if (err.response?.status === 403) {
      error.value =
        err.response.data.message ||
        'Pengaduan ini tidak dapat dihapus.'

      return
    }

    if (err.response?.status === 404) {
      error.value = 'Pengaduan tidak ditemukan.'
      return
    }

    error.value =
      err.response?.data?.message ||
      'Terjadi kesalahan saat menghapus pengaduan.'
  } finally {
    deleting.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Jalankan Ketika Halaman Dibuka
|--------------------------------------------------------------------------
*/

onMounted(() => {
  loadComplaint()
})
</script>

<template>
  <div class="layout">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main Content -->
    <main class="main-content">
      <div class="page">

        <!-- Loading -->
        <div
          v-if="loading"
          class="state-card"
        >
          <p>Memuat detail pengaduan...</p>
        </div>

        <!-- Error ketika data tidak berhasil dimuat -->
        <div
          v-else-if="error && !complaint"
          class="error-card"
        >
          <h3>Terjadi Kesalahan</h3>

          <p>
            {{ error }}
          </p>

          <button
            type="button"
            @click="router.push('/complaints')"
          >
            Kembali ke Pengaduan
          </button>
        </div>

        <!-- Detail Pengaduan -->
        <template v-else-if="complaint">

          <!-- Header -->
          <div class="page-header">
            <div>
              <button
                class="back-link"
                type="button"
                @click="router.push('/complaints')"
              >
                ← Kembali ke Pengaduan
              </button>

              <h1>Detail Pengaduan</h1>

              <p>
                Informasi lengkap pengaduan atau
                aspirasi Anda.
              </p>
            </div>

            <span
              class="status"
              :class="complaint.status"
            >
              {{ statusLabel(complaint.status) }}
            </span>
          </div>

          <!-- Error Action -->
          <div
            v-if="error"
            class="action-error"
          >
            {{ error }}
          </div>

          <!-- Informasi Utama -->
          <div class="detail-card">

            <div class="detail-header">
              <div>
                <span class="type-badge">
                  {{ typeLabel(complaint.type) }}
                </span>

                <h2>
                  {{ complaint.title }}
                </h2>
              </div>
            </div>

            <!-- Metadata -->
            <div class="meta-grid">

              <div class="meta-item">
                <span>Kategori</span>

                <strong>
                  {{
                    complaint.category?.name ||
                    '-'
                  }}
                </strong>
              </div>

              <div class="meta-item">
                <span>Status</span>

                <strong>
                  {{
                    statusLabel(
                      complaint.status
                    )
                  }}
                </strong>
              </div>

              <div class="meta-item">
                <span>Tanggal Dikirim</span>

                <strong>
                  {{
                    formatDate(
                      complaint.created_at
                    )
                  }}
                </strong>
              </div>

              <div class="meta-item">
                <span>Terakhir Diperbarui</span>

                <strong>
                  {{
                    formatDate(
                      complaint.updated_at
                    )
                  }}
                </strong>
              </div>

              <div class="meta-item">
                <span>Nama Mahasiswa</span>

                <strong>
                  {{
                    complaint.user?.name ||
                    '-'
                  }}
                </strong>
              </div>

              <div class="meta-item">
                <span>NIM</span>

                <strong>
                  {{
                    complaint.user?.nim ||
                    '-'
                  }}
                </strong>
              </div>

            </div>

            <!-- Deskripsi -->
            <div class="description-section">
              <h3>Deskripsi</h3>

              <div class="description-content">
                {{ complaint.description }}
              </div>
            </div>

          </div>

          <!-- Informasi AI -->
          <div class="ai-card">

            <div class="ai-card-header">

              <div class="ai-heading">

                <div class="ai-icon">
                  AI
                </div>

                <div>
                  <div class="ai-title-row">
                    <h2>
                      Informasi Analisis AI
                    </h2>

                    <span class="ai-badge">
                      DeepSeek
                    </span>
                  </div>

                  <p>
                    Ringkasan otomatis untuk membantu Anda
                    memahami inti laporan yang telah dikirim.
                  </p>
                </div>

              </div>

            </div>

            <!-- AI TERSEDIA -->
            <div
              v-if="hasAiSummary()"
              class="ai-content"
            >

              <div class="ai-summary-box">

                <span class="ai-label">
                  Ringkasan Laporan
                </span>

                <p>
                  {{ complaint.ai_summary || '-' }}
                </p>

              </div>

              <div class="ai-meta-grid">

                <div class="ai-meta-item">

                  <span class="ai-label">
                    Tingkat Prioritas
                  </span>

                  <span
                    v-if="complaint.ai_priority"
                    class="priority-badge"
                    :class="complaint.ai_priority"
                  >
                    <span class="badge-dot"></span>

                    {{
                      priorityLabel(
                        complaint.ai_priority
                      )
                    }}
                  </span>

                  <span
                    v-else
                    class="ai-empty-value"
                  >
                    Belum dianalisis
                  </span>

                </div>

                <div class="ai-meta-item">

                  <span class="ai-label">
                    Sentimen Laporan
                  </span>

                  <span
                    v-if="complaint.ai_sentiment"
                    class="sentiment-badge"
                    :class="complaint.ai_sentiment"
                  >
                    <span class="badge-dot"></span>

                    {{
                      sentimentLabel(
                        complaint.ai_sentiment
                      )
                    }}
                  </span>

                  <span
                    v-else
                    class="ai-empty-value"
                  >
                    Belum dianalisis
                  </span>

                </div>

              </div>

              <div class="ai-note">

                <div class="ai-note-icon">
                  i
                </div>

                <p>
                  Hasil AI bersifat informasi pendukung.
                  Analisis masalah mendalam dan rekomendasi
                  penanganan digunakan oleh administrator
                  untuk membantu proses tindak lanjut.
                </p>

              </div>

            </div>

            <!-- AI BELUM TERSEDIA -->
            <div
              v-else
              class="ai-unavailable"
            >

              <div class="ai-unavailable-icon">
                AI
              </div>

              <div>
                <strong>
                  Analisis AI belum tersedia
                </strong>

                <p>
                  Laporan Anda tetap berhasil tersimpan.
                  Hasil analisis AI dapat belum tersedia
                  apabila layanan sedang mengalami gangguan.
                </p>
              </div>

            </div>

          </div>

          <!-- Tanggapan Admin -->
          <div class="response-card">

            <div class="section-header">
              <div>
                <h2>Tanggapan Admin</h2>

                <p>
                  Tanggapan yang diberikan oleh admin
                  terhadap pengaduan ini.
                </p>
              </div>
            </div>

            <!-- Belum Ada Tanggapan -->
            <div
              v-if="
                !complaint.responses ||
                complaint.responses.length === 0
              "
              class="no-response"
            >
              <div class="empty-icon">
                💬
              </div>

              <h3>Belum ada tanggapan</h3>

              <p>
                Pengaduan ini belum mendapatkan
                tanggapan dari admin.
              </p>
            </div>

            <!-- Daftar Tanggapan -->
            <div
              v-else
              class="response-list"
            >
              <div
                v-for="response in complaint.responses"
                :key="response.id"
                class="response-item"
              >

                <div class="response-header">

                  <div class="admin-info">
                    <div class="admin-avatar">
                      A
                    </div>

                    <div>
                      <strong>
                        {{
                          response.user?.name ||
                          'Administrator'
                        }}
                      </strong>

                      <span>
                        {{
                          formatDate(
                            response.created_at
                          )
                        }}
                      </span>
                    </div>
                  </div>

                  <span class="admin-badge">
                    Admin
                  </span>

                </div>

                <div class="response-content">
                  {{ response.response }}
                </div>

              </div>
            </div>

          </div>

          <!-- Aksi mahasiswa -->
          <div
            v-if="complaint.status === 'menunggu'"
            class="action-card"
          >

            <div class="action-information">
              <div>
                <strong>
                  Pengaduan masih menunggu
                </strong>

                <p>
                  Anda masih dapat mengubah atau
                  menghapus pengaduan ini karena
                  belum diproses oleh admin.
                </p>
              </div>
            </div>

            <div class="actions">

              <!-- EDIT -->
              <button
                class="edit-button"
                type="button"
                @click="editComplaint"
              >
                Edit Pengaduan
              </button>

              <!-- DELETE -->
              <button
                class="delete-button"
                type="button"
                :disabled="deleting"
                @click="deleteComplaint"
              >
                {{
                  deleting
                    ? 'Menghapus...'
                    : 'Hapus Pengaduan'
                }}
              </button>

            </div>

          </div>

          <!-- Jika Sudah Diproses -->
          <div
            v-else
            class="locked-info"
          >
            <div class="locked-icon">
              🔒
            </div>

            <div>
              <strong>
                Pengaduan sudah diproses
              </strong>

              <p>
                Pengaduan yang sudah diproses tidak
                dapat diubah atau dihapus oleh
                mahasiswa.
              </p>
            </div>
          </div>

        </template>

      </div>
    </main>
  </div>
</template>

<style scoped>
/* =========================================================
   Layout
========================================================= */

.layout {
  min-height: 100vh;
  background: #f1f5f9;
}

.main-content {
  margin-left: 250px;
  min-height: 100vh;
}

.page {
  max-width: 1100px;
  padding: 35px;
}

/* =========================================================
   Header
========================================================= */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 25px;
}

.page-header h1 {
  margin: 12px 0 7px;
  color: #0f172a;
}

.page-header p {
  margin: 0;
  color: #64748b;
}

.back-link {
  padding: 0;
  border: none;
  background: transparent;
  color: #2563eb;
  cursor: pointer;
  font-weight: 600;
}

.back-link:hover {
  color: #1d4ed8;
}

/* =========================================================
   Status
========================================================= */

.status {
  display: inline-block;
  padding: 8px 14px;
  border-radius: 20px;
  text-transform: capitalize;
  font-size: 13px;
  font-weight: 700;
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
   Card
========================================================= */

.detail-card,
.response-card,
.ai-card,
.action-card {
  background: white;
  padding: 28px;
  border-radius: 12px;
  margin-bottom: 20px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.05);
}

/* =========================================================
   Detail
========================================================= */

.detail-header {
  padding-bottom: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.detail-header h2 {
  margin: 12px 0 0;
  color: #0f172a;
}

.type-badge {
  display: inline-block;
  padding: 6px 10px;

  background: #e0e7ff;
  color: #3730a3;

  border-radius: 6px;

  font-size: 12px;
  font-weight: 700;
}

/* =========================================================
   Metadata
========================================================= */

.meta-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;

  margin-top: 25px;
}

.meta-item {
  padding: 15px;

  background: #f8fafc;

  border: 1px solid #f1f5f9;
  border-radius: 8px;
}

.meta-item span {
  display: block;

  margin-bottom: 6px;

  color: #64748b;

  font-size: 13px;
}

.meta-item strong {
  color: #1e293b;
}

/* =========================================================
   Deskripsi
========================================================= */

.description-section {
  margin-top: 25px;
}

.description-section h3 {
  margin-bottom: 12px;
  color: #1e293b;
}

.description-content {
  padding: 18px;

  background: #f8fafc;

  border-radius: 8px;

  color: #475569;

  line-height: 1.7;

  white-space: pre-line;
}

/* =========================================================
   Analisis AI
========================================================= */

.ai-card {
  position: relative;
  overflow: hidden;

  border: 1px solid #dbeafe;
}

.ai-card::before {
  content: '';

  position: absolute;
  top: 0;
  left: 0;

  width: 100%;
  height: 4px;

  background: linear-gradient(
    90deg,
    #2563eb,
    #7c3aed
  );
}

.ai-card-header {
  margin-bottom: 20px;
  padding-bottom: 18px;

  border-bottom: 1px solid #e2e8f0;
}

.ai-heading {
  display: flex;
  align-items: center;

  gap: 13px;
}

.ai-icon {
  width: 44px;
  height: 44px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 12px;

  background: linear-gradient(
    135deg,
    #2563eb,
    #7c3aed
  );

  color: white;

  font-size: 13px;
  font-weight: 800;
}

.ai-title-row {
  display: flex;
  align-items: center;

  gap: 9px;
}

.ai-title-row h2 {
  margin: 0;

  color: #0f172a;

  font-size: 18px;
}

.ai-heading p {
  margin: 5px 0 0;

  color: #64748b;

  font-size: 13px;
  line-height: 1.5;
}

.ai-badge {
  padding: 3px 8px;

  border: 1px solid #c4b5fd;
  border-radius: 20px;

  background: #f5f3ff;
  color: #6d28d9;

  font-size: 10px;
  font-weight: 700;

  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.ai-content {
  display: flex;
  flex-direction: column;

  gap: 18px;
}

.ai-summary-box {
  padding: 17px;

  border: 1px solid #dbeafe;
  border-radius: 10px;

  background: #f8fbff;
}

.ai-label {
  display: block;

  margin-bottom: 8px;

  color: #64748b;

  font-size: 11px;
  font-weight: 700;

  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.ai-summary-box p {
  margin: 0;

  color: #334155;

  font-size: 14px;
  line-height: 1.7;
}

.ai-meta-grid {
  display: grid;

  grid-template-columns:
    repeat(2, 1fr);

  gap: 14px;
}

.ai-meta-item {
  padding: 15px;

  border: 1px solid #e2e8f0;
  border-radius: 10px;

  background: white;
}

.priority-badge,
.sentiment-badge {
  width: fit-content;

  display: inline-flex;
  align-items: center;

  gap: 7px;

  padding: 6px 10px;

  border-radius: 20px;

  font-size: 12px;
  font-weight: 700;
}

.badge-dot {
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

.sentiment-badge.positif {
  background: #dcfce7;
  color: #15803d;
}

.sentiment-badge.netral {
  background: #e2e8f0;
  color: #475569;
}

.sentiment-badge.negatif {
  background: #fee2e2;
  color: #b91c1c;
}

.ai-empty-value {
  color: #94a3b8;

  font-size: 12px;
  font-weight: 600;
}

.ai-note {
  display: flex;
  align-items: flex-start;

  gap: 10px;

  padding: 13px 14px;

  border-radius: 9px;

  background: #f8fafc;
}

.ai-note-icon {
  width: 21px;
  height: 21px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #dbeafe;
  color: #2563eb;

  font-size: 11px;
  font-weight: 800;
}

.ai-note p {
  margin: 0;

  color: #64748b;

  font-size: 12px;
  line-height: 1.6;
}

.ai-unavailable {
  display: flex;
  align-items: center;

  gap: 13px;

  padding: 16px;

  border: 1px dashed #cbd5e1;
  border-radius: 10px;

  background: #f8fafc;
}

.ai-unavailable-icon {
  width: 40px;
  height: 40px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 10px;

  background: #e2e8f0;
  color: #64748b;

  font-size: 11px;
  font-weight: 800;
}

.ai-unavailable strong {
  color: #475569;

  font-size: 13px;
}

.ai-unavailable p {
  margin: 4px 0 0;

  color: #94a3b8;

  font-size: 11px;
  line-height: 1.5;
}

/* =========================================================
   Response Admin
========================================================= */

.section-header {
  margin-bottom: 20px;
}

.section-header h2 {
  margin: 0 0 6px;
  color: #0f172a;
}

.section-header p {
  margin: 0;
  color: #64748b;
  font-size: 14px;
}

.no-response {
  padding: 30px;

  background: #f8fafc;

  text-align: center;

  border-radius: 8px;
}

.empty-icon {
  margin-bottom: 10px;
  font-size: 28px;
}

.no-response h3 {
  margin: 0 0 7px;
  color: #334155;
}

.no-response p {
  margin: 0;
  color: #64748b;
}

/* =========================================================
   Daftar Response
========================================================= */

.response-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.response-item {
  padding: 18px;

  background: #f8fafc;

  border-left: 4px solid #2563eb;

  border-radius: 8px;
}

.response-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;

  gap: 15px;
}

.admin-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.admin-avatar {
  width: 38px;
  height: 38px;

  display: flex;
  align-items: center;
  justify-content: center;

  background: #2563eb;
  color: white;

  border-radius: 50%;

  font-weight: 700;
}

.admin-info > div:last-child {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.admin-info strong {
  color: #1e293b;
}

.admin-info span {
  color: #64748b;
  font-size: 12px;
}

.admin-badge {
  padding: 5px 9px;

  background: #dbeafe;
  color: #1d4ed8;

  border-radius: 15px;

  font-size: 12px;
  font-weight: 600;
}

.response-content {
  margin-top: 15px;

  color: #334155;

  line-height: 1.7;

  white-space: pre-line;
}

/* =========================================================
   Action
========================================================= */

.action-card {
  display: flex;
  justify-content: space-between;
  align-items: center;

  gap: 20px;
}

.action-information strong {
  color: #1e293b;
}

.action-information p {
  margin: 5px 0 0;

  color: #64748b;

  font-size: 13px;
}

.actions {
  display: flex;
  gap: 12px;
  flex-shrink: 0;
}

.edit-button,
.delete-button {
  padding: 11px 18px;

  border: none;
  border-radius: 8px;

  cursor: pointer;

  font-weight: 600;
}

.edit-button {
  background: #2563eb;
  color: white;
}

.edit-button:hover {
  background: #1d4ed8;
}

.delete-button {
  background: #dc2626;
  color: white;
}

.delete-button:hover {
  background: #b91c1c;
}

.delete-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* =========================================================
   Locked
========================================================= */

.locked-info {
  display: flex;
  align-items: flex-start;

  gap: 15px;

  padding: 18px;

  background: #fef3c7;
  color: #92400e;

  border-radius: 10px;
}

.locked-icon {
  font-size: 22px;
}

.locked-info strong {
  display: block;
  margin-bottom: 5px;
}

.locked-info p {
  margin: 0;

  line-height: 1.5;

  font-size: 14px;
}

/* =========================================================
   Error
========================================================= */

.action-error {
  margin-bottom: 20px;

  padding: 15px;

  background: #fee2e2;
  color: #b91c1c;

  border-left: 4px solid #dc2626;
  border-radius: 8px;
}

.state-card,
.error-card {
  background: white;

  padding: 30px;

  border-radius: 12px;

  box-shadow:
    0 4px 15px rgba(0, 0, 0, 0.05);
}

.state-card p {
  margin: 0;
  color: #64748b;
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
  border-radius: 7px;

  background: #2563eb;
  color: white;

  cursor: pointer;
}

/* =========================================================
   Responsive
========================================================= */

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }

  .page {
    padding: 20px;
  }

  .page-header {
    flex-direction: column;
  }

  .meta-grid,
  .ai-meta-grid {
    grid-template-columns: 1fr;
  }

  .ai-title-row {
    flex-wrap: wrap;
  }

  .ai-heading {
    align-items: flex-start;
  }

  .action-card {
    flex-direction: column;
    align-items: stretch;
  }

  .actions {
    flex-direction: column;
  }

  .edit-button,
  .delete-button {
    width: 100%;
  }

  .response-header {
    flex-direction: column;
  }
}
</style>