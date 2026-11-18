<?php

namespace App\Console\Commands;

use App\Actions\Catalog\ImportWooCommerceCatalog;
use Illuminate\Console\Command;
use Throwable;

class ImportWooCommerce extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mglasto:importar-woocommerce
                            {--url= : Endereço da loja WooCommerce (padrão: WOOCOMMERCE_STORE_URL)}
                            {--sem-imagens : Não baixa as fotos dos produtos}';

    /**
     * @var string
     */
    protected $description = 'Importa categorias, produtos, variações e fotos da loja WooCommerce atual';

    public function handle(ImportWooCommerceCatalog $import): int
    {
        $url = $this->option('url') ?: config('services.woocommerce.store_url');

        $this->info("Importando de {$url}...");

        try {
            $stats = $import->handle($url, withImages: ! $this->option('sem-imagens'));
        } catch (Throwable $e) {
            $this->error('A importação falhou: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Item', 'Quantidade'], [
            ['Categorias', $stats['categories']],
            ['Produtos', $stats['products']],
            ['Variações', $stats['variants']],
            ['Fotos baixadas agora', $stats['images']],
        ]);

        return self::SUCCESS;
    }
}
