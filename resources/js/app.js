import Chart from 'chart.js/auto';
import { initDataTables } from './modules/datatables';
import { initModals } from './modules/modals';
import { initTrackingCalendar } from './modules/calendar';
import { initForms } from './modules/forms';

// Libraries are bundled with the app (no internet needed on the office network).
window.Chart = Chart;

// Mobile sidebar toggle
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebar-backdrop');
const toggle = document.getElementById('sidebar-toggle');

function setSidebar(open) {
    sidebar?.classList.toggle('-translate-x-full', !open);
    backdrop?.classList.toggle('hidden', !open);
}

toggle?.addEventListener('click', () => setSidebar(sidebar.classList.contains('-translate-x-full')));
backdrop?.addEventListener('click', () => setSidebar(false));

// Live clock in the header (24-hour military time)
const clock = document.querySelector('[data-clock]');
if (clock) {
    const tick = () => {
        const d = new Date();
        const date = d.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }).replace(',', '');
        const time = d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        clock.textContent = `${date} · ${time}H`;
    };
    tick();
    setInterval(tick, 30000);
}

initForms();
initModals();
initDataTables();
initTrackingCalendar();
