<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class PublishIndexNowKeyFile extends Command
{
    protected $signature = 'indexnow:publish-key-file';

    protected $description = 'Write the configured IndexNow verification key into this release public directory.';

    public function handle(): int
    {
        if (! app()->environment('production')) {
            $this->error('The IndexNow verification file can only be generated for a production release.');

            return self::FAILURE;
        }

        $key = (string) config('indexnow.key');

        if (! preg_match('/^[A-Za-z0-9-]{8,128}$/', $key)) {
            $this->error('The configured IndexNow key does not use a supported format.');

            return self::FAILURE;
        }

        $publicDirectory = public_path();
        $path = $publicDirectory.DIRECTORY_SEPARATOR.$key.'.txt';

        if (! is_dir($publicDirectory) || is_link($path) || (file_exists($path) && ! is_file($path))) {
            $this->error('The release public directory or IndexNow key file path is not safe to write.');

            return self::FAILURE;
        }

        try {
            if (file_put_contents($path, $key, LOCK_EX) === false || ! chmod($path, 0644) || file_get_contents($path) !== $key) {
                $this->error('Could not write and verify the exact IndexNow key file in this release.');

                return self::FAILURE;
            }
        } catch (Throwable) {
            $this->error('Could not write and verify the exact IndexNow key file in this release.');

            return self::FAILURE;
        }

        $this->info('Published IndexNow verification file in the release public directory.');

        return self::SUCCESS;
    }
}
