
import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';

window.DataTable = DataTable;

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

// Show/hide blocks driven by a checkbox: <input data-toggle="#target">
document.querySelectorAll('[data-toggle]').forEach((input) => {
    const target = document.querySelector(input.dataset.toggle);
    const sync = () => target?.classList.toggle('hidden', !input.checked);
    input.addEventListener('change', sync);
    sync();
});

// Confirmation prompts on irreversible actions: <form data-confirm="...">
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
});

// Print button
document.querySelectorAll('[data-print]').forEach((btn) => btn.addEventListener('click', () => window.print()));
