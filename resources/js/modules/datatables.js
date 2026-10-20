import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css';

/**
 * Turns every <table data-datatable> into a DataTable.
 *
 *   data-source="url"        load rows from the server (server-side paging, sorting and search)
 *   data-filters="#form"     a filter form whose fields are sent with every request (Apply/Reset reload the table)
 *   data-order='[[1,"desc"]]' initial sort
 *   data-page-length="25"
 *   data-empty="text"        message when there are no rows
 *   <th class="no-sort">     column that cannot be sorted
 *   <tr data-href="url">     clicking the row opens the URL
 */
export function initDataTables(root = document) {
    root.querySelectorAll('table[data-datatable]').forEach((table) => {
        if (DataTable.isDataTable(table)) return;

        const opts = table.dataset;
        const filterForm = opts.filters ? document.querySelector(opts.filters) : null;
        const headers = [...table.querySelectorAll('thead th')];

        const config = {
            // Report tables show every row so the printed register is complete.
            paging: opts.paging !== 'false',
            pageLength: Number(opts.pageLength) || 25,
            lengthMenu: [10, 25, 50, 100],
            order: opts.order ? JSON.parse(opts.order) : [],
            autoWidth: false,
            columnDefs: [{ targets: 'no-sort', orderable: false }],
            language: {
                search: '',
                searchPlaceholder: opts.searchPlaceholder || 'Search…',
                lengthMenu: 'Show _MENU_',
                info: 'Showing _START_–_END_ of _TOTAL_',
                infoEmpty: 'No records',
                infoFiltered: '(filtered from _MAX_)',
                zeroRecords: 'No matching records found',
                emptyTable: opts.empty || 'No records yet',
                processing: 'Loading…',
            },
            layout: {
                topStart: 'search',
                topEnd: 'pageLength',
                bottomStart: 'info',
                bottomEnd: 'paging',
            },
        };

        if (opts.source) {
            Object.assign(config, {
                serverSide: true,
                processing: true,
                searchDelay: 350,
                // Rows arrive as objects keyed "0", "1", … plus DT_RowAttr (e.g. data-href).
                columns: headers.map((th, i) => ({ data: String(i), orderable: !th.classList.contains('no-sort') })),
                ajax: {
                    url: opts.source,
                    data: (params) => {
                        if (filterForm) new FormData(filterForm).forEach((value, key) => { params[key] = value; });
                    },
                },
            });
        }

        const dt = new DataTable(table, config);

        if (filterForm) {
            const reload = () => (opts.source ? dt.ajax.reload() : dt.draw());
            filterForm.addEventListener('submit', (e) => { e.preventDefault(); reload(); });
            // Reset clears every filter (not just back to the values the page opened with).
            filterForm.addEventListener('reset', (e) => {
                e.preventDefault();
                filterForm.querySelectorAll('input, select').forEach((el) => { el.value = ''; });
                reload();
            });
            filterForm.querySelectorAll('select').forEach((el) => el.addEventListener('change', reload));
        }

        // Whole row opens the record (links and buttons inside the row keep working).
        table.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-href]');
            if (row && !e.target.closest('a, button, input, select, label')) window.location.href = row.dataset.href;
        });
    });
}

window.DataTable = DataTable;
