<?php
/**
 * Settings page rendering for Nihongo Proofreader AI
 *
 * @package NihongoProofreaderAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register settings pages in the admin menu.
 */
add_action('admin_menu', function() {
    add_options_page(
        esc_html__('Nihongo Proofreader AI Settings', 'wp-nihongo-proofreader-ai'),
        esc_html__('Nihongo Proofreader AI', 'wp-nihongo-proofreader-ai'),
        'manage_options',
        'npa-settings',
        'npa_settings_page'
    );
    add_management_page(
        esc_html__('Run Python Code', 'wp-nihongo-proofreader-ai'),
        esc_html__('Run Python Code', 'wp-nihongo-proofreader-ai'),
        'manage_options',
        'npa-run-python',
        'npa_run_python_page'
    );
});

/**
 * Render the main settings page.
 */
function npa_settings_page() {
    $current = esc_attr(get_option('npa_ai_provider', 'openai'));
    $openai_key = esc_attr(get_option('npa_openai_api_key', ''));
    $openai_model = esc_attr(get_option('npa_openai_gpt_model', 'gpt-3.5-turbo'));
    $anthropic_key = esc_attr(get_option('npa_anthropic_api_key', ''));
    $anthropic_model = esc_attr(get_option('npa_anthropic_gpt_model', 'claude-3-opus-20240229'));
    ?>
    <div class="wrap npa-admin">
        <h1><?php esc_html_e('Nihongo Proofreader AI Settings', 'wp-nihongo-proofreader-ai'); ?></h1>
        <form id="npa-settings-form" method="post" action="options.php">
            <?php settings_fields('npa_settings_group'); ?>

            <!-- Tab Selector -->
            <div id="npa-ai-tabs">
                <h2><?php esc_html_e('Select API Provider', 'wp-nihongo-proofreader-ai'); ?></h2>
                <button type="button" class="npa-tab-btn<?php if ($current === 'openai') echo ' active'; ?>" data-provider="openai">
                    <?php esc_html_e('OpenAI', 'wp-nihongo-proofreader-ai'); ?>
                    <span class="npa-tab-check"<?php if ($current !== 'openai') echo ' style="display:none;"'; ?>>
                        <svg width="16" height="16" viewBox="0 0 20 20"><polyline points="4,11 9,16 16,5" style="fill:none;stroke:green;stroke-width:2"/></svg>
                    </span>
                </button>
                <button type="button" class="npa-tab-btn<?php if ($current === 'anthropic') echo ' active'; ?>" data-provider="anthropic">
                    <?php esc_html_e('Anthropic', 'wp-nihongo-proofreader-ai'); ?>
                    <span class="npa-tab-check"<?php if ($current !== 'anthropic') echo ' style="display:none;"'; ?>>
                        <svg width="16" height="16" viewBox="0 0 20 20"><polyline points="4,11 9,16 16,5" style="fill:none;stroke:green;stroke-width:2"/></svg>
                    </span>
                </button>
                <input type="hidden" name="npa_ai_provider" id="npa_ai_provider" value="<?php echo $current; ?>">
            </div>

            <!-- Tab Content: OpenAI -->
            <div class="npa-tab-content" id="npa_tab_content_openai">
                <label><strong><?php esc_html_e('OpenAI API Key', 'wp-nihongo-proofreader-ai'); ?></strong></label><br>
                <input type="text" name="npa_openai_api_key" value="<?php echo $openai_key; ?>" size="50" />
                <p class="description"><?php esc_html_e('Enter your OpenAI API key to enable AI functionalities.', 'wp-nihongo-proofreader-ai'); ?></p>
                <label><strong><?php esc_html_e('Model', 'wp-nihongo-proofreader-ai'); ?></strong></label><br>
                <select name="npa_openai_gpt_model">
                    <option value="gpt-4.1" <?php selected($openai_model, 'gpt-4.1'); ?>>gpt-4.1</option>
                    <option value="gpt-4.1-mini" <?php selected($openai_model, 'gpt-4.1-mini'); ?>>gpt-4.1-mini</option>
                    <option value="gpt-4.1-nano" <?php selected($openai_model, 'gpt-4.1-nano'); ?>>gpt-4.1-nano</option>
                    <option value="gpt-4" <?php selected($openai_model, 'gpt-4'); ?>>gpt-4</option>
                    <option value="gpt-3.5-turbo" <?php selected($openai_model, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo</option>
                </select>
                <p class='description'><?php esc_html_e('Select the OpenAI model to use for processing.', 'wp-nihongo-proofreader-ai'); ?></p>
            </div>

            <!-- Tab Content: Anthropic -->
            <div class="npa-tab-content" id="npa_tab_content_anthropic">
                <label><strong><?php esc_html_e('Anthropic API Key', 'wp-nihongo-proofreader-ai'); ?></strong></label><br>
                <input type="text" name="npa_anthropic_api_key" value="<?php echo $anthropic_key; ?>" size="50" />
                <p class="description"><?php esc_html_e('Enter your Anthropic API key to enable Anthropic AI functionalities.', 'wp-nihongo-proofreader-ai'); ?></p>
                <label><strong><?php esc_html_e('Model', 'wp-nihongo-proofreader-ai'); ?></strong></label><br>
                <select name="npa_anthropic_gpt_model">
                    <option value="claude-opus-4-20250514" <?php selected($anthropic_model, 'claude-opus-4-20250514'); ?>>claude-opus-4-20250514</option>
                    <option value="claude-sonnet-4-20250514" <?php selected($anthropic_model, 'claude-sonnet-4-20250514'); ?>>claude-sonnet-4-20250514</option>
                    <option value="claude-3-7-sonnet-20250219" <?php selected($anthropic_model, 'claude-3-7-sonnet-20250219'); ?>>claude-3-7-sonnet-20250219</option>
                    <option value="claude-3-5-haiku-20241022" <?php selected($anthropic_model, 'claude-3-5-haiku-20241022'); ?>>claude-3-5-haiku-20241022</option>
                    <option value="claude-3-5-sonnet-20241022" <?php selected($anthropic_model, 'claude-3-5-sonnet-20241022'); ?>>claude-3-5-sonnet-20241022</option>
                    <option value="claude-3-opus-20240229" <?php selected($anthropic_model, 'claude-3-opus-20240229'); ?>>claude-3-opus-20240229</option>
                    <option value="claude-3-sonnet-20240229" <?php selected($anthropic_model, 'claude-3-sonnet-20240229'); ?>>claude-3-sonnet-20240229</option>
                    <option value="claude-3-haiku-20240307" <?php selected($anthropic_model, 'claude-3-haiku-20240307'); ?>>claude-3-haiku-20240307</option>
                </select>
                <p class='description'><?php esc_html_e('Select the Anthropic model to use for processing.', 'wp-nihongo-proofreader-ai'); ?></p>
            </div>

            <!-- RAG Settings Box -->
            <h2><?php esc_html_e('RAG Settings (Retrieval-Augmented Generation)', 'wp-nihongo-proofreader-ai'); ?></h2>
            <div class="npa-other-settings-box">
                <?php
                do_settings_sections('npa-rag-settings');
                // No submit_button() here, as the main form's submit covers all.
                ?>
            </div>

            <!-- Proofreader Settings in a Consistent Box -->
            <h2><?php esc_html_e('Proofreader Settings', 'wp-nihongo-proofreader-ai'); ?></h2>
            <div class="npa-other-settings-box">
                <?php
                do_settings_sections('npa-settings');
                submit_button();
                ?>
            </div>
        </form>
    </div>
    <script>
    (function($){
        function showTab(provider) {
            $('.npa-tab-btn').removeClass('active');
            $('.npa-tab-btn[data-provider="'+provider+'"]').addClass('active');
            $('.npa-tab-content').removeClass('active');
            $('#npa_tab_content_' + provider).addClass('active');
            $('#npa_ai_provider').val(provider);
            // Show checkmark only on active tab
            $('.npa-tab-check').hide();
            $('.npa-tab-btn[data-provider="'+provider+'"] .npa-tab-check').show();
        }
        $(document).ready(function(){
            var current = $('#npa_ai_provider').val();
            showTab(current);
            $('.npa-tab-btn').on('click', function(){
                var provider = $(this).data('provider');
                showTab(provider);
            });
        });
        $('#npa-ai-ask-btn').on('click', function() {
            var question = $('#npa-ai-question').val();
            if (!question.trim()) {
                $('#npa-ai-answer').html('<span style="color:red;">Please enter a question.</span>');
                return;
            }
            $('#npa-ai-answer').html('<em>Thinking...</em>');
            $.post(ajaxurl, {
                action: 'npa_ask_ai',
                question: question,
                _wpnonce: '<?php echo wp_create_nonce("npa_ask_ai_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    $('#npa-ai-answer').html('<strong>Answer:</strong><br>' + response.data.answer);
                } else {
                    $('#npa-ai-answer').html('<span style="color:red;">' + (response.data ? response.data : 'Error') + '</span>');
                }
            });
        });
    })(jQuery);
    var npa_ask_ai_nonce = '<?php echo wp_create_nonce("npa_ask_ai_nonce"); ?>';
    </script>
<?php
}

