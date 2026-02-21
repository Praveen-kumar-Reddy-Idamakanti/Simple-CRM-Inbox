const BASE = '' // Assumes backend APIs are available under the same host (e.g. /api/*). Adjust BASE to backend host if needed.

async function request(path, options = {}) {
  const res = await fetch(`${BASE}/api${path}`, {
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    credentials: 'same-origin',
    ...options,
  })

  const text = await res.text()
  try {
    return JSON.parse(text)
  } catch (e) {
    return text
  }
}

export const fetchConversations = async (params = '') => {
  const path = `/conversations${params ? `?${params}` : ''}`
  return request(path, { method: 'GET' })
}

export const fetchMessages = async (conversationId, params = '') => {
  const path = `/conversations/${conversationId}/messages${params ? `?${params}` : ''}`
  return request(path, { method: 'GET' })
}

export const sendReply = async (conversationId, message) => {
  return request('/reply', { method: 'POST', body: JSON.stringify({ conversation_id: conversationId, message }) })
}

export const fetchContact = async (id) => {
  return request(`/contacts/${id}`, { method: 'GET' })
}

export const addTag = async (contactId, tag) => {
  return request(`/contacts/${contactId}/tags/add`, { method: 'POST', body: JSON.stringify({ tag }) })
}

export const removeTag = async (contactId, tag) => {
  return request(`/contacts/${contactId}/tags/remove`, { method: 'POST', body: JSON.stringify({ tag }) })
}

export const getAiSuggestion = async (id) => {
  return request(`/conversations/${id}/suggest`, { method: 'GET' })
}

export default { fetchConversations, fetchMessages, sendReply, fetchContact, addTag, removeTag, getAiSuggestion }
