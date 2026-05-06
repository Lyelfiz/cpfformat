<?php
// Script para baixar e limpar os dados das cidades brasileiras

$url = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios';

echo "Baixando dados da API IBGE...\n";

// Usar curl se disponível, senão tenta file_get_contents
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 30,
        'header' => "User-Agent: Mozilla/5.0\r\n"
    ]
]);

try {
    $json = file_get_contents($url, false, $context);
    
    if ($json === false) {
        throw new Exception("Falha ao baixar dados");
    }
    
    echo "Dados baixados: " . strlen($json) . " bytes\n";
    
    // Validar JSON
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg());
    }
    
    echo "Total de cidades: " . count($data) . "\n";
    
    // Extrair apenas nomes das cidades
    $cities = [];
    foreach ($data as $city) {
        if (isset($city['nome'])) {
            $cities[] = $city['nome'];
        }
    }
    
    sort($cities);
    
    echo "Cidades extraídas: " . count($cities) . "\n";
    
    // Salvar no formato apropriado para o autocomplete
    $output = json_encode($cities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    $filepath = __DIR__ . '/municipios.json';
    $written = file_put_contents($filepath, $output);
    
    if ($written === false) {
        throw new Exception("Erro ao escrever arquivo");
    }
    
    echo "Arquivo salvo: {$filepath} (" . $written . " bytes)\n";
    
    // Validar escrita
    $verify = json_decode(file_get_contents($filepath), true);
    if (!is_array($verify)) {
        throw new Exception("Falha ao validar arquivo");
    }
    
    echo "Validação: OK\n";
    echo "Primeira cidade: " . $verify[0] . "\n";
    echo "Última cidade: " . $verify[count($verify)-1] . "\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>
