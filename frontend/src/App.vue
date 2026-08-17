<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import {
  clearToken,
  createCompany,
  deleteCompany,
  downloadBackup,
  getCompanies,
  getDashboard,
  isLoggedIn,
  logout as apiLogout,
  restoreBackup,
  updateCompany,
  updateSettings,
} from './api'
import CompanyModal from './components/CompanyModal.vue'
import LoginView from './components/LoginView.vue'
import MetaModal from './components/MetaModal.vue'
import { formatDateBr, formatMoney, dateGroupLabel } from './utils/format'

const authenticated = ref(isLoggedIn())
const loading = ref(true)
const error = ref('')
const savingMeta = ref(false)
const savingCompany = ref(false)

const dashboard = ref(null)
const companies = ref([])
const statuses = ref({
  novo_contato: 'Novo contato',
  respondeu: 'Respondeu',
  demonstracao: 'Demonstração',
  retorno: 'Retorno',
  assinou: 'Assinou',
  nao_interessado: 'Não interessado',
})
const planos = ref({
  nenhum: 'Nenhum',
  fidelidade: 'Fidelidade',
  completo: 'Completo',
})

const search = ref('')
const statusFilter = ref('todos')
const dateFilter = ref('hoje')
const periodCounts = ref({
  hoje: 0,
  semana: 0,
  anteriores: 0,
  todos: 0,
})
const metaOpen = ref(false)
const companyOpen = ref(false)
const editingCompany = ref(null)
const companyFormError = ref('')
const restoreInput = ref(null)

const meta = computed(() => dashboard.value?.meta || {
  atual: 0,
  meta_mensal: 3000,
  faltam: 3000,
  percentual: 0,
  meta_contatos_semana: 120,
})

const cards = computed(() => dashboard.value?.cards || {
  contatos_semana: { atual: 0, meta: 120, restantes: 120 },
  empresas: { total: 0, responderam: 0 },
  assinaturas: { total: 0, conversao: 0 },
  receita_recorrente: { valor: 0 },
})

const settings = computed(() => dashboard.value?.settings || {
  meta_mensal: 3000,
  meta_contatos_semana: 120,
})

const progressWidth = computed(() => `${Math.min(100, Number(meta.value.percentual || 0))}%`)

const tipText = computed(() => {
  if (!companies.value.length) {
    return 'Seu primeiro passo é cadastrar os contatos de hoje'
  }
  if (meta.value.percentual >= 100) {
    return 'Meta batida! Continue prospectando para crescer ainda mais'
  }
  return 'Continue cadastrando contatos e fechando assinaturas'
})

const groupedCompanies = computed(() => {
  const groups = []
  const map = new Map()

  for (const company of companies.value) {
    const key = company.data_contato || 'sem-data'
    if (!map.has(key)) {
      const group = {
        key,
        label: dateGroupLabel(company.data_contato),
        items: [],
      }
      map.set(key, group)
      groups.push(group)
    }
    map.get(key).items.push(company)
  }

  return groups
})

let searchTimer

function onUnauthorized() {
  authenticated.value = false
  loading.value = false
}

function onLoginSuccess() {
  authenticated.value = true
  refreshAll()
}

async function handleLogout() {
  try {
    await apiLogout()
  } catch {
    clearToken()
  }
  authenticated.value = false
}

async function loadDashboard() {
  const { data } = await getDashboard()
  dashboard.value = data
}

async function loadCompanies() {
  const { data } = await getCompanies({
    q: search.value || undefined,
    status: statusFilter.value,
    periodo: dateFilter.value,
  })
  companies.value = data.data || []
  if (data.counts) periodCounts.value = data.counts
  if (data.statuses) statuses.value = data.statuses
  if (data.planos) planos.value = data.planos
}

async function refreshAll() {
  loading.value = true
  error.value = ''
  try {
    await Promise.all([loadDashboard(), loadCompanies()])
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      'Não foi possível conectar na API. Verifique se o backend está no ar e se VITE_API_URL está certo.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  window.addEventListener('il:unauthorized', onUnauthorized)
  if (authenticated.value) {
    refreshAll()
  } else {
    loading.value = false
  }
})

onUnmounted(() => {
  window.removeEventListener('il:unauthorized', onUnauthorized)
})

watch(statusFilter, () => {
  if (!authenticated.value) return
  loadCompanies().catch(() => {})
})

