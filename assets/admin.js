(function($){
    'use strict';
    window.FukamiLensAdmin = window.FukamiLensAdmin || {};

    /**
     * Grammar Checker (Metabox)
     */
    FukamiLensAdmin.initChecker = function() {
        var $wrap = $('.fukami-lens-admin');
        if (!$wrap.length) return;
        $wrap.on('click', '#fukami-lens-check-btn', function() {
            var $btn = $(this);
            var $results = $('#fukami-lens-grammar-results');
            var $tokenUsage = $('#fukami-lens-token-usage');
            var content = $('#content').val();
            var title = $('#title').val();
            $results.html('<span class="fukami-lens-loading">校正中... しばらくお待ちください。</span>');
            $btn.prop('disabled', true);
            $.ajax({
                url: fukami_lens_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'fukami_lens_check_grammar',
                    content: content,
                    title: title,
                    _wpnonce: fukami_lens_ajax.nonce
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
    FukamiLensAdmin.initDashboardWidget = function() {
        var $wrap = $('.fukami-lens-admin');
        if (!$wrap.length) return;
        // Set default dates
        var today = new Date();
        var endDate = today.toISOString().slice(0,10);
        var lastMonth = new Date(today.getFullYear(), today.getMonth()-1, today.getDate());
        var startDate = lastMonth.toISOString().slice(0,10);
        $('#fukami-lens-rag-start-date-dashboard').val(startDate);
        $('#fukami-lens-rag-end-date-dashboard').val(endDate);
        $wrap.on('click', '#fukami-lens-ai-ask-btn-dashboard', function() {
            var question   = $('#fukami-lens-ai-question-dashboard').val();
            var startDate  = $('#fukami-lens-rag-start-date-dashboard').val();
            var endDate    = $('#fukami-lens-rag-end-date-dashboard').val();
            if (!question.trim()) {
                $('#fukami-lens-ai-answer-dashboard').html('<span style="color:red;">'+(window.wp && wp.i18n ? wp.i18n.__('質問を入力してください。', 'wp-fukami-lens-ai') : '質問を入力してください。')+'</span>');
                return;
            }
            $('#fukami-lens-ai-answer-dashboard').html('<em>'+(window.wp && wp.i18n ? wp.i18n.__('考え中...', 'wp-fukami-lens-ai') : '考え中...')+'</em>');
            $.post(fukami_lens_ajax.ajax_url, {
                action:         'fukami_lens_ask_ai',
                question:       question,
                start_date:     startDate,
                end_date:       endDate,
                from_dashboard: 1,
                _wpnonce: fukami_lens_ajax.ask_ai_nonce
            }, function(response) {
                if (response.success) {
                    $('#fukami-lens-ai-answer-dashboard').html('<strong>'+(window.wp && wp.i18n ? wp.i18n.__('回答：', 'wp-fukami-lens-ai') : '回答：')+'</strong><br>'+response.data.answer);
                } else {
                    $('#fukami-lens-ai-answer-dashboard').html('<span style="color:red;">'
                        +(response.data?response.data:(window.wp && wp.i18n ? wp.i18n.__('エラー', 'wp-fukami-lens-ai') : 'エラー'))+'</span>');
                }
            });
        });
    };

    /**
     * Settings Page Tabs
     */
    FukamiLensAdmin.initSettingsTabs = function() {
        var $wrap = $('.fukami-lens-admin');
        if (!$wrap.length) return;
        // Dynamic AI provider field loading
        function updateProviderFields() {
            var selected = $wrap.find('input[name="fukami_lens_ai_provider"]:checked').val();
            $wrap.find('.openai, .anthropic').hide();
            if (selected) {
                $wrap.find('.' + selected).show();
            }
        }
        if ($wrap.find('input[name="fukami_lens_ai_provider"]').length) {
            updateProviderFields();
            $wrap.on('change', 'input[name="fukami_lens_ai_provider"]', updateProviderFields);
        }
    };

    // Init all modules on document ready
    $(function(){
        FukamiLensAdmin.initChecker();
        FukamiLensAdmin.initDashboardWidget();
        FukamiLensAdmin.initSettingsTabs();
    });

})(jQuery); 