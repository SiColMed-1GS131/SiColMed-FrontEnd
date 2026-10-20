;(function () {
  'use strict';

  var apiBaseUrl = window.SICOLMED_API_URL || 'http://localhost:8000';
  var loginEndpoint = window.SICOLMED_LOGIN_ENDPOINT || '/auth/login';
  var registerEndpoint = window.SICOLMED_REGISTER_ENDPOINT || '/auth/register';
  var tokenKey = 'sicolmed_access_token';
  var storageKey = 'sicolmed_token_storage';
  var mockUsersKey = 'sicolmed_mock_users';
  var mockMode = new URLSearchParams(window.location.search).get('mock') === '1';

  // Solo para pruebas locales. El backend debe validar las credenciales reales.
  var demoUser = {
    username: 'medico.demo',
    email: 'medico.demo@sicolmed.test',
    password: 'Demo123!'
  };

  function getStorage() {
    return sessionStorage.getItem(storageKey) === 'local' ? localStorage : sessionStorage;
  }

  function getToken() {
    return getStorage().getItem(tokenKey);
  }

  function clearToken() {
    localStorage.removeItem(tokenKey);
    sessionStorage.removeItem(tokenKey);
    sessionStorage.removeItem(storageKey);
  }

  function encodeBase64Url(value) {
    return btoa(unescape(encodeURIComponent(value)))
      .replace(/\+/g, '-')
      .replace(/\//g, '_')
      .replace(/=+$/, '');
  }

  // Función para crear un token JWT simulado (solo para pruebas locales)
  function createMockToken(username) {
    var header = encodeBase64Url(JSON.stringify({ alg: 'none', typ: 'JWT' }));
    var payload = encodeBase64Url(JSON.stringify({
      sub: username,
      role: 'medico',
      exp: Math.floor(Date.now() / 1000) + 3600
    }));
    return header + '.' + payload + '.mock-signature';
  }

  function decodeToken(token) {
    try {
      var payload = token.split('.')[1];
      var normalized = payload.replace(/-/g, '+').replace(/_/g, '/');
      while (normalized.length % 4) {
        normalized += '=';
      }
      var decoded = decodeURIComponent(window.atob(normalized).split('').map(function (character) {
        return '%' + ('00' + character.charCodeAt(0).toString(16)).slice(-2);
      }).join(''));
      return JSON.parse(decoded);
    } catch (error) {
      return null;
    }
  }

  function isTokenExpired(token) {
    var payload = decodeToken(token);
    return !payload || Boolean(payload.exp && payload.exp * 1000 <= Date.now());
  }

  function saveToken(token, rememberSession) {
    clearToken();
    (rememberSession ? localStorage : sessionStorage).setItem(tokenKey, token);
    sessionStorage.setItem(storageKey, rememberSession ? 'local' : 'session');
  }

  function getMockUsers() {
    var storedUsers = localStorage.getItem(mockUsersKey);
    return storedUsers ? JSON.parse(storedUsers) : [demoUser];
  }

  function saveMockUser(user) {
    var users = getMockUsers();
    users.push(user);
    localStorage.setItem(mockUsersKey, JSON.stringify(users));
  }

  function mockLogin(username, password, rememberSession) {
    var user = getMockUsers().find(function (candidate) {
      return (candidate.username === username || candidate.email === username)
        && candidate.password === password;
    });
    if (!user) {
      throw new Error('Usuario o contraseña incorrectos (modo de prueba).');
    }
    var body = {
      access_token: createMockToken(user.username),
      token_type: 'bearer'
    };
    saveToken(body.access_token, rememberSession);
    return body;
  }

  async function parseResponse(response) {
    try {
      return await response.json();
    } catch (error) {
      return null;
    }
  }

  async function login(username, password, rememberSession) {
    if (mockMode) {
      return mockLogin(username, password, rememberSession);
    }

    var response = await fetch(apiBaseUrl + loginEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ username: username, password: password })
    });
    var body = await parseResponse(response);

    if (!response.ok) {
      throw new Error((body && (body.detail || body.message))
        || 'No fue posible iniciar sesión. Verifica tus credenciales.');
    }

    var token = body && (body.access_token || body.token);
    if (!token) {
      throw new Error('La respuesta de autenticación no contiene un token JWT.');
    }
    saveToken(token, rememberSession);
    return body;
  }

  async function register(userData) {
    if (mockMode) {
      var existingUser = getMockUsers().some(function (user) {
        return user.email === userData.email || user.username === userData.username;
      });
      if (existingUser) {
        throw new Error('El correo o usuario ya está registrado (modo de prueba).');
      }
      saveMockUser(userData);
      return { message: 'Registro creado correctamente.' };
    }

    var response = await fetch(apiBaseUrl + registerEndpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(userData)
    });
    var body = await parseResponse(response);
    if (!response.ok) {
      throw new Error((body && (body.detail || body.message))
        || 'No fue posible completar el registro.');
    }
    return body;
  }

  async function authenticatedFetch(url, options) {
    var token = getToken();
    if (!token || isTokenExpired(token)) {
      clearToken();
      throw new Error('La sesión ha expirado. Inicia sesión nuevamente.');
    }
    var requestOptions = options || {};
    var headers = new Headers(requestOptions.headers || {});
    headers.set('Authorization', 'Bearer ' + token);
    requestOptions.headers = headers;
    var response = await fetch(url, requestOptions);
    if (response.status === 401) {
      clearToken();
      throw new Error('La sesión ha expirado. Inicia sesión nuevamente.');
    }
    return response;
  }

  function showStatus(form, message, isError) {
    var status = form.querySelector('[data-auth-status]');
    if (!status) {
      status = document.createElement('p');
      status.setAttribute('data-auth-status', '');
      status.setAttribute('role', 'alert');
      form.insertBefore(status, form.firstChild);
    }
    status.textContent = message;
    status.style.color = isError ? '#b42318' : '#027a48';
  }

  function initializeLoginForm() {
    var form = document.getElementById('login-form');
    if (!form) {
      return;
    }
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      var button = form.querySelector('button[type="submit"]');
      button.disabled = true;
      showStatus(form, 'Iniciando sesión...', false);
      try {
        await login(document.getElementById('usuario').value.trim(),
          document.getElementById('password').value,
          document.querySelector('input[name="recordar"]').checked);
        showStatus(form, mockMode ? 'Sesión iniciada (modo de prueba).' : 'Sesión iniciada correctamente.', false);
        if (typeof window.SICOLMED_AFTER_LOGIN === 'function') {
          window.SICOLMED_AFTER_LOGIN();
        }
      } catch (error) {
        showStatus(form, error.message, true);
      } finally {
        button.disabled = false;
      }
    });
  }

  function initializeRegisterForm() {
    var form = document.getElementById('register-form');
    if (!form) {
      return;
    }
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      var button = form.querySelector('button[type="submit"]');
      button.disabled = true;
      showStatus(form, 'Registrando usuario...', false);
      try {
        await register({
          username: document.getElementById('correo').value.trim(),
          nombre: document.getElementById('nombre').value.trim(),
          idoneidad: document.getElementById('idoneidad').value.trim(),
          email: document.getElementById('correo').value.trim(),
          password: document.getElementById('password').value
        });
        showStatus(form, mockMode ? 'Registro creado (modo de prueba).' : 'Registro creado correctamente.', false);
        form.reset();
      } catch (error) {
        showStatus(form, error.message, true);
      } finally {
        button.disabled = false;
      }
    });
  }

  window.SiColMedAuth = {
    getToken: getToken,
    clearToken: clearToken,
    isAuthenticated: function () {
      var token = getToken();
      return Boolean(token && !isTokenExpired(token));
    },
    authenticatedFetch: authenticatedFetch,
    login: login,
    register: register,
    mockMode: mockMode,
    demoUser: { username: demoUser.username, password: demoUser.password }
  };

  document.addEventListener('DOMContentLoaded', function () {
    initializeLoginForm();
    initializeRegisterForm();
  });
}());
