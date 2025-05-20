<?php
add_action('admin_menu', function() {
    add_options_page('Nihongo Proofreader AI Settings', 'Nihongo Proofreader AI', 'manage_options', 'npa-settings', 'npa_settings_page');
});

function npa_settings_page() {
    ?>
    <div class="wrap">
        <h1>Nihongo Proofreader AI Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('npa_settings_group');
            do_settings_sections('npa-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('npa_settings_group', 'npa_openai_api_key', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('npa_settings_group', 'npa_gpt_model', [
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    add_settings_section('npa_main_section', '', null, 'npa-settings');
    add_settings_field('npa_openai_api_key', 'OpenAI API Key', function() {
        $value = esc_attr(get_option('npa_openai_api_key', ''));
        echo "<input type='text' name='npa_openai_api_key' value='$value' size='50' />";
    }, 'npa-settings', 'npa_main_section');
    add_settings_field('npa_gpt_model', 'GPT Model', function() {
        $current = esc_attr(get_option('npa_gpt_model', 'gpt-3.5-turbo'));
        ?>
        <select name="npa_gpt_model">
            <option value="gpt-4.1" <?php selected($current, 'gpt-4.1'); ?>>gpt-4.1</option>
            <option value="gpt-4.1-mini" <?php selected($current, 'gpt-4.1-mini'); ?>>gpt-4.1-mini</option>
            <option value="gpt-4.1-nano" <?php selected($current, 'gpt-4.1-nano'); ?>>gpt-4.1-nano</option>
            <option value="gpt-4" <?php selected($current, 'gpt-4'); ?>>gpt-4</option>
            <option value="gpt-3.5-turbo" <?php selected($current, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo</option>
        </select>
        <?php
    }, 'npa-settings', 'npa_main_section');
});
