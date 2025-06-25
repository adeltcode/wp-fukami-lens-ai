<?php
/**
 * Dashboard widget rendering for Nihongo Proofreader AI
 *
 * @package NihongoProofreaderAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render the AI Assistant dashboard widget.
 */
function npa_render_ask_ai_dashboard_widget() {
    ?>
    <!-- Styles moved to assets/css/admin.css -->
    <div class="npa-other-settings-box npa-admin" id="npa-ask-ai-box-dashboard">
        <textarea id="npa-ai-question-dashboard"
                rows="3"
                placeholder="<?php esc_attr_e('AIアシスタントに質問してください...', 'wp-nihongo-proofreader-ai'); ?>"></textarea>
        <div class="npa-flex-row">
            <label for="npa-rag-start-date-dashboard"><strong><?php esc_html_e('データ範囲：', 'wp-nihongo-proofreader-ai'); ?></strong></label>
            <span class="npa-date-range">
                <input type="date" id="npa-rag-start-date-dashboard" />
                <span class="npa-date-dash">–</span>
                <input type="date" id="npa-rag-end-date-dashboard" />
            </span>
        </div>
        <button type="button"
                id="npa-ai-ask-btn-dashboard"
                class="button button-primary">
            <?php esc_html_e('サイト内容で回答', 'wp-nihongo-proofreader-ai'); ?>
        </button>
        <div id="npa-ai-answer-dashboard"></div>
    </div>
    <script>
    jQuery(function($){
        // Set default dates
        var today = new Date();
        var endDate = today.toISOString().slice(0,10);
        var lastMonth = new Date(today.getFullYear(), today.getMonth()-1, today.getDate());
        var startDate = lastMonth.toISOString().slice(0,10);
        $('#npa-rag-start-date-dashboard').val(startDate);
        $('#npa-rag-end-date-dashboard').val(endDate);

        $('#npa-ai-ask-btn-dashboard').on('click', function() {
            var question   = $('#npa-ai-question-dashboard').val();
            var startDate  = $('#npa-rag-start-date-dashboard').val();
            var endDate    = $('#npa-rag-end-date-dashboard').val();
            if (!question.trim()) {
                $('#npa-ai-answer-dashboard').html('<span style="color:red;">'+wp.i18n.__('質問を入力してください。', 'wp-nihongo-proofreader-ai')+'</span>');
                return;
            }
            $('#npa-ai-answer-dashboard').html('<em>'+wp.i18n.__('考え中...', 'wp-nihongo-proofreader-ai')+'</em>');
            $.post(ajaxurl, {
                action:         'npa_ask_ai',
                question:       question,
                start_date:     startDate,
                end_date:       endDate,
                from_dashboard: 1
            }, function(response) {
                if (response.success) {
                    $('#npa-ai-answer-dashboard').html('<strong>'+wp.i18n.__('回答：', 'wp-nihongo-proofreader-ai')+'</strong><br>'+response.data.answer);
                } else {
                    $('#npa-ai-answer-dashboard').html('<span style="color:red;">'
                        +(response.data?response.data:wp.i18n.__('エラー', 'wp-nihongo-proofreader-ai'))+'</span>');
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
        'npa_ask_ai_widget',
        esc_html__('AI Assistant', 'wp-nihongo-proofreader-ai'),
        'npa_render_ask_ai_dashboard_widget'
    );
});