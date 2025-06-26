(function($){
    'use strict';
    window.NPAAdmin = window.NPAAdmin || {};

    /**
     * Grammar Checker (Metabox)
     */
    NPAAdmin.initChecker = function() {
        var $wrap = $('.npa-admin');
        if (!$wrap.length) return;
        $wrap.on('click', '#npa-check-btn', function() {
            var $btn = $(this);
            var $results = $('#npa-grammar-results');
            var $tokenUsage = $('#npa-token-usage');
            var content = $('#content').val();
            var title = $('#title').val();
            $results.html('<span class="npa-loading">校正中... しばらくお待ちください。</span>');
            $btn.prop('disabled', true);
            $.ajax({
                url: npa_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'npa_check_grammar',
                    content: content,
                    title: title,
                    _wpnonce: npa_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $results.html('<div>' + response.data.result.replace(/\n/g, '<br>') + '</div>');
                        // Broken links
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
                        // Invalid anchors
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
                        // Token usage
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
    };

    /**
     * Dashboard Widget (Ask AI)
     */
    NPAAdmin.initDashboardWidget = function() {
        var $wrap = $('.npa-admin');
        if (!$wrap.length) return;
        // Set default dates
        var today = new Date();
        var endDate = today.toISOString().slice(0,10);
        var lastMonth = new Date(today.getFullYear(), today.getMonth()-1, today.getDate());
        var startDate = lastMonth.toISOString().slice(0,10);
        $('#npa-rag-start-date-dashboard').val(startDate);
        $('#npa-rag-end-date-dashboard').val(endDate);
        $wrap.on('click', '#npa-ai-ask-btn-dashboard', function() {
            var question   = $('#npa-ai-question-dashboard').val();
            var startDate  = $('#npa-rag-start-date-dashboard').val();
            var endDate    = $('#npa-rag-end-date-dashboard').val();
            if (!question.trim()) {
                $('#npa-ai-answer-dashboard').html('<span style="color:red;">'+(window.wp && wp.i18n ? wp.i18n.__('質問を入力してください。', 'wp-nihongo-proofreader-ai') : '質問を入力してください。')+'</span>');
                return;
            }
            $('#npa-ai-answer-dashboard').html('<em>'+(window.wp && wp.i18n ? wp.i18n.__('考え中...', 'wp-nihongo-proofreader-ai') : '考え中...')+'</em>');
            $.post(npa_ajax.ajax_url, {
                action:         'npa_ask_ai',
                question:       question,
                start_date:     startDate,
                end_date:       endDate,
                from_dashboard: 1,
                _wpnonce: npa_ajax.ask_ai_nonce
            }, function(response) {
                if (response.success) {
                    $('#npa-ai-answer-dashboard').html('<strong>'+(window.wp && wp.i18n ? wp.i18n.__('回答：', 'wp-nihongo-proofreader-ai') : '回答：')+'</strong><br>'+response.data.answer);
                } else {
                    $('#npa-ai-answer-dashboard').html('<span style="color:red;">'
                        +(response.data?response.data:(window.wp && wp.i18n ? wp.i18n.__('エラー', 'wp-nihongo-proofreader-ai') : 'エラー'))+'</span>');
                }
            });
        });
    };

    /**
     * Settings Page Tabs
     */
    NPAAdmin.initSettingsTabs = function() {
        var $wrap = $('.npa-admin');
        if (!$wrap.length) return;
        // Dynamic AI provider field loading
        function updateProviderFields() {
            var selected = $wrap.find('input[name="npa_ai_provider"]:checked').val();
            $wrap.find('.openai, .anthropic').hide();
            if (selected) {
                $wrap.find('.' + selected).show();
            }
        }
        if ($wrap.find('input[name="npa_ai_provider"]').length) {
            updateProviderFields();
            $wrap.on('change', 'input[name="npa_ai_provider"]', updateProviderFields);
        }
    };

    // Init all modules on document ready
    $(function(){
        NPAAdmin.initChecker();
        NPAAdmin.initDashboardWidget();
        NPAAdmin.initSettingsTabs();
    });

})(jQuery); 