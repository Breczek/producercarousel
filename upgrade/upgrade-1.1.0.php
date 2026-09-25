<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Adds the slider mode, navigation style and dimension settings introduced in 1.1.0.
 */
function upgrade_module_1_1_0($module)
{
    return $module->installDefaults();
}
