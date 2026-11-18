# Contexto do projeto – Loja MGLASTO

Documento de passagem entre o planejamento (conversa no claude.ai) e a execução (extensão do Claude no VS Code).
**Agente de IA: leia este arquivo antes de qualquer tarefa neste repositório.**

## Objetivo

Refazer a loja virtual da MGLASTO (https://loja.mglasto.com.br, hoje em WordPress + WooCommerce + Elementor) como uma **demo funcional** em Laravel, para o André apresentar ao dono da empresa como proposta de produto.

- Prazo da demo: **19/11 a 02/12/2026** (2 semanas).
- Fora do escopo da demo: hospedagem definitiva, migração de domínio, redirecionamentos 301, LGPD, pagamento online, cadastro de clientes na loja. Isso só entra se a proposta for aceita.
- Visão de longo prazo: o mesmo back-end vai servir a um **sistema de pedidos multicanal** (Mercado Livre, Shopee, TikTok, Bling). Por isso os pedidos já têm `channel` e `external_id`.

## Stack

| Peça | Versão |
|---|---|
| PHP | 8.3 (Windows, `C:\PHP`) |
| Laravel | 13 |
| Inertia + React | Inertia 3, React 19, TypeScript |
| CSS | Tailwind CSS 4 |
| Autenticação | Fortify (starter kit) |
| Testes | Pest 4 |
| Banco | SQLite (demo) |
| Painel admin | Filament (tarefa 5, ainda não instalado) |

Repositório: https://github.com/AnndOB7/site_mglasto · pasta local: `C:\dev\site_mglasto`.

## Regras de negócio (não mudar sem combinar)

1. **Dinheiro sempre em centavos (inteiro).** R$ 88,60 = `8860`. Vale para `price`, `sale_price`, totais de pedido e itens. Telas e formulários convertem para reais na exibição/entrada.
2. **Preço atual** = `sale_price` se existir, senão `price` (`Product::currentPrice()`). Variação sem preço próprio usa o do produto.
3. **Estoque:** `manage_stock = false` significa "sem controle" (sempre disponível). Variação com `stock_quantity = null` compartilha o estoque do produto.
4. **Item de pedido guarda cópia** de nome, variação, SKU e preço no momento da compra. Apagar ou editar produto não altera pedidos antigos.
5. **Pedido de marketplace não pode ser importado duas vezes:** índice único em (`channel`, `external_id`).
6. **A venda hoje termina em orçamento pelo WhatsApp** (status `quote`). O checkout da demo deve gravar o pedido e abrir o WhatsApp com a mensagem pronta.
7. Produtos da categoria **"Oferta"** são destaque da home (`is_featured`).
8. Descrições importadas só mantêm tags simples (p, br, strong, em, listas, h2–h4), sem atributos. Ainda assim, **sanitizar ao exibir**.

## O que já existe

- **Banco:** `categories`, `products`, `category_product`, `product_variants` (coluna `options` em JSON, ex.: `{"Cor":"Preto","Espessura":"3 mm"}`), `product_images`, `orders`, `order_items`.
- **Enums:** `OrderChannel` (site, mercado_livre, shopee, tiktok, whatsapp) e `OrderStatus` (quote, confirmed, picking, shipped, delivered, cancelled), ambos com `label()` em português.
- **Importador:** `php artisan mglasto:importar-woocommerce [--url=] [--sem-imagens]`. Lê a Store API pública do WooCommerce, é idempotente (casa por `woo_id`) e baixa fotos para `storage/app/public/products/{slug}/`. Requer `php artisan storage:link`.
- **Testes:** `tests/Feature/Catalog/`.

### Particularidades da loja atual (descobertas na importação)

- A Store API **não lista a categoria "Oferta"** dentro de cada produto; ela só aparece consultando `products?category=<id>`.
- As variações do Piso Borracha Diamante **repetem o SKU** do produto principal. Por isso variantes com SKU igual ao do pai ficam com `sku = null`.
- O estoque só vem como texto ("92 em estoque") e só o Piso Diamante tem número. Os demais ficam sem controle de estoque.

## Cronograma da demo

| # | Tarefa | Datas | Status |
|---|---|---|---|
| 1 | Ambiente + repositório | 19/11 | ✅ |
| 2 | Projeto Laravel + Inertia + React + Tailwind | 20/11 | ✅ |
| 3 | Banco: catálogo e pedidos multicanal | 21/11 | ✅ |
| 4 | Importar catálogo do WooCommerce | 22/11 | 🔄 ajustes finais |
| 5 | Painel admin com Filament | 23–24/11 | ⏳ |
| 6 | Guia visual + layout base | 25/11 | ⏳ |
| 7 | Home e categoria com busca | 26–27/11 | ⏳ |
| 8 | Página de produto | 28–29/11 | ⏳ |
| 9 | Carrinho + orçamento via WhatsApp | 30/11–01/12 | ⏳ |
| 10 | Páginas institucionais | 01/12 | ⏳ (corta primeiro se atrasar) |
| 11 | Testes, ajustes e demo publicada | 02/12 | ⏳ |

## Combinados de trabalho

- Mensagens de commit em português, curtas, sem acento.
- Rodar os testes antes de cada commit.
- Não alterar migrations já commitadas: criar uma migration nova.
- Explicar ao André, em português e de forma didática, o que foi feito e por quê. Ele está aprendendo Laravel e React.
- **Ao concluir cada tarefa, acrescentar uma seção em `docs/GUIA-DE-ESTUDO.md`** no mesmo formato das existentes: o que foi feito, passo a passo com comandos, decisões e o porquê, problemas encontrados e um "Para fixar" com exercícios. Atualizar também o sumário e o checklist quando couber.
