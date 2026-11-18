<?php

namespace App\Support;

/**
 * Content of the institutional pages.
 *
 * Only facts about how this site works are written here. Company policies
 * (payment, delivery, returns) were NOT provided by MGLASTO yet, so they stay
 * as "[A DEFINIR]" on purpose: nothing is invented.
 */
final class InstitutionalPages
{
    public const TO_DEFINE = '[A DEFINIR]';

    /**
     * @return array<string, array{route: string, title: string, intro: string, sections: array<int, array{title: string, body: string}>}>
     */
    public static function all(): array
    {
        $todo = self::TO_DEFINE;

        return [
            'service' => [
                'route' => 'pages.service',
                'title' => 'Atendimento',
                'intro' => 'Fale com a equipe da MGLASTO para tirar dúvidas, pedir um orçamento ou acompanhar seu pedido.',
                'sections' => [
                    ['title' => 'WhatsApp', 'body' => '{whatsapp}'],
                    ['title' => 'Telefone', 'body' => '{phone}'],
                    ['title' => 'E-mail', 'body' => '{email}'],
                    ['title' => 'Endereço', 'body' => '{address}'],
                    ['title' => 'Horário de atendimento', 'body' => "{$todo}: dias e horários de atendimento."],
                ],
            ],
            'faq' => [
                'route' => 'pages.faq',
                'title' => 'Perguntas frequentes',
                'intro' => 'Respostas para as dúvidas mais comuns sobre a loja.',
                'sections' => [
                    [
                        'title' => 'Como faço um pedido?',
                        'body' => 'Escolha os produtos e clique em "Adicionar ao orçamento". Depois, abra o orçamento, preencha seus dados e clique em "Gerar pedido de orçamento". O pedido é registrado e você o envia pelo WhatsApp para a equipe da MGLASTO confirmar o preço final, o frete e o prazo.',
                    ],
                    [
                        'title' => 'Preciso criar uma conta?',
                        'body' => 'Não. O orçamento é feito sem cadastro: basta informar nome, telefone e endereço de entrega.',
                    ],
                    [
                        'title' => 'O preço do site é o preço final?',
                        'body' => 'O site mostra o preço dos produtos. O frete é calculado pela equipe e informado no WhatsApp, junto com o valor final do pedido.',
                    ],
                    ['title' => 'Quais são as formas de pagamento?', 'body' => "{$todo}: formas de pagamento aceitas."],
                    ['title' => 'Qual é o prazo de entrega?', 'body' => "{$todo}: prazos de produção e de entrega."],
                    ['title' => 'Vocês entregam na minha cidade?', 'body' => "{$todo}: regiões atendidas e formas de envio."],
                    ['title' => 'Posso retirar o pedido pessoalmente?', 'body' => "{$todo}: se há retirada e onde."],
                    ['title' => 'Vocês emitem nota fiscal?', 'body' => "{$todo}: emissão de nota fiscal."],
                ],
            ],
            'returns' => [
                'route' => 'pages.returns',
                'title' => 'Trocas e devoluções',
                'intro' => 'Regras para trocar ou devolver um produto comprado na MGLASTO.',
                'sections' => [
                    ['title' => 'Prazo para solicitar', 'body' => "{$todo}: prazo para pedir troca ou devolução."],
                    ['title' => 'Condições do produto', 'body' => "{$todo}: em que condições o produto é aceito de volta."],
                    ['title' => 'Produtos sob medida ou cortados', 'body' => "{$todo}: regra para itens cortados ou feitos sob medida."],
                    ['title' => 'Como solicitar', 'body' => "{$todo}: passo a passo e contato para solicitar."],
                    ['title' => 'Reembolso', 'body' => "{$todo}: forma e prazo do reembolso."],
                    ['title' => 'Direito de arrependimento', 'body' => "{$todo}: texto a revisar com o jurídico da empresa (art. 49 do Código de Defesa do Consumidor)."],
                ],
            ],
        ];
    }
}
