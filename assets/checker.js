jQuery(document).ready(function($) {
    $('#npa-check-btn').on('click', function() {
        var $btn = $(this);
        var $results = $('#npa-grammar-results');
        var $tokenUsage = $('#npa-token-usage');
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

                    // Display broken links info
                    var brokenLinks = response.data.broken_links || [];
                    var brokenLinksHtml = '';
                    if (brokenLinks.length > 0) {
                        brokenLinksHtml = '<div style="color:red; margin-top:24px;"><strong>リンク切れが検出されました:</strong><ul>';
                        brokenLinks.forEach(function(url) {
                            brokenLinksHtml += '<li><a href="' + url + '" target="_blank" rel="noopener">' + url + '</a></li>';
                        });
                        brokenLinksHtml += '</ul></div>';
                    } else {
                        brokenLinksHtml = '<div style="color:green; margin-top:24px;"><strong>リンク切れは検出されませんでした。</strong></div>';
                    }
                    $results.append(brokenLinksHtml);

                    // Display invalid anchor tags info
                    var invalidAnchors = response.data.invalid_anchors || [];
                    var invalidAnchorsHtml = '';
                    if (invalidAnchors.length > 0) {
                        invalidAnchorsHtml = '<div style="color:orange; margin-top:10px;"><strong>無効なアンカータグが検出されました:</strong><ul>';
                        invalidAnchors.forEach(function(tag) {
                            invalidAnchorsHtml += '<li><code>' + $('<div>').text(tag).html() + '</code></li>';
                        });
                        invalidAnchorsHtml += '</ul></div>';
                    } else {
                        invalidAnchorsHtml = '<div style="color:green; margin-top:10px;"><strong>無効なアンカータグは検出されませんでした。</strong></div>';
                    }
                    $results.append(invalidAnchorsHtml);

                    // Display token usage and time
                    $tokenUsage.html(
                        '<strong>プロンプト:</strong> ' + response.data.prompt_tokens +
                        ' | <strong>補完:</strong> ' + response.data.completion_tokens +
                        ' | <strong>合計:</strong> ' + response.data.total_tokens +
                        ' | <strong>処理時間:</strong> ' + response.data.time_spent.toFixed(2) + '秒'
                    );
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
