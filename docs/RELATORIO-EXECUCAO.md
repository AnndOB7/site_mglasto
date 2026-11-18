# Relatório da execução autônoma – tarefas 5 a 11

Execução feita em 18/11/2026, seguindo `docs/PROMPT-EXECUCAO-AUTONOMA.md`. Todo o trabalho está na branch **`demo`**. **Nada foi juntado no `main`**: ele continua em `2a3ada6` (identidade visual).

## Resumo

- **Todas as tarefas de 5 a 11 foram concluídas.** Nenhuma travou.
- **O critério de "pronto" foi verificado num navegador real:** home no celular → categoria → produto real com fotos → variação do Piso Diamante → adicionar ao orçamento → dados → pedido gravado → pedido visível no `/admin`.
- **Qualidade no fim:** 231 testes passando (2 pulados, ver abaixo), PHPStan nível 7 com **0 erros** no projeto inteiro, Pint sem pendências, `npm run build`, checagem de tipos e lint sem erros. Nenhuma página passa da largura da tela com 375 px.
- **O seu banco local (`database/database.sqlite`) não ganhou pedidos nem usuários.** Todos os testes manuais foram feitos numa cópia.

## 1. O que foi concluído

| Tarefa | Commit | O que entrou |
|---|---|---|
| Pendências do `main` | `2a3ada6` (no `main`) | Logo em SVG, favicons e docs, com a mensagem pedida |
| 5 – Painel admin | `996c0e6` | Filament 5.11.4 em `/admin`; acesso só por `ADMIN_EMAILS`; categorias; produtos com variações e fotos; pedidos (só o status editável); preço digitado em reais e gravado em centavos |
| 6 – Guia visual | `3e47070` | Tokens no `@theme` do Tailwind, fonte Inter, componentes `Button`, `ProductCard`, `Price`, `Badge`, `Container`; `StoreLayout` com cabeçalho, menu, hambúrguer e rodapé; página `/guia-visual` |
| 7 – Home, categoria e busca | `dd23063` | `/`, `/categoria/{slug}` (ordenação por nome e preço), `/busca?q=` (nome, SKU e SKU de variação), sem produtos repetidos na home, sem N+1 |
| 8 – Página de produto | `8ef8cfa` | `/produto/{slug}`: galeria, preço com promoção, disponibilidade, seletor de variações, quantidade, WhatsApp, descrição sanitizada, relacionados, metatags Open Graph no HTML do servidor |
| 9 – Carrinho e orçamento | `37e0dc6` | Carrinho na sessão com preço sempre do banco; checkout com máscaras, CPF/CNPJ validado, CEP pelo ViaCEP com alternativa manual; pedido `MGL-000123` (`quote`/`site`) numa transação; link do WhatsApp com o resumo |
| 10 – Institucionais | `a54b603` | Atendimento, Perguntas frequentes, Trocas e devoluções; políticas como `[A DEFINIR]`; teste que visita todos os links do layout |
| 11 – Revisão final | `e1a27ac` | PHPStan zerado; fotos do painel independentes do `APP_URL`; telefone, CPF e CEP formatados no painel; este relatório |

Cada etapa está explicada no `docs/GUIA-DE-ESTUDO.md` (etapas 10 a 16), e cada mudança tem uma linha no **Diário do projeto**.

## 2. O que ficou pendente ou travou

**Nada travou.** O que ficou de fora de propósito, ou com limitação conhecida:

| Item | Situação |
|---|---|
| Busca sem acento | "marmore" não encontra "MÁRMORE": o SQLite não compara ignorando acentos. Resolve-se ao migrar para MySQL ou com uma coluna de busca normalizada. |
| Foto por variação | Escolher "Vermelho" não troca a foto da galeria: o banco não liga fotos a variações. |
| Frete | Não é calculado: aparece "frete a combinar", como no fluxo atual pelo WhatsApp. |
| Estoque | Gerar um orçamento **não** baixa o estoque (orçamento não é venda). |
| SSR (renderização no servidor) | Não configurado. As metatags de compartilhamento já saem no HTML do servidor; o resto da página é montado no navegador. |
| Testes pulados (2) | Testes do starter kit para recursos do Fortify que estão desligados neste projeto. Já eram pulados antes. |

## 3. Decisões que tomei sem você (revise)

1. **Validação nova no painel:** o preço promocional precisa ser **menor** que o preço normal.
2. **Endereço obrigatório no orçamento** (CEP, rua, número, bairro, cidade, UF). E-mail e CPF/CNPJ são opcionais.
3. **O WhatsApp abre por um botão** na confirmação, não sozinho: navegadores bloqueiam janelas abertas sem clique.
4. **A confirmação do pedido só abre na sessão que o criou** (404 para os outros), porque os números são sequenciais e expõem dados de clientes.
5. **Envio de orçamento limitado a 10 por minuto** por visitante, contra spam.
6. **A loja é sempre clara**, mesmo com o modo escuro do sistema ligado (o painel `/admin` segue o sistema).
7. **Texto laranja usa `#C25400`** (token `brand-ink`): o `#FF7A00` sobre branco não tem contraste suficiente para texto.
8. **"Oferta" não aparece como categoria da trilha de navegação** do produto quando ele tem outra categoria real.
9. **Pedidos não podem ser criados nem apagados no painel**, só ter o status alterado.
10. **A página `welcome` do starter kit foi removida**: `/` agora é a loja.
11. **Os assets do Filament (`public/css`, `public/js`, `public/fonts`) foram commitados**, e o `filament:upgrade` roda a cada `composer install`.
12. **`withoutVite()` no `tests/TestCase.php`:** os testes não dependem mais de `npm run build`.
13. **Correções de tipo no importador e no `CategoryFactory`** para zerar o PHPStan, sem mudar regras. O importador foi rodado de novo contra a loja real (numa cópia do banco) e trouxe os mesmos 8/25/5.
14. **Formatador do front-end não mexe mais em `docs/` nem em `public/`** (`vite.config.ts`). Ver o incidente no item 7.

