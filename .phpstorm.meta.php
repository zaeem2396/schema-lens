<?php
/**
 * IDE Helper for Laravel Classes
 * This file helps IDEs recognize Laravel framework classes
 */

namespace PHPSTORM_META {
    override(
        \Illuminate\Console\Command::class,
        map([
            '' => '@',
        ])
    );
}

