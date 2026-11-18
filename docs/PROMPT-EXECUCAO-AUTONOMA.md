# Execução autônoma – tarefas 5 a 11 da demo MGLASTO

Você vai trabalhar **sozinho por várias horas**. O André está fora e não vai poder responder perguntas. Siga este roteiro do início ao fim, tomando as decisões que ele permite tomar e registrando tudo.

## 0. Antes de começar

1. Leia **`docs/CONTEXTO-PROJETO.md`** inteiro. As regras de negócio, a paleta, o uso do logo e as regras de documentação de lá valem para todo este trabalho.
2. Leia **`docs/GUIA-DE-ESTUDO.md`** para entender o formato da documentação.
3. Faça commit do que está pendente no `main` (logo em `public/images/logo/`, favicons, `docs/` atualizados), com a mensagem `Identidade visual: logo em SVG e paleta de cores`, e dê push.
4. Crie a branch **`demo`** a partir do `main` e trabalhe **só nela**. Dê push dela a cada tarefa concluída. **Não faça merge no `main`**: o André vai revisar antes.
5. Rode `php artisan test` e confirme que tudo passa antes de mexer em qualquer coisa.

## Regras durante todo o trabalho

- **Uma tarefa por vez, na ordem abaixo.** Ao fim de cada uma: testes passando → documentação (etapa no guia + linha no diário) → commit → push da branch `demo` → marcar ✅ no cronograma do `CONTEXTO-PROJETO.md`.
- **Nunca:** `git push --force`, apagar branches, rodar `migrate:fresh` ou `db:wipe` no banco local (`database/database.sqlite` tem o catálogo importado), editar migrations já commitadas, commitar o `.env`, instalar pacotes pagos ou criar contas em serviços externos.
- **Não invente informação da empresa.** Telefone, WhatsApp, CNPJ, políticas de troca, prazos de entrega e endereço vêm do `.env`/config ou ficam como `[A DEFINIR]`, bem visíveis. O telefone "11 2222-4444" do site atual parece fictício: não use.
- **Se ficar travado** numa tarefa (o mesmo erro depois de 3 tentativas diferentes), registre o problema no relatório final, faça commit do que funciona e siga para a próxima tarefa que não dependa dela.
- **Comandos longos** (`npm run build`, `composer require`) podem demorar. Espere terminar.
- Explique decisões no guia em português didático: o André está aprendendo.

## Direção visual (até chegarem as telas de referência)

O André vai enviar telas de referência depois. Até lá, siga esta direção, e deixe o visual **fácil de ajustar**: cores e tamanhos centralizados em tokens do Tailwind (CSS do `@theme` no Tailwind 4) e componentes reutilizáveis.

- **Base:** a identidade atual modernizada. Grafite `#1C1D1C`, laranja `#FF7A00`, muito branco e cinza `#F5F5F5`. Fonte Inter.
- **Mobile-first:** desenhe primeiro para 375 px de largura, depois amplie. Alvos de toque com no mínimo 44 px.
- **Botões:** laranja com **texto grafite** (contraste). Cantos arredondados médios, sem os cantos assimétricos do site antigo.
- **Cards de produto:** foto quadrada com fundo claro, nome em até 2 linhas, preço em destaque, selo "Oferta" quando `sale_price` existir, botão de ação.
- **Cabeçalho:** `logo-mglasto.svg` em fundo branco, busca visível, carrinho com contador. No celular: menu hambúrguer.
- **Rodapé:** fundo grafite com `logo-mglasto-fundo-escuro.svg`.
- Nada de carrosséis repetindo os mesmos produtos (um dos defeitos do site atual).

## Tarefas

### Tarefa 5 – Painel administrativo (Filament)
1. Instale a versão mais recente do Filament compatível com Laravel 13 (confira com o `search-docs` do Boost) e crie o painel em `/admin`.
2. **Acesso:** só usuários cujo e-mail esteja em `ADMIN_EMAILS` no `.env` (lista separada por vírgula, lida via `config/`), checado em `canAccessPanel` no `User`. Adicione `ADMIN_EMAILS=` ao `.env.example`. Teste: usuário comum recebe 403.
3. **Categorias:** nome, slug gerado do nome, descrição, categoria-pai, posição, ativa.
4. **Produtos:** nome, slug, SKU, categorias (múltiplas), descrições, preço e preço promocional **digitados em reais (88,60) e salvos em centavos (8860)**, controle de estoque, quantidade, ativo, destaque. Gerenciadores de variações (nome, opções, preço opcional, estoque) e de fotos (upload no disco `public`, ordem, texto alternativo). Tabela com foto, nome, preço em R$, estoque e filtros por categoria, ativo e destaque.
5. **Pedidos:** lista com número, canal, status, cliente, total em R$ e data, com filtros por canal e status. Só o status é editável; os itens aparecem somente para leitura.
6. Teste a conversão de reais para centavos (88,60 → 8860 e de volta), porque erro nela multiplica ou divide preços por 100.

