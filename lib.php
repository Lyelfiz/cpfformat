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

function local_cpfformat_pre_user_signup($user) {

    if (!empty($_POST['profile_field_cpf'])) {

        $cpf = preg_replace('/\D/', '', $_POST['profile_field_cpf']);

        // Define como username
        $user->username = $cpf;
    }
}

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
        $formatusername = get_config('local_cpfformat', 'formatusername');
        if (!isset($formatusername) && isset($CFG->local_cpfformat_formatusername)) {
            $formatusername = $CFG->local_cpfformat_formatusername;
        }
        $formatusername = !empty($formatusername);

        // Username como CPF
        if ($formatusername && $mform->elementExists('username')) {
            $username = $mform->getElement('username');
            $username->setLabel(get_string('cpfform', 'local_cpfformat'));
            $username->updateAttributes([
                'placeholder' => '000.000.000-00',
                'maxlength'   => '14'
            ]);
        }

        // Profile field CPF
        if ($mform->elementExists('profile_field_cpf')) {
            $cpf = $mform->getElement('profile_field_cpf');
            $cpf->updateAttributes([
                'placeholder' => '000.000.000-00',
                'maxlength'   => '14'
            ]);
        }

        // 🔥 JS UMA ÚNICA VEZ
        $PAGE->requires->js_init_code(
            local_cpfformat_get_js($formatusername)
        );

        $enabled = get_config('local_cpfformat', 'modifynamesurname');

        if (empty($enabled)) {
            return;
        }

        $PAGE->requires->js_init_code("
            document.addEventListener('input', function(e) {
                if (e.target.name === 'firstname' || e.target.name === 'lastname') {
                    e.target.value = e.target.value.toUpperCase();
                }
            });

            document.addEventListener('submit', function(e) {
                const f = e.target.querySelector('input[name=\"firstname\"]');
                const l = e.target.querySelector('input[name=\"lastname\"]');
                if (f) f.value = f.value.toUpperCase();
                if (l) l.value = l.value.toUpperCase();
            });
        ");
}

// Validate CPF format and uniqueness on signup
function local_cpfformat_validate_extend_signup_form($data) {
    global $CFG, $DB;

    // Só valida cadastro por e-mail
    if (empty($CFG->registerauth) || $CFG->registerauth !== 'email') {
        return [];
    }

    // Plugin habilitado?
    $enabled = get_config('local_cpfformat', 'enabled');
    if (empty($enabled) && isset($CFG->local_cpfformat_enabled)) {
        $enabled = $CFG->local_cpfformat_enabled;
    }
    if (isset($CFG->local_cpfformat_enabled) && $CFG->local_cpfformat_enabled === false) {
        return [];
    }
    if (empty($enabled)) {
        return [];
    }

    // Flag: CPF no username?
    $formatusername = get_config('local_cpfformat', 'formatusername');
    if (!isset($formatusername) && isset($CFG->local_cpfformat_formatusername)) {
        $formatusername = $CFG->local_cpfformat_formatusername;
    }
    $formatusername = !empty($formatusername);

    $errors = [];

    /*
     * =========================================================
     * MODO 1 — CPF NO USERNAME (comportamento antigo)
     * =========================================================
     */
    if ($formatusername && !empty($data['username'])) {

        $cpfraw = $data['username'];

        if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cpfraw)) {
            $errors['username'] = get_string('invalidcpf', 'local_cpfformat');
            return $errors;
        }

        $cpf = preg_replace('/\D/', '', $cpfraw);

        if (!local_cpfformat_validate_cpf($cpf)) {
            $errors['username'] = get_string('invalidcpfverify', 'local_cpfformat');
            return $errors;
        }

        // CPF já usado como username
        if (
            $DB->record_exists('user', ['username' => $cpf]) ||
            $DB->record_exists('user', ['username' => $cpfraw])
        ) {
            $errors['username'] = get_string('thiscpfinuse', 'local_cpfformat');
            return $errors;
        }

        // CPF já usado em campo de perfil
        $sql = "SELECT 1
                  FROM {user} u
                  JOIN {user_info_data} uid ON uid.userid = u.id
                  JOIN {user_info_field} uif ON uif.id = uid.fieldid
                 WHERE uif.shortname = 'cpf'
                   AND (uid.data = ? OR uid.data = ?)
                   AND u.deleted = 0";

        if ($DB->record_exists_sql($sql, [$cpf, $cpfraw])) {
            $errors['username'] = get_string('thiscpfisregistred', 'local_cpfformat');
        }
    }

    /*
     * =========================================================
     * MODO 2 — CPF SOMENTE NO profile_field_cpf
     * =========================================================
     */
    if (!$formatusername && !empty($data['profile_field_cpf'])) {

        $cpfraw = $data['profile_field_cpf'];

        if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cpfraw)) {
            $errors['profile_field_cpf'] =
                get_string('thiscpfisneedinformatted', 'local_cpfformat');
            return $errors;
        }

        $cpf = preg_replace('/\D/', '', $cpfraw);

        if (!local_cpfformat_validate_cpf($cpf)) {
            $errors['profile_field_cpf'] =
                get_string('invalidcpfverify', 'local_cpfformat');
            return $errors;
        }

        // CPF já usado no campo de perfil
        $sql = "SELECT 1
                  FROM {user_info_data} uid
                  JOIN {user_info_field} uif ON uif.id = uid.fieldid
                 WHERE uif.shortname = 'cpf'
                   AND (uid.data = ? OR uid.data = ?)";

        if ($DB->record_exists_sql($sql, [$cpf, $cpfraw])) {
            $errors['profile_field_cpf'] =
                get_string('thiscpfisregistred', 'local_cpfformat');
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
function local_cpfformat_get_js($formatusername = false) {
    $formatusername = $formatusername ? 'true' : 'false';

    return "
    require(['jquery'], function($) {

        function formatCPF(value) {
            value = value.replace(/\\D/g, '').substring(0, 11);

            if (value.length <= 3) {
                return value;
            } else if (value.length <= 6) {
                return value.replace(/(\\d{3})(\\d+)/, '\$1.\$2');
            } else if (value.length <= 9) {
                return value.replace(/(\\d{3})(\\d{3})(\\d+)/, '\$1.\$2.\$3');
            } else {
                return value.replace(/(\\d{3})(\\d{3})(\\d{3})(\\d+)/, '\$1.\$2.\$3-\$4');
            }
        }

        function bindCPF(selector) {
            $(document).on('input', selector, function() {
                var newValue = formatCPF(this.value);
                if (this.value !== newValue) {
                    this.value = newValue;
                }
            });

            $(document).on('focus', selector, function() {
                $(this)
                    .attr('placeholder', '000.000.000-00')
                    .attr('maxlength', '14');
            });
        }

        // Sempre formata o profile_field_cpf
        bindCPF('input[name=\"profile_field_cpf\"]');

        // Só formata username se habilitado
        if ($formatusername) {
            bindCPF('input[name=\"username\"]');
        }

    });
    ";
}