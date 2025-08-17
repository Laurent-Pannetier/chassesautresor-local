jQuery(function ($) {
  const wrapper = $('.conversion-history');
  wrapper.each(function () {
    const container = $(this);
    const toggle = container.find('.conversion-history-toggle');
    const tableWrapper = container.find('.conversion-history-table');
    const loading = container.find('.conversion-history-loading');
    const pager = container.find('.points-history-pager');

    if (toggle.attr('aria-expanded') !== 'true') {
      tableWrapper.hide();
    }

    toggle.on('click', function (e) {
      e.preventDefault();
      const expanded = $(this).attr('aria-expanded') === 'true';
      $(this)
        .attr('aria-expanded', expanded ? 'false' : 'true')
        .attr('aria-label', expanded ? $(this).data('label-open') : $(this).data('label-close'))
        .find('.conversion-history-toggle-text')
        .text(expanded ? $(this).data('label-open') : $(this).data('label-close'))
        .end();
      tableWrapper.slideToggle();
    });

    function loadPage(page) {
      loading.show();
      $.post(ConversionHistoryAjax.ajax_url, {
        action: 'load_conversion_history',
        nonce: ConversionHistoryAjax.nonce,
        page: page,
      })
        .done(function (response) {
          if (response.success) {
            tableWrapper.find('tbody').html(response.data.rows);
          }
        })
        .always(function () {
          loading.hide();
        });
    }

    pager.on('pager:change', function (e) {
      const page = e.originalEvent.detail.page;
      loadPage(page);
    });
  });
});
