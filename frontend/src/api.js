import axios from 'axios'

const TOKEN_KEY = 'il_auth_token'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      clearToken()
      window.dispatchEvent(new Event('il:unauthorized'))
    }
    return Promise.reject(error)
  },
)

export function getToken() {
  return localStorage.getItem(TOKEN_KEY) || ''
}

export function setToken(token) {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken() {
  localStorage.removeItem(TOKEN_KEY)
}

export function isLoggedIn() {
  return Boolean(getToken())
}

export function login(email, password) {
  return api.post('/login', { email, password })
}

export function logout() {
  return api.post('/logout').finally(() => clearToken())
}

export function getMe() {
  return api.get('/me')
}

export function getDashboard() {
  return api.get('/dashboard')
}

export function updateSettings(payload) {
  return api.put('/settings', payload)
}

export function getCompanies(params = {}) {
  return api.get('/companies', { params })
}

export function createCompany(payload) {
  return api.post('/companies', payload)
}

export function updateCompany(id, payload) {
  return api.put(`/companies/${id}`, payload)
}

export function deleteCompany(id) {
  return api.delete(`/companies/${id}`)
}

export function downloadBackup() {
  return api.get('/backup', { responseType: 'blob' })
}

export function restoreBackup(payload) {
  return api.post('/backup/restore', payload)
}

export default api
