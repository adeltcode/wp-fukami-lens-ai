jQuery(document).ready(function($) {
    $('#npa-check-btn').on('click', function() {
        var $btn = $(this);
        var $results = $('#npa-grammar-results');
        var content = $('#content').val();
        var title = $('#title').val();

        // Show loading indicator and disable button
        $results.html('<span class="npa-loading">校正中... しばらくお待ちください。</span>');
        $btn.prop('disabled', true);

        $.ajax({
            url: npa_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'npa_check_grammar',
                content: content,
                title: title,
                nonce: npa_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $results.html('<div>' + response.data.result.replace(/\n/g, '<br>') + '</div>');
                } else {
                    $results.html('<span style="color:red;">' + response.data + '</span>');
                }
                $btn.prop('disabled', false);
            },
            error: function() {
                $results.html('<span style="color:red;">エラーが発生しました。</span>');
                $btn.prop('disabled', false);
            }
        });
    });
});