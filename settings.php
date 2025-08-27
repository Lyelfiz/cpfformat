<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;

if ($hassiteconfig) {

    // Create settings page - using simple strings to avoid language loading issues
    $settings = new admin_settingpage('local_cpfformat', get_string('pluginname', 'local_cpfformat'));

    if ($ADMIN->fulltree) {
        // Instructions
        $settings->add(new admin_setting_heading(
            'local_cpfformat/instructions',
            get_string('instructions', 'local_cpfformat'),
            get_string('descinstructions', 'local_cpfformat')
        ));

        // Enable/disable plugin setting
        $settings->add(new admin_setting_configcheckbox(
            'local_cpfformat/enabled',
            get_string('enabled_register', 'local_cpfformat'),
            get_string('enabled_desc', 'local_cpfformat'),
            0 // Default enabled
        ));

        // Enable manual HTML injection setting
        $settings->add(new admin_setting_heading(
            'local_cpfformat/inject_html_manual',
            get_string('inject_html_manual', 'local_cpfformat'),
            get_string('manual_js_code_desc', 'local_cpfformat'),
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