/**
 * Render the Python code runner page.
 */
function npa_run_python_page() {
    require_once plugin_dir_path(__FILE__) . '../python/runner.php';
    npa_python_runner_page();
}

add_action('admin_init', function() {
    // === Proofreader Settings ===
    register_setting('npa_settings_group', 'npa_system_role_prompt', [
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);
    register_setting('npa_settings_group', 'npa_temperature', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_max_tokens', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_dashboard_system_prompt', [
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);

    // === RAG Settings ===
    register_setting('npa_settings_group', 'npa_rag_enabled', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_rag_data_source', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_rag_context_window', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_rag_temperature', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_rag_max_tokens', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    // === API Provider Settings ===
    register_setting('npa_settings_group', 'npa_ai_provider', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_openai_api_key', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_openai_gpt_model', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_anthropic_api_key', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_anthropic_gpt_model', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    // === Settings Sections ===
    add_settings_section('npa_main_section', '', null, 'npa-settings');
    add_settings_section('npa_rag_section', '', null, 'npa-rag-settings');

    // === Proofreader Settings Fields ===
    add_settings_field(
        'npa_system_role_prompt',
        'Proofreader System Role Prompt',
        function() {
            $value = esc_textarea(get_option('npa_system_role_prompt', 'あなたは日本語のスペルと文法の校正者です。まずスペルミスを優先的に指摘し修正案を出し、その後に文法ミスを指摘し修正案を出してください。'));
            echo "<textarea name='npa_system_role_prompt' rows='10' cols='100'>$value</textarea>";
            echo "<p class='description'>Prompt for the proofreader AI system role and behavior.</p>";
        },
        'npa-settings',
        'npa_main_section'
    );
    add_settings_field('npa_temperature', 'Proofreader Temperature', function() {
        $value = esc_attr(get_option('npa_temperature', 1));
        echo "<input type='number' step='0.01' min='0' max='2' name='npa_temperature' value='$value' />";
        echo "<p class='description'>Temperature for the proofreader AI model (randomness). Min: 0, Max: 2.</p>";
    }, 'npa-settings', 'npa_main_section');
    add_settings_field('npa_max_tokens', 'Proofreader Max Tokens', function() {
        $value = esc_attr(get_option('npa_max_tokens', 1000));
        echo "<input type='number' step='1' min='1' max='10000' name='npa_max_tokens' value='$value' />";
        echo "<p class='description'>Max tokens for the proofreader AI response. Min: 1, Max: 10000.</p>";
    }, 'npa-settings', 'npa_main_section');

    // === RAG Settings Fields ===
    add_settings_field('npa_rag_enabled', 'Enable RAG', function() {
        $value = esc_attr(get_option('npa_rag_enabled', '0'));
        echo "<input type='checkbox' name='npa_rag_enabled' value='1' " . checked($value, '1', false) . " /> Enable Retrieval-Augmented Generation (RAG) features.";
    }, 'npa-rag-settings', 'npa_rag_section');
    add_settings_field('npa_rag_data_source', 'RAG Data Source URL', function() {
        $value = esc_attr(get_option('npa_rag_data_source', ''));
        echo "<input type='text' name='npa_rag_data_source' value='$value' size='60' />";
        echo "<p class='description'>Data source URL for RAG context retrieval.</p>";
    }, 'npa-rag-settings', 'npa_rag_section');
    add_settings_field('npa_rag_context_window', 'RAG Context Window Size', function() {
        $value = esc_attr(get_option('npa_rag_context_window', 2048));
        echo "<input type='number' name='npa_rag_context_window' value='$value' min='1' max='10000' />";
        echo "<p class='description'>Max tokens for the RAG context window.</p>";
    }, 'npa-rag-settings', 'npa_rag_section');
    add_settings_field('npa_rag_temperature', 'RAG Temperature', function() {
        $value = esc_attr(get_option('npa_rag_temperature', 1));
        echo "<input type='number' step='0.01' min='0' max='2' name='npa_rag_temperature' value='$value' />";
        echo "<p class='description'>Temperature for the RAG AI model (randomness). Min: 0, Max: 2.</p>";
    }, 'npa-rag-settings', 'npa_rag_section');
    add_settings_field('npa_rag_max_tokens', 'RAG Max Tokens', function() {
        $value = esc_attr(get_option('npa_rag_max_tokens', 1000));
        echo "<input type='number' step='1' min='1' max='8191' name='npa_rag_max_tokens' value='$value' />";
        echo "<p class='description'>Max tokens for the RAG AI response. Min: 1, Max: 8191.</p>";
    }, 'npa-rag-settings', 'npa_rag_section');
    add_settings_field(
        'npa_dashboard_system_prompt',
        'RAG System Role Prompt',
        function() {
            $value = esc_textarea(get_option('npa_dashboard_system_prompt', 'You are an AI assistant for the WordPress Dashboard. Answer user questions helpfully.'));
            echo "<textarea name='npa_dashboard_system_prompt' rows='5' cols='100'>$value</textarea>";
            echo "<p class='description'>Prompt for the AI Assistant box in the Dashboard widget only.</p>";
        },
        'npa-rag-settings',
        'npa_rag_section'
    );
});
