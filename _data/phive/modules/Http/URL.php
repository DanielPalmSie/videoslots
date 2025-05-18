<?php

/**
 * Class URL
 *
 * A simple class to handle url modifications
 */
class URL extends PhModule {

    public const MOBILE_DIR_PART = '/mobile';

    /**
     * Check if we can find a hostname name in the url provided
     * Note: This is a relatively soft check and should not be used with very complex urls
     *
     * @param string $url
     * @return bool
     */
    public function isRelative(string $url): bool
    {
        return !parse_url($url, PHP_URL_HOST);
    }

    /**
     * Add a directory path to a URL - Modifies url in place
     *
     * @param string $url The base URL to modify
     * @param string $prepend If specified this will be added to the beginning of the path
     * @param string $append If specified this will be added to the end of the path
     * @param bool $with_trailing_slash Include / Remove trailing slash from path
     * @return string The modified url if successful or original url on failure
     */
    public function addDirPart(string $url, string $prepend = '', string $append = '', bool $with_trailing_slash = false): string
    {
        $prepend = trim($prepend, '/');
        $append = trim($append, '/');

        $parts = parse_url($url);
        if (is_array($parts) && !empty($parts) && ($prepend || $append)) {
            $path       = trim($parts['path'], '/');
            $final_path = ($prepend) ? $prepend . '/'. $path : $path . '/' . $append;

            // Return an absolute path if that was provided, else relative
            if (!$this->isRelative($url)) {
                $base = $parts['scheme'] . '://' . $parts['host'];
                if (!empty($parts['port'])) $base .= ':' . $parts['port'];
            } else {
                $base = '/';
            }

            // Include a trailing slash if needed
            $updated_url = rtrim($base . $final_path, '/');
            if ($with_trailing_slash) $updated_url .= '/';

            // Add the query string and/or fragment if present
            if (!empty($parts['query']))    $updated_url .= '?' . $parts['query'];
            if (!empty($parts['fragment'])) $updated_url .= '#' . $parts['fragment'];

            return $updated_url;
        }

        // Failed parsing the url or $prepend & $append where not provided; return original
        return $url;
    }

    /**
     * If the user is currently on a mobile device and the $url provided is a desktop url
     * automatically transform it to a mobile url, else leave it untouched
     *
     * @param string $url
     * @param string $mobile_dir_part
     * @return string
     */
    public function prependMobileDirPart(string $url, string $mobile_dir_part = self::MOBILE_DIR_PART): string
    {
        return (phive()->isMobile() && !str_contains($url, $mobile_dir_part))
            ? $this->addDirPart($url, $mobile_dir_part, '', true)
            : $url;
    }
}
