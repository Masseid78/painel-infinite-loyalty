<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  open: Boolean,
  settings: {
    type: Object,
    default: () => ({
      meta_mensal: 3000,
      meta_contatos_semana: 120,
    }),
  },
  saving: Boolean,
})

const emit = defineEmits(['close', 'save'])

const form = reactive({
  meta_mensal: 3000,
  meta_contatos_semana: 120,
})

watch(
  () => props.open,
  (open) => {
    if (open) {
      form.meta_mensal = Number(props.settings.meta_mensal || 0)
      form.meta_contatos_semana = Number(props.settings.meta_contatos_semana || 0)
    }
  },
)

function submit() {
  emit('save', {
    meta_mensal: Number(form.meta_mensal),
    meta_contatos_semana: Number(form.meta_contatos_semana),
  })
}
</script>

<template>
  <div v-if="open" class="overlay" @click.self="$emit('close')">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="meta-title">
      <div class="modal-head">
        <div>
          <p class="eyebrow">METAS</p>
          <h2 id="meta-title">Definir meta</h2>
        </div>
        <button class="icon-btn" type="button" aria-label="Fechar" @click="$emit('close')">×</button>
      </div>

      <form class="form" @submit.prevent="submit">
        <label>
          <span>Meta mensal (R$)</span>
          <input v-model.number="form.meta_mensal" type="number" min="0" step="0.01" required />
        </label>

        <label>
          <span>Meta de contatos na semana</span>
          <input v-model.number="form.meta_contatos_semana" type="number" min="0" step="1" required />
        </label>

        <p class="hint">
          A meta mensal acompanha a receita recorrente das assinaturas. A meta semanal conta os
          contatos cadastrados na semana.
        </p>

        <div class="actions">
          <button type="button" class="btn ghost" @click="$emit('close')">Cancelar</button>
          <button type="submit" class="btn primary" :disabled="saving">
            {{ saving ? 'Salvando...' : 'Salvar meta' }}
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
  width: min(480px, 100%);
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
  font-size: 28px;
  font-weight: 700;
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
  gap: 16px;
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

input {
  width: 100%;
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  padding: 12px 14px;
  background: #fff;
}

.hint {
  margin: 0;
  font-size: 13px;
  color: var(--text-muted);
  line-height: 1.45;
}

.actions {
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
  color: var(--text);
}

.btn.primary {
  background: var(--gold);
  color: #fff;
}

.btn.primary:disabled {
  opacity: 0.7;
  cursor: wait;
}
</style>
