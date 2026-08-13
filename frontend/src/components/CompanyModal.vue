<script setup>
import { reactive, watch } from 'vue'
import { todayIso } from '../utils/format'

const props = defineProps({
  open: Boolean,
  company: { type: Object, default: null },
  statuses: { type: Object, default: () => ({}) },
  planos: { type: Object, default: () => ({}) },
  saving: Boolean,
})

const emit = defineEmits(['close', 'save'])

const emptyForm = () => ({
  nome: '',
  contato: '',
  nicho: '',
  status: 'novo_contato',
  plano: 'nenhum',
  data_contato: todayIso(),
  proximo_retorno: '',
  observacao: '',
})

const form = reactive(emptyForm())

watch(
  () => props.open,
  (open) => {
    if (!open) return
    const base = props.company || emptyForm()
    Object.assign(form, {
      ...emptyForm(),
      ...base,
      data_contato: base.data_contato || todayIso(),
      proximo_retorno: base.proximo_retorno || '',
    })
  },
)

function submit() {
  emit('save', {
    ...form,
    proximo_retorno: form.proximo_retorno || null,
    data_contato: form.data_contato || null,
  })
}
</script>

<template>
  <div v-if="open" class="overlay" @click.self="$emit('close')">
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-head">
        <div>
          <p class="eyebrow">PROSPECÇÃO</p>
          <h2>{{ company ? 'Editar empresa' : 'Nova empresa' }}</h2>
        </div>
        <button class="icon-btn" type="button" aria-label="Fechar" @click="$emit('close')">×</button>
      </div>

      <form class="form" @submit.prevent="submit">
        <label class="full">
          <span>Nome da empresa*</span>
          <input v-model="form.nome" type="text" required />
        </label>

        <label>
          <span>WhatsApp / Instagram</span>
          <input v-model="form.contato" type="text" />
        </label>

        <label>
          <span>Nicho</span>
          <input v-model="form.nicho" type="text" placeholder="Ex.: Barbearia" />
        </label>

        <label>
          <span>Status</span>
          <select v-model="form.status">
            <option v-for="(label, value) in statuses" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
        </label>

        <label>
          <span>Plano</span>
          <select v-model="form.plano">
            <option v-for="(label, value) in planos" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
        </label>

        <label>
          <span>Data do contato</span>
          <input v-model="form.data_contato" type="date" />
        </label>

        <label>
          <span>Próximo retorno</span>
          <input v-model="form.proximo_retorno" type="date" />
        </label>

        <label class="full">
          <span>Observação</span>
          <textarea v-model="form.observacao" rows="4" />
        </label>

        <div class="actions">
          <button type="button" class="btn ghost" @click="$emit('close')">Cancelar</button>
          <button type="submit" class="btn primary" :disabled="saving">
            {{ saving ? 'Salvando...' : 'Salvar empresa' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: grid;
  place-items: center;
  padding: 24px;
  background: rgba(15, 12, 10, 0.62);
  backdrop-filter: blur(4px);
}

.modal {
  width: min(720px, 100%);
  max-height: min(92vh, 900px);
  overflow: auto;
  background: #fff;
  border-radius: 18px;
  padding: 28px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
}

.modal-head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 22px;
}

.eyebrow {
  margin: 0 0 6px;
  color: var(--gold);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
}

h2 {
  margin: 0;
  font-family: var(--font-serif);
  font-size: 30px;
}

.icon-btn {
  width: 36px;
  height: 36px;
  border: 1px solid var(--border);
  border-radius: 999px;
  background: #fff;
  font-size: 22px;
  line-height: 1;
  color: var(--text-muted);
}

.form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px 16px;
}

.full {
  grid-column: 1 / -1;
}

label {
  display: grid;
  gap: 8px;
}

label span {
  font-size: 13px;
  font-weight: 600;
  color: #3f3a34;
}

input,
select,
textarea {
  width: 100%;
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  padding: 12px 14px;
  background: #fff;
  color: var(--text);
}

textarea {
  resize: vertical;
}

.actions {
  grid-column: 1 / -1;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}

.btn {
  border-radius: 10px;
  padding: 11px 16px;
  border: 1px solid transparent;
  font-weight: 600;
}

.btn.ghost {
  background: #fff;
  border-color: var(--border-strong);
}

.btn.primary {
  background: var(--gold);
  color: #fff;
}

.btn.primary:disabled {
  opacity: 0.7;
  cursor: wait;
}

@media (max-width: 700px) {
  .form {
    grid-template-columns: 1fr;
  }
}
</style>
