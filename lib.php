<?php
defined('MOODLE_INTERNAL') || die();

/*
// Old Hook to inject JS in the login page if enabled in settings
function local_cpfformat_before_http_headers() {
    global $PAGE;

    //  Only add script if enabled in settings
    if (get_config('local_cpfformat', 'enabledcpfformatedlogin')) {
        $PAGE->requires->js(new moodle_url('/local/cpfformat/cpfmask.js.php'));
    }
}
*/

// Extend the signup form to include CPF formatting and Brazilian cities
function local_cpfformat_extend_signup_form($mform)
{
    global $CFG, $PAGE;

    if (empty($CFG->registerauth) || $CFG->registerauth !== 'email') {
        return;
    }

    //Verify if formatting on registration is enabled
    $enabled = get_config('local_cpfformat', 'enabled');

    // Fallback for manual config in config.php
    if (empty($enabled) && isset($CFG->local_cpfformat_enabled)) {
        $enabled = $CFG->local_cpfformat_enabled;
    }

    // Desable if manually disabled in config.php
    if (isset($CFG->local_cpfformat_enabled) && $CFG->local_cpfformat_enabled === false) {
        return;
    }

    // If not enabled, exit
    if (empty($enabled)) {
        return;
    }

    // Modify form field city to use Brazilian list with autocomplete
    if ($mform->elementExists('city')) {
        $mform->removeElement('city');

        // add city field with Brazilian cities autocomplete
        $cities = get_brazilian_cities();
        $mform->addElement(
            'autocomplete',
            'city',
            get_string('city'),
            $cities,
            array(
                'noselectionstring' => get_string('selectacity', 'local_cpfformat'),
                'allowclear' => true
            )
        );
        $mform->addRule('city', get_string('required'), 'required', null, 'client');
    }

    // Add CPF field with formatting
    if ($mform->elementExists('username')) {
        $username_element = $mform->getElement('username');
        if ($mform->elementExists('profile_field_cpf')) {
            $username_element->setLabel(get_string('cpfformmatch', 'local_cpfformat'));
        }
        else {
            $username_element->setLabel(get_string('cpfform', 'local_cpfformat'));
        }
        $username_element->updateAttributes(array(
            'placeholder' => '000.000.000-00',
            'maxlength' => '14',
            'id' => 'cpf-input'
        ));

        // Add custom JavaScript for CPF formatting
        $PAGE->requires->js_init_code(local_cpfformat_get_js());
    }

}

// Validate CPF format and uniqueness on signup
function local_cpfformat_validate_extend_signup_form($data)
{
    global $CFG, $DB;

    if (empty($CFG->registerauth) || $CFG->registerauth !== 'email') {
        return array();
    }

    $enabled = get_config('local_cpfformat', 'enabled');
    if (empty($enabled) && isset($CFG->local_cpfformat_enabled)) {
        $enabled = $CFG->local_cpfformat_enabled;
    }
    if (isset($CFG->local_cpfformat_enabled) && $CFG->local_cpfformat_enabled === false) {
        return array();
    }
    if (empty($enabled)) {
        return array();
    }

    $errors = array();

    // Validate CPF format and uniqueness
    if (isset($data['username'])) {
        if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $data['username'])) {
            $errors['username'] = get_string('invalidcpf', 'local_cpfformat');
        } else {
            $cpf = preg_replace('/\D/', '', $data['username']);

            // Check if CPF is valid
            if (!local_cpfformat_validate_cpf($cpf)) {
                $errors['username'] = get_string('invalidcpfverify', 'local_cpfformat');
            } else {
                $cpf_formatado = $data['username'];

                if (
                    $DB->record_exists('user', array('username' => $cpf)) ||
                    $DB->record_exists('user', array('username' => $cpf_formatado))
                ) {
                    $errors['username'] = get_string('thiscpfinuse', 'local_cpfformat');
                }

                $sql = "SELECT u.id FROM {user} u 
                        JOIN {user_info_data} uid ON uid.userid = u.id 
                        JOIN {user_info_field} uif ON uif.id = uid.fieldid 
                        WHERE uif.shortname = 'cpf' 
                        AND (uid.data = ? OR uid.data = ?) 
                        AND u.deleted = 0";

                if ($DB->record_exists_sql($sql, array($cpf, $cpf_formatado))) {
                    $errors['username'] = get_string('thiscpfisregistred', 'local_cpfformat');
                }
            }

            // Validate profile field CPF if exists
            if (!empty($data['profile_field_cpf'])) {
                if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $data['profile_field_cpf'])) {
                    $errors['profile_field_cpf'] = get_string('thiscpfisneedinformatted', 'local_cpfformat');
                } else if ($data['username'] !== $data['profile_field_cpf']) {
                    $errors['username'] = get_string('thecpfnotmatch', 'local_cpfformat');
                    $errors['profile_field_cpf'] = get_string('thecpfnotmatch', 'local_cpfformat');
                }
            }
        }
    }

    return $errors;
}