### Tarefa 6 – Guia visual e layout base
1. Defina os tokens no CSS do Tailwind (cores da paleta, fonte, raios, sombras).
2. Crie componentes React reutilizáveis: `Button`, `ProductCard`, `Price` (formata centavos em `R$ 88,60` com `Intl.NumberFormat('pt-BR')`), `Badge`, `Container`.
3. Crie o layout da loja (`StoreLayout`): cabeçalho, menu de categorias, rodapé. Separado do layout do painel e da área logada do starter kit.
4. Use o logo correto em cada fundo, conforme o `CONTEXTO-PROJETO.md`.

### Tarefa 7 – Home, categoria e busca
1. **Home (`/`):** faixa de abertura, categorias com destaque, vitrine "Ofertas" (`featured`), vitrine "Todos os produtos". Sem produtos repetidos entre as vitrines.
2. **Categoria (`/categoria/{slug}`):** produtos ativos da categoria, com ordenação por nome e por preço.
3. **Busca (`/busca?q=`):** por nome e SKU.
4. Controllers finos. Evite N+1 (eager loading de imagens e categorias). Só produtos e categorias ativos.
5. Testes de feature para cada rota: carrega, mostra o que deve e esconde inativos.

### Tarefa 8 – Página de produto
1. Rota **`/produto/{slug}`**, o mesmo padrão do site atual (`/produto/...`), o que facilita redirecionamentos no futuro.
2. Galeria com todas as fotos, nome, preço (promocional riscando o normal), disponibilidade, seletor de variações (atualiza preço e disponibilidade), quantidade, botão "Adicionar ao orçamento" e botão "Tirar dúvidas no WhatsApp".
3. Descrição: **sanitize o HTML ao exibir**, permitindo só as tags simples listadas no contexto.
4. Produtos relacionados da mesma categoria.
5. Metatags básicas (título, descrição, imagem para compartilhamento).

### Tarefa 9 – Carrinho e orçamento via WhatsApp
1. Carrinho na **sessão** (sem login): adicionar, alterar quantidade, remover. Preços sempre recalculados do banco, **nunca confiando no valor enviado pelo navegador**.
2. Checkout de orçamento: nome, telefone, e-mail (opcional), CPF/CNPJ (opcional, com máscara e validação), CEP com busca de endereço (ViaCEP, com fallback manual se a API falhar). Validação com Form Request.
3. Ao enviar: criar `Order` com `channel = site`, `status = quote`, número legível (ex.: `MGL-000123`), itens com **cópia** de nome, variação, SKU e preço, tudo numa transação. Depois, abrir `https://wa.me/{WHATSAPP_NUMBER}?text=...` com o resumo do pedido.
4. `WHATSAPP_NUMBER` vem do `.env` via `config/`. Adicione ao `.env.example` **vazio**. Se estiver vazio, mostre a confirmação do pedido sem o botão do WhatsApp e um aviso "[A DEFINIR: número do WhatsApp]".
5. Testes: total calculado no servidor, preço adulterado no navegador é ignorado, pedido e itens gravados, carrinho limpo após o envio.

### Tarefa 10 – Páginas institucionais
Atendimento, Perguntas frequentes, Trocas e devoluções. Estrutura e layout prontos, com o conteúdo marcado **`[A DEFINIR]`**. Não invente políticas. Nenhum link do site pode apontar para `#`.

### Tarefa 11 – Revisão final
1. `php artisan test` passando. Rode o Pint (`vendor/bin/pint`) e o PHPStan, se configurado.
2. `npm run build` sem erros.
3. Revise as páginas em 375 px e em 1280 px de largura. Se tiver como ver, confira se nada estoura a tela no celular.
4. Escreva **`docs/RELATORIO-EXECUCAO.md`** com:
   - o que foi concluído, tarefa por tarefa, com os commits;
   - o que ficou pendente ou travou, e por quê;
   - decisões que você tomou sem o André e que ele precisa revisar;
   - tudo o que está marcado `[A DEFINIR]`;
   - como rodar e testar a demo do zero (comandos);
   - como criar o usuário administrador e acessar `/admin`.
5. Commit e push da branch `demo`. **Não faça merge no `main`.**

## Critério de "pronto"

A demo está pronta quando, rodando `composer run dev`, dá para: navegar pela home no celular → entrar numa categoria → abrir um produto real com fotos → escolher uma variação do Piso Diamante → adicionar ao orçamento → preencher os dados → gerar o pedido no banco → vê-lo no painel `/admin`.
