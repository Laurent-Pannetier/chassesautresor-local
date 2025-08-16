jQuery(function ($) {
  $('.stats-table-wrapper').on('click', '.points-history-pager .page-link', function (e) {
    e.preventDefault();
    const page = $(this).data('page');
    const wrapper = $(this).closest('.stats-table-wrapper');
    $.post(PointsHistoryAjax.ajax_url, {
      action: 'load_points_history',
      nonce: PointsHistoryAjax.nonce,
      page: page,
    }).done(function (response) {
      if (response.success) {
        wrapper.find('tbody').html(response.data.rows);
        wrapper.find('.points-history-pager .page-link').removeClass('active');
        wrapper.find('.points-history-pager .page-link[data-page="' + page + '"]').addClass('active');
      }
    });
  });
});
