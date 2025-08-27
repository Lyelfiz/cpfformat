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
$string['pluginname'] = 'Formatação de CPF Login/Cadastro';

// Login Form Translation
$string['cpfplaceholder'] = 'CPF ou E-mail';

// Register Form Translation
$string['cpfform'] = 'CPF';
$string['cpfformmatch'] = 'CPF (novamente)';
$string['selectacity'] = 'Selecione uma cidade...';
$string['invalidcpf'] = 'O CPF deve estar no formato: 000.000.000-00';
$string['invalidcpfverify'] = 'CPF inválido verifique os números e tente novamente.';
$string['thiscpfinuse'] = 'Esse CPF já está sendo usado por outra conta.';
$string['thiscpfisregistred'] = 'Esse CPF já está registrado no sistema.';
$string['thiscpfisneedinformatted'] = 'O CPF deve estar no formato correto: 000.000.000-00';
$string['thecpfnotmatch'] = 'Os CPF não são iguais.';

// Settings for CPF format plugin
$string['instructions'] = 'Instruções';
$string['descinstructions'] = 'Para usar:<br>1) Habilite as opções abaixo<br>2) <strong>Configure Auto-cadastro para "Auto-cadastro por e-mail"</strong>';
$string['descinstructions'] .= ' Você pode acessar por aqui ' . $enableselfregister . '.';

$string['enabled_register'] = 'Habilitar formatação de CPF no cadastro';
$string['enabled_desc'] = 'Quando habilitado, o campo usuário no cadastro será formatado como CPF 000.000.000-00, e se existir um campo personalizado chamado "CPF", este campo também será formatado e fará comparação entre os dois campos.';

$string['inject_html_manual'] = 'Formatação de CPF Página de Login via HTML manual';
$string['manual_js_code_desc'] = 'Para ativar a formatação de CPF na página de login, adicione o código abaixo no <strong>HTML adicional</strong> do Moodle <strong>no campo (Dentro da tag HEAD
)</strong>:<br>';
$string['manual_js_code_desc'] .= '<code>&lt;script src="' . $jsurl . '"&gt;&lt;/script&gt;</code><br>';
$string['manual_js_code_desc'] .= 'Você pode acessar o HTML adicional em ' . $enableformatlogin . '.';