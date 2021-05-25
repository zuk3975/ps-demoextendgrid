<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

declare(strict_types=1);

use PrestaShop\Module\DemoExtendGrid\Install\Installer;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollectionInterface;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\SubmitRowAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnInterface;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;
use PrestaShop\PrestaShop\Core\Grid\Exception\ColumnNotFoundException;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

class DemoExtendGrid extends Module
{
    public function __construct()
    {
        $this->name = 'demoextendgrid';
        $this->author = 'PrestaShop';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.7.7.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Demo extend grid');
        $this->description = $this->l('Demonstration of how to extend grids');
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $installer = new Installer();

        return $installer->install($this);
    }

    public function hookActionAdminControllerSetMedia(array $params)
    {
        // check if it is orders controller
        if ($this->context->controller->controller_name !== 'AdminOrders') {
            return;
        }
        $action = Tools::getValue('action');

        // check if it is orders index page (we want to skip if it is `order create` or `order view` page)
        if ($action === 'vieworder' || $action === 'addorder') {
            return;
        }

        // now we are sure it is Orders index (listing) page where we need our javascript
        $this->context->controller->addJS('modules/' . $this->name . '/views/js/orders-listing.js');
    }

    /**
     * @param array $params
     */
    public function hookActionOrderGridDefinitionModifier(array $params): void
    {
        /** @var GridDefinitionInterface $orderGridDefinition */
        $orderGridDefinition = $params['definition'];

        /** @var RowActionCollectionInterface $actionsCollection */
        $actionsCollection = $this->getActionsColumn($orderGridDefinition)->getOption('actions');
        $actionsCollection->add(
            // mark order is just an example of some custom action
            (new SubmitRowAction('mark_order'))
                ->setName($this->trans('Mark', [], 'Admin.Actions'))
                ->setIcon('push_pin')
                ->setOptions([
                    'route' => 'demo_admin_orders_mark_order',
                    'route_param_name' => 'orderId',
                    'route_param_field' => 'id_order',
                    // use this if you want to show the action inline instead of adding it to dropdown
                    'use_inline_display' => true,
                ])
        );
        // Button is not working by default, because SubmitRowActionExtension component is not loaded in Orders grid javascript part.
        // To replace that behavior there is an example of custom javascript in views/orders-listing.js
        // Adding grid extension in non-compiled javascript is not supported yet, we hope to fix it in future.
    }

    private function getActionsColumn(GridDefinitionInterface $gridDefinition): ColumnInterface
    {
        try {
            return $gridDefinition->getColumnById('actions');
        } catch (ColumnNotFoundException $e) {
            // It is possible that not every grid will have actions column.
            // In this case you can create a new column or throw exception depending on your needs
            throw $e;
        }
    }
}
