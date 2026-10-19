<x-layouts.app title="Delivery Report">

    {{-- External library styles --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.css">

    <style>
        .report-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            min-width: 0;
        }

        .report-panel-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        #dailyDeliveryChart {
            display: block;
            width: 100%;
            max-height: 380px;
        }

        #documentCalendar {
            width: 100%;
            min-height: 500px;
        }

        .fc .fc-daygrid-day.has-documents {
            background-color: #e8efff !important;
        }

        .fc .fc-daygrid-day.has-documents .fc-daygrid-day-number {
            color: #0033a0 !important;
            font-weight: 800;
        }

        .fc .fc-daygrid-day-frame {
            min-height: 80px;
        }

        .calendar-date-count {
            display: block;
            margin: 4px 3px;
            padding: 3px;
            border-radius: 4px;
            background: #0033a0;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            white-space: normal;
        }

        .fc .fc-toolbar-title {
            font-size: 1.15rem;
            font-weight: 700;
        }

        .fc .fc-button-primary {
            background-color: #0033a0;
            border-color: #0033a0;
        }

        .fc .fc-button-primary:hover {
            background-color: #002675;
            border-color: #002675;
        }

        .report-table {
            width: 100%;
        }

        #calendarDateDetails {
            margin-top: 20px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
        }

        .calendar-document {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .calendar-document:last-child {
            border-bottom: none;
        }

        @media print {

            .no-print,
            .dt-search,
            .dt-length,
            .dt-paging,
            .dt-info,
            .fc-button {
                display: none !important;
            }

            .report-panel,
            .card {
                box-shadow: none !important;
                break-inside: avoid;
            }

            #documentCalendar {
                min-height: 0;
            }
        }
    </style>

    {{-- Report heading --}}
    @include('reports.partials.header', [
        'heading' => 'Outgoing Delivery Report',
        'subheading' => $from->format('d M Y') . ' – ' . $to->format('d M Y'),
    ])

    {{-- Filters --}}
    <form method="GET" action="{{ url()->current() }}" class="no-print card mb-5 flex flex-wrap items-end gap-3 p-4">

        <div>
            <label for="from" class="label">From</label>
            <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input">
        </div>

        <div>
            <label for="to" class="label">To</label>
            <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input">
        </div>

        <div>
            <label for="status" class="label">Status</label>

            <select id="status" name="status" class="input">
                <option value="">All</option>

                <option value="pending" @selected(request('status') === 'pending')>
                    Pending (open)
                </option>

                @foreach (config('dts.outgoing_statuses') as $k => $l)
                    <option value="{{ $k }}" @selected(request('status') === $k)>
                        {{ $l }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">
            Show
        </button>

        <button type="button" onclick="window.print()" class="btn">
            Print
        </button>
    </form>

    {{-- Prepare chart and calendar data --}}
    @php
        $byStatus = $records->countBy('status');

        $datedRecords = $records->filter(function ($record) {
            return !empty($record->document_date);
        });

        $dailyCounts = $datedRecords
            ->groupBy(function ($record) {
                return \Illuminate\Support\Carbon::parse($record->document_date)->toDateString();
            })
            ->sortKeys()
            ->map(function ($group) {
                return $group->count();
            });

        $calendarEvents = $datedRecords
            ->map(function ($record) {
                return [
                    'title' => $record->reference_no,
                    'start' => \Illuminate\Support\Carbon::parse($record->document_date)->toDateString(),
                    'allDay' => true,
                    'extendedProps' => [
                        'subject' => $record->subject ?? '',
                        'destination' => $record->destination_office ?? '',
                        'status' => $record->statusLabel(),
                    ],
                ];
            })
            ->values();
    @endphp

    {{-- Status summary --}}
    <div class="mb-4 flex flex-wrap gap-2 text-xs">
        <span class="badge bg-navy-800 text-white">
            Total {{ $records->count() }}
        </span>

        @foreach (config('dts.outgoing_statuses') as $k => $l)
            @if ($byStatus[$k] ?? 0)
                <span class="badge bg-navy-50 text-navy-800">
                    {{ $l }}: {{ $byStatus[$k] }}
                </span>
            @endif
        @endforeach
    </div>

    {{-- Daily graph --}}
    <section class="report-panel mb-6">
        <h2 class="report-panel-title">
            Daily Outgoing Documents
        </h2>

        <p class="mb-4 text-sm text-slate-500">
            Number of outgoing communications by document date.
        </p>

        <div style="position: relative; height: 350px; width: 100%;">
            <canvas id="dailyDeliveryChart"></canvas>
        </div>

        <p id="chartError" class="mt-3 hidden text-sm text-red-600"></p>
    </section>

    {{-- Calendar --}}
    <section class="report-panel mb-6">
        <h2 class="report-panel-title">
            Document Date Calendar
        </h2>

        <p class="mb-4 text-sm text-slate-500">
            Highlighted dates contain outgoing documents. Select a date
            to see its records.
        </p>

        <div class="mb-4 flex items-center gap-2 text-sm">
            <span class="inline-block h-3 w-3 rounded-sm" style="background: #0033a0;"></span>
            <span>Document date with outgoing records</span>
        </div>

        <div id="documentCalendar"></div>

        <p id="calendarError" class="mt-3 hidden text-sm text-red-600"></p>

        <div id="calendarDateDetails" class="hidden">
            <h3 id="calendarSelectedDate" class="mb-3 font-bold text-slate-800"></h3>

            <div id="calendarSelectedDetails" class="text-sm text-slate-600"></div>
        </div>
    </section>

    {{-- Outgoing delivery DataTable --}}
    <section class="card overflow-hidden">
        <div class="border-b border-slate-200 p-4">
            <h2 class="font-bold text-slate-800">
                Outgoing Delivery Records
            </h2>
        </div>

        <div class="overflow-x-auto p-3">
            <table id="deliveryReportTable" class="table report-table display stripe hover w-full">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Document Date</th>
                        <th>Subject</th>
                        <th>Destination</th>
                        <th>Liaison</th>
                        <th>Dispatched</th>
                        <th>Attempts</th>
                        <th>Received By</th>
                        <th>Received</th>
                        <th>Verified</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($records as $r)
                        <tr>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('outgoing.show', $r) }}" class="ref hover:underline">
                                    {{ $r->reference_no }}
                                </a>
                            </td>

                            <td class="whitespace-nowrap"
                                data-order="{{ $r->document_date ? \Illuminate\Support\Carbon::parse($r->document_date)->toDateString() : '' }}">
                                {{ $r->document_date ? \Illuminate\Support\Carbon::parse($r->document_date)->format('d M Y') : '—' }}
                            </td>

                            <td class="text-xs">
                                {{ \Illuminate\Support\Str::limit($r->subject, 70) }}
                            </td>

                            <td class="text-xs">
                                {{ $r->destination_office ?? '—' }}
                            </td>

                            <td class="text-xs">
                                {{ $r->liaison_name ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap text-xs">
                                {{ $r->dispatched_at?->format('d M Y H:i') ?? '—' }}
                            </td>

                            <td class="text-center text-xs">
                                {{ $r->delivery_attempts_count ?? 0 }}
                            </td>

                            <td class="text-xs">
                                {{ $r->received_by_name ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap text-xs">
                                {{ $r->received_at?->format('d M Y H:i') ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap text-xs">
                                {{ $r->verified_at?->format('d M Y H:i') ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap text-xs">
                                {{ $r->statusLabel() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Signatures --}}
    <div class="mt-8">
        @include('reports.partials.signatures')
    </div>

    {{-- Load libraries directly in this view --}}
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('deliveryReportTable');

            if (!table || !window.DataTable) {
                console.error(
                    'DataTables is not loaded or deliveryReportTable was not found.'
                );
                return;
            }

            // Prevent duplicate initialization.
            if (DataTable.isDataTable('#deliveryReportTable')) {
                return;
            }

            new DataTable('#deliveryReportTable', {
                responsive: false,
                scrollX: true,
                autoWidth: false,

                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, 'All']
                ],

                order: [
                    [1, 'desc']
                ],

                columnDefs: [{
                    targets: [6],
                    className: 'dt-body-center'
                }],

                language: {
                    search: 'Search records:',
                    lengthMenu: 'Show _MENU_ records',
                    info: 'Showing _START_ to _END_ of _TOTAL_ records',
                    infoEmpty: 'No delivery records available',
                    infoFiltered: '(filtered from _MAX_ total records)',
                    zeroRecords: 'No matching delivery records found',
                    emptyTable: 'No outgoing delivery records available',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: 'Next',
                        previous: 'Previous'
                    }
                },

                layout: {
                    topStart: 'pageLength',
                    topEnd: 'search',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                }
            });
        });
    </script>
    <script>
        (function() {
            'use strict';

            function initializeDeliveryReport() {
                const dailyLabels = @json($dailyCounts->keys()->values());
                const dailyValues = @json($dailyCounts->values()->values());
                const calendarEvents = @json($calendarEvents);

                // ==============================================
                // 1. DATATABLES
                // ==============================================
                const table = document.getElementById('deliveryReportTable');

                if (table) {
                    if (typeof DataTable === 'undefined') {
                        console.error('DataTables did not load.');
                    } else {
                        try {
                            if (!DataTable.isDataTable(table)) {
                                new DataTable(table, {
                                    pageLength: 25,
                                    lengthMenu: [10, 25, 50, 100],
                                    searching: true,
                                    ordering: true,
                                    paging: true,
                                    info: true,
                                    autoWidth: false,
                                    scrollX: true,
                                    order: [
                                        [1, 'asc']
                                    ],
                                    language: {
                                        search: 'Search records:',
                                        lengthMenu: 'Show _MENU_ records',
                                        info: 'Showing _START_ to _END_ of _TOTAL_ records',
                                        infoEmpty: 'No records available',
                                        zeroRecords: 'No matching records found',
                                        emptyTable: 'No outgoing communications in this range.'
                                    },
                                    columnDefs: [{
                                            targets: [5, 7, 8, 9, 10],
                                            orderable: true
                                        },
                                        {
                                            targets: [6],
                                            className: 'dt-body-center'
                                        }
                                    ]
                                });
                            }
                        } catch (error) {
                            console.error('DataTables initialization failed:', error);
                        }
                    }
                }

                // ==============================================
                // 2. DAILY GRAPH
                // ==============================================
                const chartCanvas = document.getElementById('dailyDeliveryChart');
                const chartError = document.getElementById('chartError');

                if (chartCanvas) {
                    if (typeof Chart === 'undefined') {
                        chartError.textContent =
                            'The graph library could not load. Check your internet connection.';
                        chartError.classList.remove('hidden');
                    } else {
                        new Chart(chartCanvas, {
                            type: 'bar',
                            data: {
                                labels: dailyLabels,
                                datasets: [{
                                    label: 'Outgoing documents',
                                    data: dailyValues,
                                    backgroundColor: '#0033a0',
                                    borderColor: '#002675',
                                    borderWidth: 1,
                                    borderRadius: 5,
                                    maxBarThickness: 48
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            title: function(items) {
                                                return items.length ?
                                                    'Document date: ' + items[0].label :
                                                    '';
                                            },
                                            label: function(item) {
                                                return item.raw + ' document(s)';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Document date'
                                        },
                                        ticks: {
                                            autoSkip: true,
                                            maxRotation: 45,
                                            minRotation: 0
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0,
                                            stepSize: 1
                                        },
                                        title: {
                                            display: true,
                                            text: 'Number of documents'
                                        }
                                    }
                                }
                            }
                        });
                    }
                }

                // ==============================================
                // 3. CALENDAR
                // ==============================================
                const calendarElement = document.getElementById('documentCalendar');
                const calendarError = document.getElementById('calendarError');
                const detailsPanel = document.getElementById('calendarDateDetails');
                const selectedDateHeading = document.getElementById('calendarSelectedDate');
                const selectedDateDetails = document.getElementById('calendarSelectedDetails');

                const countsByDate = {};

                calendarEvents.forEach(function(event) {
                    if (event.start) {
                        countsByDate[event.start] =
                            (countsByDate[event.start] || 0) + 1;
                    }
                });

                function formatDate(dateString) {
                    const parts = dateString.split('-');

                    if (parts.length !== 3) {
                        return dateString;
                    }

                    return parts[2] + '/' + parts[1] + '/' + parts[0];
                }

                function showDateDetails(dateString) {
                    const dayEvents = calendarEvents.filter(function(event) {
                        return event.start === dateString;
                    });

                    selectedDateHeading.textContent =
                        'Document Date: ' + formatDate(dateString) +
                        ' (' + dayEvents.length + ' document(s))';

                    selectedDateDetails.replaceChildren();

                    if (dayEvents.length === 0) {
                        const message = document.createElement('p');
                        message.textContent =
                            'No outgoing documents were recorded for this date.';
                        selectedDateDetails.appendChild(message);
                    } else {
                        dayEvents.forEach(function(event) {
                            const item = document.createElement('div');
                            item.className = 'calendar-document';

                            const reference = document.createElement('strong');
                            reference.textContent = event.title || 'Outgoing document';
                            item.appendChild(reference);

                            const subject = document.createElement('p');
                            subject.textContent =
                                event.extendedProps.subject || 'No subject provided';
                            item.appendChild(subject);

                            const destination = document.createElement('p');
                            destination.textContent =
                                'Destination: ' +
                                (event.extendedProps.destination || '—');
                            item.appendChild(destination);

                            const status = document.createElement('p');
                            status.textContent =
                                'Status: ' + (event.extendedProps.status || '—');
                            item.appendChild(status);

                            selectedDateDetails.appendChild(item);
                        });
                    }

                    detailsPanel.classList.remove('hidden');
                }

                if (calendarElement) {
                    if (typeof FullCalendar === 'undefined') {
                        calendarError.textContent =
                            'The calendar library could not load. Check your internet connection.';
                        calendarError.classList.remove('hidden');
                    } else {
                        try {
                            const calendar = new FullCalendar.Calendar(
                                calendarElement, {
                                    initialView: 'dayGridMonth',
                                    height: 'auto',
                                    firstDay: 1,
                                    dayMaxEvents: true,

                                    headerToolbar: {
                                        left: 'prev,next today',
                                        center: 'title',
                                        right: 'dayGridMonth,listMonth'
                                    },

                                    buttonText: {
                                        today: 'Today',
                                        month: 'Month',
                                        list: 'List'
                                    },

                                    events: calendarEvents,

                                    // Highlight days that have documents.
                                    dayCellClassNames: function(info) {
                                        const year = info.date.getFullYear();
                                        const month = String(
                                            info.date.getMonth() + 1
                                        ).padStart(2, '0');
                                        const day = String(
                                            info.date.getDate()
                                        ).padStart(2, '0');

                                        const key = year + '-' + month + '-' + day;

                                        return countsByDate[key] ? ['has-documents'] : [];
                                    },

                                    // Show the number of documents per day.
                                    dayCellDidMount: function(info) {
                                        const year = info.date.getFullYear();
                                        const month = String(
                                            info.date.getMonth() + 1
                                        ).padStart(2, '0');
                                        const day = String(
                                            info.date.getDate()
                                        ).padStart(2, '0');

                                        const key = year + '-' + month + '-' + day;
                                        const count = countsByDate[key];

                                        if (count) {
                                            const frame = info.el.querySelector(
                                                '.fc-daygrid-day-frame'
                                            );

                                            if (frame) {
                                                const badge =
                                                    document.createElement('span');

                                                badge.className =
                                                    'calendar-date-count';

                                                badge.textContent = count +
                                                    (count === 1 ?
                                                        ' document' :
                                                        ' documents');

                                                badge.title = count +
                                                    ' document(s) on ' + key;

                                                frame.appendChild(badge);
                                            }
                                        }
                                    },

                                    // Clicking a calendar date shows its records.
                                    dateClick: function(info) {
                                        showDateDetails(info.dateStr);
                                    },

                                    // Clicking an event also opens the date details.
                                    eventClick: function(info) {
                                        info.jsEvent.preventDefault();

                                        const dateString = info.event.startStr
                                            .substring(0, 10);

                                        showDateDetails(dateString);
                                    }
                                }
                            );

                            calendar.render();
                        } catch (error) {
                            console.error('Calendar initialization failed:', error);
                            calendarError.textContent =
                                'The calendar could not be initialized. Check the browser console.';
                            calendarError.classList.remove('hidden');
                        }
                    }
                }
            }

            // Works whether this script runs before or after DOMContentLoaded.
            if (document.readyState === 'loading') {
                document.addEventListener(
                    'DOMContentLoaded',
                    initializeDeliveryReport
                );
            } else {
                initializeDeliveryReport();
            }
        })();
    </script>

</x-layouts.app>
