jQuery(document).ready(function($) {
    $('#npa-check-btn').on('click', function() {
        var content = $('#content').val();
        $('#npa-grammar-results').html('校正中...');
        $.post(npa_ajax.ajax_url, {
            action: 'npa_check_grammar',
            nonce: npa_ajax.nonce,
            content: content
        }, function(response) {
            if (response.success) {
                $('#npa-grammar-results').html('<pre>' + response.data + '</pre>');
            } else {
                $('#npa-grammar-results').html('<span style="color:red;">' + response.data + '</span>');
            }
        });
    });
});