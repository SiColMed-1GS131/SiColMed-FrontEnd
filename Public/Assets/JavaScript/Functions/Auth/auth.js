// Servicio compartido: API de autenticación y peticiones con Bearer token.
import { getToken, clearToken, isTokenExpired, saveToken, isAuthenticated } from './token.js';
import { mockMode, mockLogin, mockRegister, demoUser } from './mockAuth.js';

const apiBaseUrl = window.SICOLMED_API_URL || 'http://localhost:8000';
const loginEndpoint = window.SICOLMED_LOGIN_ENDPOINT || '/auth/login';
const registerEndpoint = window.SICOLMED_REGISTER_ENDPOINT || '/auth/register';

async function parseResponse(response) {
  try {
    return await response.json();
  } catch (_error) {
    return null;
  }
}

function apiError(body, fallback) {
  const detail = body && (body.detail || body.message);
  return typeof detail === 'string' ? detail : fallback;
}

export async function login(username, password, rememberSession = false) {
  if (mockMode) return mockLogin(username, password, rememberSession);

  const response = await fetch(apiBaseUrl + loginEndpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ username, password })
  });
  const body = await parseResponse(response);
  if (!response.ok) {
    throw new Error(apiError(body, 'No fue posible iniciar sesión. Verifica tus credenciales.'));
  }
  const token = body && (body.access_token || body.token);
  if (!token) throw new Error('La respuesta de autenticación no contiene un token JWT.');
  saveToken(token, rememberSession);
  return body;
}

export async function establishFrontendSession(token) {
  const endpoint = document.body.dataset.sessionEndpoint ||
    '/index.php?c=sesion&a=iniciar';
  const response = await fetch(endpoint, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json'
    },
    body: JSON.stringify({ token })
  });
  const body = await parseResponse(response);
  if (!response.ok) {
    throw new Error(apiError(body, 'No fue posible establecer la sesión.'));
  }
  if (!body || typeof body.destino !== 'string' || body.destino === '') {
    throw new Error('La respuesta de sesión no contiene un destino válido.');
  }
  return body;
}

export async function register(userData) {
  if (mockMode) return mockRegister(userData);

  const response = await fetch(apiBaseUrl + registerEndpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(userData)
  });
  const body = await parseResponse(response);
  if (!response.ok) throw new Error(apiError(body, 'No fue posible completar el registro.'));
  return body;
}

export async function authenticatedFetch(url, options = {}) {
  const token = getToken();
  if (!token || isTokenExpired(token)) {
    clearToken();
    throw new Error('La sesión ha expirado. Inicia sesión nuevamente.');
  }
  const headers = new Headers(options.headers || {});
  headers.set('Authorization', 'Bearer ' + token);
  const response = await fetch(url, { ...options, headers });
  if (response.status === 401) {
    clearToken();
    throw new Error('La sesión ha expirado. Inicia sesión nuevamente.');
  }
  return response;
}

// Compatibilidad con otros scripts que consultaban window.SiColMedAuth.
// La API estará disponible una vez que el módulo se haya cargado.
window.SiColMedAuth = {
  getToken,
  clearToken,
  isAuthenticated,
  authenticatedFetch,
  login,
  register,
  mockMode,
  demoUser: mockMode ? { username: demoUser.username, password: demoUser.password } : null
};
