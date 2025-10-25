<?php

namespace Tacman\CastorTools;

// at the moment, we don't use any castor functions, but we will later
use Castor\Import\Remote\ComposerApplication;
use http\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;

use function Castor\context;
use function Castor\fingerprint;
use function Castor\hasher;
use function Castor\output;
use function Castor\run_php;


/**
 * Ensure a key exists in an .env file with the specified value.
 * Creates the file if it doesn't exist.
 * Updates the value if the key exists, or adds it if it doesn't.
 *
 * @param string $filePath Path to the .env file
 * @param string $key The environment variable key
 * @param string $value The value to set
 * @return bool Success status
 */
function ensure_env(string $filePath, string $key, string $value): bool
{
    // Create file if it doesn't exist
    if (!file_exists($filePath)) {
        file_put_contents($filePath, '');
    }

    $content = file_get_contents($filePath);

    // Escape special characters in the key for regex
    $escapedKey = preg_quote($key, '/');

    // Quote the value if it contains spaces or special characters
    if (preg_match('/[\s#]/', $value)) {
        $value = '"' . addslashes($value) . '"';
    }

    $replacement = "{$key}={$value}";

    // Pattern matches: KEY=value or KEY="value" (not commented)
    $pattern = "/^{$escapedKey}=.*$/m";

    if (preg_match($pattern, $content)) {
        // Update existing key
        $content = preg_replace($pattern, $replacement, $content);
    } else {
        // Add new key (ensure file ends with newline first)
        $content = rtrim($content);
        if (!empty($content)) {
            $content .= "\n";
        }
        $content .= "{$replacement}\n";
    }

    return file_put_contents($filePath, $content) !== false;
}

/**
 * Remove or comment out a key from an .env file.
 *
 * @param string $filePath Path to the .env file
 * @param string $key The environment variable key
 * @param bool $comment If true, comments out the line; if false, deletes it entirely
 * @return bool Success status (false if file doesn't exist or key not found)
 */
function remove_env(string $filePath, string $key, bool $comment = false): bool
{
    if (!file_exists($filePath)) {
        return false;
    }

    $content = file_get_contents($filePath);
    $escapedKey = preg_quote($key, '/');

    // Pattern matches: KEY=value or KEY="value" (not already commented)
    $pattern = "/^{$escapedKey}=.*$/m";

    if (!preg_match($pattern, $content)) {
        return false; // Key not found
    }

    if ($comment) {
        // Comment out the line
        $content = preg_replace($pattern, '# $0', $content);
    } else {
        // Delete the entire line including the newline
        $content = preg_replace("/^{$escapedKey}=.*\n?/m", '', $content);
    }

    return file_put_contents($filePath, $content) !== false;
}
