<?php

namespace Dentro\Yalr\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class DisplayCommand extends Command
{
    protected $signature = 'yalr:display';

    protected $description = 'Show Registered YALR classes.';

    public function handle(): int
    {
        $routes = app('yalr')->all();
        $rows = [];

        foreach ($routes as $group => $classes) {
            foreach ($classes as $class) {
                $rows[] = [
                    $class,
                    $group,
                ];
            }
        }

        if (method_exists($this->laravel, 'version') &&
            version_compare($this->laravel->version(), '8.0.0', '>=') &&
            property_exists($this, 'components') &&
            method_exists($this->components, 'twoColumnDetail')) {

            $this->components->twoColumnDetail('<fg=green;options=bold>Route Class</>', '<fg=green;options=bold>Group</>');

            foreach ($rows as $row) {
                $this->components->twoColumnDetail($row[0], $row[1]);
            }

            $this->newLine();
        } else {
            // Fallback to the traditional table for older Laravel versions
            $this->table(['Route Class', 'Group'], $rows);
        }

        return 0;
    }
}
