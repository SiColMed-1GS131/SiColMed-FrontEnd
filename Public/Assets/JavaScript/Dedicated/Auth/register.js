// Solo interacción visual. El registro real lo gestiona PHP y lo valida FastAPI.
import { initializePasswordToggles } from '../../Functions/passwordToggle.js';

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializePasswordToggles, { once: true });
} else {
  initializePasswordToggles();
}
