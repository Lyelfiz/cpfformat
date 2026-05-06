document.addEventListener("DOMContentLoaded", function() {

    // run if we are on the login page
    if (window.location.pathname.endsWith('/login/index.php')) {
        console.log("📌 CPF Formatting enable");

        const usernameField = document.getElementById("username");
        if (!usernameField) return;

        // Add placeholder text to the username field
        //usernameField.setAttribute("placeholder", "CPF / E-mail");

        // Function to check if the value is a valid CPF (11 digits)
        // Valida CPF (dígitos verificadores)
        function isValidCPF(cpf) {
            cpf = cpf.replace(/\D/g, "");

            if (cpf.length !== 11) return false;

            // Elimina CPFs inválidos conhecidos (11111111111, 00000000000, etc)
            if (/^(\d)\1{10}$/.test(cpf)) return false;

            let sum = 0;
            let remainder;

            // 1º dígito verificador
            for (let i = 1; i <= 9; i++)
                sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);

            remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            if (remainder !== parseInt(cpf.substring(9, 10))) return false;

            sum = 0;

            // 2º dígito verificador
            for (let i = 1; i <= 10; i++)
                sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);

            remainder = (sum * 10) % 11;
            if (remainder === 10 || remainder === 11) remainder = 0;
            if (remainder !== parseInt(cpf.substring(10, 11))) return false;

            return true;
        }

        // Function to format CPF as 000.000.000-00
        function formatCPF(value) {
            return value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
        }

        // Add event listeners for blur
        usernameField.addEventListener("blur", function(e) {
            let value = e.target.value.replace(/\D/g, "");
            if (isValidCPF(value)) {
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