watch(dateFilter, () => {
  if (!authenticated.value) return
  loadCompanies().catch(() => {})
})

watch(search, () => {
  if (!authenticated.value) return
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    loadCompanies().catch(() => {})
  }, 300)
})

function openNewCompany() {
  editingCompany.value = null
  companyFormError.value = ''
  companyOpen.value = true
}

function openEditCompany(company) {
  editingCompany.value = company
  companyFormError.value = ''
  companyOpen.value = true
}

async function saveMeta(payload) {
  savingMeta.value = true
  error.value = ''
  try {
    await updateSettings(payload)
    metaOpen.value = false
    await loadDashboard()
  } catch (e) {
    error.value = e?.response?.data?.message || 'Erro ao salvar meta.'
  } finally {
    savingMeta.value = false
  }
}

async function saveCompany(payload) {
  savingCompany.value = true
  error.value = ''
  companyFormError.value = ''
  try {
    if (editingCompany.value?.id) {
      await updateCompany(editingCompany.value.id, payload)
    } else {
      await createCompany(payload)
    }
    companyOpen.value = false
    editingCompany.value = null
    await Promise.all([loadDashboard(), loadCompanies()])
  } catch (e) {
    const errors = e?.response?.data?.errors
    const msg =
      (errors && Object.values(errors).flat()[0]) ||
      e?.response?.data?.message ||
      'Erro ao salvar empresa.'
    companyFormError.value = msg
    error.value = msg
  } finally {
    savingCompany.value = false
  }
}

async function removeCompany(company) {
  if (!confirm(`Remover "${company.nome}"?`)) return
  try {
    await deleteCompany(company.id)
    await Promise.all([loadDashboard(), loadCompanies()])
  } catch (e) {
    error.value = e?.response?.data?.message || 'Erro ao remover empresa.'
  }
}

async function handleBackup() {
  try {
    const { data } = await downloadBackup()
    const url = URL.createObjectURL(data)
    const a = document.createElement('a')
    a.href = url
    a.download = `backup-infinite-loyalty-${new Date().toISOString().slice(0, 10)}.json`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    error.value = 'Erro ao baixar backup.'
  }
}

function triggerRestore() {
  restoreInput.value?.click()
}

async function handleRestore(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return
  try {
    const text = await file.text()
    const json = JSON.parse(text)
    await restoreBackup({
      settings: json.settings,
      companies: json.companies || [],
    })
    await refreshAll()
  } catch (e) {
    error.value = 'Arquivo de backup inválido.'
  }
}
</script>

