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
            0 // Default disabled
        ));

        // Enable manual HTML injection setting
        $settings->add(new admin_setting_heading(
            'local_cpfformat/inject_html_manual',
            get_string('inject_html_manual', 'local_cpfformat'),
            get_string('manual_js_code_desc', 'local_cpfformat'),
        ));

        // Enable/disable in login page plugin setting
        $settings->add(new admin_setting_configcheckbox(
            'local_cpfformat/enabledcpfformatedlogin',
            get_string('enabledcpfformatedlogin', 'local_cpfformat'),
            get_string('enabledcpfformatedlogin_desc', 'local_cpfformat'),
            0 // Default disabled
        ));
    }

    $ADMIN->add('localplugins', $settings);
}

// Manage the inclusion of the JS script in additionalhtmlhead based on the setting
$enableloginscript = get_config('local_cpfformat', 'enabledcpfformatedlogin') ?? 0;
$scriptlocal = '<script src="' . $CFG->wwwroot . '/local/cpfformat/cpfmask.js.php"></script>';

// If enabled, ensure the script is included in additionalhtmlhead
if ($enableloginscript) {
    if (empty($CFG->additionalhtmlhead) ||
        strpos($CFG->additionalhtmlhead, $scriptlocal) === false) {
        set_config('additionalhtmlhead', $scriptlocal);
    }
} else {
    // If disabled, remove the script if it exists
    if (!empty($CFG->additionalhtmlhead) &&
        strpos($CFG->additionalhtmlhead, $scriptlocal) !== false) {
        $newvalue = str_replace($scriptlocal, '', $CFG->additionalhtmlhead);
        set_config('additionalhtmlhead', $newvalue);
    }
}