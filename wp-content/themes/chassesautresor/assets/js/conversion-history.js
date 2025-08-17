jQuery(function ($) {
  const wrapper = $('.conversion-history');
  wrapper.each(function () {
    const container = $(this);
    const toggle = container.find('.conversion-history-toggle');
    const tableWrapper = container.find('.conversion-history-table');

    if (toggle.attr('aria-expanded') !== 'true') {
      tableWrapper.hide();
    }

    toggle.on('click', function (e) {
      e.preventDefault();
      const expanded = $(this).attr('aria-expanded') === 'true';
      $(this)
        .attr('aria-expanded', expanded ? 'false' : 'true')
        .attr('aria-label', expanded ? $(this).data('label-open') : $(this).data('label-close'));
      tableWrapper.slideToggle();
    });

    container.on('click', '.points-history-pager .page-link', function (e) {
      e.preventDefault();
      const page = $(this).data('page');
      $.post(ConversionHistoryAjax.ajax_url, {
        action: 'load_conversion_history',
        nonce: ConversionHistoryAjax.nonce,
        page: page,
      }).done(function (response) {
        if (response.success) {
          tableWrapper.find('tbody').html(response.data.rows);
          container.find('.points-history-pager .page-link').removeClass('active');
          container.find('.points-history-pager .page-link[data-page="' + page + '"]').addClass('active');
        }
      });
    });
  });
});
