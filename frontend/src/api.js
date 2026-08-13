import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

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
