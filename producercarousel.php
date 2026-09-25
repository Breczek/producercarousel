<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class ProducerCarousel extends Module implements WidgetInterface
{
    private const MFR_COUNT = 'PC_MFR_COUNT';
    private const MFR_SPEED = 'PC_MFR_SPEED';
    private const MFR_EXCLUDED = 'PC_MFR_EXCLUDED';
    private const SUP_COUNT = 'PC_SUP_COUNT';
    private const SUP_SPEED = 'PC_SUP_SPEED';
    private const SUP_EXCLUDED = 'PC_SUP_EXCLUDED';
    private const DISPLAY_MODE = 'PC_DISPLAY_MODE';

    private const COUNTS = [2, 3, 4, 5, 6, 8];
    private const SPEEDS = [0, 2000, 3000, 4000, 5000, 7000, 10000];
    private const DISPLAY_MODES = ['all', 'manufacturers', 'suppliers'];

    public function __construct()
    {
        $this->name = 'producercarousel';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Producer Carousel';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '9.99.99'];

        parent::__construct();

        $this->displayName = $this->trans('Karuzela producentów i dostawców', [], 'Modules.Producercarousel.Admin');
        $this->description = $this->trans('Wyświetla producentów i dostawców w dwóch niezależnych karuzelach.', [], 'Modules.Producercarousel.Admin');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHome')
            && $this->registerHook('displayHeader')
            && Configuration::updateValue(self::MFR_COUNT, 6)
            && Configuration::updateValue(self::MFR_SPEED, 4000)
            && Configuration::updateValue(self::MFR_EXCLUDED, '[]')
            && Configuration::updateValue(self::SUP_COUNT, 6)
            && Configuration::updateValue(self::SUP_SPEED, 4000)
            && Configuration::updateValue(self::SUP_EXCLUDED, '[]')
            && Configuration::updateValue(self::DISPLAY_MODE, 'all');
    }

    public function uninstall()
    {
        foreach ([self::MFR_COUNT, self::MFR_SPEED, self::MFR_EXCLUDED, self::SUP_COUNT, self::SUP_SPEED, self::SUP_EXCLUDED, self::DISPLAY_MODE] as $key) {
            Configuration::deleteByName($key);
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
                'title' => $this->trans('Producenci', [], 'Modules.Producercarousel.Shop'),
                'items' => $this->getManufacturerItems(),
                'count' => $this->getAllowedValue(self::MFR_COUNT, self::COUNTS, 6),
                'speed' => $this->getAllowedValue(self::MFR_SPEED, self::SPEEDS, 4000),
            ];
        }
        if ($type === 'all' || $type === 'suppliers') {
            $carousels[] = [
                'id' => $instancePrefix . '-suppliers',
                'type' => 'suppliers',
                'title' => $this->trans('Dostawcy', [], 'Modules.Producercarousel.Shop'),
                'items' => $this->getSupplierItems(),
                'count' => $this->getAllowedValue(self::SUP_COUNT, self::COUNTS, 6),
                'speed' => $this->getAllowedValue(self::SUP_SPEED, self::SPEEDS, 4000),
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
                $output .= $this->displayConfirmation($this->trans('Ustawienia zostały zapisane.', [], 'Admin.Notifications.Success'));
            }
        }

        return $output . $this->renderConfigurationInfo() . $this->renderForm();
    }

    private function saveConfiguration()
    {
        $errors = [];
        $values = [];
        foreach ([[self::MFR_COUNT, self::COUNTS], [self::SUP_COUNT, self::COUNTS], [self::MFR_SPEED, self::SPEEDS], [self::SUP_SPEED, self::SPEEDS]] as $setting) {
            $value = (int) Tools::getValue($setting[0]);
            if (!in_array($value, $setting[1], true)) {
                $errors[] = $this->trans('Wybrano niedozwoloną wartość ustawienia.', [], 'Modules.Producercarousel.Admin');
            } else {
                $values[$setting[0]] = $value;
            }
        }

        $displayMode = (string) Tools::getValue(self::DISPLAY_MODE);
        if (!in_array($displayMode, self::DISPLAY_MODES, true)) {
            $errors[] = $this->trans('Wybrano niedozwoloną wartość ustawienia.', [], 'Modules.Producercarousel.Admin');
        }

        if ($errors) {
            return $errors;
        }

        Configuration::updateValue(self::DISPLAY_MODE, $displayMode);

        foreach ($values as $key => $value) {
            Configuration::updateValue($key, $value);
        }

        Configuration::updateValue(self::MFR_EXCLUDED, json_encode($this->collectExcludedIds('PC_MFR_', Manufacturer::getManufacturers(false, (int) $this->context->language->id, false))));
        Configuration::updateValue(self::SUP_EXCLUDED, json_encode($this->collectExcludedIds('PC_SUP_', Supplier::getSuppliers(false, (int) $this->context->language->id, false))));

        return $errors;
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
        $countOptions = array_map(function ($value) {
            return ['id' => $value, 'name' => (string) $value];
        }, self::COUNTS);
        $speedOptions = array_map(function ($value) {
            return ['id' => $value, 'name' => $value === 0 ? $this->trans('Wyłączone', [], 'Admin.Global') : $value . ' ms'];
        }, self::SPEEDS);
        $displayModeOptions = [
            ['id' => 'all', 'name' => $this->trans('Obie karuzele', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'manufacturers', 'name' => $this->trans('Tylko producenci', [], 'Modules.Producercarousel.Admin')],
            ['id' => 'suppliers', 'name' => $this->trans('Tylko dostawcy', [], 'Modules.Producercarousel.Admin')],
        ];

        $fieldsForm = [[
            'form' => [
                'legend' => ['title' => $this->trans('Ustawienia karuzeli', [], 'Modules.Producercarousel.Admin'), 'icon' => 'icon-cogs'],
                'input' => [
                    $this->selectField(self::DISPLAY_MODE, $this->trans('Co wyświetlać w głównym hooku (displayHome)', [], 'Modules.Producercarousel.Admin'), $displayModeOptions),
                    $this->selectField(self::MFR_COUNT, $this->trans('Liczba widocznych producentów', [], 'Modules.Producercarousel.Admin'), $countOptions),
                    $this->selectField(self::MFR_SPEED, $this->trans('Szybkość producentów', [], 'Modules.Producercarousel.Admin'), $speedOptions),
                    $this->checkboxField('PC_MFR', $this->trans('Widoczni producenci', [], 'Modules.Producercarousel.Admin'), $manufacturers, 'id_manufacturer'),
                    $this->selectField(self::SUP_COUNT, $this->trans('Liczba widocznych dostawców', [], 'Modules.Producercarousel.Admin'), $countOptions),
                    $this->selectField(self::SUP_SPEED, $this->trans('Szybkość dostawców', [], 'Modules.Producercarousel.Admin'), $speedOptions),
                    $this->checkboxField('PC_SUP', $this->trans('Widoczni dostawcy', [], 'Modules.Producercarousel.Admin'), $suppliers, 'id_supplier'),
                ],
                'submit' => ['title' => $this->trans('Zapisz', [], 'Admin.Actions')],
            ],
        ]];

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

    private function selectField($name, $label, array $options)
    {
        return ['type' => 'select', 'label' => $label, 'name' => $name, 'options' => ['query' => $options, 'id' => 'id', 'name' => 'name']];
    }

    private function checkboxField($name, $label, array $entities, $idKey)
    {
        $options = [];
        foreach ($entities as $entity) {
            $options[] = ['id' => (int) $entity[$idKey], 'name' => $entity['name'] . (empty($entity['active']) ? ' (' . $this->trans('nieaktywny', [], 'Admin.Global') . ')' : '')];
        }

        return ['type' => 'checkbox', 'label' => $label, 'name' => $name, 'values' => ['query' => $options, 'id' => 'id', 'name' => 'name'], 'desc' => $this->trans('Odznacz pozycje, których nie chcesz wyświetlać.', [], 'Modules.Producercarousel.Admin')];
    }

    private function getFormValues(array $manufacturers, array $suppliers)
    {
        $values = [
            self::DISPLAY_MODE => $this->getDisplayMode(),
            self::MFR_COUNT => $this->getAllowedValue(self::MFR_COUNT, self::COUNTS, 6),
            self::MFR_SPEED => $this->getAllowedValue(self::MFR_SPEED, self::SPEEDS, 4000),
            self::SUP_COUNT => $this->getAllowedValue(self::SUP_COUNT, self::COUNTS, 6),
            self::SUP_SPEED => $this->getAllowedValue(self::SUP_SPEED, self::SPEEDS, 4000),
        ];
        $mfrExcluded = $this->getExcluded(self::MFR_EXCLUDED);
        foreach ($manufacturers as $manufacturer) {
            $id = (int) $manufacturer['id_manufacturer'];
            $values['PC_MFR_' . $id] = !in_array($id, $mfrExcluded, true);
        }
        $supExcluded = $this->getExcluded(self::SUP_EXCLUDED);
        foreach ($suppliers as $supplier) {
            $id = (int) $supplier['id_supplier'];
            $values['PC_SUP_' . $id] = !in_array($id, $supExcluded, true);
        }

        return $values;
    }

    private function renderConfigurationInfo()
    {
        return '<div class="alert alert-info"><p><strong>' . $this->trans('Wywołanie jako widget', [], 'Modules.Producercarousel.Admin') . '</strong></p>'
            . '<p><code>{widget name=\'producercarousel\' type=\'manufacturers\'}</code></p>'
            . '<p><code>{widget name=\'producercarousel\' type=\'suppliers\'}</code></p>'
            . '<p>' . $this->trans('Bez parametru type moduł wyświetli to, co wybrano w ustawieniu „Co wyświetlać w głównym hooku”.', [], 'Modules.Producercarousel.Admin') . '</p></div>';
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

    private function getAllowedValue($key, array $allowed, $default)
    {
        $value = (int) Configuration::get($key);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function getDisplayMode()
    {
        $value = (string) Configuration::get(self::DISPLAY_MODE);

        return in_array($value, self::DISPLAY_MODES, true) ? $value : 'all';
    }
}
