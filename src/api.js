async function request(path, options = {}) {
  const response = await fetch(`api/index.php${path}`, {
    credentials: 'same-origin',
    headers: options.body ? { 'Content-Type': 'application/json' } : {},
    ...options,
  })
  const data = await response.json().catch(() => ({ error: 'サーバーから不正な応答が返されました。' }))
  if (!response.ok) {
    const error = new Error(data.error || '通信に失敗しました。')
    error.status = response.status
    throw error
  }
  return data
}

export const api = {
  dashboard: (carId) => request(`/dashboard${carId ? `?carId=${encodeURIComponent(carId)}` : ''}`),
  cars: () => request('/cars'),
  addCar: (carName) => request('/cars', { method: 'POST', body: JSON.stringify({ carName }) }),
  addFuel: (record) => request('/fuel-records', { method: 'POST', body: JSON.stringify(record) }),
}
