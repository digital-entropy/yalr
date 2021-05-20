<?php

namespace Dentro\Yalr\Console;

use Illuminate\Console\Command;

class DisplayCommnad extends Command
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
                    $class, $group,
                ];
            }
        }

        $this->table(['Route Class', 'Group'], $rows);

        return 0;
    }
}
