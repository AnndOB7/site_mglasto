<?php

/*
|--------------------------------------------------------------------------
| Configurações da loja MGLASTO
|--------------------------------------------------------------------------
|
| Tudo o que é específico da empresa fica aqui, lido do .env. No resto do
| código use config('mglasto.chave'), nunca env() diretamente.
|
*/

return [

    /*
     * E-mails que podem entrar no painel /admin, separados por vírgula.
     * Ex.: ADMIN_EMAILS=fulano@mglasto.com.br,ciclano@mglasto.com.br
     */
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

];
