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
10. [Checklist para reaproveitar em outros projetos](#checklist)
11. [Glossário](#glossario)
12. [Diário do projeto](#diario)

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

**Qualidade**
- [ ] Testes para cada regra de negócio
- [ ] `php artisan test` passando antes de cada commit
- [ ] Depois de importar ou migrar dados, conferi com consultas

---

<a id="glossario"></a>
## Glossário

| Termo | Significado |
|---|---|
| **Artisan** | Ferramenta de linha de comando do Laravel (`php artisan ...`) |
| **Composer** | Instala pacotes PHP (lista em `composer.json`) |
| **Eloquent** | O sistema de models do Laravel |
| **Enum** | Tipo com um conjunto fixo de valores |
| **Factory** | Gerador de dados falsos para testes |
| **Favicon** | Ícone do site na aba do navegador |
| **Flat (design plano)** | Estilo sem relevo, sombras ou brilho |
| **Idempotente** | Pode rodar várias vezes com o mesmo resultado, sem duplicar |
| **Inertia** | Liga o Laravel ao React sem precisar de uma API separada |
| **Migration** | Arquivo que cria ou altera tabelas do banco |
| **Model** | Classe que representa uma tabela |
| **Scope** | Filtro reutilizável de consulta (`Product::active()`) |
| **Seeder** | Script que preenche o banco com dados iniciais |
| **SVG** | Formato de imagem vetorial: nítido em qualquer tamanho, descrito em código |
| **SKU** | Código interno do produto |
| **Slug** | Versão do nome usada em URLs (`piso-borracha-diamante`) |
| **Soft delete** | "Apagar" marcando a data, sem remover de verdade |
| **Transação** | Grupo de operações no banco que acontece por inteiro ou não acontece |
| **Vite** | Ferramenta que compila o React e o Tailwind |

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
| 18/11/2026 | Logo redesenhado em SVG plano, com 5 versões para fundos diferentes, mais o símbolo; favicon e ícone da Apple trocados | O logo antigo sumia em fundo escuro, tinha fundo cinza embutido e o 3D borrava em tamanho pequeno | *(próximo commit)* | [9.1](#etapa-9-1) |
| 18/11/2026 | Paleta de cores definida a partir do site atual | Base do guia visual (tarefa 6) | *(próximo commit)* | [9.1](#etapa-9-1) |

---

*Próximas seções a serem adicionadas conforme o projeto avança: painel administrativo (Filament), layout e Tailwind, páginas da loja em React, carrinho e orçamento via WhatsApp, publicação da demo.*
