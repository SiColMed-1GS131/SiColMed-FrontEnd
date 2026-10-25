// SOLO para pruebas locales: tokens falsos y usuarios/contraseñas en localStorage.
// Nunca usar credenciales reales aquí ni confiar en estos tokens en el backend.
import { saveToken } from './token.js';

const MOCK_USERS_KEY = 'sicolmed_mock_users';
export const demoUser = {
  username: 'medico.demo',
  email: 'medico.demo@sicolmed.test',
  password: 'Demo123!'
};

// El modo de prueba solo se activa en localhost / 127.0.0.1.
const LOCAL_HOSTS = ['localhost', '127.0.0.1', '[::1]'];
export const mockMode = LOCAL_HOSTS.includes(window.location.hostname) &&
  new URLSearchParams(window.location.search).get('mock') === '1';

function assertMockMode() {
  if (!mockMode) throw new Error('El modo de prueba no está habilitado.');
}

function encodeBase64Url(value) {
  return btoa(unescape(encodeURIComponent(value)))
    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function createMockToken(username) {
  const header = encodeBase64Url(JSON.stringify({ alg: 'none', typ: 'JWT' }));
  const payload = encodeBase64Url(JSON.stringify({
    sub: username,
    role: 'medico',
    exp: Math.floor(Date.now() / 1000) + 3600
  }));
  return `${header}.${payload}.mock-signature`;
}

function getMockUsers() {
  const stored = localStorage.getItem(MOCK_USERS_KEY);
  if (!stored) return [demoUser];
  try {
    const users = JSON.parse(stored);
    return Array.isArray(users) ? users : [demoUser];
  } catch (_error) {
    return [demoUser];
  }
}

function saveMockUser(user) {
  const users = getMockUsers();
  users.push(user);
  localStorage.setItem(MOCK_USERS_KEY, JSON.stringify(users));
}

export function mockLogin(username, password, rememberSession) {
  assertMockMode();
  const user = getMockUsers().find(candidate =>
    (candidate.username === username || candidate.email === username) &&
    candidate.password === password
  );
  if (!user) throw new Error('Usuario o contraseña incorrectos (modo de prueba).');
  const body = { access_token: createMockToken(user.username), token_type: 'bearer' };
  saveToken(body.access_token, rememberSession);
  return body;
}

export function mockRegister(userData) {
  assertMockMode();
  const existing = getMockUsers().some(user =>
    user.email === userData.email || user.username === userData.username
  );
  if (existing) throw new Error('El correo o usuario ya está registrado (modo de prueba).');
  saveMockUser(userData);
  return { message: 'Registro creado correctamente.' };
}
