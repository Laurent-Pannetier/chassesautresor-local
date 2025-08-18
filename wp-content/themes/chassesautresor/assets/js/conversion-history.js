jQuery(function ($) {
  function toggleTable(e) {
    e.preventDefault();
    const button = $(this);
    const container = button.closest('.conversion-history');
    const tableWrapper = container.find('.conversion-history-table');
    const expanded = button.attr('aria-expanded') === 'true';
    button
      .attr('aria-expanded', expanded ? 'false' : 'true')
      .attr('aria-label', expanded ? button.data('label-open') : button.data('label-close'))
      .find('.conversion-history-toggle-text')
      .text(expanded ? button.data('label-open') : button.data('label-close'));
    tableWrapper.slideToggle();
  }

  function loadPage(container, page) {
    const loading = container.find('.conversion-history-loading');
    const tableWrapper = container.find('.conversion-history-table');
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

  $(document).on('click', '.conversion-history-toggle', toggleTable);

  $(document).on('pager:change', '.conversion-history .points-history-pager', function (e) {
    const container = $(this).closest('.conversion-history');
    const page = e.originalEvent.detail.page;
    loadPage(container, page);
  });
});
