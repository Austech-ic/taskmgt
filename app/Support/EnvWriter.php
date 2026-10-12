<?php

namespace App\Support;

use RuntimeException;

class EnvWriter
{
    public static function set(array $values, ?string $path = null): void
    {
        $path ??= base_path('.env');
        if (! file_exists($path)) {
            copy(base_path('.env.example'), $path);
        }
        if (! is_writable($path)) {
            throw new RuntimeException('The .env file is not writable. Set its permissions to 664 and retry.');
        }
        $contents = file_get_contents($path);

        foreach ($values as $key => $value) {
            $value = (string) $value;
            if ($value === '' || preg_match('/[\s#"\'$\\\\=]/', $value)) {
                $value = '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
            }
            $line = $key.'='.$value;
            if (preg_match("/^{$key}=.*$/m", $contents)) {
                $contents = preg_replace("/^{$key}=.*$/m", str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $contents);
            } else {
                $contents = rtrim($contents)."\n".$line."\n";
            }
        }

        file_put_contents($path, $contents);
    }
}