<template>
  <LoginView v-if="!authenticated" @success="onLoginSuccess" />

  <div v-else class="page">
    <header class="hero">
      <div class="container hero-top">
        <div class="brand">
          <div class="logo" aria-hidden="true">
            <svg viewBox="0 0 48 48" fill="none">
              <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2" />
              <path
                d="M16 24c0-5 3.2-8 7-8 5.5 0 7.5 8 13 8 3.5 0 6-2.8 6-6s-2.5-6-6-6c-5.5 0-7.5 8-13 8-3.8 0-7-3-7-8"
                stroke="currentColor"
                stroke-width="2.4"
                stroke-linecap="round"
              />
            </svg>
          </div>
          <div>
            <h1>Infinite Loyalty</h1>
            <p>Painel comercial</p>
          </div>
        </div>

        <div class="hero-actions">
          <button class="btn ghost-dark" type="button" @click="handleLogout">Sair</button>
          <button class="btn gold" type="button" @click="openNewCompany">+ Nova empresa</button>
        </div>
      </div>

      <div class="container meta-block">
        <div class="meta-left">
          <div class="meta-label-row">
            <p class="eyebrow">META MENSAL</p>
            <button class="btn meta-btn" type="button" @click="metaOpen = true">
              Definir meta
            </button>
          </div>
          <div class="meta-value">
            <strong>{{ formatMoney(meta.atual) }}</strong>
            <span>de {{ formatMoney(meta.meta_mensal) }}</span>
          </div>
          <p class="meta-tip">{{ tipText }}</p>
        </div>

        <div class="meta-right">
          <strong class="pct">{{ Number(meta.percentual).toFixed(1) }}%</strong>
          <div class="bar"><i :style="{ width: progressWidth }" /></div>
          <p>Faltam {{ formatMoney(meta.faltam) }}</p>
        </div>
      </div>
    </header>

    <main class="container content">
      <p v-if="error" class="banner error">{{ error }}</p>
      <p v-if="loading" class="banner">Carregando painel...</p>

      <section class="cards">
        <article class="card">
          <p class="card-title">Contatos na semana</p>
          <p class="card-value">
            {{ cards.contatos_semana.atual }} / {{ cards.contatos_semana.meta }}
          </p>
          <p class="card-sub">{{ cards.contatos_semana.restantes }} restantes</p>
        </article>

        <article class="card">
          <p class="card-title">Empresas cadastradas</p>
          <p class="card-value">{{ cards.empresas.total }}</p>
          <p class="card-sub">{{ cards.empresas.responderam }} responderam</p>
        </article>

        <article class="card">
          <p class="card-title">Assinaturas</p>
          <p class="card-value">{{ cards.assinaturas.total }}</p>
          <p class="card-sub">{{ Number(cards.assinaturas.conversao).toFixed(1) }}% de conversão</p>
        </article>

        <article class="card">
          <p class="card-title">Receita recorrente</p>
          <p class="card-value">{{ formatMoney(cards.receita_recorrente.valor) }}</p>
          <p class="card-sub">por mês</p>
        </article>
      </section>

      <section class="panel">
        <div class="panel-head">
          <div>
            <p class="eyebrow dark">PROSPECÇÃO</p>
            <h2>Empresas</h2>
          </div>
          <div class="panel-actions">
            <button class="btn outline" type="button" @click="handleBackup">Baixar backup</button>
            <button class="btn outline" type="button" @click="triggerRestore">Restaurar</button>
            <input
              ref="restoreInput"
              class="sr-only"
              type="file"
              accept="application/json,.json"
              @change="handleRestore"
            />
          </div>
        </div>

        <div class="date-tabs">
          <button
            type="button"
            class="date-tab"
            :class="{ active: dateFilter === 'hoje' }"
            @click="dateFilter = 'hoje'"
          >
            Hoje <small>{{ periodCounts.hoje }}</small>
          </button>
          <button
            type="button"
            class="date-tab"
            :class="{ active: dateFilter === 'semana' }"
            @click="dateFilter = 'semana'"
          >
            Esta semana <small>{{ periodCounts.semana }}</small>
          </button>
          <button
            type="button"
            class="date-tab"
            :class="{ active: dateFilter === 'anteriores' }"
            @click="dateFilter = 'anteriores'"
          >
            Dias anteriores <small>{{ periodCounts.anteriores }}</small>
          </button>
          <button
            type="button"
            class="date-tab"
            :class="{ active: dateFilter === 'todos' }"
            @click="dateFilter = 'todos'"
          >
            Todos <small>{{ periodCounts.todos }}</small>
          </button>
        </div>

        <div class="filters">
          <label class="search">
            <span class="sr-only">Buscar</span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" fill="none" />
              <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            <input
              v-model="search"
              type="search"
              placeholder="Buscar empresa, contato ou nicho"
            />
          </label>

          <select v-model="statusFilter">
            <option value="todos">Todos os status</option>
            <option v-for="(label, value) in statuses" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>EMPRESA</th>
                <th>CONTATO</th>
                <th>STATUS</th>
                <th>DATA CADASTRO</th>
                <th>PRÓXIMO RETORNO</th>
                <th>PLANO</th>
                <th></th>
              </tr>
            </thead>
            <tbody v-if="companies.length">
              <template v-for="group in groupedCompanies" :key="group.key">
                <tr class="date-group-row">
                  <td colspan="7">
                    <div class="date-group">
                      <strong>{{ group.label }}</strong>
                      <span>{{ group.items.length }} contato(s)</span>
                    </div>
                  </td>
                </tr>
                <tr v-for="company in group.items" :key="company.id">
                  <td>
                    <strong>{{ company.nome }}</strong>
                    <small v-if="company.nicho">{{ company.nicho }}</small>
                  </td>
                  <td>{{ company.contato || '—' }}</td>
                  <td><span class="pill">{{ company.status_label }}</span></td>
                  <td>{{ formatDateBr(company.data_contato) }}</td>
                  <td>{{ formatDateBr(company.proximo_retorno) }}</td>
                  <td>{{ company.plano_label }}</td>
                  <td class="row-actions">
                    <button type="button" @click="openEditCompany(company)">Editar</button>
                    <button type="button" class="danger" @click="removeCompany(company)">Excluir</button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>

          <div v-if="!companies.length && !loading" class="empty">
            <div class="empty-icon" aria-hidden="true">∞</div>
            <p v-if="dateFilter === 'hoje'">Nenhum contato cadastrado hoje</p>
            <p v-else>Nenhuma empresa por aqui</p>
            <span>
              {{
                dateFilter === 'hoje'
                  ? 'Cadastre os contatos de hoje nesta aba. Os de outros dias ficam em Dias anteriores.'
                  : 'Cadastre seu primeiro contato e acompanhe a evolução.'
              }}
            </span>
            <button class="btn gold" type="button" @click="openNewCompany">+ Nova empresa</button>
          </div>
        </div>
      </section>
    </main>

    <MetaModal
      :open="metaOpen"
      :settings="settings"
      :saving="savingMeta"
      @close="metaOpen = false"
      @save="saveMeta"
    />

    <CompanyModal
      :open="companyOpen"
      :company="editingCompany"
      :statuses="statuses"
      :planos="planos"
      :saving="savingCompany"
      :form-error="companyFormError"
      @close="companyOpen = false"
      @save="saveCompany"
    />
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background:
    radial-gradient(1000px 420px at 10% -10%, rgba(196, 160, 90, 0.12), transparent 60%),
    var(--bg-page);
}

