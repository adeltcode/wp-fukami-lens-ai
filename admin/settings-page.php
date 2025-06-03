<?php
add_action('admin_menu', function() {
    add_options_page('Nihongo Proofreader AI Settings', 'Nihongo Proofreader AI', 'manage_options', 'npa-settings', 'npa_settings_page');
});

function npa_settings_page() {
    $current = esc_attr(get_option('npa_ai_provider', 'openai'));
    $openai_key = esc_attr(get_option('npa_openai_api_key', ''));
    $openai_model = esc_attr(get_option('npa_openai_gpt_model', 'gpt-3.5-turbo'));
    $anthropic_key = esc_attr(get_option('npa_anthropic_api_key', ''));
    $anthropic_model = esc_attr(get_option('npa_anthropic_gpt_model', 'claude-3-opus-20240229'));
    ?>
    <div class="wrap">
        <h1>Nihongo Proofreader AI Settings</h1>
        <form id="npa-settings-form" method="post" action="options.php">
            <?php settings_fields('npa_settings_group'); ?>

            <!-- Tab Selector -->
            <div id="npa-ai-tabs">
                <h2>Select API Provider</h2>
                <button type="button" class="npa-tab-btn<?php if ($current === 'openai') echo ' active'; ?>" data-provider="openai">
                    OpenAI
                    <span class="npa-tab-check"<?php if ($current !== 'openai') echo ' style="display:none;"'; ?>>
                        <svg width="16" height="16" viewBox="0 0 20 20"><polyline points="4,11 9,16 16,5" style="fill:none;stroke:green;stroke-width:2"/></svg>
                    </span>
                </button>
                <button type="button" class="npa-tab-btn<?php if ($current === 'anthropic') echo ' active'; ?>" data-provider="anthropic">
                    Anthropic
                    <span class="npa-tab-check"<?php if ($current !== 'anthropic') echo ' style="display:none;"'; ?>>
                        <svg width="16" height="16" viewBox="0 0 20 20"><polyline points="4,11 9,16 16,5" style="fill:none;stroke:green;stroke-width:2"/></svg>
                    </span>
                </button>
                <input type="hidden" name="npa_ai_provider" id="npa_ai_provider" value="<?php echo $current; ?>">
            </div>

            <!-- Tab Content: OpenAI -->
            <div class="npa-tab-content" id="npa_tab_content_openai">
                <label><strong>OpenAI API Key</strong></label><br>
                <input type="text" name="npa_openai_api_key" value="<?php echo $openai_key; ?>" size="50" />
                <p class="description">Enter your OpenAI API key to enable AI functionalities.</p>
                <label><strong>Model</strong></label><br>
                <select name="npa_openai_gpt_model">
                    <option value="gpt-4.1" <?php selected($openai_model, 'gpt-4.1'); ?>>gpt-4.1</option>
                    <option value="gpt-4.1-mini" <?php selected($openai_model, 'gpt-4.1-mini'); ?>>gpt-4.1-mini</option>
                    <option value="gpt-4.1-nano" <?php selected($openai_model, 'gpt-4.1-nano'); ?>>gpt-4.1-nano</option>
                    <option value="gpt-4" <?php selected($openai_model, 'gpt-4'); ?>>gpt-4</option>
                    <option value="gpt-3.5-turbo" <?php selected($openai_model, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo</option>
                </select>
                <p class='description'>Select the OpenAI model to use for processing.</p>
            </div>

            <!-- Tab Content: Anthropic -->
            <div class="npa-tab-content" id="npa_tab_content_anthropic">
                <label><strong>Anthropic API Key</strong></label><br>
                <input type="text" name="npa_anthropic_api_key" value="<?php echo $anthropic_key; ?>" size="50" />
                <p class="description">Enter your Anthropic API key to enable Anthropic AI functionalities.</p>
                <label><strong>Model</strong></label><br>
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
                <p class='description'>Select the Anthropic model to use for processing.</p>
            </div>

            <!-- Other Settings in a Consistent Box -->
            <h2>Other Settings</h2>
            <div class="npa-other-settings-box">
                <?php
                do_settings_sections('npa-settings');
                submit_button();
                ?>
            </div>
        </form>
    </div>
    <style>
        #npa-ai-tabs { margin-bottom: 0; }
        .npa-tab-btn { margin-right: 8px; padding: 6px 18px; border: none; background: #f9f9f9; cursor: pointer; border-radius: 4px 4px 0 0; position: relative; transform: translateY( 3px); }
        .npa-tab-btn.active { background: #fff; border-bottom: 1px solid #fff; font-weight: bold; transform: translateY(0); }
        .npa-tab-check { margin-left: 6px; vertical-align: middle; transform: translateY( 3px); }
        .npa-tab-btn:not(.active) .npa-tab-check { display: none; }
        .npa-tab-content { display: none; border-top: none; padding: 16px; background: #fff; }
        .npa-tab-content.active { display: block; }
        .npa-other-settings-box { padding: 16px; background: #fff; margin-top: 24px; }
    </style>
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
    })(jQuery);
    </script>
<?php
}

add_action('admin_init', function() {
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
    register_setting('npa_settings_group', 'npa_system_role_prompt', [
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);
    register_setting('npa_settings_group', 'npa_temperature', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_max_tokens', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);

    add_settings_section('npa_main_section', '', null, 'npa-settings');

    // Temperature
    add_settings_field('npa_temperature', 'Temperature', function() {
        $value = esc_attr(get_option('npa_temperature', 1));
        echo "<input type='number' step='0.01' min='0' max='2' name='npa_temperature' value='$value' />";
        echo "<p class='description'>Set the temperature for the AI model, affecting randomness in responses. Minimum: 0, Maximum: 2.</p>";
    }, 'npa-settings', 'npa_main_section');

    // Max Tokens
    add_settings_field('npa_max_tokens', 'Max Tokens', function() {
        $value = esc_attr(get_option('npa_max_tokens', 1000));
        echo "<input type='number' step='1' min='1' max='10000' name='npa_max_tokens' value='$value' />";
        echo "<p class='description'>Define the maximum number of tokens the AI can use in a response. Minimum: 1, Maximum: 10000.</p>";
    }, 'npa-settings', 'npa_main_section');

    // System Role Prompt
    add_settings_field(
        'npa_system_role_prompt',
        'System Role Prompt',
        function() {
            $value = esc_textarea(get_option('npa_system_role_prompt', 'あなたは日本語のスペルと文法の校正者です。まずスペルミスを優先的に指摘し修正案を出し、その後に文法ミスを指摘し修正案を出してください。'));
            echo "<textarea name='npa_system_role_prompt' rows='20' cols='100'>$value</textarea>";
            echo "<p class='description'>Provide a prompt to define the system role and behavior for the AI.</p>";
        },
        'npa-settings',
        'npa_main_section'
    );
});