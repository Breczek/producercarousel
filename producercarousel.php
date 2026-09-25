<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class ProducerCarousel extends Module implements WidgetInterface
{
    private const MFR_PREFIX = 'PC_MFR_';
    private const MFR_EXCLUDED = 'PC_MFR_EXCLUDED';
    private const SUP_PREFIX = 'PC_SUP_';
    private const SUP_EXCLUDED = 'PC_SUP_EXCLUDED';
    private const DISPLAY_MODE = 'PC_DISPLAY_MODE';

    private const DISPLAY_MODES = ['all', 'manufacturers', 'suppliers'];

    /**
     * Per-carousel settings, stored as prefix + key (e.g. PC_MFR_COUNT).
     * "choices" settings accept only listed values, "min"/"max" settings accept
     * an integer from the range; "auto" additionally allows 0 as "automatic".
     */
    private const CAROUSEL_SETTINGS = [
        'COUNT' => ['choices' => [2, 3, 4, 5, 6, 8], 'default' => 6],
        'MODE' => ['choices' => ['slide', 'loop', 'marquee'], 'default' => 'loop'],
        'SPEED' => ['choices' => [0, 2000, 3000, 4000, 5000, 7000, 10000], 'default' => 4000],
        'ARROWS' => ['choices' => ['none', 'minimal', 'circle', 'square'], 'default' => 'minimal'],
        'DOTS' => ['choices' => ['none', 'dots', 'lines', 'dynamic'], 'default' => 'none'],
        'WIDTH' => ['min' => 40, 'max' => 600, 'auto' => true, 'default' => 0],
        'HEIGHT' => ['min' => 30, 'max' => 400, 'auto' => true, 'default' => 0],
        'GAP' => ['min' => 0, 'max' => 100, 'default' => 16],
    ];

    public function __construct()
    {
        $this->name = 'producercarousel';
        $this->tab = 'front_office_features';
        $this->version = '1.1.1';
        $this->author = 'Marcin Bręczewski';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '9.99.99'];

        parent::__construct();

        $this->displayName = $this->trans('Brand & Supplier Carousel', [], 'Modules.Producercarousel.Admin');
        $this->description = $this->trans('Displays manufacturers and suppliers in two independent logo carousels.', [], 'Modules.Producercarousel.Admin');
    }

    /**
     * Source strings are English; bundled catalogues live in translations/<locale>/.
     */
    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHome')
            && $this->registerHook('displayHeader')
            && $this->installDefaults();
    }

    /**
     * Also used by upgrade scripts: only missing keys are created, so saved values stay untouched.
     */
    public function installDefaults()
    {
        $defaults = [
            self::MFR_EXCLUDED => '[]',
            self::SUP_EXCLUDED => '[]',
            self::DISPLAY_MODE => 'all',
        ];
        foreach ([self::MFR_PREFIX, self::SUP_PREFIX] as $prefix) {
            foreach (self::CAROUSEL_SETTINGS as $key => $definition) {
                $defaults[$prefix . $key] = $definition['default'];
            }
        }

        $result = true;
        foreach ($defaults as $key => $value) {
            if (Configuration::get($key) === false) {
                $result = Configuration::updateValue($key, $value) && $result;
            }
        }

        return $result;
    }

    public function uninstall()
    {
        foreach ([self::MFR_EXCLUDED, self::SUP_EXCLUDED, self::DISPLAY_MODE] as $key) {
            Configuration::deleteByName($key);
        }
        foreach ([self::MFR_PREFIX, self::SUP_PREFIX] as $prefix) {
            foreach (array_keys(self::CAROUSEL_SETTINGS) as $key) {
                Configuration::deleteByName($prefix . $key);
            }
        }

        return parent::uninstall();
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerStylesheet(
            'module-' . $this->name . '-swiper',
            'modules/' . $this->name . '/views/vendor/swiper/swiper-bundle.min.css',
            ['media' => 'all', 'priority' => 150]
        );
        $this->context->controller->registerStylesheet(
            'module-' . $this->name . '-style',
            'modules/' . $this->name . '/views/css/front.css',
            ['media' => 'all', 'priority' => 151]
        );
        $this->context->controller->registerJavascript(
            'module-' . $this->name . '-swiper',
            'modules/' . $this->name . '/views/vendor/swiper/swiper-bundle.min.js',
            ['position' => 'bottom', 'priority' => 150]
        );
        $this->context->controller->registerJavascript(
            'module-' . $this->name . '-front',
            'modules/' . $this->name . '/views/js/front.js',
            ['position' => 'bottom', 'priority' => 151]
        );
    }

    public function hookDisplayHome($params)
    {
        if (!isset($params['type'])) {
            $params['type'] = $this->getDisplayMode();
        }

        return $this->renderWidget('displayHome', $params);
    }

    public function renderWidget($hookName, array $configuration)
    {
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));

        return $this->fetch('module:' . $this->name . '/views/templates/hook/carousels.tpl');
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $type = isset($configuration['type']) ? (string) $configuration['type'] : 'all';
        if (!in_array($type, self::DISPLAY_MODES, true)) {
            $type = 'all';
        }

        $carousels = [];
        $instancePrefix = $this->name . '-' . substr(md5(uniqid('', true)), 0, 10);
        if ($type === 'all' || $type === 'manufacturers') {
            $carousels[] = [
                'id' => $instancePrefix . '-manufacturers',
                'type' => 'manufacturers',
                'title' => $this->trans('Brands', [], 'Modules.Producercarousel.Shop'),
                'items' => $this->getManufacturerItems(),
                'settings' => $this->getCarouselSettings(self::MFR_PREFIX),
            ];
        }
        if ($type === 'all' || $type === 'suppliers') {
            $carousels[] = [
                'id' => $instancePrefix . '-suppliers',
                'type' => 'suppliers',
                'title' => $this->trans('Suppliers', [], 'Modules.Producercarousel.Shop'),
                'items' => $this->getSupplierItems(),
                'settings' => $this->getCarouselSettings(self::SUP_PREFIX),
            ];
        }

        return ['producer_carousels' => $carousels];
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitProducerCarousel')) {
            $errors = $this->saveConfiguration();
            if ($errors) {
                $output .= $this->displayError($errors);
            } else {
                $output .= $this->displayConfirmation($this->trans('The settings have been updated.', [], 'Admin.Notifications.Success'));
            }
        }

        return $output . $this->renderConfigurationInfo() . $this->renderForm();
    }

    private function saveConfiguration()
    {
        $errors = [];
        $values = [];
        foreach ([self::MFR_PREFIX, self::SUP_PREFIX] as $prefix) {
            foreach (self::CAROUSEL_SETTINGS as $key => $definition) {
                $value = $this->normalizeSetting($definition, trim((string) Tools::getValue($prefix . $key)));
                if ($value === null) {
                    $errors[] = $this->getInvalidValueMessage($definition);
                } else {
                    $values[$prefix . $key] = $value;
                }
            }
        }

        $displayMode = (string) Tools::getValue(self::DISPLAY_MODE);
        if (!in_array($displayMode, self::DISPLAY_MODES, true)) {
            $errors[] = $this->trans('An invalid value was selected.', [], 'Modules.Producercarousel.Admin');
        }

        if ($errors) {
            return array_values(array_unique($errors));
        }

        Configuration::updateValue(self::DISPLAY_MODE, $displayMode);

        foreach ($values as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        Configuration::updateValue(self::MFR_EXCLUDED, json_encode($this->collectExcludedIds(self::MFR_PREFIX, Manufacturer::getManufacturers(false, (int) $this->context->language->id, false))));
        Configuration::updateValue(self::SUP_EXCLUDED, json_encode($this->collectExcludedIds(self::SUP_PREFIX, Supplier::getSuppliers(false, (int) $this->context->language->id, false))));

        return $errors;
    }

    /**
     * Returns the value cast to its setting type, or null when it is not allowed.
     */
    private function normalizeSetting(array $definition, $raw)
    {
        if (isset($definition['choices'])) {
            if (is_int($definition['default']) && !ctype_digit((string) $raw)) {
                return null;
            }
            $value = is_int($definition['default']) ? (int) $raw : (string) $raw;

            return in_array($value, $definition['choices'], true) ? $value : null;
        }

        if ($raw === '' && !empty($definition['auto'])) {
            return 0;
        }
        if (!ctype_digit((string) $raw)) {
            return null;
        }
        $value = (int) $raw;
        if ($value === 0 && !empty($definition['auto'])) {
            return 0;
        }

        return $value >= $definition['min'] && $value <= $definition['max'] ? $value : null;
    }

    private function getInvalidValueMessage(array $definition)
    {
        if (isset($definition['choices'])) {
            return $this->trans('An invalid value was selected.', [], 'Modules.Producercarousel.Admin');
        }

        return $this->trans(
            'Dimensions must be a whole number between %min% and %max% px (0 = automatic where allowed).',
            ['%min%' => $definition['min'], '%max%' => $definition['max']],
            'Modules.Producercarousel.Admin'
        );
    }

    private function collectExcludedIds($prefix, array $entities)
    {
        $excluded = [];
        foreach ($entities as $entity) {
            $id = isset($entity['id_manufacturer']) ? (int) $entity['id_manufacturer'] : (int) $entity['id_supplier'];
            if (!(bool) Tools::getValue($prefix . $id)) {
                $excluded[] = $id;
            }
        }

        return $excluded;
    }

    private function renderForm()
    {
        $manufacturers = Manufacturer::getManufacturers(false, (int) $this->context->language->id, false);
        $suppliers = Supplier::getSuppliers(false, (int) $this->context->language->id, false);
        $displayModeOptions = [
            ['id' => 'all', 'name' => $this->trans('Both carousels', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'manufacturers', 'name' => $this->trans('Manufacturers only', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'suppliers', 'name' => $this->trans('Suppliers only', [], 'Modules.Producercarousel.Admin')],
        ];
        $submit = ['title' => $this->trans('Save', [], 'Admin.Actions')];

        $fieldsForm = [
            [
                'form' => [
                    'legend' => ['title' => $this->trans('General settings', [], 'Modules.Producercarousel.Admin'), 'icon' => 'icon-cogs'],
                    'input' => [
                        $this->selectField(self::DISPLAY_MODE, $this->trans('What to display in the main hook (displayHome)', [], 'Modules.Producercarousel.Admin'), $displayModeOptions),
                    ],
                    'submit' => $submit,
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => $this->trans('Manufacturer carousel', [], 'Modules.Producercarousel.Admin'), 'icon' => 'icon-industry'],
                    'input' => array_merge(
                        $this->carouselFields(self::MFR_PREFIX),
                        [$this->checkboxField('PC_MFR', $this->trans('Visible manufacturers', [], 'Modules.Producercarousel.Admin'), $manufacturers, 'id_manufacturer')]
                    ),
                    'submit' => $submit,
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => $this->trans('Supplier carousel', [], 'Modules.Producercarousel.Admin'), 'icon' => 'icon-truck'],
                    'input' => array_merge(
                        $this->carouselFields(self::SUP_PREFIX),
                        [$this->checkboxField('PC_SUP', $this->trans('Visible suppliers', [], 'Modules.Producercarousel.Admin'), $suppliers, 'id_supplier')]
                    ),
                    'submit' => $submit,
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitProducerCarousel';
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->fields_value = $this->getFormValues($manufacturers, $suppliers);

        return $helper->generateForm($fieldsForm);
    }

    private function carouselFields($prefix)
    {
        $countOptions = array_map(function ($value) {
            return ['id' => $value, 'name' => (string) $value];
        }, self::CAROUSEL_SETTINGS['COUNT']['choices']);
        $speedOptions = array_map(function ($value) {
            return ['id' => $value, 'name' => $value === 0 ? $this->trans('Disabled', [], 'Admin.Global') : $value . ' ms'];
        }, self::CAROUSEL_SETTINGS['SPEED']['choices']);
        $modeOptions = [
            ['id' => 'slide', 'name' => $this->trans('Standard (stops at the end, autoplay rewinds to the start)', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'loop', 'name' => $this->trans('Infinite loop', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'marquee', 'name' => $this->trans('Continuous scroll (infinite ticker)', [], 'Modules.Producercarousel.Admin')],
        ];
        $arrowOptions = [
            ['id' => 'none', 'name' => $this->trans('None', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'minimal', 'name' => $this->trans('Minimal (chevron only)', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'circle', 'name' => $this->trans('Filled circle', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'square', 'name' => $this->trans('Outlined square', [], 'Modules.Producercarousel.Admin')],
        ];
        $dotOptions = [
            ['id' => 'none', 'name' => $this->trans('None', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'dots', 'name' => $this->trans('Dots', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'lines', 'name' => $this->trans('Lines', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'dynamic', 'name' => $this->trans('Dynamic dots (scaled)', [], 'Modules.Producercarousel.Admin')],
        ];

        return [
            $this->selectField($prefix . 'COUNT', $this->trans('Visible items', [], 'Modules.Producercarousel.Admin'), $countOptions),
            $this->selectField($prefix . 'MODE', $this->trans('Scroll mode', [], 'Modules.Producercarousel.Admin'), $modeOptions, $this->trans('In standard mode arrows and dots are hidden when all logos fit on screen. Loop and continuous scroll always run — with few logos, the logos are duplicated.', [], 'Modules.Producercarousel.Admin')),
            $this->selectField($prefix . 'SPEED', $this->trans('Speed', [], 'Modules.Producercarousel.Admin'), $speedOptions, $this->trans('Standard and loop modes: delay between slides. Continuous mode: time it takes one logo to pass (lower = faster).', [], 'Modules.Producercarousel.Admin')),
            $this->selectField($prefix . 'ARROWS', $this->trans('Arrow style', [], 'Modules.Producercarousel.Admin'), $arrowOptions),
            $this->selectField($prefix . 'DOTS', $this->trans('Dot style (pagination)', [], 'Modules.Producercarousel.Admin'), $dotOptions),
            $this->pixelField($prefix . 'WIDTH', $this->trans('Item width', [], 'Modules.Producercarousel.Admin'), self::CAROUSEL_SETTINGS['WIDTH'], $this->trans('0 = automatic, based on the number of visible items. A fixed width overrides the number of visible items.', [], 'Modules.Producercarousel.Admin')),
            $this->pixelField($prefix . 'HEIGHT', $this->trans('Item height', [], 'Modules.Producercarousel.Admin'), self::CAROUSEL_SETTINGS['HEIGHT'], $this->trans('0 = default height. Logos are scaled proportionally to fit this height.', [], 'Modules.Producercarousel.Admin')),
            $this->pixelField($prefix . 'GAP', $this->trans('Space between items', [], 'Modules.Producercarousel.Admin'), self::CAROUSEL_SETTINGS['GAP']),
        ];
    }

    private function selectField($name, $label, array $options, $desc = null)
    {
        $field = ['type' => 'select', 'label' => $label, 'name' => $name, 'options' => ['query' => $options, 'id' => 'id', 'name' => 'name']];
        if ($desc !== null) {
            $field['desc'] = $desc;
        }

        return $field;
    }

    private function pixelField($name, $label, array $definition, $desc = null)
    {
        $range = $this->trans('Range: %min%–%max% px.', ['%min%' => $definition['min'], '%max%' => $definition['max']], 'Modules.Producercarousel.Admin');

        return [
            'type' => 'text',
            'label' => $label,
            'name' => $name,
            'suffix' => 'px',
            'class' => 'fixed-width-sm',
            'desc' => trim($range . ' ' . (string) $desc),
        ];
    }

    private function checkboxField($name, $label, array $entities, $idKey)
    {
        $options = [];
        foreach ($entities as $entity) {
            $options[] = ['id' => (int) $entity[$idKey], 'name' => $entity['name'] . (empty($entity['active']) ? ' (' . $this->trans('inactive', [], 'Modules.Producercarousel.Admin') . ')' : '')];
        }

        return ['type' => 'checkbox', 'label' => $label, 'name' => $name, 'values' => ['query' => $options, 'id' => 'id', 'name' => 'name'], 'desc' => $this->trans('Uncheck the items you do not want to display.', [], 'Modules.Producercarousel.Admin')];
    }

    private function getFormValues(array $manufacturers, array $suppliers)
    {
        $values = [self::DISPLAY_MODE => $this->getDisplayMode()];
        foreach ([self::MFR_PREFIX, self::SUP_PREFIX] as $prefix) {
            foreach ($this->getCarouselSettings($prefix) as $key => $value) {
                $values[$prefix . strtoupper($key)] = $value;
            }
        }
        $mfrExcluded = $this->getExcluded(self::MFR_EXCLUDED);
        foreach ($manufacturers as $manufacturer) {
            $id = (int) $manufacturer['id_manufacturer'];
            $values[self::MFR_PREFIX . $id] = !in_array($id, $mfrExcluded, true);
        }
        $supExcluded = $this->getExcluded(self::SUP_EXCLUDED);
        foreach ($suppliers as $supplier) {
            $id = (int) $supplier['id_supplier'];
            $values[self::SUP_PREFIX . $id] = !in_array($id, $supExcluded, true);
        }

        return $values;
    }

    private function renderConfigurationInfo()
    {
        return '<div class="alert alert-info"><p><strong>' . $this->trans('Use as a widget', [], 'Modules.Producercarousel.Admin') . '</strong></p>'
            . '<p><code>{widget name=\'producercarousel\' type=\'manufacturers\'}</code></p>'
            . '<p><code>{widget name=\'producercarousel\' type=\'suppliers\'}</code></p>'
            . '<p>' . $this->trans('Without the type parameter the module displays what is selected in “What to display in the main hook”.', [], 'Modules.Producercarousel.Admin') . '</p></div>';
    }

    private function getManufacturerItems()
    {
        $items = [];
        $excluded = $this->getExcluded(self::MFR_EXCLUDED);
        foreach (Manufacturer::getManufacturers(false, (int) $this->context->language->id, true) as $manufacturer) {
            $id = (int) $manufacturer['id_manufacturer'];
            if (!in_array($id, $excluded, true)) {
                $items[] = ['name' => $manufacturer['name'], 'url' => $this->context->link->getManufacturerLink($id), 'image' => $this->getEntityImage('manufacturer', $id)];
            }
        }
        return $items;
    }

    private function getSupplierItems()
    {
        $items = [];
        $excluded = $this->getExcluded(self::SUP_EXCLUDED);
        foreach (Supplier::getSuppliers(false, (int) $this->context->language->id, true) as $supplier) {
            $id = (int) $supplier['id_supplier'];
            if (!in_array($id, $excluded, true)) {
                $items[] = ['name' => $supplier['name'], 'url' => $this->context->link->getSupplierLink($id), 'image' => $this->getEntityImage('supplier', $id)];
            }
        }
        return $items;
    }

    private function getEntityImage($type, $id)
    {
        $directory = $type === 'manufacturer' ? _PS_MANU_IMG_DIR_ : _PS_SUPP_IMG_DIR_;
        $imageType = ImageType::getFormattedName('medium');
        $typedFile = $directory . $id . '-' . $imageType . '.jpg';
        $plainFile = $directory . $id . '.jpg';
        if (!is_file($typedFile) && !is_file($plainFile)) {
            return null;
        }
        if ($type === 'manufacturer') {
            return $this->context->link->getManufacturerImageLink($id, is_file($typedFile) ? $imageType : null);
        }
        return $this->context->link->getSupplierImageLink($id, is_file($typedFile) ? $imageType : null);
    }

    private function getExcluded($key)
    {
        $decoded = json_decode((string) Configuration::get($key), true);
        return is_array($decoded) ? array_values(array_map('intval', $decoded)) : [];
    }

    /**
     * Stored settings of one carousel, with invalid or missing values replaced by defaults.
     */
    private function getCarouselSettings($prefix)
    {
        $settings = [];
        foreach (self::CAROUSEL_SETTINGS as $key => $definition) {
            $raw = Configuration::get($prefix . $key);
            $value = $raw === false ? null : $this->normalizeSetting($definition, trim((string) $raw));
            $settings[strtolower($key)] = $value === null ? $definition['default'] : $value;
        }

        return $settings;
    }

    private function getDisplayMode()
    {
        $value = (string) Configuration::get(self::DISPLAY_MODE);

        return in_array($value, self::DISPLAY_MODES, true) ? $value : 'all';
    }
}