## 4. Tudo o que está `[A DEFINIR]`

| Onde aparece | O que falta | Onde preencher |
|---|---|---|
| Botões do WhatsApp (produto, confirmação, atendimento) | Número do WhatsApp | `WHATSAPP_NUMBER=55DDNUMERO` no `.env` |
| Rodapé e Atendimento | Telefone, e-mail, endereço | `COMPANY_PHONE`, `COMPANY_EMAIL`, `COMPANY_ADDRESS` no `.env` |
| Rodapé | CNPJ | `COMPANY_CNPJ` no `.env` |
| Atendimento | Horário de atendimento | `app/Support/InstitutionalPages.php` |
| Perguntas frequentes | Formas de pagamento, prazo de entrega, regiões atendidas, retirada no local, nota fiscal | `app/Support/InstitutionalPages.php` |
| Trocas e devoluções | Prazo, condições, itens cortados sob medida, como solicitar, reembolso, direito de arrependimento (revisar com o jurídico) | `app/Support/InstitutionalPages.php` |

O telefone "11 2222-4444" do site atual **não** foi usado, como pedido.

## 5. Como rodar e testar a demo do zero

```powershell
git clone https://github.com/AnndOB7/site_mglasto.git
cd site_mglasto
git checkout demo

composer install
npm install
copy .env.example .env
php artisan key:generate

# banco SQLite
New-Item -ItemType File database\database.sqlite
php artisan migrate

# catálogo real e fotos
php artisan storage:link
php artisan mglasto:importar-woocommerce

# rodar
composer run dev          # abre em http://localhost:8000
```

Pré-requisitos do PHP (`C:\PHP\php.ini`): `extension=intl`, `extension=gd`, `extension=zip`, e `curl.cainfo` / `openssl.cafile` apontando para o `cacert.pem` (ver Etapa 1 do guia).

**Testes e verificações:**
```powershell
php artisan test                  # 231 testes
vendor/bin/pint --test            # estilo do PHP
vendor/bin/phpstan analyse        # análise estática (nível 7)
npm run types:check; npm run check
npm run build
```

**Roteiro da apresentação (critério de "pronto"):** no celular, abrir `/` → "Pisos e Revestimentos" → "PISO BORRACHA DIAMANTE" → escolher uma cor → "Adicionar ao orçamento" → ícone do carrinho → "Continuar" → preencher (o CEP preenche o endereço) → "Gerar pedido de orçamento" → abrir `/admin/pedidos`.

## 6. Como criar o administrador e acessar `/admin`

```powershell
# 1) no .env, o(s) e-mail(s) que podem entrar (separados por vírgula)
ADMIN_EMAILS=seu-email@exemplo.com

# 2) criar o usuário
php artisan make:filament-user --name="Seu Nome" --email="seu-email@exemplo.com"
#    (pede a senha; mínimo de 8 caracteres)

# 3) abrir
http://localhost:8000/admin
```
Um usuário com e-mail fora de `ADMIN_EMAILS` consegue fazer login no site, mas recebe **403** no painel.

## 7. Mudanças fora do repositório e incidentes

- **`C:\PHP\php.ini`:** ativei `extension=intl` (exigida pelo Filament) e `extension=gd` (imagens). Backup em `C:\PHP\php.ini.bak-intl`. As quebras de linha (CRLF) foram preservadas e conferidas com `diff`.
- **Incidente com o formatador:** o `npm run check:fix` reformatou também os `.md` de `docs/` (inclusive o `PROMPT-EXECUCAO-AUTONOMA.md`): alinhou tabelas e trocou `*itálico*` por `_itálico_`. Percebi antes de commitar, restaurei `docs/` com `git checkout -- docs/` (todo o conteúdo estava no último commit) e configurei o formatador para ignorar `docs/**`. Nenhuma versão reformatada chegou ao GitHub.

## 8. Como foi verificado

- **Testes automatizados** para cada regra: conversão reais↔centavos, acesso ao painel, preço adulterado ignorado, cópia nos itens do pedido, estoque compartilhado, sanitização contra XSS, privacidade da confirmação, todos os links do layout.
- **Navegador real (Chrome controlado por script)** numa **cópia** do banco e num servidor separado: fluxo completo em 375 px e 1280 px, ViaCEP de verdade, login no painel e pedido na lista.
- **Largura real de 375 px:** o Chrome sem interface não abre janelas com menos de 504 px, então a medição foi feita por emulação de dispositivo e por um iframe de 375 px. Todas as páginas da loja ficaram com 0 px de overflow.
