<?php

declare(strict_types=1);

/** @var array{groups:list<array<string, mixed>>, direct:list<array{installation_id:int, miscno2:string, in_service:bool}>} $access */

if (!isset($access)) {
    return;
}

abas_render_vc_anlaegsbruger_installation_access($access);
