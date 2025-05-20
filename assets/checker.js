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
                var data = response.data;
                var html = '<pre>' + data.result + '</pre>';
                html += '<div style="margin-top:10px;font-size:90%;">';
                html += '送信トークン: ' + data.prompt_tokens + '　';
                html += '受信トークン: ' + data.completion_tokens + '　';
                html += '合計: ' + data.total_tokens + '　';
                html += '所要時間: ' + (data.time_spent ? data.time_spent.toFixed(2) : '?') + '秒';
                html += '</div>';
                $('#npa-grammar-results').html(html);
            } else {
                $('#npa-grammar-results').html('<span style="color:red;">' + response.data + '</span>');
            }
        });
    });
});
