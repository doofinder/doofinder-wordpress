<?php

namespace Doofinder\WP;

class Helpers {

    /**
     * Returns `true` if we are currently in debug mode, `false` otherwise.
     *
     * @return bool
     */
    public static function is_debug_mode() {
        return WP_DEBUG && ! Settings::get_disable_debug_mode();
    }
}
