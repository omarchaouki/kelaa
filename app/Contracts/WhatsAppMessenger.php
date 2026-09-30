<?php

namespace App\Contracts;

/**
 * WhatsApp Business API provider (to be chosen). Messages outside a
 * customer-initiated window must use pre-approved templates.
 */
interface WhatsAppMessenger
{
    /**
     * @param  list<string>  $parameters
     */
    public function sendTemplate(string $phone, string $template, array $parameters): void;
}
