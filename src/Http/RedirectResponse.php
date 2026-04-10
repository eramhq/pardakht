<?php

declare(strict_types=1);

namespace EramDev\Pardakht\Http;

/**
 * Represents the response from a purchase request — either a redirect URL
 * or an auto-submitting HTML form (common for SOAP-based bank gateways).
 */
final class RedirectResponse
{
    /**
     * @param array<string, string> $formData POST form fields for auto-submit forms.
     */
    private function __construct(
        private string $url,
        private string $method,
        private string $referenceId,
        private array $formData = [],
    ) {
    }

    /**
     * Create a GET redirect response (used by REST gateways like Zarinpal).
     */
    public static function redirect(string $url, string $referenceId): self
    {
        return new self($url, 'GET', $referenceId);
    }

    /**
     * Create a POST form redirect response (used by SOAP bank gateways like Mellat).
     *
     * @param array<string, string> $formData
     */
    public static function post(string $url, string $referenceId, array $formData = []): self
    {
        return new self($url, 'POST', $referenceId, $formData);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    /**
     * @return array<string, string>
     */
    public function getFormData(): array
    {
        return $this->formData;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /**
     * Generate an auto-submitting HTML form for POST redirects.
     */
    public function renderAutoSubmitForm(string $submitText = 'در حال انتقال به درگاه...'): string
    {
        if (!$this->isPost()) {
            return \sprintf(
                '<meta http-equiv="refresh" content="0;url=%s">',
                \htmlspecialchars($this->url, ENT_QUOTES, 'UTF-8'),
            );
        }

        $fields = '';
        foreach ($this->formData as $name => $value) {
            $fields .= \sprintf(
                '<input type="hidden" name="%s" value="%s">',
                \htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                \htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            );
        }

        $escapedUrl = \htmlspecialchars($this->url, ENT_QUOTES, 'UTF-8');
        $escapedSubmit = \htmlspecialchars($submitText, ENT_QUOTES, 'UTF-8');

        return <<<HTML
            <html dir="rtl">
            <body>
                <form id="pardakht-form" method="POST" action="{$escapedUrl}">
                    {$fields}
                    <noscript><button type="submit">{$escapedSubmit}</button></noscript>
                </form>
                <script>document.getElementById('pardakht-form').submit();</script>
            </body>
            </html>
            HTML;
    }
}
