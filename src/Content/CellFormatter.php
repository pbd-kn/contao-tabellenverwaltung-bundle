<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\Content;
final class CellFormatter
{
    private string $root;
    public function __construct(string $root) { $this->root = $root; }
    public function render(string $key, string $value): string { return self::format($key, $value, $this->root); }
    public static function format(string $key, string $value, string $root): string
    {
        $escape = static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $key = strtoupper($key);
        $value = trim($value);
        if ($value === '') { return ''; }
        if ($key === 'LOGO' && str_starts_with($value, 'files/') && !str_contains($value, '\\')) {
            $path = realpath($root.'/'.$value);
            $files = realpath($root.'/files');
            if ($path && $files && str_starts_with(str_replace('\\', '/', $path), str_replace('\\', '/', $files).'/') && is_file($path) && @getimagesize($path)) {
                $url = implode('/', array_map('rawurlencode', explode('/', $value)));
                return '<img class="directory-logo" src="'.$escape($url).'" alt="Firmenlogo" loading="lazy" style="max-width:120px;max-height:80px;width:auto;height:auto">';
            }
        }
        if (preg_match('/^INTERNET\d*$/D', $key)) {
            $url = preg_match('~^www\.~i', $value) ? 'https://'.$value : $value;
            if (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http','https'], true)) {
                return '<a href="'.$escape($url).'">'.$escape($value).'</a>';
            }
        }
        if (preg_match('/^EMAIL\d*$/D', $key) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return '<a href="mailto:'.$escape($value).'">'.$escape($value).'</a>';
        }
        if (preg_match('/^(TELEFON|MOBIL)\d*$/D', $key)) {
            $number = preg_replace('/[^0-9+]/', '', $value);
            if (preg_match('/^\+?[0-9]{3,}$/D', $number)) {
                return '<a href="tel:'.$escape($number).'">'.$escape($value).'</a>';
            }
        }
        return nl2br($escape($value));
    }
}
