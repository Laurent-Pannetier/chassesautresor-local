document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.conversion-history').forEach((wrapper) => {
        function load(page) {
            const params = new URLSearchParams();
            params.append('action', 'load_conversion_history');
            params.append('nonce', ConversionHistory.nonce);
            params.append('page', page);

            fetch(ConversionHistory.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
            const btn = e.target.closest('button');
            if (!btn) {
                return;
            }
            e.preventDefault();
            const page = parseInt(wrapper.dataset.page || '1', 10);
            const pages = parseInt(wrapper.dataset.pages || '1', 10);
            if (btn.classList.contains('pager-first')) {
                load(1);
            } else if (btn.classList.contains('pager-prev')) {
                if (page > 1) load(page - 1);
            } else if (btn.classList.contains('pager-next')) {
                if (page < pages) load(page + 1);
            } else if (btn.classList.contains('pager-last')) {
                load(pages);
            }
        });
    });
});

