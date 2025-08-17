(() => {
    function init(context = document) {
        context.querySelectorAll('.points-history').forEach((wrapper) => {
            if (wrapper.dataset.pagerInit === '1') {
                return;
            }
            wrapper.dataset.pagerInit = '1';

            function load(page) {
                const params = new URLSearchParams();
                params.append('action', 'load_points_history');
                params.append('nonce', PointsHistoryAjax.nonce);
                params.append('page', page);

                fetch(PointsHistoryAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    credentials: 'same-origin',
                    body: params.toString(),
                })
                    .then((r) => r.json())
                    .then((res) => {
                        if (!res.success) {
                            return;
                        }
                        wrapper.querySelector('tbody').innerHTML = res.data.rows;
                        wrapper.dataset.page = res.data.page;
                        wrapper.dataset.pages = res.data.pages;
                        const info = wrapper.querySelector('.pager-info');
                        if (info) {
                            info.textContent = res.data.page + ' / ' + res.data.pages;
                        }
                    })
                    .catch(() => {});
            }

            wrapper.addEventListener('click', (e) => {
                const page = parseInt(wrapper.dataset.page || '1', 10);
                const pages = parseInt(wrapper.dataset.pages || '1', 10);

                if (e.target.closest('.pager-first')) {
                    e.preventDefault();
                    load(1);
                } else if (e.target.closest('.pager-prev')) {
                    e.preventDefault();
                    if (page > 1) {
                        load(page - 1);
                    }
                } else if (e.target.closest('.pager-next')) {
                    e.preventDefault();
                    if (page < pages) {
                        load(page + 1);
                    }
                } else if (e.target.closest('.pager-last')) {
                    e.preventDefault();
                    load(pages);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => init());
    document.addEventListener('myaccountSectionLoaded', () => init());
    init();
})();

