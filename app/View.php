<?php
/**
 * Warstwa widoku - renderowanie szablonów z układem (layout).
 */

declare(strict_types=1);

final class View
{
    /** @var array<string,mixed> dane przekazywane do szablonu */
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * Renderuje widok w layoutcie.
     *
     * @param array<string,mixed> $data
     * @param string $layout 'default' (sidebar + topbar) albo 'none' (samodzielna strona)
     */
    public static function render(string $view, array $data = [], string $layout = 'default'): void
    {
        $file = VIEW_PATH . '/' . $view . '.php';
        if (!is_file($file)) {
            http_response_code(404);
            echo 'Brak widoku: ' . e($view);
            return;
        }

        $data = array_merge(self::$shared, $data);
        $data['flash'] = flash_pull();

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = (string) ob_get_clean();

        if ($layout === 'none') {
            echo $content;
            return;
        }

        require VIEW_PATH . '/layout.php';
    }
}
