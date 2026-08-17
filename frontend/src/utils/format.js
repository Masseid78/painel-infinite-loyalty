export function formatMoney(value) {
  const n = Number(value || 0)
  return n.toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  })
}

export function formatDateBr(iso) {
  if (!iso) return '—'
  const [y, m, d] = iso.split('-')
  if (!y || !m || !d) return iso
  return `${d}/${m}/${y}`
}

export function todayIso() {
  const d = new Date()
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

export function dateGroupLabel(iso) {
  if (!iso) return 'Sem data de cadastro'
  const today = todayIso()
  if (iso === today) return 'Hoje'
  const yesterday = new Date()
  yesterday.setDate(yesterday.getDate() - 1)
  const yyyy = yesterday.getFullYear()
  const mm = String(yesterday.getMonth() + 1).padStart(2, '0')
  const dd = String(yesterday.getDate()).padStart(2, '0')
  if (iso === `${yyyy}-${mm}-${dd}`) return 'Ontem'
  return formatDateBr(iso)
}
