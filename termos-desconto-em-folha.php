// === FUNÇÃO AUXILIAR: verifica se o carrinho tem produtos da categoria ===
function carrinho_tem_categoria($categoria_slug) {
    foreach (WC()->cart->get_cart() as $item) {
        $product_id = $item['product_id'];
        if (has_term($categoria_slug, 'product_cat', $product_id)) {
            return true;
        }
    }
    return false;
}

// === AVISO NO CARRINHO: link para o termo (não obriga aceitar) ===
add_action('woocommerce_before_cart_totals', 'mostrar_termo_carrinho_personalizado');
function mostrar_termo_carrinho_personalizado() {
    if (!carrinho_tem_categoria('desconto-em-folha')) return;

    $link_termo_pdf = site_url('/termo-de-aceite');
    echo '<div class="termo-aviso-carrinho" style="margin: 20px 0; padding: 15px; border: 1px solid #ccc; background: #f9f9f9;">';
    echo '<strong>Antes de finalizar sua compra:</strong> por favor, leia e concorde com nosso <a href="' . esc_url($link_termo_pdf) . '" target="_blank">termo de aceite</a>. Você deverá aceitar o termo na próxima etapa (finalização de compra).';
    echo '</div>';
}

// === CHECKOUT: adiciona checkbox, campo de jaqueta e campo de RG ===
add_action('woocommerce_review_order_before_submit', 'adicionar_termo_personalizado_checkout', 9);
function adicionar_termo_personalizado_checkout() {
    if (!carrinho_tem_categoria('desconto-em-folha')) return;

    $link_termo_pdf = site_url('/termo-de-aceite');

    echo '<div id="termo_customizado_checkout" style="margin-bottom:20px;">';
    echo '<h3>Termo de Aceite</h3>';

    // Checkbox de aceite
    echo '<p><input type="checkbox" class="input-checkbox" name="termo_aceite" id="termo_aceite" /> ';
    echo '<label for="termo_aceite">Li e concordo com o <a href="' . esc_url($link_termo_pdf) . '" target="_blank">termo de aceite (Clique aqui para visualizar)</a></label></p>';

    // Campo RG
    echo '<p><label for="rg_cliente"><strong>RG:</strong></label><br>';
    echo '<input type="text" name="rg_cliente" id="rg_cliente" required></p>';

    // Campo Tamanho da Jaqueta
    echo '<p><label for="tamanho_jaqueta"><strong>Tamanho da Jaqueta:</strong></label><br>';
    echo '<select name="tamanho_jaqueta" id="tamanho_jaqueta" required>
            <option value="">Selecione</option>
            <option value="PMasculino">P Masculino</option>
            <option value="MMasculino">M Masculino</option>
            <option value="GMasculino">G Masculino</option>
            <option value="GGMasculino">GG Masculino</option>
            <option value="xGMasculino">XG Masculino</option>
            <option value="EGMasculino">EG Masculino</option>
            <option value="EXGMasculino">EXG Masculino</option>
            <option value="PFeminino">P Feminino</option>
            <option value="MFeminino">M Feminino</option>
            <option value="GFeminino">G Feminino</option>
            <option value="GGFeminino">GG Feminino</option>
            <option value="xGFeminino">XG Feminino</option>
            <option value="EGFeminino">EG Feminino</option>
            <option value="EXGFeminino">EXG Feminino</option>
          </select></p>';
    echo '</div>';
}

// === CHECKOUT: valida se termo, jaqueta e RG foram preenchidos ===
add_action('woocommerce_checkout_process', 'validar_termo_customizado_checkout');
function validar_termo_customizado_checkout() {
    if (carrinho_tem_categoria('desconto-em-folha')) {
        if (empty($_POST['termo_aceite'])) {
            wc_add_notice(__('Você precisa aceitar o termo para continuar.'), 'error');
        }
        if (empty($_POST['tamanho_jaqueta'])) {
            wc_add_notice(__('Por favor, selecione o tamanho da jaqueta.'), 'error');
        }
        if (empty($_POST['rg_cliente'])) {
            wc_add_notice(__('Por favor, informe seu RG.'), 'error');
        }
    }
}

