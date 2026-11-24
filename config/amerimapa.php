<?php

return [
    /*
    | Chave compartilhada com o ESP32 (header X-Device-Key). Troque em produção.
    */
    'device_key' => env('DEVICE_API_KEY', 'troque-esta-chave-do-esp32'),

    /*
    | Segundos sem heartbeat até o mapa ser considerado desconectado (RN20).
    */
    'hardware_timeout_seconds' => (int) env('HARDWARE_TIMEOUT_SECONDS', 15),

    /*
    | Mapa padrão usado pelas telas quando nenhum outro foi escolhido.
    */
    'default_device_id' => env('DEFAULT_DEVICE_ID', 'map-01'),

    /*
    | Validade dos tokens de acesso da API (dias).
    */
    'token_ttl_days' => (int) env('API_TOKEN_TTL_DAYS', 7),

    /*
    | Conta de professor/admin criada pelo seeder.
    */
    'seed_admin_email' => env('SEED_ADMIN_EMAIL', 'admin@amerimapa.com'),
    'seed_admin_password' => env('SEED_ADMIN_PASSWORD', 'amerimapa123'),
];
