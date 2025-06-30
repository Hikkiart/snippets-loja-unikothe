add_action('wp_footer', 'alterar_botao_checkout_com_soma_valores_ajax');
function alterar_botao_checkout_com_soma_valores_ajax() {
    if (!is_checkout()) return;

    $categoria_slug = 'desconto-em-folha'; // 🔧 Altere se necessário
    $total = 0.0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];

        if (has_term($categoria_slug, 'product_cat', $product->get_id())) {
            $descricao = $product->get_description();
            $linhas = preg_split('/\r\n|\r|\n/', $descricao);
            $primeira_linha = trim($linhas[0]);

            // Remove conteúdo entre parênteses
            $linha_limpa = preg_replace('/\s*\(.*?\)/', '', $primeira_linha);

            // Extrai o valor numérico
            if (preg_match('/([\d\.,]+)/', $linha_limpa, $match)) {
                $valor_str = str_replace(',', '.', $match[1]);
                $valor_float = floatval($valor_str);
                $total += $valor_float * $cart_item['quantity'];
            }
        }
    }

    if ($total > 0) {
        $total_formatado = number_format($total, 2, ',', '');

        ?>
        <script>
        function atualizarBotaoDescontoEmFolha() {
            const btn = document.querySelector('#place_order');
            if (btn) {
                const texto = 'Confirmar desconto em folha (R$ <?php echo $total_formatado; ?>)';
                btn.innerText = texto;
                btn.value = texto;
                btn.setAttribute('data-value', texto);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            atualizarBotaoDescontoEmFolha();

            // WooCommerce dispara esse evento quando atualiza o checkout via AJAX
            jQuery(document.body).on('updated_checkout', function () {
                atualizarBotaoDescontoEmFolha();
            });
        });
        </script>
        <?php
    }
}
