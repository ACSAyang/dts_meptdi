import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';

const plugins = [dayGridPlugin, interactionPlugin, listPlugin];

/**
 * Document tracking calendar: every tracking step (received, forwarded, dispatched,
 * received by addressee, closed …) shown on the day it happened.
 *
 *   <div data-tracking-calendar data-source="/calendar/events" data-filters="#form">
 */
export function initTrackingCalendar() {
    const el = document.querySelector('[data-tracking-calendar]');
    if (!el) return;

    const filterForm = el.dataset.filters ? document.querySelector(el.dataset.filters) : null;
    const details = document.querySelector('[data-calendar-details]');

    const calendar = new Calendar(el, {
        plugins,
        initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
        initialDate: el.dataset.initialDate || undefined,
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
        buttonText: { today: 'Today', month: 'Month', list: 'List' },
        height: 'auto',
        firstDay: 1,
        dayMaxEvents: 3,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        events: {
            url: el.dataset.source,
            extraParams: () => (filterForm ? Object.fromEntries(new FormData(filterForm)) : {}),
        },
        eventClick: (info) => {
            info.jsEvent.preventDefault();
            if (info.event.url) window.location.href = info.event.url;
        },
        dateClick: (info) => showDay(info.date),
    });

    // Lists every entry of the clicked day under the calendar.
    function showDay(date) {
        if (!details) return;
        const sameDay = (d) => d && d.toDateString() === date.toDateString();
        const events = calendar.getEvents().filter((ev) => sameDay(ev.start)).sort((a, b) => a.start - b.start);
        const title = date.toLocaleDateString('en-GB', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });

        details.querySelector('[data-calendar-details-title]').textContent = `${title} — ${events.length} entr${events.length === 1 ? 'y' : 'ies'}`;
        const list = details.querySelector('[data-calendar-details-list]');
        list.innerHTML = '';
        events.forEach((ev) => {
            const p = ev.extendedProps;
            const li = document.createElement('li');
            li.className = 'flex flex-wrap items-baseline gap-x-3 gap-y-0.5 px-5 py-3';
            li.innerHTML = `
                <span class="w-12 shrink-0 text-xs text-slate-500 tabular-nums"></span>
                <a class="ref hover:underline"></a>
                <span class="badge"></span>
                <span class="min-w-0 flex-1 truncate text-sm text-slate-700"></span>
                <span class="text-xs text-slate-400"></span>`;
            const [time, ref, badge, subject, by] = li.children;
            time.textContent = ev.start.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
            ref.textContent = p.reference;
            ref.href = ev.url;
            badge.textContent = p.step;
            badge.style.background = ev.backgroundColor;
            badge.style.color = '#fff';
            subject.textContent = p.subject;
            by.textContent = p.by;
            list.appendChild(li);
        });
        if (!events.length) list.innerHTML = '<li class="px-5 py-6 text-center text-sm text-slate-400">No tracking activity on this day.</li>';
        details.classList.remove('hidden');
    }

    let typing;
    filterForm?.addEventListener('change', () => calendar.refetchEvents());
    filterForm?.addEventListener('input', () => {
        clearTimeout(typing);
        typing = setTimeout(() => calendar.refetchEvents(), 400);
    });
    calendar.render();
}

/**
 * Global FullCalendar for views that build their own calendar (e.g. the delivery report),
 * served from the app bundle so it also works without internet access.
 */
window.FullCalendar = {
    Calendar: class extends Calendar {
        constructor(el, options = {}) {
            super(el, { plugins, ...options });
        }
    },
};