// Validate CPF format
// This function checks if the CPF is valid according to the Brazilian CPF rules.
// It returns true if the CPF is valid, false otherwise.
function local_cpfformat_validate_cpf($cpf)
{
    $cpf = preg_replace('/\D/', '', $cpf);

    if (strlen($cpf) !== 11) {
        return false;
    }

    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : 11 - $resto;

    if (intval($cpf[9]) !== $dv1) {
        return false;
    }

    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : 11 - $resto;

    if (intval($cpf[10]) !== $dv2) {
        return false;
    }

    return true;
}

// Get Brazilian cities from local file or API IBGE
function get_brazilian_cities()
{
    global $CFG;

    $possible_paths = [
        __DIR__ . '/municipios.json',
    ];

    $json_file_path = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $json_file_path = $path;
            break;
        }
    }

    if ($json_file_path) {
        $cities = file_get_contents($json_file_path);
        $cities_array = json_decode($cities, true);
        $city_names = array_column($cities_array, 'nome');
        sort($city_names);

        $sorted_cities = array('' => '');
        foreach ($city_names as $name) {
            $sorted_cities[$name] = $name;
        }
        return $sorted_cities;
    } else {
        return get_brazilian_cities_from_api();
    }
}

// Fetch Brazilian cities from IBGE API if local file is not available
function get_brazilian_cities_from_api()
{
    $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios';
    $cities = file_get_contents($url);

    if ($cities) {
        file_put_contents(__DIR__ . '/municipios.json', $cities);
        $cities_array = json_decode($cities, true);
        $city_names = array_column($cities_array, 'nome');
        sort($city_names);

        $sorted_cities = array('' => '');
        foreach ($city_names as $name) {
            $sorted_cities[$name] = $name;
        }
        return $sorted_cities;
    } else {
        return array(
            '' => '',
            'São Paulo' => 'São Paulo',
            'Rio de Janeiro' => 'Rio de Janeiro',
            'Brasília' => 'Brasília',
            'Salvador' => 'Salvador',
            'Fortaleza' => 'Fortaleza'
        );
    }
}