// === CHECKOUT: salva o campo de RG como meta do pedido (opcional) ===
add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    if (isset($_POST['rg_cliente'])) {
        update_post_meta($order_id, '_rg_cliente', sanitize_text_field($_POST['rg_cliente']));
    }
});

// === CHECKOUT: envia e-mail com HTML formatado e adiciona nota no pedido ===
add_action('woocommerce_checkout_order_processed', 'enviar_email_termo_aceito', 10, 3);
function enviar_email_termo_aceito($order_id, $posted_data, $order) {
    if (!carrinho_tem_categoria('desconto-em-folha') || !isset($_POST['termo_aceite'])) return;

    $email_destino = 'rh.agro@kothe.com.br, recrutamento.frota.jnd@kothe.com.br';
    $cpf = xprofile_get_field_data(44, $order->get_user_id());
    $rg = sanitize_text_field($_POST['rg_cliente']);
    $tamanho = sanitize_text_field($_POST['tamanho_jaqueta']);
    $dia = date('j');
    $mes_extenso = traduzir_mes(date('n'));
    $ano = date('Y');

    $valor_total = 0;
    $nomes_produtos = [];

    foreach (WC()->cart->get_cart() as $item) {
        $product_id = $item['product_id'];
        if (has_term('desconto-em-folha', 'product_cat', $product_id)) {
            $produto_nome = get_the_title($product_id);
            $descricao = get_post_field('post_content', $product_id);
            $primeira_linha = strtok($descricao, "\n");

            if (preg_match('/R\$ ?([\d.,]+)/', $primeira_linha, $matches)) {
                $preco = str_replace(['.', ','], ['', '.'], $matches[1]);
                $preco = floatval($preco);
                $quantidade = $item['quantity'];
                $valor_total += $preco * $quantidade;

                $nomes_produtos[] = $quantidade . 'x ' . $produto_nome;
            }
        }
    }

    $parcelas = 3;
    $valor_parcela = number_format($valor_total / $parcelas, 2, ',', '.');
    $valor_formatado = number_format($valor_total, 2, ',', '.');
    $lista_produtos = implode(', ', $nomes_produtos);

    $html_email = '
    <div style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd;">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="https://unikothe.com.br/wp-content/uploads/2023/12/UNIKOTHE-LOGO-OK-1.png" alt="Logo da Empresa" style="max-width: 200px;">
        </div>

        <h2 style="text-align: center;">TERMO DE RECEBIMENTO E AUTORIZAÇÃO DE DESCONTO</h2>

        <p>Pelo presente instrumento particular, eu <strong>' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</strong>, 
        portador do RG <strong>' . $rg . '</strong>, inscrito no CPF sob o nº <strong>' . $cpf . '</strong>, 
        DECLARO para todos os fins de direito que se fizerem necessários, que, na data de 
        <strong>' . date('d/m/Y') . '</strong>, recebi da empresa Trans Kothe Transportes os seguintes itens:</p>

        <p><strong>' . $lista_produtos . ' (Tamanho: ' . $tamanho . ')</strong> no valor total de <strong>R$ ' . $valor_formatado . '</strong></p>

        <p>Em ' . $parcelas . ' prestações iguais e sucessivas no valor de <strong>R$ ' . $valor_parcela . '</strong> conforme os termos do artigo 462 da CLT.</p>

        <p style="margin-top: 30px;">Jundiaí-SP, <strong>' . $dia . '</strong> de <strong>' . $mes_extenso . '</strong> de <strong>' . $ano . '</strong></p>

        <p style="margin-top: 50px;">Assinatura do colaborador: <strong>' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</strong></p>
    </div>';

    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($email_destino, 'Termo Aceito na Finalização de Compra', $html_email, $headers);

    $order->add_order_note('O cliente aceitou o termo, informou RG: ' . $rg . ' e escolheu tamanho ' . $tamanho . '. Produtos: ' . $lista_produtos . '. Valor total: R$ ' . $valor_formatado);
}

// === FUNÇÃO AUXILIAR PARA MÊS POR EXTENSO ===
function traduzir_mes($mes) {
    $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
    ];
    return $meses[intval($mes)];
}