.container {
  width: min(1120px, calc(100% - 32px));
  margin: 0 auto;
}

.hero {
  background:
    linear-gradient(180deg, #1b1613 0%, #221c18 70%, #2a231e 100%);
  color: var(--text-on-dark);
  padding: 28px 0 42px;
}

.hero-top,
.meta-block,
.panel-head,
.filters,
.meta-label-row,
.hero-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.btn.ghost-dark {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: #f7f2ea;
}

.brand {
  display: flex;
  align-items: center;
  gap: 14px;
}

.logo {
  width: 48px;
  height: 48px;
  color: var(--gold);
}

.logo svg {
  width: 100%;
  height: 100%;
}

.brand h1 {
  margin: 0;
  font-family: var(--font-serif);
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.brand p {
  margin: 2px 0 0;
  color: var(--text-on-dark-muted);
  font-size: 14px;
}

.meta-block {
  margin-top: 34px;
  align-items: flex-end;
  gap: 28px;
}

.eyebrow {
  margin: 0;
  color: var(--gold);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.14em;
}

.eyebrow.dark {
  color: var(--gold);
}

.meta-label-row {
  justify-content: flex-start;
  margin-bottom: 10px;
}

.meta-value {
  display: flex;
  align-items: baseline;
  gap: 10px;
  flex-wrap: wrap;
}

.meta-value strong {
  font-size: clamp(34px, 5vw, 48px);
  font-weight: 700;
  letter-spacing: -0.03em;
}

.meta-value span,
.meta-tip,
.meta-right p {
  color: var(--text-on-dark-muted);
}

.meta-tip {
  margin: 10px 0 0;
  font-size: 14px;
}

.meta-right {
  min-width: min(320px, 100%);
  text-align: right;
}

.pct {
  display: block;
  color: var(--gold);
  font-size: 28px;
  margin-bottom: 10px;
}

.bar {
  height: 4px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  overflow: hidden;
  margin-bottom: 10px;
}

.bar i {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, #b08d48, var(--gold));
  border-radius: inherit;
  transition: width 0.4s ease;
}

.meta-right p {
  margin: 0;
  font-size: 14px;
}

.content {
  padding: 28px 0 56px;
  margin-top: -8px;
}

.cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 18px;
}

.card,
.panel {
  background: var(--bg-card);
  border: 1px solid rgba(210, 201, 187, 0.7);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

.card {
  padding: 18px 18px 16px;
}

.card-title {
  margin: 0;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 600;
}

.card-value {
  margin: 12px 0 6px;
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.card-sub {
  margin: 0;
  color: var(--text-muted);
  font-size: 13px;
}

.panel {
  padding: 22px;
}

.panel-head h2 {
  margin: 4px 0 0;
  font-family: var(--font-serif);
  font-size: 34px;
}

.panel-actions,
.row-actions {
  display: flex;
  gap: 8px;
}

.filters {
  margin: 20px 0 10px;
}

.date-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 18px;
}

.date-tab {
  border: 1px solid var(--border-strong);
  background: #fff;
  border-radius: 999px;
  padding: 8px 14px;
  font-weight: 600;
  color: var(--text-muted);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.date-tab small {
  background: #f3efe7;
  color: var(--text);
  border-radius: 999px;
  padding: 2px 8px;
  font-size: 12px;
}

.date-tab.active {
  background: rgba(196, 160, 90, 0.14);
  border-color: rgba(196, 160, 90, 0.45);
  color: #8a6b2f;
}

.date-tab.active small {
  background: rgba(196, 160, 90, 0.22);
  color: #8a6b2f;
}

.date-group-row td {
  border-bottom: 0;
  padding-top: 18px;
  padding-bottom: 6px;
  background: transparent;
}

.date-group {
  display: flex;
  align-items: baseline;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 10px;
  background: #f7f3ec;
}

.date-group strong {
  font-size: 14px;
  color: var(--text);
}

.date-group span {
  font-size: 12px;
  color: var(--text-muted);
}

.search {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid var(--border-strong);
  border-radius: 12px;
  padding: 0 14px;
  background: #fff;
}

.search svg {
  width: 18px;
  height: 18px;
  color: var(--text-muted);
  flex: 0 0 auto;
}

.search input,
.filters select {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  padding: 13px 0;
}

.filters select {
  width: 180px;
  border: 1px solid var(--border-strong);
  border-radius: 12px;
  padding: 12px 14px;
  background: #fff;
}

.table-wrap {
  overflow-x: auto;
  min-height: 280px;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  text-align: left;
  padding: 14px 10px;
  border-bottom: 1px solid var(--border);
  vertical-align: top;
}

th {
  color: #9a9186;
  font-size: 11px;
  letter-spacing: 0.08em;
  font-weight: 700;
}

td strong {
  display: block;
}

td small {
  display: block;
  margin-top: 3px;
  color: var(--text-muted);
}

.pill {
  display: inline-flex;
  padding: 4px 10px;
  border-radius: 999px;
  background: var(--gold-soft);
  color: #8a6b2f;
  font-size: 12px;
  font-weight: 600;
}

.row-actions button {
  border: 0;
  background: transparent;
  color: var(--text-muted);
  font-weight: 600;
  padding: 0;
}

.row-actions button:hover {
  color: var(--text);
}

.row-actions .danger:hover {
  color: var(--danger);
}

.empty {
  display: grid;
  place-items: center;
  text-align: center;
  gap: 8px;
  padding: 56px 16px 40px;
  color: var(--text-muted);
}

.empty-icon {
  width: 42px;
  height: 42px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  background: var(--gold-soft);
  color: var(--gold);
  font-size: 22px;
  margin-bottom: 6px;
}

.empty p {
  margin: 0;
  color: var(--text);
  font-size: 18px;
  font-weight: 700;
}

.empty span {
  margin-bottom: 10px;
}

.btn {
  border: 0;
  border-radius: 10px;
  padding: 11px 16px;
  font-weight: 650;
}

.btn.gold {
  background: var(--gold);
  color: #fff;
}

.btn.gold:hover {
  background: var(--gold-hover);
}

.btn.outline {
  background: #fff;
  border: 1px solid var(--border-strong);
  color: var(--text);
}

.btn.meta-btn {
  background: rgba(196, 160, 90, 0.14);
  color: var(--gold);
  border: 1px solid rgba(196, 160, 90, 0.35);
  padding: 7px 12px;
  font-size: 13px;
}

.banner {
  margin: 0 0 14px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #fff;
  border: 1px solid var(--border);
  color: var(--text-muted);
}

.banner.error {
  color: #7a1d16;
  background: #fff5f4;
  border-color: #f0c7c3;
}

@media (max-width: 960px) {
  .cards {
    grid-template-columns: 1fr 1fr;
  }

  .meta-block,
  .panel-head,
  .hero-top {
    flex-direction: column;
    align-items: flex-start;
  }

  .meta-right {
    width: 100%;
    text-align: left;
  }
}

@media (max-width: 640px) {
  .cards {
    grid-template-columns: 1fr;
  }

  .filters {
    flex-direction: column;
    align-items: stretch;
  }

  .filters select {
    width: 100%;
  }

  .panel-actions {
    width: 100%;
    flex-wrap: wrap;
  }
}
</style>