// Get JavaScript code for CPF formatting
// This function returns a string containing the JavaScript code that formats CPF input fields.
function local_cpfformat_get_js()
{
    return "
    require(['jquery'], function($) {
        $(document).ready(function() {
            setTimeout(function() {
                reorderFormFields();
            }, 100);

            $('input[name=\"profile_field_cpf\"]').attr('placeholder', '000.000.000-00');

            function reorderFormFields() {
                var form = $('#mform1, form[data-form-id=\"signup\"], .mform');
                if (form.length === 0) {
                    form = $('form').first();
                }

                if (form.length > 0) {
                    var nameFields = form.find('input[name=\"firstname\"], input[name=\"lastname\"]').closest('.fitem');
                    var emailField = form.find('input[name=\"email\"]').closest('.fitem');
                    var email2Field = form.find('input[name=\"email2\"]').closest('.fitem');
                    var passwordField = form.find('input[name=\"password\"]').closest('.fitem');
                    var passwordInfo = form.find('.fitem').has('*[id*=\"passwordpolicy\"]');
                    var countryField = form.find('select[name=\"country\"]').closest('.fitem');
                    var cityField = form.find('input[name=\"city\"], select[name=\"city\"]').closest('.fitem');
                    var usernameField = form.find('input[name=\"username\"]').closest('.fitem');
                    var profileFields = form.find('.fitem').has('*[name*=\"profile_field_\"]');
                    var captchaField = form.find('.fitem').has('*[name*=\"recaptcha\"]');
                    var submitButtons = form.find('.fitem_actionbuttons, .fitem').has('input[type=\"submit\"]');

                    var orderedFields = [];
                    nameFields.each(function() { orderedFields.push($(this)); });
                    if (emailField.length) orderedFields.push(emailField);
                    if (email2Field.length) orderedFields.push(email2Field);
                    if (passwordInfo.length) orderedFields.push(passwordInfo);
                    if (passwordField.length) orderedFields.push(passwordField);
                    if (countryField.length) orderedFields.push(countryField);
                    if (cityField.length) orderedFields.push(cityField);
                    profileFields.each(function() { orderedFields.push($(this)); });
                    if (usernameField.length) orderedFields.push(usernameField);
                    if (captchaField.length) orderedFields.push(captchaField);
                    if (submitButtons.length) orderedFields.push(submitButtons);

                    var container = form.find('.fcontainer, fieldset').first();
                    if (container.length === 0) {
                        container = form;
                    }

                    $.each(orderedFields, function(index, field) {
                        if (field && field.length) {
                            container.append(field);
                        }
                    });
                }
            }

            function formatCPF(value) {
                value = value.replace(/\D/g, '');
                value = value.substring(0, 11);

                if (value.length <= 3) {
                    return value;
                } else if (value.length <= 6) {
                    return value.replace(/(\d{3})(\d+)/, '\$1.\$2');
                } else if (value.length <= 9) {
                    return value.replace(/(\d{3})(\d{3})(\d+)/, '\$1.\$2.\$3');
                } else {
                    return value.replace(/(\d{3})(\d{3})(\d{3})(\d+)/, '\$1.\$2.\$3-\$4');
                }
            }

            function getNewCursorPosition(oldValue, newValue, oldCursorPos) {
                var oldSpecialChars = (oldValue.substring(0, oldCursorPos).match(/[.-]/g) || []).length;
                var newSpecialChars = (newValue.substring(0, oldCursorPos + (newValue.length - oldValue.length)).match(/[.-]/g) || []).length;
                var newPos = oldCursorPos + (newSpecialChars - oldSpecialChars);
                return Math.min(newPos, newValue.length);
            }

            function applyFormatting(element) {
                var cursorPos = element.selectionStart;
                var oldValue = element.value;
                var newValue = formatCPF(oldValue);

                if (oldValue !== newValue) {
                    element.value = newValue;
                    var newCursorPos = getNewCursorPosition(oldValue, newValue, cursorPos);
                    setTimeout(function() {
                        element.setSelectionRange(newCursorPos, newCursorPos);
                    }, 0);
                }
            }

            $('#cpf-input, input[name=\"username\"]').on('input', function() {
                applyFormatting(this);
            });

            $('input[name=\"profile_field_cpf\"]').on('input', function() {
                applyFormatting(this);
            });

            $('#cpf-input, input[name=\"username\"], input[name=\"profile_field_cpf\"]').on('keydown', function(e) {
                if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true) ||
                    (e.keyCode >= 35 && e.keyCode <= 40)) {
                    return;
                }

                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }

                var currentDigits = this.value.replace(/\D/g, '');
                if (currentDigits.length >= 11 && [8, 9, 27, 13, 46].indexOf(e.keyCode) === -1) {
                    e.preventDefault();
                }
            });

            $('#cpf-input, input[name=\"username\"], input[name=\"profile_field_cpf\"]').on('paste', function(e) {
                var element = this;
                setTimeout(function() {
                    applyFormatting(element);
                }, 0);
            });
        });
    });
    ";
}
