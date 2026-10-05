<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/axios'

const route = useRoute()
const router = useRouter()

const user = ref(null)
const complaint = ref(null)

const loading = ref(true)
const error = ref('')
const success = ref('')

const selectedStatus = ref('')
const responseText = ref('')

const updatingStatus = ref(false)
const sendingResponse = ref(false)

/*
|--------------------------------------------------------------------------
| Load Detail
|--------------------------------------------------------------------------
*/

const loadComplaint = async () => {
  loading.value = true
  error.value = ''

  try {
    const [userResponse, complaintResponse] =
      await Promise.all([
        api.get('/me'),
        api.get(`/complaints/${route.params.id}`),
      ])

    user.value = userResponse.data.data

    if (user.value.role !== 'admin') {
      router.push('/dashboard')
      return
    }

    complaint.value = complaintResponse.data.data

    selectedStatus.value =
      complaint.value.status

    localStorage.setItem(
      'user',
      JSON.stringify(user.value)
    )
  } catch (err) {
    console.error(err)

    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')

      router.push('/login')
      return
    }

    if (err.response?.status === 403) {
      error.value =
        'Anda tidak memiliki akses ke pengaduan ini.'
      return
    }

    if (err.response?.status === 404) {
      error.value =
        'Data pengaduan tidak ditemukan.'
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
| Update Status
|--------------------------------------------------------------------------
*/

const updateStatus = async () => {
  if (!complaint.value) return

  error.value = ''
  success.value = ''

  updatingStatus.value = true

  try {
    const response = await api.put(
        `/complaints/${complaint.value.id}`,
        {
            status: selectedStatus.value,
        }
    )

    complaint.value = response.data.data

    selectedStatus.value =
      complaint.value.status

    success.value =
      'Status pengaduan berhasil diperbarui.'
  } catch (err) {
    console.error(err)

    error.value =
      err.response?.data?.message ||
      'Status pengaduan gagal diperbarui.'
  } finally {
    updatingStatus.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Kirim Tanggapan
|--------------------------------------------------------------------------
*/

const sendResponse = async () => {
  error.value = ''
  success.value = ''

  if (!responseText.value.trim()) {
    error.value =
      'Tanggapan tidak boleh kosong.'
    return
  }

  sendingResponse.value = true

  try {
    await api.post(
      `/complaints/${complaint.value.id}/responses`,
      {
        response: responseText.value,
      }
    )

    responseText.value = ''

    success.value =
      'Tanggapan berhasil dikirim.'

    /*
    |--------------------------------------------------------------------------
    | Reload
    |--------------------------------------------------------------------------
    |
    | ResponseController dapat mengubah status dari
    | "menunggu" menjadi "diproses".
    | Karena itu kita ambil ulang detail pengaduan.
    |
    */

    const detailResponse =
      await api.get(
        `/complaints/${complaint.value.id}`
      )

    complaint.value =
      detailResponse.data.data

    selectedStatus.value =
      complaint.value.status
  } catch (err) {
    console.error(err)

    if (err.response?.status === 403) {
      error.value =
        'Hanya admin yang dapat memberikan tanggapan.'
      return
    }

    error.value =
      err.response?.data?.message ||
      'Tanggapan gagal dikirim.'
  } finally {
    sendingResponse.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const formatDate = (date) => {
  if (!date) return '-'

  return new Date(date).toLocaleString(
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

  return labels[priority] || '-'
}

const sentimentLabel = (sentiment) => {
  const labels = {
    positif: 'Positif',
    netral: 'Netral',
    negatif: 'Negatif',
  }

  return labels[sentiment] || '-'
}

const hasAiAnalysis = () => {
  return Boolean(
    complaint.value?.ai_summary ||
    complaint.value?.ai_priority ||
    complaint.value?.ai_sentiment
  )
}

const backToComplaints = () => {
  router.push('/admin/complaints')
}

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (err) {
    console.error(err)
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')

    router.push('/login')
  }
}

onMounted(() => {
  loadComplaint()
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

    <!-- CONTENT -->
    <main class="main-content">

      <button
        class="back-button"
        @click="backToComplaints"
      >
        ← Kembali ke Semua Pengaduan
      </button>

      <!-- LOADING -->
      <div
        v-if="loading"
        class="state-card"
      >
        Memuat detail pengaduan...
      </div>

      <!-- ERROR TANPA DATA -->
      <div
        v-else-if="error && !complaint"
        class="error-card"
      >
        {{ error }}
      </div>

      <!-- DETAIL -->
      <template v-else-if="complaint">

        <!-- HEADER -->
        <div class="page-header">

          <div>
            <div class="header-label">
              Detail Pengaduan
            </div>

            <h1>
              {{ complaint.title }}
            </h1>

            <p>
              ID Pengaduan #{{ complaint.id }}
            </p>
          </div>

          <span
            class="status"
            :class="complaint.status"
          >
            {{ statusLabel(complaint.status) }}
          </span>

        </div>

        <!-- MESSAGE -->
        <div
          v-if="success"
          class="success-message"
        >
          {{ success }}
        </div>

        <div
          v-if="error"
          class="error-message"
        >
          {{ error }}
        </div>

        <div class="content-grid">

          <!-- LEFT -->
          <div class="left-column">

            <!-- INFORMASI MAHASISWA -->
            <section class="card">

              <div class="card-header">
                <h2>
                  Informasi Mahasiswa
                </h2>
              </div>

              <div class="info-grid">

                <div class="info-item">
                  <span>Nama</span>
                  <strong>
                    {{
                      complaint.user?.name ||
                      '-'
                    }}
                  </strong>
                </div>

                <div class="info-item">
                  <span>NIM</span>
                  <strong>
                    {{
                      complaint.user?.nim ||
                      '-'
                    }}
                  </strong>
                </div>

                <div class="info-item">
                  <span>Email</span>
                  <strong>
                    {{
                      complaint.user?.email ||
                      '-'
                    }}
                  </strong>
                </div>

              </div>

            </section>

            <!-- DETAIL PENGADUAN -->
            <section class="card">

              <div class="card-header">
                <h2>
                  Informasi Pengaduan
                </h2>
              </div>

              <div class="info-grid">

                <div class="info-item">
                  <span>Jenis</span>

                  <strong>
                    {{
                      typeLabel(
                        complaint.type
                      )
                    }}
                  </strong>
                </div>

                <div class="info-item">
                  <span>Kategori</span>

                  <strong>
                    {{
                      complaint.category?.name ||
                      '-'
                    }}
                  </strong>
                </div>

                <div class="info-item">
                  <span>Dikirim</span>

                  <strong>
                    {{
                      formatDate(
                        complaint.created_at
                      )
                    }}
                  </strong>
                </div>

              </div>

              <div class="description-section">

                <span class="section-label">
                  Isi Pengaduan / Aspirasi
                </span>

                <div class="description-box">
                  {{ complaint.description }}
                </div>

              </div>

            </section>

            <!-- ANALISIS AI -->
            <section class="card ai-card">

              <div class="ai-header">

                <div class="ai-title-area">

                  <div class="ai-icon">
                    AI
                  </div>

                  <div>
                    <div class="ai-heading-row">
                      <h2>Analisis AI</h2>

                      <span class="ai-badge">
                        DeepSeek
                      </span>
                    </div>

                    <p>
                      Analisis otomatis menggunakan AI melalui AtmoRouter.
                    </p>
                  </div>

                </div>

              </div>

              <div
                v-if="hasAiAnalysis()"
                class="ai-content"
              >

                <div class="ai-summary">

                  <span class="ai-label">
                    Ringkasan Laporan
                  </span>

                  <p>
                    {{ complaint.ai_summary || '-' }}
                  </p>

                </div>

                <div class="ai-detail-grid">

                  <div class="ai-detail-box analysis-box">

                    <div class="ai-detail-heading">
                      <span class="ai-detail-icon">
                        A
                      </span>

                      <div>
                        <span class="ai-label">
                          Analisis Masalah
                        </span>

                        <small>
                          Dampak dan konteks laporan mahasiswa
                        </small>
                      </div>
                    </div>

                    <p>
                      {{ complaint.ai_analysis || '-' }}
                    </p>

                  </div>

                  <div class="ai-detail-box recommendation-box">

                    <div class="ai-detail-heading">
                      <span class="ai-detail-icon">
                        R
                      </span>

                      <div>
                        <span class="ai-label">
                          Rekomendasi Penanganan
                        </span>

                        <small>
                          Saran awal yang dapat dipertimbangkan admin
                        </small>
                      </div>
                    </div>

                    <p>
                      {{ complaint.ai_recommendation || '-' }}
                    </p>

                  </div>

                </div>

                <div class="ai-result-grid">

                  <div class="ai-result-item">

                    <span class="ai-label">
                      Tingkat Prioritas
                    </span>

                    <span
                      class="ai-value-badge priority"
                      :class="complaint.ai_priority"
                    >
                      <span class="badge-dot"></span>

                      {{
                        priorityLabel(
                          complaint.ai_priority
                        )
                      }}
                    </span>

                  </div>

                  <div class="ai-result-item">

                    <span class="ai-label">
                      Sentimen
                    </span>

                    <span
                      class="ai-value-badge sentiment"
                      :class="complaint.ai_sentiment"
                    >
                      <span class="badge-dot"></span>

                      {{
                        sentimentLabel(
                          complaint.ai_sentiment
                        )
                      }}
                    </span>

                  </div>

                  <div class="ai-result-item">

                    <span class="ai-label">
                      Model AI
                    </span>

                    <strong class="ai-model">
                      DeepSeek V4.1 Flash
                    </strong>

                  </div>

                </div>

                <div class="ai-disclaimer">

                  <span class="ai-disclaimer-icon">
                    i
                  </span>

                  <p>
                    Hasil analisis AI digunakan sebagai pendukung keputusan.
                    Penanganan dan keputusan akhir tetap dilakukan oleh administrator.
                  </p>

                </div>

              </div>

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
                    Pengaduan berhasil tersimpan, tetapi belum memiliki hasil analisis AI.
                  </p>
                </div>

              </div>

            </section>

            <!-- TANGGAPAN -->
            <section class="card">

              <div class="card-header">
                <div>
                  <h2>
                    Riwayat Tanggapan
                  </h2>

                  <p>
                    Tanggapan yang telah diberikan
                    kepada mahasiswa.
                  </p>
                </div>
              </div>

              <div
                v-if="
                  !complaint.responses ||
                  complaint.responses.length === 0
                "
                class="no-response"
              >
                Belum ada tanggapan untuk
                pengaduan ini.
              </div>

              <div
                v-else
                class="response-list"
              >

                <div
                  v-for="item in complaint.responses"
                  :key="item.id"
                  class="response-item"
                >

                  <div class="response-header">

                    <div>
                      <strong>
                        {{
                          item.user?.name ||
                          'Administrator'
                        }}
                      </strong>

                      <span class="admin-badge">
                        Admin
                      </span>
                    </div>

                    <small>
                      {{
                        formatDate(
                          item.created_at
                        )
                      }}
                    </small>

                  </div>

                  <p>
                    {{ item.response }}
                  </p>

                </div>

              </div>

            </section>

          </div>

          <!-- RIGHT -->
          <div class="right-column">

            <!-- UPDATE STATUS -->
            <section class="card">

              <div class="card-header">
                <h2>
                  Kelola Status
                </h2>
              </div>

              <label class="form-label">
                Status Pengaduan
              </label>

              <select
                v-model="selectedStatus"
                class="form-control"
              >
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

              <button
                class="primary-button"
                :disabled="updatingStatus"
                @click="updateStatus"
              >
                {{
                  updatingStatus
                    ? 'Menyimpan...'
                    : 'Simpan Status'
                }}
              </button>

            </section>

            <!-- RESPONSE FORM -->
            <section class="card">

              <div class="card-header">
                <div>
                  <h2>
                    Berikan Tanggapan
                  </h2>

                  <p>
                    Tanggapan akan dapat dilihat
                    oleh mahasiswa.
                  </p>
                </div>
              </div>

              <label class="form-label">
                Tanggapan Admin
              </label>

              <textarea
                v-model="responseText"
                class="form-control textarea"
                placeholder="Tuliskan tanggapan untuk mahasiswa..."
                rows="7"
              ></textarea>

              <div class="character-info">
                {{ responseText.length }}
                karakter
              </div>

              <button
                class="response-button"
                :disabled="sendingResponse"
                @click="sendResponse"
              >
                {{
                  sendingResponse
                    ? 'Mengirim...'
                    : 'Kirim Tanggapan'
                }}
              </button>

            </section>

            <!-- INFO -->
            <div class="information-card">

              <strong>
                Informasi
              </strong>

              <p>
                Jika pengaduan masih berstatus
                <b>Menunggu</b>, memberikan
                tanggapan pertama akan mengubah
                status menjadi
                <b>Diproses</b>.
              </p>

            </div>

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

/* SIDEBAR */

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

  border-radius: 10px;

  background: #2563eb;

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

  border-radius: 8px;

  color: #cbd5e1;

  text-decoration: none;
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

/* MAIN */

.main-content {
  margin-left: 260px;

  min-height: 100vh;

  padding: 35px;
}

.back-button {
  margin-bottom: 22px;

  padding: 9px 0;

  border: none;

  background: transparent;

  color: #2563eb;

  font-weight: 600;

  cursor: pointer;
}

/* HEADER */

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;

  gap: 20px;

  margin-bottom: 25px;
}

.header-label {
  margin-bottom: 8px;

  color: #2563eb;

  font-size: 13px;
  font-weight: 700;

  text-transform: uppercase;
}

.page-header h1 {
  margin: 0 0 7px;

  color: #0f172a;

  font-size: 28px;
}

.page-header p {
  margin: 0;

  color: #64748b;
}

/* GRID */

.content-grid {
  display: grid;

  grid-template-columns:
    minmax(0, 2fr)
    minmax(300px, 1fr);

  gap: 22px;

  align-items: start;
}

.left-column,
.right-column {
  display: flex;
  flex-direction: column;

  gap: 20px;
}

/* CARD */

.card {
  padding: 22px;

  background: white;

  border-radius: 12px;

  box-shadow:
    0 3px 12px rgba(0, 0, 0, 0.05);
}

.card-header {
  margin-bottom: 20px;

  padding-bottom: 15px;

  border-bottom: 1px solid #e2e8f0;
}

.card-header h2 {
  margin: 0;

  color: #1e293b;

  font-size: 17px;
}

.card-header p {
  margin: 5px 0 0;

  color: #64748b;

  font-size: 12px;
}

/* INFO */

.info-grid {
  display: grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;

  gap: 5px;
}

.info-item span,
.section-label {
  color: #64748b;

  font-size: 12px;
  font-weight: 600;
}

.info-item strong {
  color: #1e293b;

  font-size: 14px;

  word-break: break-word;
}

.description-section {
  margin-top: 25px;
}

.description-box {
  margin-top: 8px;

  padding: 16px;

  min-height: 120px;

  border: 1px solid #e2e8f0;
  border-radius: 8px;

  background: #f8fafc;

  color: #334155;

  line-height: 1.7;

  white-space: pre-wrap;
}

/* AI ANALYSIS */

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

.ai-header {
  margin-bottom: 20px;
  padding-bottom: 17px;

  border-bottom: 1px solid #e2e8f0;
}

.ai-title-area {
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

.ai-heading-row {
  display: flex;
  align-items: center;

  gap: 9px;
}

.ai-heading-row h2 {
  margin: 0;

  color: #1e293b;

  font-size: 17px;
}

.ai-header p {
  margin: 5px 0 0;

  color: #64748b;

  font-size: 12px;
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

  gap: 20px;
}

.ai-summary {
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

.ai-summary p {
  margin: 0;

  color: #334155;

  font-size: 14px;
  line-height: 1.7;
}

.ai-detail-grid {
  display: grid;

  grid-template-columns:
    repeat(2, minmax(0, 1fr));

  gap: 14px;
}

.ai-detail-box {
  padding: 17px;

  border: 1px solid #e2e8f0;
  border-radius: 10px;

  background: #ffffff;
}

.analysis-box {
  border-color: #bfdbfe;
  background: #f8fbff;
}

.recommendation-box {
  border-color: #ddd6fe;
  background: #fbfaff;
}

.ai-detail-heading {
  display: flex;
  align-items: flex-start;

  gap: 10px;

  margin-bottom: 12px;
}

.ai-detail-heading .ai-label {
  margin-bottom: 3px;
}

.ai-detail-heading small {
  display: block;

  color: #94a3b8;

  font-size: 10px;
  line-height: 1.4;
}

.ai-detail-icon {
  width: 28px;
  height: 28px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 8px;

  background: #dbeafe;
  color: #2563eb;

  font-size: 11px;
  font-weight: 800;
}

.recommendation-box .ai-detail-icon {
  background: #ede9fe;
  color: #7c3aed;
}

.ai-detail-box p {
  margin: 0;

  color: #334155;

  font-size: 13px;
  line-height: 1.75;

  white-space: pre-wrap;
}

.ai-result-grid {
  display: grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap: 14px;
}

.ai-result-item {
  padding: 15px;

  border: 1px solid #e2e8f0;
  border-radius: 10px;

  background: #ffffff;
}

.ai-value-badge {
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

.ai-value-badge.priority.rendah {
  background: #dcfce7;
  color: #15803d;
}

.ai-value-badge.priority.sedang {
  background: #fef3c7;
  color: #b45309;
}

.ai-value-badge.priority.tinggi {
  background: #fee2e2;
  color: #b91c1c;
}

.ai-value-badge.sentiment.positif {
  background: #dcfce7;
  color: #15803d;
}

.ai-value-badge.sentiment.netral {
  background: #e2e8f0;
  color: #475569;
}

.ai-value-badge.sentiment.negatif {
  background: #fee2e2;
  color: #b91c1c;
}

.ai-model {
  display: block;

  color: #1e293b;

  font-size: 13px;
}

.ai-disclaimer {
  display: flex;
  align-items: flex-start;

  gap: 10px;

  padding: 12px 14px;

  border-radius: 8px;

  background: #f8fafc;
}

.ai-disclaimer-icon {
  width: 20px;
  height: 20px;

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

.ai-disclaimer p {
  margin: 0;

  color: #64748b;

  font-size: 11px;
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

/* STATUS */

.status {
  display: inline-block;

  padding: 7px 13px;

  border-radius: 20px;

  font-size: 12px;
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

/* FORM */

.form-label {
  display: block;

  margin-bottom: 7px;

  color: #334155;

  font-size: 13px;
  font-weight: 600;
}

.form-control {
  width: 100%;

  padding: 11px 12px;

  border: 1px solid #cbd5e1;
  border-radius: 8px;

  background: white;

  font-family: inherit;

  resize: vertical;
}

.form-control:focus {
  outline: none;

  border-color: #2563eb;
}

.textarea {
  line-height: 1.6;
}

.primary-button,
.response-button {
  width: 100%;

  margin-top: 14px;

  padding: 11px 15px;

  border: none;
  border-radius: 8px;

  color: white;

  font-weight: 600;

  cursor: pointer;
}

.primary-button {
  background: #2563eb;
}

.primary-button:hover {
  background: #1d4ed8;
}

.response-button {
  background: #059669;
}

.response-button:hover {
  background: #047857;
}

.primary-button:disabled,
.response-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.character-info {
  margin-top: 6px;

  color: #94a3b8;

  font-size: 11px;

  text-align: right;
}

/* RESPONSE */

.no-response {
  padding: 20px;

  border: 1px dashed #cbd5e1;
  border-radius: 8px;

  color: #64748b;

  text-align: center;

  font-size: 13px;
}

.response-list {
  display: flex;
  flex-direction: column;

  gap: 15px;
}

.response-item {
  padding: 16px;

  border-left: 4px solid #2563eb;
  border-radius: 8px;

  background: #f8fafc;
}

.response-header {
  display: flex;
  justify-content: space-between;

  gap: 15px;

  margin-bottom: 10px;
}

.response-header strong {
  color: #1e293b;
}

.response-header small {
  color: #94a3b8;
}

.admin-badge {
  margin-left: 7px;

  padding: 3px 7px;

  border-radius: 10px;

  background: #dbeafe;
  color: #1d4ed8;

  font-size: 10px;
  font-weight: 700;
}

.response-item p {
  margin: 0;

  color: #475569;

  line-height: 1.6;

  white-space: pre-wrap;
}

/* MESSAGES */

.success-message {
  margin-bottom: 20px;

  padding: 13px 15px;

  border: 1px solid #bbf7d0;
  border-radius: 8px;

  background: #f0fdf4;
  color: #15803d;
}

.error-message,
.error-card {
  margin-bottom: 20px;

  padding: 13px 15px;

  border: 1px solid #fecaca;
  border-radius: 8px;

  background: #fef2f2;
  color: #b91c1c;
}

.state-card {
  padding: 30px;

  border-radius: 12px;

  background: white;

  color: #64748b;
}

/* INFO CARD */

.information-card {
  padding: 16px;

  border: 1px solid #bfdbfe;
  border-radius: 10px;

  background: #eff6ff;

  color: #1e40af;
}

.information-card strong {
  font-size: 13px;
}

.information-card p {
  margin: 7px 0 0;

  font-size: 12px;

  line-height: 1.6;
}

/* RESPONSIVE */

@media (max-width: 1050px) {
  .content-grid {
    grid-template-columns: 1fr;
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

  .info-grid {
    grid-template-columns: 1fr;
  }

  .page-header {
    flex-direction: column;
  }

  .response-header {
    flex-direction: column;
  }

  .ai-result-grid,
  .ai-detail-grid {
    grid-template-columns: 1fr;
  }

  .ai-heading-row {
    flex-wrap: wrap;
  }
}
</style>