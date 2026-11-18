# Guia de estudo – Como a loja MGLASTO está sendo construída

Este guia registra **cada etapa do projeto**: o que foi feito, por que foi feito assim e como repetir em outro projeto. Ele cresce junto com o projeto: cada tarefa concluída ganha uma seção nova.

> **Como estudar com este guia:** leia a seção, abra os arquivos citados no VS Code e tente explicar com suas palavras o que cada parte faz. No fim de cada etapa há um **"Para fixar"** com exercícios curtos.

---

## Sumário

0. [Antes de programar: analisar o que já existe](#etapa-0)
1. [Ambiente de desenvolvimento no Windows](#etapa-1)
2. [Git e GitHub: o básico que você vai usar todo dia](#etapa-2)
3. [Criando o projeto Laravel](#etapa-3)
4. [Entendendo as pastas de um projeto Laravel](#etapa-4)
5. [Banco de dados: migrations](#etapa-5)
6. [Models, relacionamentos e enums](#etapa-6)
7. [Factories e testes automatizados](#etapa-7)
8. [Importando dados de outro sistema (comando Artisan + API)](#etapa-8)
9. [Trabalhando com um agente de IA no projeto](#etapa-9)
9.1. [Identidade visual: logo e cores](#etapa-9-1)
10. [Painel administrativo com Filament](#etapa-10)
11. [Guia visual: tokens do Tailwind, componentes e layout da loja](#etapa-11)
12. [Páginas da loja: home, categoria e busca](#etapa-12)
13. [Página de produto](#etapa-13)
14. [Checklist para reaproveitar em outros projetos](#checklist)
15. [Glossário](#glossario)
16. [Diário do projeto](#diario)

---

<a id="etapa-0"></a>
## Etapa 0 – Antes de programar: analisar o que já existe

**O que fizemos:** antes de escrever qualquer linha, investigamos o site atual para entender o que ele faz e com que tecnologia.

### Passo a passo

1. **Descobrir a tecnologia do site.** No navegador, abra as Ferramentas do Desenvolvedor (F12) e vá em **Console**. Rode:
   ```js
   [...document.querySelectorAll('meta[name=generator]')].map(m => m.content)
   ```
   No site da MGLASTO isso revelou **WordPress 7.1 + WooCommerce 11.2 + Elementor 4.3**. Outras pistas: endereços com `/wp-content/` e `/wp-json/`, e as classes CSS do `<body>`.

2. **Ver se existe uma API pública.** Lojas WooCommerce têm a *Store API*. Abra no navegador:
   ```
   https://loja.mglasto.com.br/wp-json/wc/store/v1/products?per_page=100
   https://loja.mglasto.com.br/wp-json/wc/store/v1/products/categories
   ```
   Isso devolve os produtos em JSON, e foi o que nos permitiu importar o catálogo sem digitar nada.

3. **Mapear o fluxo de venda.** Descobrimos que o único "pagamento" é um orçamento pelo WhatsApp (`mglasto_whatsapp_quote`). Isso mudou o escopo: a demo precisa recriar esse fluxo, não um checkout com cartão.

4. **Listar os problemas reais.** Links que apontam para `#`, produtos repetidos nas vitrines, texto em inglês misturado. Viraram requisitos da versão nova.

### Por que isso importa

A primeira proposta era "refazer em PHP". A análise mostrou que **o site já era PHP**. Sem ela, teríamos gastado meses reconstruindo coisas que funcionavam. **Regra de ouro: entenda o problema antes de escolher a solução.**

### Para fixar
- Escolha um site qualquer e descubra com que tecnologia ele foi feito.
- Explique com suas palavras por que "o site é feio" não justifica, sozinho, trocar o back-end.

---

<a id="etapa-1"></a>
## Etapa 1 – Ambiente de desenvolvimento no Windows

**O que fizemos:** conferimos e instalamos as ferramentas necessárias.

| Ferramenta | Para que serve | Como conferir |
|---|---|---|
| **PHP 8.3+** | Linguagem do back-end (Laravel) | `php -v` |
| **Composer** | Gerenciador de pacotes do PHP (como o npm, mas para PHP) | `composer -V` |
| **Node.js LTS + npm** | Compila o front-end (React, Tailwind) | `node -v` e `npm -v` |
| **Git** | Controle de versão | `git --version` |
| **VS Code** | Editor | `code --version` |

### Passo a passo

1. Abra o **PowerShell** e confira tudo de uma vez:
   ```powershell
   php -v; composer -V; node -v; npm -v; git --version
   ```
   Cada "não é reconhecido" é uma ferramenta que falta.

2. Para instalar o que falta no Windows:
   - **PHP + Composer + Node juntos:** o [Laravel Herd](https://herd.laravel.com/windows) é o caminho mais fácil.
   - **Git:** `winget install --id Git.Git -e`
   - **Depois de instalar, feche e abra o PowerShell**, para o Windows recarregar o PATH.

3. Confira as **extensões do PHP** que o Laravel exige:
   ```powershell
   php -m | Select-String -Pattern "openssl|pdo_sqlite|sqlite3|mbstring|fileinfo|curl|zip|intl"
   ```
   Se faltar alguma, ative no `php.ini` tirando o `;` da frente da linha `extension=...`.

4. Configure seu nome no Git (uma vez por computador):
   ```powershell
   git config --global user.name "Seu Nome"
   git config --global user.email "seu-email@exemplo.com"
   ```

### Cuidados que aprendemos

- **Não crie projetos dentro do OneDrive, Google Drive ou Dropbox.** As pastas `vendor` e `node_modules` têm dezenas de milhares de arquivos, e a sincronização deixa tudo lento e trava arquivos. Use algo como `C:\dev\nome-do-projeto`. O backup do código é o GitHub.
- **Cuidado com dois PHPs instalados.** No nosso caso, o PHP de `C:\PHP` (de um projeto antigo) estava à frente do PATH. Rode `where.exe php` para ver qual está sendo usado.
- **Erro `cURL error 60: unable to get local issuer certificate`.** O PHP do Windows não vem com a lista de autoridades certificadoras, então qualquer acesso a um site `https://` falha. Foi o que aconteceu na primeira importação do WooCommerce. A solução **correta** é dar ao PHP essa lista:
  1. Baixe o pacote oficial: `curl -o C:\PHP\extras\ssl\cacert.pem https://curl.se/ca/cacert.pem`
  2. No `php.ini` (descubra qual com `php --ini`), preencha as duas linhas:
     ```ini
     curl.cainfo = "C:/PHP/extras/ssl/cacert.pem"
     openssl.cafile = "C:/PHP/extras/ssl/cacert.pem"
     ```
  3. Confira: `php -r "echo ini_get('curl.cainfo');"`

  **Nunca "resolva" desligando a verificação SSL no código** (`withoutVerifying()`, `verify => false`). Isso funciona, mas deixa qualquer um no meio do caminho trocar as respostas da API. Guardamos um backup do original em `C:\PHP\php.ini.bak`.

### Para fixar
- Rode `where.exe php` e `where.exe node` e descubra de onde vem cada um.

---

<a id="etapa-2"></a>
## Etapa 2 – Git e GitHub: o básico que você vai usar todo dia

**Conceitos:**
- **Repositório:** a pasta do projeto com todo o histórico de mudanças (fica na pasta oculta `.git`).
- **Commit:** uma "foto" do projeto num momento, com uma mensagem dizendo o que mudou.
- **Branch:** uma linha de trabalho paralela. A principal se chama `main`.
- **Remote (`origin`):** a cópia do repositório no GitHub.
- **Push / Pull:** enviar e baixar commits do GitHub.

### Passo a passo: primeira vez num projeto novo

```powershell
cd C:\dev\site_mglasto
git init -b main                         # cria o repositório com a branch main
git add .                                # prepara todos os arquivos
git commit -m "Projeto inicial"          # tira a "foto"
git remote add origin https://github.com/USUARIO/REPOSITORIO.git
git push -u origin main                  # envia e liga a branch local à remota
```

Se o push for recusado porque o repositório do GitHub foi criado com README:
```powershell
git pull origin main --allow-unrelated-histories --no-edit
git push -u origin main
```

### O ciclo do dia a dia

```powershell
git status                       # o que mudou?
git add .                        # prepara as mudanças
git commit -m "Descreve o que mudou"
git push                         # envia para o GitHub
```

### O arquivo `.gitignore`

Lista o que **não** vai para o GitHub. No Laravel, o principal:
- `.env` → **senhas e chaves. Nunca suba esse arquivo.**
- `vendor/` e `node_modules/` → dependências, que qualquer um reinstala com `composer install` e `npm install`.

Depois do primeiro push, **sempre confira no GitHub que o `.env` não foi enviado.**

### Boas mensagens de commit

Curtas e no imperativo, dizendo **o que** mudou:
- ✅ `Banco de dados: catalogo e pedidos multicanal`
- ✅ `Importador: categoria Oferta e estoque das variacoes`
- ❌ `ajustes`, `teste`, `agora vai`

### Para fixar
- Faça uma alteração pequena num arquivo, rode `git status` e `git diff` e observe o que aparece.
- Explique a diferença entre `git commit` e `git push`.

---

<a id="etapa-3"></a>
## Etapa 3 – Criando o projeto Laravel

### Passo a passo

```powershell
composer global require laravel/installer   # instala o comando "laravel" (uma vez só)
mkdir C:\dev; cd C:\dev
laravel new site_mglasto
```

O instalador faz perguntas. O que escolhemos e por quê:

| Pergunta | Escolha | Motivo |
|---|---|---|
| Starter kit | **React** | Telas em React, mas dentro do Laravel (via Inertia). Aproveita o que você já conhecia do TCC. |
| Autenticação | Laravel built-in (Fortify) | Login, cadastro e recuperação de senha prontos e seguros. |
| Testes | **Pest** | Sintaxe mais simples de ler que o PHPUnit. |
| Banco | **SQLite** | Um arquivo só, não instala nada. Ideal para demo e estudo. Trocar para MySQL depois é só mudar o `.env`. |
| Laravel Boost | Sim | Gera regras e ferramentas para agentes de IA entenderem o projeto. |

### O que é o Inertia (e por que não uma API separada)

Há dois jeitos comuns de juntar Laravel e React:

1. **API + front separado:** o Laravel só devolve JSON, e o React é outro projeto. Dá mais trabalho (autenticação por token, CORS, dois deploys).
2. **Inertia:** o Laravel cuida das rotas e dos dados, e cada página é um componente React. **Um projeto só.**

Para um site de loja feito por uma pessoa só, o Inertia é muito mais simples, e ainda permite gerar as páginas no servidor, o que ajuda no Google (SEO).

### Configurando o `.env`

O `.env` guarda as configurações da sua máquina. Ajustamos:
```
APP_NAME="MGLasto"
APP_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR
```
- `APP_LOCALE`: idioma das mensagens (validação, e-mails).
- `APP_FAKER_LOCALE`: idioma dos dados falsos dos testes (nomes, endereços brasileiros).

O `.env.example` é o modelo **sem segredos** que vai para o GitHub. Quem clonar o projeto copia ele para `.env`.

### Rodando o projeto

```powershell
composer run dev
```
Abre em http://localhost:8000. Esse comando sobe o servidor PHP e o Vite (que recompila o React a cada alteração).

### Para fixar
- Rode o projeto e crie uma conta na tela de cadastro.
- Abra o `.env` e encontre onde está definido o banco de dados.

---

<a id="etapa-4"></a>
## Etapa 4 – Entendendo as pastas de um projeto Laravel

| Pasta | O que vai nela |
|---|---|
| `app/Models` | As classes que representam tabelas (Product, Order...) |
| `app/Enums` | Listas fixas de valores (status do pedido, canal de venda) |
| `app/Actions` | Classes que fazem **uma** tarefa de negócio (ex.: importar catálogo) |
| `app/Console/Commands` | Comandos de terminal (`php artisan ...`) |
| `app/Http/Controllers` | Recebem a requisição do navegador e decidem o que responder |
| `config/` | Configurações, que leem valores do `.env` |
| `database/migrations` | A "receita" de cada tabela do banco |
| `database/factories` | Geradores de dados falsos para testes |
| `resources/js` | O front-end em React (páginas, componentes) |
| `routes/web.php` | As URLs do site |
| `storage/` | Arquivos gerados: logs, uploads, fotos |
| `tests/` | Testes automatizados |

**Artisan** é a ferramenta de linha de comando do Laravel. Alguns comandos que você vai usar muito:
```powershell
php artisan migrate              # cria/atualiza as tabelas
php artisan test                 # roda os testes
php artisan route:list           # lista todas as URLs
php artisan make:model Product -mf   # cria model + migration + factory
php artisan tinker               # console interativo para testar código
```

---

<a id="etapa-5"></a>
## Etapa 5 – Banco de dados: migrations

**Arquivos:** `database/migrations/2026_11_18_000001_create_catalog_tables.php` e `..._000002_create_orders_tables.php`

### O que é uma migration

É um arquivo PHP que descreve como criar (método `up`) e desfazer (método `down`) tabelas. Em vez de criar tabelas "na mão", o banco é **reproduzível**: qualquer pessoa roda `php artisan migrate` e tem o mesmo banco.

### Como desenhamos o banco

Primeiro listamos **o que a loja precisa guardar**, olhando o site atual:

```
categories ──< category_product >── products ──< product_variants
                                        │
                                        └──< product_images

users ──< orders ──< order_items >── products / product_variants
```
(`──<` = "um para muitos", `>──<` = "muitos para muitos")

### Decisões importantes (e o porquê)

**1. Dinheiro em centavos, como número inteiro.**
```php
$table->unsignedInteger('price'); // R$ 88,60 é gravado como 8860
```
Computadores erram contas com decimais: `0.1 + 0.2` dá `0.30000000000000004`. Com inteiros não há erro. **Use isso em qualquer projeto que lide com dinheiro.**

**2. Tabela intermediária para "muitos para muitos".**
Um produto pode estar em várias categorias (o Piso Diamante está em "Oferta" e em "Pisos"). Isso exige uma tabela só de ligações:
```php
Schema::create('category_product', function (Blueprint $table) {
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->primary(['category_id', 'product_id']);
});
```
O nome segue a convenção do Laravel: os dois models no singular, em ordem alfabética.

**3. Chaves estrangeiras com regra de exclusão.**
- `cascadeOnDelete()`: apagou o produto, apaga as fotos dele.
- `nullOnDelete()`: apagou o produto, o item do pedido **continua existindo**, só perde a ligação.

**4. O item do pedido copia nome e preço.**
```php
$table->string('product_name');
$table->unsignedInteger('unit_price');
```
Se o preço mudar amanhã, o pedido de ontem continua certo. **Pedido é histórico, não pode mudar sozinho.**

**5. Índice único para evitar duplicidade.**
```php
$table->unique(['channel', 'external_id']);
```
O mesmo pedido do Mercado Livre nunca entra duas vezes, e quem garante é o próprio banco, não o seu código.

**6. Colunas preparando o futuro.**
- `woo_id`: o id do produto no WooCommerce, que permite reimportar sem duplicar.
- `channel`: de onde veio o pedido (site, Mercado Livre, Shopee...).

**7. `softDeletes()` em produtos.** O produto "apagado" ganha uma data em `deleted_at` e some das consultas, mas pode ser recuperado.

### Armadilha que evitamos

Quase demos à coluna de opções da variação o nome `attributes`. **No Laravel, `$attributes` é uma propriedade interna de todo model**, e usar esse nome gera bugs muito difíceis de achar. Renomeamos para `options`. Evite também `attributes`, `original`, `relations` e `connection`.

### Regra prática

**Nunca edite uma migration que já foi para o GitHub.** Crie uma migration nova que altera a tabela. Quem já rodou a antiga não vai rodar de novo.

### Para fixar
- Desenhe no papel o banco de uma biblioteca: livros, autores, empréstimos. Onde precisa de tabela intermediária?
- Por que `order_items.product_id` usa `nullOnDelete` e não `cascadeOnDelete`?

---

<a id="etapa-6"></a>
## Etapa 6 – Models, relacionamentos e enums

**Arquivos:** `app/Models/*.php`, `app/Enums/OrderChannel.php`, `app/Enums/OrderStatus.php`

### Model

Cada tabela tem uma classe. Com ela você escreve PHP em vez de SQL:
```php
Product::where('is_active', true)->get();   // SELECT * FROM products WHERE is_active = 1
```

### Partes de um model (veja `app/Models/Product.php`)

**1. `#[Fillable([...])]`**: quais campos podem ser preenchidos em massa (`Product::create([...])`). Protege contra alguém mandar `is_admin=1` num formulário.

**2. `casts()`**: converte tipos automaticamente.
```php
'is_active' => 'boolean',       // 0/1 do banco vira true/false
'options' => 'array',           // JSON do banco vira array PHP
'status' => OrderStatus::class, // texto do banco vira enum
```

**3. Relacionamentos:**
```php
public function categories(): BelongsToMany { return $this->belongsToMany(Category::class); }
public function variants(): HasMany          { return $this->hasMany(ProductVariant::class); }
```
Uso: `$product->variants`, `$product->categories()->attach($id)`.

| Relação | Exemplo | Método |
|---|---|---|
| Um para muitos | Produto tem muitas fotos | `hasMany` / `belongsTo` |
| Muitos para muitos | Produto ↔ Categoria | `belongsToMany` |

**4. Regras de negócio dentro do model:**
```php
public function currentPrice(): int
{
    return $this->sale_price ?? $this->price;   // promoção, se houver
}
```
Assim a regra fica **num lugar só**. Tela, carrinho e painel chamam `currentPrice()` em vez de repetir a conta.

**5. Scopes (filtros reutilizáveis):**
```php
public function scopeActive(Builder $query): void { $query->where('is_active', true); }
// uso: Product::active()->featured()->get();
```

### Enums

Valores fixos ficam num enum, não em textos soltos pelo código:
```php
enum OrderStatus: string
{
    case Quote = 'quote';
    case Confirmed = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::Quote => 'Orçamento',
            self::Confirmed => 'Confirmado',
        };
    }
}
```
Vantagens: o editor autocompleta, erro de digitação vira erro de código (em vez de um bug silencioso), e os rótulos em português ficam num lugar só.

**Convenção que seguimos:** código em inglês (nomes de classes, colunas), textos para o usuário em português.

### Para fixar
- Abra o `php artisan tinker` e rode `App\Models\Product::active()->count()`.
- Rode `App\Models\Product::first()->categories->pluck('name')`.

---

<a id="etapa-7"></a>
## Etapa 7 – Factories e testes automatizados

**Arquivos:** `database/factories/*.php`, `tests/Feature/Catalog/CatalogModelsTest.php`

### Factory

Gera dados falsos realistas para os testes:
```php
Product::factory()->create();                         // 1 produto qualquer
Product::factory()->count(10)->create();              // 10 produtos
Product::factory()->onSale()->featured()->create();   // estados prontos
```
Os "estados" (`onSale`, `outOfStock`) são métodos da factory que mudam alguns campos.

### Teste automatizado

Um teste é um código que **confere se outro código faz o que deveria**:
```php
test('the current price uses the sale price when there is one', function () {
    $product = Product::factory()->create(['price' => 10_000, 'sale_price' => 8_860]);

    expect($product->currentPrice())->toBe(8_860);
});
```
Estrutura de quase todo teste: **prepara → executa → confere.**

### O que vale testar

As **regras de negócio**, não o Laravel:
- ✅ o preço promocional é usado quando existe
- ✅ o mesmo pedido de marketplace não entra duas vezes
- ✅ o item do pedido mantém o nome depois que o produto é apagado
- ❌ "o `create` grava no banco" (o Laravel já garante isso)

### Rodando

```powershell
php artisan test                               # todos
php artisan test --filter=CatalogModelsTest    # só um arquivo
```
Os testes usam um banco temporário (`RefreshDatabase`), então **não mexem nos seus dados**.

### Por que isso importa tanto

Quando você mexer no código daqui a um mês, ou quando uma IA mexer, os testes avisam se algo quebrou. Foi assim que corrigimos o importador sem medo: ajustamos o teste para simular o problema real e só então mudamos o código.

### Para fixar
- Escreva um teste: "um produto com `manage_stock = false` está sempre em estoque".
- Quebre `currentPrice()` de propósito e veja o teste falhar.

---

<a id="etapa-8"></a>
## Etapa 8 – Importando dados de outro sistema (comando Artisan + API)

**Arquivos:** `app/Actions/Catalog/ImportWooCommerceCatalog.php`, `app/Console/Commands/ImportWooCommerce.php`, `tests/Feature/Catalog/ImportWooCommerceTest.php`

**Uso:**
```powershell
php artisan storage:link                    # uma vez: deixa as fotos acessíveis pelo navegador
php artisan mglasto:importar-woocommerce    # importa tudo
php artisan mglasto:importar-woocommerce --sem-imagens
```

### Arquitetura: comando fino, Action com a lógica

- **Command** (`ImportWooCommerce`): só lê as opções, chama a Action e mostra o resultado na tela.
- **Action** (`ImportWooCommerceCatalog`): faz o trabalho de verdade.

Por quê? A mesma Action pode ser chamada depois por um botão no painel ou por uma tarefa agendada, sem copiar código.

### Técnicas usadas (reaproveitáveis)

**1. Cliente HTTP do Laravel com retentativas:**
```php
Http::baseUrl($url)->acceptJson()->timeout(30)->retry(3, 500)->get('products')->throw()->json();
```
`retry(3, 500)` tenta 3 vezes com meio segundo de intervalo, e `throw()` gera erro se a resposta falhar, em vez de seguir com dados vazios.

**2. Paginação:** APIs devolvem os dados em páginas. O loop pede a próxima página enquanto vierem 100 itens.

**3. Importação idempotente (pode rodar várias vezes):**
```php
Product::updateOrCreate(['woo_id' => $data['id']], [...dados...]);
```
Se já existe um produto com aquele `woo_id`, atualiza. Se não existe, cria. **Nunca duplica.**

**4. Transação:** cada produto é gravado dentro de `DB::transaction()`. Se algo falhar no meio, nada daquele produto fica gravado pela metade.

**5. Limpeza de HTML:** descrições vindas de outro sistema podem trazer `<script>` ou `onclick`. Mantemos só tags simples e removemos os atributos. **Nunca confie em HTML de fora.**

**6. Configuração no `.env`, não no código:**
```php
// config/services.php
'woocommerce' => ['store_url' => env('WOOCOMMERCE_STORE_URL', 'https://loja.mglasto.com.br')],
// no código
config('services.woocommerce.store_url');
```
Regra do Laravel: **`env()` só dentro de `config/`**. No resto do código, use `config()`.

**7. Testar sem internet com `Http::fake()`:** o teste simula as respostas da API. Fica rápido, não depende do site estar no ar e permite simular casos difíceis.

### Problemas reais que a importação revelou

Esta é a parte mais valiosa para estudar: **os dados reais nunca são como a documentação promete.**

| Problema | Como descobrimos | Solução |
|---|---|---|
| A categoria "Oferta" não aparecia na lista de categorias de cada produto | Conferindo o banco depois da importação: 0 destaques e 2 produtos sem categoria | Perguntar a cada categoria quais produtos ela tem |
| As 5 variações repetiam o SKU do produto principal | Olhando o JSON real da API | Variação com SKU igual ao do pai fica sem SKU |
| As variações repetiam o estoque do produto (92 cada, somando 460) | Conferindo o banco | Variação que só repete o estoque do pai fica com estoque `null` (compartilhado) |
| Estoque só vinha como texto ("92 em estoque") | Olhando o JSON | Extrair o número com expressão regular |

**Lição:** depois de qualquer importação, **confira os dados com consultas**, não só "rodou sem erro". Exemplo do que usamos:
```sql
SELECT name FROM products p
WHERE NOT EXISTS (SELECT 1 FROM category_product cp WHERE cp.product_id = p.id);
```

### Para fixar
- Abra `https://loja.mglasto.com.br/wp-json/wc/store/v1/products/371` no navegador e encontre no JSON o preço, o estoque e as variações.
- Por que o importador usa `woo_id` e não o `slug` para saber se o produto já existe?

---

<a id="etapa-9"></a>
## Etapa 9 – Trabalhando com um agente de IA no projeto

Usamos dois "Claudes" com papéis diferentes:

| Onde | Papel |
|---|---|
| Conversa no claude.ai | Planejamento, decisões, revisão, conferir o resultado no banco e no GitHub |
| Extensão no VS Code | Execução: rodar comandos, testes, corrigir erros, fazer commits |

**Eles não compartilham memória.** A ponte é:
- `docs/CONTEXTO-PROJETO.md`: decisões e regras que o agente deve seguir.
- Este guia: o que foi feito, para você estudar.
- O GitHub: onde os dois veem o mesmo código.

### Como pedir tarefas a um agente (modelo reaproveitável)

```
Tarefa: <o que fazer, em uma frase>
Contexto: leia docs/CONTEXTO-PROJETO.md
Regras: <o que NÃO pode mudar>
Passos: 1) ... 2) ... 3) rode os testes
Se falhar: corrija sem mudar as regras de negócio e me explique o que mudou
Final: commit "<mensagem>" e push
```

**Nunca aceite "deu certo" sem conferir.** Na etapa 8, o comando rodou sem erro, mas os dados estavam errados. Só a conferência no banco mostrou o problema.

---

<a id="etapa-9-1"></a>
## Etapa 9.1 – Identidade visual: logo e cores

**Arquivos:** `public/images/logo/*.svg`, `public/favicon.svg`, `public/favicon.ico`, `public/apple-touch-icon.png`

### O problema do logo antigo

O logo do site atual (`mglasto_logo_3d_3.png`) tinha três defeitos que o impediam de funcionar em fundos diferentes:

1. **"M" e "LASTO" em preto:** somem em fundo escuro.
2. **Fundo cinza embutido na imagem:** o PNG não é transparente, então sempre aparece um retângulo atrás.
3. **Efeito 3D (relevo e sombra):** vira borrão em tamanho pequeno (ícone da aba, cabeçalho no celular).

### O que fizemos

**1. Redesenho em vetor (SVG).** O logo foi redesenhado com formas geométricas, mantendo o desenho original: o "M" com o "V" central, o triângulo laranja e o "G". O "LASTO" foi convertido de fonte para contorno, então aparece igual em qualquer computador, mesmo sem a fonte instalada.

**Por que SVG e não PNG?**
- Fica nítido em qualquer tamanho, do ícone de 16 px a um banner.
- O arquivo é pequeno e o fundo é transparente.
- As cores podem ser trocadas no próprio código.

**2. Estilo plano (flat).** Sem relevo nem sombra. É o padrão atual de marcas digitais, porque funciona em tela pequena e em qualquer fundo.

**3. Uma versão para cada tipo de fundo:**

| Arquivo | Quando usar |
|---|---|
| `logo-mglasto.svg` | Fundos claros (branco, cinza-claro). Versão principal. |
| `logo-mglasto-fundo-escuro.svg` | Fundos escuros e fotos escuras: "M" e "LASTO" em branco. |
| `logo-mglasto-fundo-laranja.svg` | Faixas e banners laranja: triângulo e "G" em branco. |
| `logo-mglasto-mono-preto.svg` / `-mono-branco.svg` | Uma cor só: carimbo, nota fiscal, gravação, etiqueta. |
| `simbolo-mglasto.svg` | Só o "MG" num quadrado escuro: favicon, ícone de app, foto de perfil. |
| `simbolo-mglasto-claro.svg` | Só o "MG" sem fundo, para fundos claros. |

**4. Ícones do navegador.** O `favicon.ico` (16, 32 e 48 px), o `favicon.svg` e o `apple-touch-icon.png` (180 px, usado quando alguém salva o site na tela do iPhone) foram gerados a partir do símbolo. O `resources/views/app.blade.php` já apontava para esses arquivos, então só trocamos as imagens.

### Paleta de cores

Extraída do site atual (variáveis do Elementor) e organizada:

| Papel | Cor | Uso |
|---|---|---|
| Laranja principal | `#FF7A00` | Botões, destaques, preços em promoção |
| Laranja escuro | `#FF6000` | Botão ao passar o mouse (hover) |
| Laranja claro | `#FFF3E8` | Fundos de seções e selos |
| Grafite | `#1C1D1C` | Textos principais, cabeçalho, rodapé |
| Cinza texto | `#54595F` | Textos secundários |
| Cinza borda | `#E9E9E9` | Linhas e bordas |
| Cinza fundo | `#F5F5F5` | Fundo de cards e seções |

**Atenção à acessibilidade:** texto branco sobre o laranja `#FF7A00` tem contraste baixo (cerca de 2,6:1, quando o mínimo recomendado para texto é 4,5:1). Em botões laranja, use **texto grafite `#1C1D1C`**, ou texto branco em **negrito e tamanho grande**. Isso será definido no guia visual.

### Como testamos

1. Renderizamos o logo nos fundos branco, grafite e laranja e conferimos que nenhuma parte some.
2. Renderizamos o símbolo em 16, 32 e 64 px, os tamanhos reais do favicon, e conferimos que o "MG" continua legível.

### Para fixar
- Abra um dos `.svg` no VS Code e troque a cor `#FF7A00` por outra. Abra no navegador e veja o resultado.
- Por que um logo precisa de uma versão monocromática?

---

<a id="etapa-10"></a>
## Etapa 10 – Painel administrativo com Filament

**Arquivos:** `app/Providers/Filament/AdminPanelProvider.php`, `app/Filament/Resources/**`, `app/Filament/Forms/MoneyInput.php`, `app/Support/Money.php`, `config/mglasto.php`, `app/Models/User.php`, `tests/Unit/MoneyTest.php`, `tests/Feature/Admin/AdminPanelTest.php`

**O que fizemos:** criamos o painel em `/admin`, onde a MGLASTO cadastra categorias, produtos, variações e fotos e acompanha os pedidos, sem escrever nenhuma tela do zero.

### O que é o Filament

É um pacote que **gera telas de administração a partir dos seus models**: listas com busca, filtros e paginação, formulários com validação, upload de fotos. Você descreve *quais campos* existem, e ele monta a tela. Escrever isso em React levaria semanas.

O painel do Filament é **separado da loja**: ele usa Livewire (telas em PHP que se atualizam sozinhas), não Inertia/React. São dois "mundos" no mesmo projeto, e tudo bem: o cliente nunca vê o painel.

### Passo a passo

1. **Ativar a extensão `intl` do PHP.** O Filament usa essa extensão para formatar números e datas. O primeiro `composer require` falhou com `ext-intl * -> it is missing from your system`. No `C:\PHP\php.ini`, tiramos o `;` da frente de `extension=intl` (e de `extension=gd`, usada para imagens):
   ```powershell
   php -m | Select-String intl     # deve mostrar "intl"
   ```
   Não use `--ignore-platform-req=ext-intl`: o Composer instala, mas o painel quebra na hora de usar.

2. **Instalar o Filament e criar o painel:**
   ```powershell
   composer require filament/filament:"^5.11" -W
   php artisan make:filament-panel admin
   php artisan filament:assets
   ```
   Usamos a versão 5.11.4, a mais recente compatível com Laravel 13 (conferida com `composer show filament/filament --all`).

3. **Gerar as telas (resources) a partir do banco:**
   ```powershell
   php artisan make:filament-resource Category --generate
   php artisan make:filament-resource Product --generate --soft-deletes
   php artisan make:filament-resource Order --generate
   php artisan make:filament-relation-manager ProductResource variants name
   php artisan make:filament-relation-manager ProductResource images path
   php artisan make:filament-relation-manager OrderResource items product_name
   ```
   O `--generate` lê as colunas da tabela e cria um formulário inicial. Depois **ajustamos tudo à mão**: rótulos em português, campos na ordem certa, regras de validação.

### Como um resource é organizado

```
app/Filament/Resources/Products/
├── ProductResource.php          ← "índice": model, ícone, rótulos, páginas
├── Schemas/ProductForm.php      ← campos do formulário
├── Tables/ProductsTable.php     ← colunas e filtros da lista
├── Pages/                       ← Listar, Criar, Editar
└── RelationManagers/            ← abas de Variações e Fotos dentro do produto
```
Um **relation manager** é uma tabela dentro da página de edição que gerencia um relacionamento (`hasMany`). Por isso as variações e fotos são editadas *dentro* do produto.

### Decisões importantes (e o porquê)

**1. Quem pode entrar: lista de e-mails no `.env`.**
```php
// app/Models/User.php
public function canAccessPanel(Panel $panel): bool
{
    return in_array(strtolower($this->email), config('mglasto.admin_emails'), true);
}
```
```
# .env
ADMIN_EMAILS=voce@mglasto.com.br,outra.pessoa@mglasto.com.br
```
Qualquer pessoa pode criar conta no site (o starter kit tem cadastro), mas só os e-mails da lista entram no painel. Os outros recebem **403 (proibido)**. A lista é lida em `config/mglasto.php`, porque `env()` só pode ser usado dentro de `config/` (regra da Etapa 8). Comparamos em minúsculas, porque `Admin@MGLasto.com.br` e `admin@mglasto.com.br` são o mesmo e-mail.

**2. Dinheiro: digita em reais, grava em centavos.** É o ponto mais perigoso do painel: um erro aqui multiplica ou divide todos os preços por 100. Por isso a conversão fica **num lugar só**, a classe `App\Support\Money`:

| Método | Entrada | Saída |
|---|---|---|
| `Money::toCents()` | `"1.234,56"` | `123456` |
| `Money::fromCents()` | `8860` | `"88,60"` |
| `Money::format()` | `8860` | `"R$ 88,60"` |

Valores que não dá para entender com certeza (`"88,605"`, `"abc"`) viram `null` e o formulário mostra *"Informe o valor em reais, por exemplo 88,60"*. **Nunca adivinhe um preço.**

O campo reutilizável `MoneyInput` liga a conversão ao formulário:
```php
TextInput::make($name)
    ->prefix('R$')
    ->formatStateUsing(fn (?int $state) => Money::fromCents($state))   // banco → tela
    ->dehydrateStateUsing(fn (?string $state) => Money::toCents($state)) // tela → banco
```
`formatStateUsing` roda ao **abrir** o formulário e `dehydrateStateUsing` ao **salvar**. Na tabela usamos `->money('BRL', divideBy: 100, locale: 'pt_BR')`, que divide por 100 só para exibir.

**3. Pedido é histórico: só o status muda.** Na tela do pedido, os dados do cliente, os valores e os itens aparecem como **texto** (`TextEntry`), não como campo editável. O único campo é o `Select` de status. Também desligamos criar e apagar pedidos (`canCreate()` e `canDelete()` retornam `false`): pedidos nascem no checkout ou na importação dos marketplaces, e um pedido errado é **cancelado**, não apagado.

**4. Variação sem preço ou sem estoque herda do produto.** No formulário da variação, deixar o preço vazio significa *"usa o preço do produto"* e deixar o estoque vazio significa *"compartilha o estoque do produto"* (regras 2 e 3 do contexto). Os textos de ajuda dizem isso na tela.

**5. Preço promocional precisa ser menor que o normal.** Validação nova, para evitar uma "promoção" mais cara que o preço normal. *(Decisão tomada sem o André: está no relatório para revisão.)*

**6. Descrição com editor limitado.** O editor de texto só oferece negrito, itálico, títulos H2/H3 e listas, as mesmas tags que a loja aceita (regra 8). A loja ainda vai sanitizar o HTML ao exibir.

**7. Endereços e textos em português.** `/admin/produtos`, `/admin/categorias`, `/admin/pedidos`, via `$slug` em cada resource. Cor principal laranja `#FF7A00`, logo da MGLASTO (versão clara e versão para modo escuro).

### Problemas encontrados

| Problema | Causa | Solução |
|---|---|---|
| `ext-intl ... is missing from your system` | Extensão `intl` desligada no `php.ini` | Tirar o `;` de `extension=intl` |
| `composer require` passou de 10 minutos e o `filament:install` ficou parado | O instalador fazia uma pergunta e esperava resposta | Rodar os comandos separados com argumentos explícitos (`make:filament-panel admin`) |
| Comandos `filament:*` não existiam depois da instalação | O Composer foi interrompido antes do *package discovery* | `composer install` de novo |
| O `sed` estragou as quebras de linha do `php.ini` | O `sed` do Git Bash troca CRLF por LF | Normalizar o arquivo e conferir com `diff --strip-trailing-cr` |

### Como criar o primeiro administrador

```powershell
# 1) no .env
ADMIN_EMAILS=seu-email@exemplo.com
# 2) criar o usuário (pede nome, e-mail e senha)
php artisan make:filament-user
# 3) abrir http://localhost:8000/admin
```

### Para fixar
- No `tinker`, rode `App\Support\Money::toCents('1.234,56')` e `App\Support\Money::fromCents(99)`. Explique cada resultado.
- Por que é melhor `canDelete()` retornar `false` do que só esconder o botão de apagar?
- Abra `ProductsTable.php` e adicione uma coluna com a data de criação, escondida por padrão (`->toggleable(isToggledHiddenByDefault: true)`).
- Rode `php artisan test --filter=AdminPanelTest` e leia cada teste: qual regra de negócio cada um protege?

---

<a id="etapa-11"></a>
## Etapa 11 – Guia visual: tokens do Tailwind, componentes e layout da loja

**Arquivos:** `resources/css/app.css` (bloco `@theme`), `resources/js/components/store/*`, `resources/js/layouts/store-layout.tsx`, `resources/js/lib/money.ts`, `resources/js/types/store.ts`, `resources/js/pages/store/style-guide.tsx`, `app/Support/StoreLayoutData.php`, `config/mglasto.php`

**O que fizemos:** definimos as cores, a fonte e os tamanhos da marca num lugar só, criamos as peças reutilizáveis da loja (botão, card de produto, preço, selo, container) e o layout com cabeçalho, menu e rodapé. A página **`/guia-visual`** mostra tudo junto, para revisar e ajustar o visual (ela não existe em produção).

### Tokens: o visual inteiro num lugar só

No Tailwind 4 a configuração fica **no CSS**, dentro de `@theme`. Cada variável vira classes automaticamente:
```css
@theme {
    --color-brand: #ff7a00;      /* vira bg-brand, text-brand, border-brand... */
    --color-graphite: #1c1d1c;   /* vira text-graphite, bg-graphite... */
    --radius-card: 0.75rem;      /* vira rounded-card */
    --shadow-card: ...;          /* vira shadow-card */
    --spacing-touch: 2.75rem;    /* vira min-h-touch, size-touch (44 px) */
}
```
**Por que isso importa:** quando o André mandar as telas de referência, trocar o laranja ou o arredondamento é mudar **uma linha**, e o site inteiro acompanha. Se tivéssemos escrito `bg-[#FF7A00]` em cada botão, seria preciso caçar e trocar em dezenas de arquivos.

### Contraste: por que existe um "laranja para texto"

O contraste entre a cor do texto e o fundo precisa ser de pelo menos **4,5:1** para texto normal (regra WCAG de acessibilidade).

| Combinação | Contraste | Pode usar? |
|---|---|---|
| Laranja `#FF7A00` sobre branco | 2,6:1 | ❌ para texto |
| Grafite `#1C1D1C` sobre laranja | 6,5:1 | ✅ botões e selos |
| Laranja escuro `#C25400` sobre branco | 4,6:1 | ✅ links ("Ver produto") |
| Laranja `#FF7A00` sobre grafite | 6,5:1 | ✅ hover no rodapé |

Por isso criamos o token `brand-ink` (`#C25400`): o laranja da marca continua nos fundos, e o texto laranja usa a versão escura.

### Componentes reutilizáveis

Ficam em `resources/js/components/store/`, separados de `components/ui/` (que são do starter kit e da área logada):

| Componente | O que faz |
|---|---|
| `Container` | Centraliza o conteúdo com as mesmas margens laterais em todas as páginas |
| `Button` / `ButtonLink` | Botão laranja com texto grafite (e variações), altura mínima de 44 px. `ButtonLink` é um link com cara de botão; com `external` abre outro site (WhatsApp) em nova aba |
| `Badge` | Selo: "Oferta", "Esgotado"... |
| `Price` | Recebe **centavos** e mostra `R$ 88,60`; com promoção, risca o preço normal |
| `ProductCard` | Foto quadrada, nome em até 2 linhas, preço, selo "Oferta" |

Os botões usam o `cva` (*class-variance-authority*), que organiza as variações de um componente:
```tsx
<Button>Adicionar ao orçamento</Button>             // laranja (padrão)
<Button variant="outline" size="lg" block>...</Button>
```

**Dinheiro no React:** o servidor sempre manda centavos, e a formatação é feita só para exibir:
```ts
new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(8860 / 100) // "R$ 88,60"
```
Nunca faça conta com o texto formatado.

### O layout da loja (`StoreLayout`)

- **Cabeçalho fixo:** logo, busca (no celular fica numa linha própria, bem visível) e o link do orçamento com contador de itens.
- **Menu de categorias:** em linha no computador; no celular, um menu lateral (hambúrguer) que fecha com Esc, com o botão X ou tocando fora.
- **Rodapé grafite** com o logo para fundo escuro, categorias, links de ajuda e contato.
- **Link "Pular para o conteúdo"**, que só aparece ao navegar pelo teclado (acessibilidade).

O `app.tsx` escolhe o layout pelo nome da página: tudo em `pages/store/` usa o `StoreLayout`.

### De onde vêm os dados do cabeçalho e do rodapé

O menu de categorias, o contador do carrinho e os dados da empresa são **iguais em todas as páginas**. Em vez de cada controller mandar isso, o middleware do Inertia compartilha com todas as páginas:
```php
// app/Http/Middleware/HandleInertiaRequests.php
'store' => fn () => StoreLayoutData::make($request),
```
No React: `const { store } = usePage().props;`

### Dados da empresa: nada inventado

Telefone, e-mail, endereço e CNPJ vêm do `.env` (`COMPANY_PHONE`, `COMPANY_EMAIL`, `COMPANY_ADDRESS`, `COMPANY_CNPJ`). Enquanto estiverem vazios, a loja mostra **`[A DEFINIR]`** bem visível, em vez de um dado falso que alguém poderia achar que é verdadeiro.

### Decisões tomadas (e o porquê)

- **A loja é sempre clara.** O starter kit tem modo escuro na área logada, mas a identidade da MGLASTO é "muito branco". A classe `.store` no CSS garante isso.
- **O card inteiro é um link**, com "Ver produto" como chamada. Um botão dentro de um link é proibido em HTML, e o card inteiro clicável é mais fácil de tocar no celular.
- **Fonte Inter** carregada pelo próprio Vite (`bunny('Inter')` no `vite.config.ts`), sem link manual.
- **Os endereços vêm prontos do servidor** (`url` em cada produto e categoria), e o React não monta URLs.

### Problemas encontrados

| Problema | Causa | Solução |
|---|---|---|
| No celular, a página ficava mais larga que a tela | O botão tinha `whitespace-nowrap`: texto longo não quebra e empurra a página | Tirar o `nowrap`; o botão pode ter duas linhas |
| Caminhos de arquivo em `<code>` também empurravam a página | Palavras longas sem espaço não quebram | `break-all` nesses trechos |
| O screenshot "de 375 px" saía cortado | O Chrome sem interface (*headless*) não abre janelas com menos de **504 px**: a imagem era um recorte de uma página de 504 px | Abrir a página dentro de um `<iframe>` de 375 px e medir com JavaScript se algum elemento passa da largura |
| `npm run check` acusava 45 arquivos | O formatador olhava os arquivos do Filament em `public/` (código de terceiros) | `public/**` nos `ignorePatterns` do formatador |

**Lição:** confira o resultado **na largura real do celular**. "Rodou sem erro" não quer dizer "cabe na tela".

### Para fixar
- Abra `/guia-visual`, troque `--color-brand` em `app.css` por outra cor e veja todos os botões e selos mudarem.
- Por que o texto dos botões laranja é grafite e não branco?
- Use o DevTools do navegador (F12 → ícone de celular) para ver o `/guia-visual` com 375 px.
- Crie uma variação `danger` no `Button` (fundo vermelho) e use na página do guia.

---

<a id="etapa-12"></a>
## Etapa 12 – Páginas da loja: home, categoria e busca

**Arquivos:** `routes/web.php`, `app/Http/Controllers/Store/*`, `app/Http/Resources/ProductCardResource.php`, `app/Support/ProductListing.php`, `resources/js/pages/store/{home,category,search}.tsx`, `resources/js/components/store/{product-grid,listing-page}.tsx`, `tests/Feature/Store/StorefrontTest.php`

**O que fizemos:** a loja ganhou suas primeiras páginas de verdade, com o catálogo importado:

| Endereço | Página |
|---|---|
| `/` | Home: faixa de abertura, categorias, vitrine "Ofertas" e "Mais produtos" |
| `/categoria/{slug}` | Produtos da categoria (e das subcategorias), com ordenação e paginação |
| `/busca?q=...` | Busca por nome e código (SKU). Sem `q`, lista todos os produtos |

### O caminho de uma página, do navegador à tela

```
navegador → routes/web.php → Controller → Inertia::render('store/category', [dados])
                                                     ↓
                               resources/js/pages/store/category.tsx recebe os dados como props
```
```php
// routes/web.php
Route::get('/categoria/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
```
O `{category:slug}` é o **route model binding**: o Laravel procura a categoria pelo `slug` e entrega o model pronto ao controller. Se não existir, responde **404** sozinho.

### Controllers finos

Cada controller só **busca os dados e escolhe a página**. As regras repetidas ficam em classes próprias:

- **`ProductListing`**: tudo o que as listas têm em comum (só produtos ativos, a primeira foto carregada junto, ordenação, 24 por página).
- **`ProductCardResource`**: transforma um `Product` nos campos que o card precisa (`name`, `url`, `price`, `sale_price`, `in_stock`, `image`). É um **API Resource** do Laravel: decide exatamente o que vai para o navegador, sem mandar colunas internas como `woo_id`.

### Decisões importantes (e o porquê)

**1. Ordenar pelo preço que o cliente paga.** Um produto de R$ 500 em promoção por R$ 10 deve aparecer entre os mais baratos:
```php
$query->orderByRaw('COALESCE(sale_price, price) asc');
```
`COALESCE` devolve o primeiro valor que não é nulo: o preço promocional se existir, senão o normal. É a regra 2 do contexto (`currentPrice()`) escrita em SQL.

**2. Sem produtos repetidos na home.** A vitrine "Mais produtos" exclui os que já estão em "Ofertas" (`whereKeyNot($featured->modelKeys())`), um dos defeitos do site antigo.

**3. Busca por palavras, em qualquer ordem.** "diamante piso" encontra "PISO BORRACHA DIAMANTE": cada palavra precisa aparecer no nome **ou** no SKU do produto **ou** no SKU de uma variação.

**4. Endereços prontos no servidor.** Cada produto e categoria chega ao React com `url` gerada por `route('products.show', $slug, absolute: false)`. O React só usa, nunca monta URL. Se a rota mudar, muda num lugar só.

**5. Fotos com endereço relativo** (`/storage/products/...`), que funciona em qualquer domínio ou porta sem depender do `APP_URL`.

### Evitando o problema N+1

Mostrar 24 produtos com foto pode virar **25 consultas** ao banco: 1 para os produtos e mais 1 para a foto de *cada* produto. Esse é o problema **N+1**. A solução é o **eager loading**, que carrega tudo junto:
```php
Product::query()->active()->with(['images' => fn ($query) => $query->limit(1)]);
```
São **2 consultas**, não importa quantos produtos. E o `limit(1)` dentro do `with` (recurso do Laravel 11+) traz só a primeira foto de cada produto.

**Como garantimos:** um teste conta as consultas com 3 produtos e com 30. Se alguém tirar o `with()`, o número cresce e o teste falha.

### Problemas encontrados

| Problema | Causa | Solução |
|---|---|---|
| Testes quebraram com `Unable to locate file in Vite manifest` | Os testes renderizavam o HTML usando o *build* do front-end; página nova sem `npm run build` = erro | `$this->withoutVite()` no `tests/TestCase.php`: teste confere o servidor, não o build |
| O teste "busca com `%`" passava pelo motivo errado | No SQLite, o `LIKE` não tem caractere de escape padrão: `\%` não protegia nada | Remover `%`, `_` e `\` do termo (não fazem sentido numa busca de produto) e testar com `L%l`, que *casaria* com "Lencol" se o `%` funcionasse como curinga |
| No celular, nomes de categoria cortados ("Infraestrut e...") | Imagem e texto lado a lado em cards estreitos | Imagem em cima do texto no celular, lado a lado a partir de `sm` |
| Textos de destaque da home inventavam promessas ("Enviamos para todo o Brasil") | Texto de exemplo escrito sem confirmar com a empresa | Trocados por descrições do próprio funcionamento da loja (orçamento sem cadastro, envio pelo WhatsApp) |

**Limitação conhecida:** a busca não ignora acentos: "marmore" não encontra "MÁRMORE". O SQLite não tem essa função pronta. Fica para quando o banco for MySQL, que já compara sem acento por padrão.

### Para fixar
- Acesse `/categoria/pisos-e-revestimentos?ordem=menor-preco` e confira a ordem com os preços do painel.
- Comente o `->with([...])` em `ProductListing::query()` e rode `php artisan test --filter=queries`. Por que o teste falhou? (Depois desfaça.)
- O que acontece ao acessar `/categoria/nao-existe`? Qual linha do código produz esse resultado?
- Explique com suas palavras por que o React recebe `url` pronta em vez de montar `/produto/` + slug.

---

<a id="etapa-13"></a>
## Etapa 13 – Página de produto

**Arquivos:** `app/Http/Controllers/Store/ProductController.php`, `app/Support/HtmlSanitizer.php`, `app/Support/WhatsApp.php`, `app/Models/ProductVariant.php` (`isInStock()`), `resources/views/app.blade.php` (metatags), `resources/js/pages/store/product.tsx`, `tests/Unit/HtmlSanitizerTest.php`, `tests/Feature/Store/ProductPageTest.php`, `tests/Feature/Catalog/VariantStockTest.php`

**O que fizemos:** a página `/produto/{slug}` (o mesmo padrão de endereço do site atual) com galeria de fotos, preço (o promocional risca o normal), disponibilidade, seletor de variações, quantidade, botão "Adicionar ao orçamento", botão "Tirar dúvidas no WhatsApp", descrição e produtos relacionados.

### O seletor de variações

O servidor manda as variações **e** os grupos de opções já organizados:
```json
"option_groups": [
  { "name": "Cor", "values": ["Amarelo", "Azul", "Cinza", "Preto", "Vermelho"] },
  { "name": "Espessura", "values": ["3 mm"] }
]
```
No React, cada clique guarda a escolha em `selected` (um `useState`). A variação escolhida é a que tem **todas** as opções iguais às escolhidas. Quando ela é encontrada, o preço e a disponibilidade mudam na hora, sem ir ao servidor.

Detalhes que melhoram o uso:
- Grupos com **um valor só** (Espessura "3 mm") já vêm marcados.
- Uma opção que não existe junto com as outras já escolhidas fica **desativada**.
- Enquanto falta escolher, o botão diz **"Escolha uma opção"** e não deixa adicionar.

### Regra do estoque da variação (regra 3 do contexto)

```php
// app/Models/ProductVariant.php
public function isInStock(): bool
{
    if (! $this->is_active) return false;
    if ($this->stock_quantity === null || ! $this->product->manage_stock) {
        return $this->product->isInStock();   // compartilha o estoque do produto
    }
    return $this->stock_quantity > 0;          // estoque próprio
}
```
A regra fica **no model**, não na tela: o carrinho (próxima etapa) vai usar a mesma função.

### Segurança: sanitizar o HTML da descrição

A descrição veio de outro sistema e é exibida como HTML (`dangerouslySetInnerHTML`). Se alguém conseguir gravar um `<script>` nela, ele rodaria no navegador de todo cliente. Esse ataque se chama **XSS** (*cross-site scripting*).

O `HtmlSanitizer` usa um **leitor de HTML de verdade** (`DOMDocument`), não expressões regulares, e:
- **mantém** só as tags simples (`p`, `br`, `strong`, `em`, listas, `h2`–`h4`), **sem nenhum atributo**;
- **apaga com o conteúdo** `script`, `style`, `iframe`, `svg`...;
- **desembrulha** o resto (`div`, `span`, `a`): some a tag, fica o texto.

O teste `HtmlSanitizerTest` tenta vários ataques conhecidos (`onerror`, `javascript:`, `<svg><script>`, aspas dentro de atributos) e confere que nenhum passa.

**Por que limpar de novo, se o importador já limpou?** Porque a descrição também pode ser editada no painel, e amanhã pode vir de outro lugar. **Sanitize na saída**, sempre (regra 8).

### Metatags e a pré-visualização no WhatsApp

Quando alguém cola o link de um produto no WhatsApp, aparece um cartão com foto, título e descrição. Quem monta esse cartão é um robô que **não executa JavaScript**, então as metatags precisam vir **no HTML do servidor**:
```php
return Inertia::render('store/product', [...])->withViewData('meta', [
    'title' => $product->name, 'description' => ..., 'image' => url($image->url()), ...
]);
```
O `app.blade.php` escreve as tags `og:title`, `og:description`, `og:image` e `canonical`. As tags `og:` são do protocolo **Open Graph**, usado por WhatsApp, Facebook e LinkedIn.

### Botão do WhatsApp sem número inventado

O número vem de `WHATSAPP_NUMBER` no `.env`. A classe `WhatsApp` remove tudo que não é dígito (aceita `+55 (11) 99999-0000`) e monta `https://wa.me/5511999990000?text=...` com o nome e o link do produto. **Sem número configurado, não existe link**: a página mostra **"[A DEFINIR: número do WhatsApp]"**.

### Problemas encontrados

| Problema | Causa | Solução |
|---|---|---|
| A página passava 8 px da tela no celular | No celular a grade tinha uma coluna *implícita*, que cresce até caber o conteúdo; a fila de miniaturas da galeria empurrava a largura | `grid-cols-1` (vira `minmax(0, 1fr)`): a coluna respeita a tela e as miniaturas rolam dentro dela |
| O verificador de overflow não pegou isso na primeira vez | Ele só procurava elementos além de 376 px, mas a área útil era 360 px (a barra de rolagem ocupa o resto) | Comparar com `clientWidth`, a largura visível real |
| A trilha mostrava "Início › Oferta" | "Oferta" vinha primeiro pela posição | Preferir uma categoria real; "Oferta" é vitrine, não tipo de produto |

### Para fixar
- Abra `/produto/piso-borracha-diamante`, escolha uma cor e veja o texto "Opção: ..." mudar. Em que linha do `product.tsx` a variação é encontrada?
- No `tinker`, rode `App\Support\HtmlSanitizer::clean('<p onclick="x()">Oi<script>alert(1)</script></p>')`. Explique o resultado.
- Por que as metatags `og:` estão no Blade e não no `<Head>` do React?
- Coloque um número fictício em `WHATSAPP_NUMBER`, rode `php artisan config:clear` e veja o botão aparecer. (Depois apague.)

---

<a id="checklist"></a>
## Checklist para reaproveitar em outros projetos

**Antes de programar**
- [ ] Entendi o problema real e o que o sistema atual já faz
- [ ] Defini o que entra na primeira versão e o que fica para depois
- [ ] Escolhi a stack pelo problema, não pela moda

**Ambiente**
- [ ] Projeto fora de pastas sincronizadas (OneDrive etc.)
- [ ] `php -v`, `composer -V`, `node -v` e `git --version` funcionando
- [ ] Extensões do PHP conferidas

**Projeto**
- [ ] `laravel new` com o starter kit adequado
- [ ] `.env` ajustado (nome, idioma `pt_BR`)
- [ ] Repositório no GitHub com o primeiro push
- [ ] Conferido que o `.env` **não** foi para o GitHub

**Banco**
- [ ] Desenhei as tabelas no papel antes
- [ ] Dinheiro em centavos (inteiro)
- [ ] Chaves estrangeiras com `cascadeOnDelete` ou `nullOnDelete` pensados caso a caso
- [ ] Índices únicos onde duplicidade seria um erro
- [ ] Nenhuma coluna com nome reservado (`attributes`...)

**Código**
- [ ] Regras de negócio no model ou numa Action, num lugar só
- [ ] Valores fixos em enums
- [ ] `env()` só dentro de `config/`
- [ ] Dados de fora sempre validados e limpos
- [ ] Conversão de dinheiro (tela ↔ banco) numa classe só, com testes
- [ ] Painel administrativo com acesso restrito e testado (403 para quem não pode)
- [ ] Cores e tamanhos da marca em tokens (`@theme`), não espalhados em classes
- [ ] Contraste de texto de pelo menos 4,5:1
- [ ] Dados da empresa vindos de configuração; o que não se sabe aparece como `[A DEFINIR]`
- [ ] Controllers finos; regras repetidas em classes próprias (ex.: `ProductListing`)
- [ ] Listas com eager loading (`with`) e um teste que conta consultas
- [ ] HTML vindo de fora sanitizado **ao exibir**, com parser de HTML e testes de ataques conhecidos
- [ ] Metatags de compartilhamento no HTML do servidor

**Qualidade**
- [ ] Testes para cada regra de negócio
- [ ] `php artisan test` passando antes de cada commit
- [ ] Depois de importar ou migrar dados, conferi com consultas
- [ ] Páginas conferidas com 375 px reais de largura (nada passa da tela)
- [ ] Testes que não dependem do build do front-end (`withoutVite()`)
- [ ] Teste de busca que falharia se o defeito existisse (não só um que passa)

---

<a id="glossario"></a>
## Glossário

| Termo | Significado |
|---|---|
| **403** | Código HTTP de "proibido": a pessoa está logada, mas não tem permissão |
| **API Resource** | Classe do Laravel que define exatamente quais campos de um model vão para o navegador |
| **Artisan** | Ferramenta de linha de comando do Laravel (`php artisan ...`) |
| **COALESCE** | Função SQL que devolve o primeiro valor não nulo de uma lista |
| **Composer** | Instala pacotes PHP (lista em `composer.json`) |
| **Contraste (WCAG)** | Diferença de luminosidade entre texto e fundo; mínimo de 4,5:1 para texto normal |
| **Eager loading** | Carregar relacionamentos junto com a consulta principal (`with()`), evitando o N+1 |
| **Eloquent** | O sistema de models do Laravel |
| **Enum** | Tipo com um conjunto fixo de valores |
| **Factory** | Gerador de dados falsos para testes |
| **Favicon** | Ícone do site na aba do navegador |
| **Filament** | Pacote que gera telas de administração (listas, formulários) a partir dos models |
| **Flat (design plano)** | Estilo sem relevo, sombras ou brilho |
| **Headless** | Navegador rodando sem janela, usado para testes e screenshots |
| **Idempotente** | Pode rodar várias vezes com o mesmo resultado, sem duplicar |
| **Inertia** | Liga o Laravel ao React sem precisar de uma API separada |
| **Livewire** | Telas em PHP que se atualizam sem recarregar a página; o Filament é feito com ele |
| **Migration** | Arquivo que cria ou altera tabelas do banco |
| **Model** | Classe que representa uma tabela |
| **N+1** | Problema de desempenho: 1 consulta para a lista e mais 1 para cada item dela |
| **Open Graph** | Metatags (`og:title`, `og:image`) que definem o cartão de pré-visualização de um link |
| **Relation manager** | Tabela dentro da página de edição do Filament que gerencia um relacionamento (ex.: variações do produto) |
| **Resource (Filament)** | Conjunto de telas (listar, criar, editar) de um model no painel |
| **Route model binding** | O Laravel busca o model pelo parâmetro da rota (`{category:slug}`) e responde 404 se não achar |
| **Sanitizar** | Limpar um conteúdo deixando só o que é seguro |
| **Scope** | Filtro reutilizável de consulta (`Product::active()`) |
| **Seeder** | Script que preenche o banco com dados iniciais |
| **Shared props (Inertia)** | Dados que o servidor manda para todas as páginas (menu, carrinho) |
| **SKU** | Código interno do produto |
| **Slug** | Versão do nome usada em URLs (`piso-borracha-diamante`) |
| **Soft delete** | "Apagar" marcando a data, sem remover de verdade |
| **SVG** | Formato de imagem vetorial: nítido em qualquer tamanho, descrito em código |
| **Token de design** | Variável com uma decisão visual (cor, raio, sombra) usada em todo o site |
| **Transação** | Grupo de operações no banco que acontece por inteiro ou não acontece |
| **useState** | Função do React que guarda um valor da tela e redesenha quando ele muda |
| **Vite** | Ferramenta que compila o React e o Tailwind |
| **XSS** | Ataque que injeta código JavaScript numa página para rodar no navegador de outras pessoas |

---

---

<a id="diario"></a>
## Diário do projeto

Registro de **tudo** o que muda no projeto, na ordem em que aconteceu. Cada linha diz o que foi feito, por quê e em qual commit, e aponta para a etapa do guia que explica o assunto.

| Data | O que foi feito | Por quê | Commit | Etapa |
|---|---|---|---|---|
| 18/11/2026 | Análise do site atual: WordPress + WooCommerce + Elementor, 25 produtos, 8 categorias, venda por orçamento no WhatsApp | Decidir o escopo antes de programar | – | [0](#etapa-0) |
| 18/11/2026 | Decisão: refazer em Laravel como demo de 2 semanas, para apresentar como proposta | Liberdade de código e base para o futuro sistema de pedidos multicanal | – | [0](#etapa-0) |
| 18/11/2026 | Ambiente conferido: PHP 8.3.31, Composer 2.9.8, Node 22.19, Git 2.56 | Pré-requisitos do Laravel 13 | – | [1](#etapa-1) |
| 18/11/2026 | Pasta do projeto movida do OneDrive para `C:\dev\site_mglasto` | A sincronização trava `vendor` e `node_modules` | – | [1](#etapa-1) |
| 18/11/2026 | Projeto criado com `laravel new`: React, Fortify, Pest, SQLite, Laravel Boost | Stack definida para a demo | `b943af1` | [3](#etapa-3) |
| 18/11/2026 | `.env.example` com nome MGLasto e idioma `pt_BR` | Mensagens e dados de teste em português | `b943af1` | [3](#etapa-3) |
| 18/11/2026 | Repositório publicado em github.com/AnndOB7/site_mglasto | Versionamento e backup | `b943af1` | [2](#etapa-2) |
| 18/11/2026 | Migrations de catálogo (categorias, produtos, variações, fotos) e de pedidos multicanal | Base de dados da loja e do futuro sistema de pedidos | `93ae51d` | [5](#etapa-5) |
| 18/11/2026 | Models, enums `OrderChannel` e `OrderStatus`, factories e 7 testes | Regras de negócio num lugar só e verificadas | `93ae51d` | [6](#etapa-6), [7](#etapa-7) |
| 18/11/2026 | Coluna `attributes` da variação renomeada para `options` antes do commit | `attributes` é nome reservado nos models do Laravel | `93ae51d` | [5](#etapa-5) |
| 18/11/2026 | Comando `mglasto:importar-woocommerce` e 5 testes | Trazer o catálogo real sem digitar nada | `50cdd4d` | [8](#etapa-8) |
| 18/11/2026 | PHP configurado com o pacote de certificados CA (`cacert.pem`) no `C:\PHP\php.ini` | A primeira importação falhou com `cURL error 60`: o PHP não conseguia validar o HTTPS da loja | – (fora do repositório) | [1](#etapa-1) |
| 18/11/2026 | Primeira importação: 8 categorias, 25 produtos, 5 variações, 33 fotos | Popular o banco da demo | – | [8](#etapa-8) |
| 18/11/2026 | Correção: categoria "Oferta" buscada direto na categoria; variações sem cópia do estoque do pai | A conferência no banco mostrou 0 destaques, 2 produtos sem categoria e estoque somando 460 | `7521c05` | [8](#etapa-8) |
| 18/11/2026 | Reimportação conferida: 4 destaques, nenhum produto sem categoria, variações com estoque compartilhado | Validar a correção com os dados reais | – | [8](#etapa-8) |
| 18/11/2026 | Criados `docs/CONTEXTO-PROJETO.md` e este guia | Passar o contexto ao agente do VS Code e registrar o aprendizado | `a68fd0a` | [9](#etapa-9) |
| 18/11/2026 | Logo redesenhado em SVG plano, com 5 versões para fundos diferentes, mais o símbolo; favicon e ícone da Apple trocados | O logo antigo sumia em fundo escuro, tinha fundo cinza embutido e o 3D borrava em tamanho pequeno | `2a3ada6` | [9.1](#etapa-9-1) |
| 18/11/2026 | Paleta de cores definida a partir do site atual | Base do guia visual (tarefa 6) | `2a3ada6` | [9.1](#etapa-9-1) |
| 18/11/2026 | Commit da identidade visual no `main` e criação da branch `demo` para as tarefas 5 a 11 | O André vai revisar a demo antes de juntar no `main` | `2a3ada6` | [9](#etapa-9) |
| 18/11/2026 | Extensões `intl` e `gd` ativadas no `C:\PHP\php.ini` | O Filament exige `intl`; `gd` trata imagens | – (fora do repositório) | [10](#etapa-10) |
| 18/11/2026 | Filament 5.11.4 instalado, painel em `/admin` com cores e logo da MGLASTO; `filament:upgrade` no `post-autoload-dump` do Composer | Painel administrativo pronto sem escrever telas do zero | `996c0e6` | [10](#etapa-10) |
| 18/11/2026 | Acesso ao painel só para e-mails de `ADMIN_EMAILS` (`config/mglasto.php`, `User::canAccessPanel`) | Qualquer um pode se cadastrar no site, mas só a equipe entra no painel | `996c0e6` | [10](#etapa-10) |
| 18/11/2026 | Classe `App\Support\Money` e campo `MoneyInput`: preço digitado em reais, gravado em centavos | Um erro nessa conversão multiplicaria ou dividiria todos os preços por 100 | `996c0e6` | [10](#etapa-10) |
| 18/11/2026 | Telas de categorias, produtos (com variações e fotos) e pedidos (só o status é editável) | Cadastro do catálogo e acompanhamento de pedidos | `996c0e6` | [10](#etapa-10) |
| 18/11/2026 | Validação nova: preço promocional menor que o normal | Evita promoção mais cara que o preço cheio (decisão a revisar) | `996c0e6` | [10](#etapa-10) |
| 18/11/2026 | 32 testes novos (conversão de dinheiro, acesso ao painel, formulários de produto e pedido) | Proteger as regras de dinheiro e de acesso | `996c0e6` | [10](#etapa-10) |
| 18/11/2026 | Tokens da marca no `@theme` do Tailwind (cores, raios, sombras, alvo de toque de 44 px) e fonte Inter | Visual inteiro ajustável num lugar só, até chegarem as telas de referência | `3e47070` | [11](#etapa-11) |
| 18/11/2026 | Token `brand-ink` (`#C25400`) para texto laranja | Laranja `#FF7A00` sobre branco tem contraste 2,6:1, abaixo do mínimo de 4,5:1 | `3e47070` | [11](#etapa-11) |
| 18/11/2026 | Componentes `Button`, `ButtonLink`, `Badge`, `Price`, `ProductCard`, `Container` em `components/store/` | Peças reutilizáveis da loja, separadas das do starter kit | `3e47070` | [11](#etapa-11) |
| 18/11/2026 | `StoreLayout` (cabeçalho com busca e carrinho, menu de categorias, menu hambúrguer, rodapé grafite) e dados compartilhados `store` | Layout único para todas as páginas da loja | `3e47070` | [11](#etapa-11) |
| 18/11/2026 | Dados da empresa em `config/mglasto.php` lidos do `.env`; vazios aparecem como `[A DEFINIR]` | Não inventar telefone, CNPJ ou endereço | `3e47070` | [11](#etapa-11) |
| 18/11/2026 | Página `/guia-visual` (fora de produção) | Revisar e ajustar o visual num lugar só | `3e47070` | [11](#etapa-11) |
| 18/11/2026 | Correção: botões e textos longos estouravam a tela do celular | `whitespace-nowrap` e palavras sem quebra; conferido com 375 px reais | `3e47070` | [11](#etapa-11) |
| 18/11/2026 | `public/**` ignorado pelo formatador (`vite.config.ts`) | Os arquivos do Filament em `public/` são de terceiros | `3e47070` | [11](#etapa-11) |
| 18/11/2026 | Problema: `npm run check:fix` reformatou os `.md` de `docs/` (tabelas alinhadas, `*itálico*` virou `_itálico_`); arquivos restaurados com `git checkout -- docs/` e `docs/**` ignorado pelo formatador | O formatador do projeto também formata Markdown; documentos escritos à mão não devem ser reescritos por ferramenta | `3e47070` | [11](#etapa-11) |
| 18/11/2026 | Rotas da loja: `/` (home), `/categoria/{slug}`, `/busca`, `/produto/{slug}` (provisória); página `welcome` do starter kit removida | A loja substitui a página de boas-vindas do Laravel | `dd23063` | [12](#etapa-12) |
| 18/11/2026 | Home com categorias, vitrine "Ofertas" (`is_featured`) e "Mais produtos" sem repetição | Regra 7 do contexto e defeito do site antigo (produtos repetidos) | `dd23063` | [12](#etapa-12) |
| 18/11/2026 | Página de categoria (inclui subcategorias) e busca por nome, SKU e SKU de variação, com ordenação por nome e por preço pago | Encontrar produtos por categoria ou código | `dd23063` | [12](#etapa-12) |
| 18/11/2026 | `ProductListing` e `ProductCardResource` | Regras das listas e formato do card num lugar só; controllers finos | `dd23063` | [12](#etapa-12) |
| 18/11/2026 | Eager loading da primeira foto e teste que conta consultas (3 vs. 30 produtos) | Evitar o problema N+1 | `dd23063` | [12](#etapa-12) |
| 18/11/2026 | `withoutVite()` no `TestCase` | Os testes dependiam de `npm run build` ter sido rodado | `dd23063` | [12](#etapa-12) |
| 18/11/2026 | Busca remove `%`, `_` e `\` do termo | No SQLite o `LIKE` não tem escape padrão; esses caracteres funcionariam como curinga | `dd23063` | [12](#etapa-12) |
| 18/11/2026 | Tipos do PHPDoc corrigidos e dados da empresa validados ao ler do `config()` | PHPStan nível 7 sem erros no código novo | `dd23063` | [12](#etapa-12) |
| 18/11/2026 | Página `/produto/{slug}`: galeria, preço com promoção riscada, disponibilidade, seletor de variações, quantidade, descrição e relacionados | Tarefa 8 da demo | *(próximo commit)* | [13](#etapa-13) |
| 18/11/2026 | `ProductVariant::isInStock()` (estoque próprio ou compartilhado com o produto) | Regra 3 do contexto num lugar só, para a página e o carrinho | *(próximo commit)* | [13](#etapa-13) |
| 18/11/2026 | `HtmlSanitizer` com `DOMDocument`: descrição sanitizada ao exibir | Regra 8 do contexto; proteção contra XSS | *(próximo commit)* | [13](#etapa-13) |
| 18/11/2026 | Metatags Open Graph e `canonical` no HTML do servidor (`withViewData('meta')`) | Pré-visualização do link no WhatsApp e no Google, que não executam JavaScript | *(próximo commit)* | [13](#etapa-13) |
| 18/11/2026 | `WHATSAPP_NUMBER` (vazio no `.env.example`) e classe `WhatsApp`; sem número, a página mostra `[A DEFINIR]` | Não inventar número de contato | *(próximo commit)* | [13](#etapa-13) |
| 18/11/2026 | Correção: página de produto passava 8 px da tela no celular (`grid-cols-1`) | Coluna implícita da grade crescia com as miniaturas | *(próximo commit)* | [13](#etapa-13) |
| 18/11/2026 | Trilha de navegação prefere categoria real a "Oferta"; opção de valor único já vem marcada | Usabilidade | *(próximo commit)* | [13](#etapa-13) |

---

*Próximas seções a serem adicionadas conforme o projeto avança: painel administrativo (Filament), layout e Tailwind, páginas da loja em React, carrinho e orçamento via WhatsApp, publicação da demo.*
