<?php
// Script para adicionar validação de CPF no index.html

$file = 'index.html';
$content = file_get_contents($file);

// Código da função de validação de CPF
$cpfValidationCode = <<<'JS'
        // Função para validar CPF
        function validarCPF(cpf) {
            cpf = cpf.replace(/[^\d]+/g, '');
            if (cpf == '') return false;
            // Elimina CPFs invalidos conhecidos
            if (cpf.length != 11 ||
                cpf == "00000000000" ||
                cpf == "11111111111" ||
                cpf == "22222222222" ||
                cpf == "33333333333" ||
                cpf == "44444444444" ||
                cpf == "55555555555" ||
                cpf == "66666666666" ||
                cpf == "77777777777" ||
                cpf == "88888888888" ||
                cpf == "99999999999")
                return false;
            // Valida 1o digito
            add = 0;
            for (i = 0; i < 9; i++)
                add += parseInt(cpf.charAt(i)) * (10 - i);
            rev = 11 - (add % 11);
            if (rev == 10 || rev == 11)
                rev = 0;
            if (rev != parseInt(cpf.charAt(9)))
                return false;
            // Valida 2o digito
            add = 0;
            for (i = 0; i < 10; i++)
                add += parseInt(cpf.charAt(i)) * (11 - i);
            rev = 11 - (add % 11);
            if (rev == 10 || rev == 11)
                rev = 0;
            if (rev != parseInt(cpf.charAt(10)))
                return false;
            return true;
        }

        // Função para verificar CPF ao sair do campo
        function verificarCPF(input) {
            const cpf = input.value;
            // Só valida se tiver 11 dígitos ou mais (para não validar enquanto digita)
            if (cpf.replace(/\D/g, '').length === 11) {
                if (!validarCPF(cpf)) {
                    alert('CPF inválido! Por favor, verifique o número digitado.');
                    input.value = ''; // Limpa o campo se for inválido
                    input.focus();
                }
            }
        }
JS;

// Inserir as funções antes do fechamento do script
$content = str_replace('</script>', $cpfValidationCode . "\n    </script>", $content);

// Adicionar o evento onblur ao input de CPF
// Procurar o input com id="cpf" e adicionar onblur="verificarCPF(this)"
$content = preg_replace(
    '/(<input type="text" id="cpf"[^>]*?)(\/?>)/',
    '$1 onblur="verificarCPF(this)" $2',
    $content
);

file_put_contents($file, $content);
echo "Validação de CPF adicionada ao index.html!\n";
?>
