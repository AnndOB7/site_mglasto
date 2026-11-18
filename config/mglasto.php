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

    /*
     * Número do WhatsApp que recebe os orçamentos, com DDI e DDD.
     * Ex.: WHATSAPP_NUMBER=5511999999999. Vazio = a loja mostra "[A DEFINIR]".
     */
    'whatsapp_number' => env('WHATSAPP_NUMBER'),

    /*
     * Dados da empresa exibidos na loja. Vazio = a loja mostra "[A DEFINIR]".
     * Não invente esses dados: confirme com a MGLASTO antes de preencher.
     */
    'company' => [
        'name' => env('COMPANY_NAME', 'MGLASTO'),
        'phone' => env('COMPANY_PHONE') ?: null,
        'email' => env('COMPANY_EMAIL') ?: null,
        'address' => env('COMPANY_ADDRESS') ?: null,
        'document' => env('COMPANY_CNPJ') ?: null,
    ],

];
