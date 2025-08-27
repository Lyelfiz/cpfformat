<?php
// Força que esse arquivo seja servido como JS
header('Content-Type: application/javascript');

// Bootstrap do Moodle
require(__DIR__ . '/../../config.php');

// Pega a string traduzida
$cpfplaceholder = get_string('cpfplaceholder', 'local_cpfformat');
?>

document.addEventListener("DOMContentLoaded", function() {

    // run if we are on the login page
    if (window.location.pathname.endsWith('/login/index.php')) {

        const usernameField = document.getElementById("username");
        if (!usernameField) return;

        // Add placeholder text (usando string do Moodle)
        usernameField.setAttribute("placeholder", "<?php echo addslashes($cpfplaceholder); ?>");

        // Function to check if the value is a valid CPF (11 digits)
        function shouldFormatAsCPF(value) {
            value = value.trim();
            return value.length === 11 && /^\d{11}$/.test(value);
        }

        // Function to format CPF as 000.000.000-00
        function formatCPF(value) {
            return value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
        }

        // Add event listeners for blur
        usernameField.addEventListener("blur", function(e) {
            let value = e.target.value;
            if (shouldFormatAsCPF(value)) {
                e.target.value = formatCPF(value);
            }
        });

        // Add event listener for focus to remove formatting
        usernameField.addEventListener("focus", function(e) {
            let value = e.target.value;
            if (/^\d{3}\.\d{3}\.\d{3}-\d{2}$/.test(value)) {
                e.target.value = value.replace(/\D/g, "");
            }
        });
    }
});
