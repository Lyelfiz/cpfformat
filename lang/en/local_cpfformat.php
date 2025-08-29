<?php

/**
 * Language file.
 *
 * @package   local_cpfformat
 * @copyright 2025 Luiz Henrique Carvalho Vacilio - lh05447511@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Global config variable
$jsurl = $CFG->wwwroot . '/local/cpfformat/cpfmask.js.php';
$enableselfregister = '<a href="' . $CFG->wwwroot . '/admin/settings.php?section=manageauths" target="_blank">Administration → Plugins → Authentication → Manage authentication → Self registration</a>';
$enableformatlogin = '<a href="' . $CFG->wwwroot . '/admin/settings.php?section=additionalhtml" target="_blank">Administration → Appearance → Additional HTML</a>';

// Language strings for the CPF format plugin
$string['pluginname'] = 'Formatting CPF Login/Register';

// Login Form Translation
$string['cpfplaceholder'] = 'CPF or E-mail';

// Register Form Translation
$string['cpfform'] = 'CPF';
$string['cpfformmatch'] = 'CPF (again)';
$string['selectacity'] = 'Select a city...';
$string['invalidcpf'] = 'CPF needs to be in the format: 000.000.000-00';
$string['invalidcpfverify'] = 'Invalid CPF. Please check the numbers entered.';
$string['thiscpfinuse'] = 'This CPF is already in use for another account.';
$string['thiscpfisregistred'] = 'This CPF is already registered in the system.';
$string['thiscpfisneedinformatted'] = 'The profile CPF must be in the correct format: 000.000.000-00';
$string['thecpfnotmatch'] = 'The CPFs entered do not match.';

// Settings for CPF format plugin
$string['instructions'] = 'Instrutions';
$string['descinstructions'] = 'For use, Enable options below,<br>1) <strong>Config method Self Registration for "Email-based self-registration" </strong>';
$string['descinstructions'] .= ' You can access in ' . $enableselfregister . '.';

$string['enabled_register'] = 'Enable CPF formatting on registration formulary';
$string['enabled_desc'] = 'If enable, the field CPF is formatted for 000.000.000-00, and will validate CPF, and if exist one "User profile fields" called "CPF", will formatted this field too, and will comparing with the other field CPF.';

$string['inject_html_manual'] = 'Formatting CPF Login Page via manual HTML';
$string['manual_js_code_desc'] = 'For active the formatted "CPF" in login screen, will need input the script below in: <strong>Additional HTML</strong> Of Moodle <strong>in field (Within HEAD)</strong>:<br>';
$string['manual_js_code_desc'] .= '<code>&lt;script src="' . $jsurl . '"&gt;&lt;/script&gt;</code><br>';
$string['manual_js_code_desc'] .= 'You can access the field "Additional HTML" in ' . $enableformatlogin .'.';

$string['enabledcpfformatedlogin'] = 'Force script code in Additional HTML field';
$string['enabledcpfformatedlogin_desc'] = 'If enable, will set forced the script in HTML head in <strong>aditional HTML field</strong>, to format the CPF field in login page.<br><strong>WARNING: If you have other plugins that also add code to this field, it may cause conflicts. Use with caution.</strong>';