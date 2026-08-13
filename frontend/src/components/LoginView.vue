<script setup>
import { ref } from 'vue'
import { login, setToken } from '../api'

const emit = defineEmits(['success'])

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const { data } = await login(email.value.trim(), password.value)
    setToken(data.token)
    emit('success', data.user)
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      'Não foi possível entrar. Confira e-mail, senha e se a API está no ar.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <form class="card" @submit.prevent="submit">
      <div class="brand">
        <div class="logo" aria-hidden="true">∞</div>
        <h1>Infinite Loyalty</h1>
        <p>Painel comercial</p>
      </div>

      <label>
        <span>E-mail</span>
        <input v-model="email" type="email" autocomplete="username" required />
      </label>

      <label>
        <span>Senha</span>
        <input v-model="password" type="password" autocomplete="current-password" required />
      </label>

      <p v-if="error" class="error">{{ error }}</p>

      <button class="btn" type="submit" :disabled="loading">
        {{ loading ? 'Entrando...' : 'Entrar' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background:
    radial-gradient(900px 420px at 20% -10%, rgba(196, 160, 90, 0.18), transparent 55%),
    linear-gradient(180deg, #171311 0%, #221c18 100%);
}

.card {
  width: min(420px, 100%);
  background: #fff;
  border-radius: 18px;
  padding: 28px;
  display: grid;
  gap: 14px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
}

.brand {
  text-align: center;
  margin-bottom: 8px;
}

.logo {
  width: 52px;
  height: 52px;
  margin: 0 auto 10px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  background: rgba(196, 160, 90, 0.14);
  color: #c4a05a;
  font-size: 28px;
  font-weight: 700;
}

h1 {
  margin: 0;
  font-family: var(--font-serif);
  font-size: 28px;
}

.brand p {
  margin: 4px 0 0;
  color: #7a736b;
}

label {
  display: grid;
  gap: 8px;
}

label span {
  font-size: 13px;
  font-weight: 600;
}

input {
  width: 100%;
  border: 1px solid #d2c9bb;
  border-radius: 10px;
  padding: 12px 14px;
}

.error {
  margin: 0;
  color: #7a1d16;
  background: #fff5f4;
  border: 1px solid #f0c7c3;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 13px;
}

.btn {
  border: 0;
  border-radius: 10px;
  padding: 12px 16px;
  background: #c4a05a;
  color: #fff;
  font-weight: 700;
}

.btn:disabled {
  opacity: 0.7;
}
</style>
