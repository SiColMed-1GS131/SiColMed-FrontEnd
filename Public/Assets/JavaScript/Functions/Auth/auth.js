// Servicio compartido: API de autenticación y peticiones con Bearer token.
import { getToken, clearToken, isTokenExpired, saveToken, isAuthenticated } from './token.js';
import { mockMode, mockLogin, mockRegister, demoUser } from './mockAuth.js';

// La URL pública de la API la inyecta el servidor en <meta name="sicolmed-api-url">
// (variable de entorno API_PUBLIC_URL). window.SICOLMED_API_URL permite sobrescribirla.
const REQUEST_TIMEOUT_MS = 10000;
const apiBaseUrl = (
  window.SICOLMED_API_URL ||
  document.querySelector('meta[name="sicolmed-api-url"]')?.content?.trim() ||
  'http://localhost:8000'
).replace(/\/+$/, '');
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

// POST JSON con tiempo límite; los fallos de red se traducen a mensajes para el usuario
// (fetch lanza "Failed to fetch", que no es útil ni está en español).
async function postJson(url, payload) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
  try {
    return await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload),
      signal: controller.signal
    });
  } catch (error) {
    if (error && error.name === 'AbortError') {
      throw new Error('El servidor tardó demasiado en responder. Inténtalo de nuevo.');
    }
    throw new Error('No se pudo conectar con el servidor. Verifica tu conexión e inténtalo de nuevo.');
  } finally {
    clearTimeout(timer);
  }
}

// No se muestra el texto crudo del backend (puede venir en inglés o ser una lista de errores
// de validación); se traduce el código de estado a un mensaje claro y sin filtrar detalles.
function loginErrorMessage(status) {
  if (status === 400 || status === 401 || status === 404) return 'Usuario o contraseña incorrectos.';
  if (status === 403) return 'Tu cuenta no tiene permiso para acceder en este momento.';
  if (status === 422) return 'Revisa el usuario y la contraseña e inténtalo de nuevo.';
  if (status === 429) return 'Demasiados intentos. Espera unos minutos e inténtalo de nuevo.';
  if (status >= 500) return 'El servicio no está disponible en este momento. Inténtalo más tarde.';
  return 'No fue posible iniciar sesión. Inténtalo de nuevo.';
}

export async function login(username, password, rememberSession = false) {
  if (mockMode) return mockLogin(username, password, rememberSession);

  const response = await postJson(apiBaseUrl + loginEndpoint, { username, password });
  const body = await parseResponse(response);
  if (!response.ok) throw new Error(loginErrorMessage(response.status));
  const token = body && (body.access_token || body.token);
  if (typeof token !== 'string' || token === '') {
    throw new Error('El servidor respondió de forma inesperada. Inténtalo de nuevo.');
  }
  saveToken(token, rememberSession);
  return body;
}

export async function register(userData) {
  if (mockMode) return mockRegister(userData);

  const response = await postJson(apiBaseUrl + registerEndpoint, userData);
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
