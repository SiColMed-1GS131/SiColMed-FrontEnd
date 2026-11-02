/**
 * perfil.js · Conmutador de pestañas del perfil médico.
 */
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.perfil-tab-btn');
    const tabPanels = document.querySelectorAll('.perfil-tab-panel');

    if (!tabButtons.length || !tabPanels.length) return;

    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-tab');

            tabButtons.forEach(btn => btn.classList.remove('activo'));
            tabPanels.forEach(panel => panel.classList.remove('activo'));

            this.classList.add('activo');
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add('activo');
            }
        });
    });
});
