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
        <button type="button"
                id="fukami-lens-ai-ask-btn-dashboard"
                class="button button-primary">
            <?php esc_html_e('サイト内容で回答', 'wp-fukami-lens-ai'); ?>
        </button>
        <div id="fukami-lens-ai-answer-dashboard"></div>
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