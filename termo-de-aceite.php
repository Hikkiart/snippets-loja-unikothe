function shortcode_termo_aceite_html() {
    if (!is_user_logged_in() || WC()->cart->is_empty()) return '';

    $user = wp_get_current_user();
    $nome_cliente = $user->first_name . ' ' . $user->last_name;
    $cpf = xprofile_get_field_data(44, $user->ID);
    $tamanho = sanitize_text_field($_POST['tamanho_jaqueta'] ?? '---');
    $dia = date('j');
    $mes = traduzir_mes(date('n'));
    $ano = date('Y');
    $data_formatada = "$dia de $mes de $ano";
    $data_completa = date('d/m/Y');

    // Pegando o primeiro item do carrinho (pode ajustar se quiser múltiplos)
    $cart_item = current(WC()->cart->get_cart());
    $product = wc_get_product($cart_item['product_id']);
    $nome_produto = $product->get_name();
    $descricao = $product->get_description();
$descricao_linhas = explode("\n", strip_tags($descricao));
$primeira_linha = trim($descricao_linhas[0]);

// Tenta extrair o valor da primeira linha
preg_match('/\d+(?:[\.,]\d{2})?/', $primeira_linha, $matches);
$valor = isset($matches[0]) ? floatval(str_replace(',', '.', $matches[0])) : $product->get_price();

    $parcela = number_format($valor / 3, 2, ',', '');

    ob_start();
    ?>
    <div style="max-width: 800px; margin: auto; font-family: Arial, sans-serif; color: #333; padding: 20px;">
        <h2 style="text-align: center;">TERMO DE RECEBIMENTO E AUTORIZAÇÃO DE DESCONTO</h2>

        <p>
            Pelo presente instrumento particular, eu <strong><?= esc_html($nome_cliente); ?></strong>,
            portador do RG _______________________, inscrito no CPF sob o nº <strong><?= esc_html($cpf); ?></strong>,
            declaro para todos os fins de direito que, na data de <strong><?= $data_completa; ?></strong>, recebi da empresa <strong>Trans Kothe Transportes</strong> os seguintes itens:
        </p>

        <p>
            <strong><?= esc_html($nome_produto); ?> tamanho <?= esc_html($tamanho); ?></strong> no valor de 
            <strong>R$ <?= number_format($valor, 2, ',', '.'); ?></strong>, em 3 parcelas iguais de 
            <strong>R$ <?= $parcela; ?></strong> cada, conforme o artigo 462 da CLT.
        </p>

        <p style="margin-top: 30px;">
            Jundiaí-SP, <strong><?= $data_formatada; ?></strong>
        </p>

        <p style="margin-top: 50px;">
            Assinatura do colaborador: <strong><?= esc_html($nome_cliente); ?></strong>
        </p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('termo_aceite', 'shortcode_termo_aceite_html');
