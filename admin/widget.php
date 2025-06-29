<?php
/**
 * Dashboard widget rendering for WP Fukami Lens AI
 *
 * @package FukamiLensAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render the AI Assistant dashboard widget.
 */
function fukami_lens_render_ask_ai_dashboard_widget() {
    ?>
    <!-- Styles moved to assets/css/admin.css -->
    <div class="fukami-lens-other-settings-box fukami-lens-admin" id="fukami-lens-ask-ai-box-dashboard">
        <textarea id="fukami-lens-ai-question-dashboard"
                rows="3"
                placeholder="<?php esc_attr_e('AIアシスタントに質問してください...', 'wp-fukami-lens-ai'); ?>"></textarea>
        <div class="fukami-lens-flex-row">
            <label for="fukami-lens-rag-start-date-dashboard"><strong><?php esc_html_e('データ範囲：', 'wp-fukami-lens-ai'); ?></strong></label>
            <span class="fukami-lens-date-range">
                <input type="date" id="fukami-lens-rag-start-date-dashboard" />
                <span class="fukami-lens-date-dash">–</span>
                <input type="date" id="fukami-lens-rag-end-date-dashboard" />
            </span>
        </div>
        <div class="fukami-lens-button-row">
            <button type="button"
                    id="fukami-lens-ai-ask-btn-dashboard"
                    class="button button-primary">
                <?php esc_html_e('サイト内容で回答', 'wp-fukami-lens-ai'); ?>
            </button>
            <button type="button"
                    id="fukami-lens-chunk-posts-btn-dashboard"
                    class="button">
                <?php esc_html_e('投稿をチャンク', 'wp-fukami-lens-ai'); ?>
            </button>
        </div>
        <div id="fukami-lens-ai-answer-dashboard"></div>
        <div id="fukami-lens-chunking-results-dashboard" style="display: none;"></div>
    </div>
    <script>
    jQuery(function($){
        // Set default dates
        var today = new Date();
        var endDate = today.toISOString().slice(0,10);
        var lastMonth = new Date(today.getFullYear(), today.getMonth()-1, today.getDate());
        var startDate = lastMonth.toISOString().slice(0,10);
        $('#fukami-lens-rag-start-date-dashboard').val(startDate);
        $('#fukami-lens-rag-end-date-dashboard').val(endDate);

        $('#fukami-lens-ai-ask-btn-dashboard').on('click', function() {
            var question   = $('#fukami-lens-ai-question-dashboard').val();
            var startDate  = $('#fukami-lens-rag-start-date-dashboard').val();
            var endDate    = $('#fukami-lens-rag-end-date-dashboard').val();
            if (!question.trim()) {
                $('#fukami-lens-ai-answer-dashboard').html('<span style="color:red;">'+wp.i18n.__('質問を入力してください。', 'wp-fukami-lens-ai')+'</span>');
                return;
            }
            $('#fukami-lens-ai-answer-dashboard').html('<em>'+wp.i18n.__('考え中...', 'wp-fukami-lens-ai')+'</em>');
            $.post(ajaxurl, {
                action:         'fukami_lens_ask_ai',
                question:       question,
                start_date:     startDate,
                end_date:       endDate,
                from_dashboard: 1
            }, function(response) {
                if (response.success) {
                    $('#fukami-lens-ai-answer-dashboard').html('<strong>'+wp.i18n.__('回答：', 'wp-fukami-lens-ai')+'</strong><br>'+response.data.answer);
                } else {
                    $('#fukami-lens-ai-answer-dashboard').html('<span style="color:red;">'
                        +(response.data?response.data:wp.i18n.__('エラー', 'wp-fukami-lens-ai'))+'</span>');
                }
            });
        });

        $('#fukami-lens-chunk-posts-btn-dashboard').on('click', function() {
            var $btn = $(this);
            var $results = $('#fukami-lens-chunking-results-dashboard');
            var startDate = $('#fukami-lens-rag-start-date-dashboard').val();
            var endDate = $('#fukami-lens-rag-end-date-dashboard').val();
            
            $btn.prop('disabled', true).text(wp.i18n.__('チャンク中...', 'wp-fukami-lens-ai'));
            $results.show().html('<em>'+wp.i18n.__('投稿をチャンク中...', 'wp-fukami-lens-ai')+'</em>');
            
            $.post(ajaxurl, {
                action: 'fukami_lens_chunk_posts',
                start_date: startDate,
                end_date: endDate,
                _wpnonce: fukami_lens_ajax.chunk_posts_nonce
            }, function(response) {
                if (response.success) {
                    $results.html('<strong>'+wp.i18n.__('チャンク結果：', 'wp-fukami-lens-ai')+'</strong><br><pre style="max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 8px; border: 1px solid #ddd;">'+response.data+'</pre>');
                } else {
                    $results.html('<span style="color:red;">'+(response.data?response.data:wp.i18n.__('エラー', 'wp-fukami-lens-ai'))+'</span>');
                }
                $btn.prop('disabled', false).text(wp.i18n.__('投稿をチャンク', 'wp-fukami-lens-ai'));
            });
        });
    });
    </script>
    <?php
}

// Register the dashboard widget
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'fukami_lens_ask_ai_widget',
        esc_html__('AI Assistant', 'wp-fukami-lens-ai'),
        'fukami_lens_render_ask_ai_dashboard_widget'
    );
});