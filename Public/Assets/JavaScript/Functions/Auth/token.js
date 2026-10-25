// Gestión de tokens y persistencia de la sesión.
const TOKEN_KEY = 'sicolmed_access_token';
const STORAGE_KEY = 'sicolmed_token_storage';

function getStorage() {
  return sessionStorage.getItem(STORAGE_KEY) === 'local' ? localStorage : sessionStorage;
}

export function getToken() {
  return getStorage().getItem(TOKEN_KEY);
}

export function clearToken() {
  localStorage.removeItem(TOKEN_KEY);
  sessionStorage.removeItem(TOKEN_KEY);
  sessionStorage.removeItem(STORAGE_KEY);
}

export function decodeToken(token) {
  try {
    const payload = token.split('.')[1];
    let normalized = payload.replace(/-/g, '+').replace(/_/g, '/');
    while (normalized.length % 4) normalized += '=';
    const decoded = decodeURIComponent(window.atob(normalized).split('').map(character =>
      '%' + ('00' + character.charCodeAt(0).toString(16)).slice(-2)
    ).join(''));
    return JSON.parse(decoded);
  } catch (_error) {
    return null;
  }
}

// Esta comprobación NO verifica la firma del JWT: el backend debe hacerlo.
export function isTokenExpired(token) {
  const payload = decodeToken(token);
  return !payload || Boolean(payload.exp && payload.exp * 1000 <= Date.now());
}

export function saveToken(token, rememberSession = false) {
  clearToken();
  (rememberSession ? localStorage : sessionStorage).setItem(TOKEN_KEY, token);
  sessionStorage.setItem(STORAGE_KEY, rememberSession ? 'local' : 'session');
}

export function isAuthenticated() {
  const token = getToken();
  return Boolean(token && !isTokenExpired(token));
}
