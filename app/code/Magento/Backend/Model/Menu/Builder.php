<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Magento
 * @package     Magento_Backend
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Backend\Model\Menu;

/**
 * Menu builder object. Retrieves commands (\Magento\Backend\Model\Menu\Builder\AbstractCommand)
 * to build menu (\Magento\Backend\Model\Menu)
 */
class Builder
{
    /**
     * @var \Magento\Backend\Model\Menu\Builder\AbstractCommand[]
     */
    protected $_commands = array();

    /**
     * @var \Magento\Backend\Model\Menu\Item\Factory
     */
    protected $_itemFactory;

    /**
     * @param \Magento\Backend\Model\Menu\Item\Factory $menuItemFactory
     */
    public function __construct(\Magento\Backend\Model\Menu\Item\Factory $menuItemFactory)
    {
        $this->_itemFactory = $menuItemFactory;
    }

    /**
     * Process provided command object
     *
     * @param \Magento\Backend\Model\Menu\Builder\AbstractCommand $command
     * @return $this
     */
    public function processCommand(\Magento\Backend\Model\Menu\Builder\AbstractCommand $command)
    {
        if (!isset($this->_commands[$command->getId()])) {
            $this->_commands[$command->getId()] = $command;
        } else {
            $this->_commands[$command->getId()]->chain($command);
        }
        return $this;
    }

    /**
     * Populate menu object
     *
     * @param \Magento\Backend\Model\Menu $menu
     * @return \Magento\Backend\Model\Menu
     * @throws \OutOfRangeException in case given parent id does not exists
     */
    public function getResult(\Magento\Backend\Model\Menu $menu)
    {
        /** @var $items \Magento\Backend\Model\Menu\Item[] */
        $params = array();
        $items = array();

        // Create menu items
        foreach ($this->_commands as $id => $command) {
            $params[$id] = $command->execute();
            $item = $this->_itemFactory->create($params[$id]);
            $items[$id] = $item;
        }

        // Build menu tree based on "parent" param
        foreach ($items as $id => $item) {
            $sortOrder = $this->_getParam($params[$id], 'sortOrder');
            $parentId = $this->_getParam($params[$id], 'parent');
            $isRemoved = isset($params[$id]['removed']);

            if ($isRemoved) {
                continue;
            }
            if (!$parentId) {
                $menu->add($item, null, $sortOrder);
            } else {
                if (!isset($items[$parentId])) {
                    throw new \OutOfRangeException(sprintf('Specified invalid parent id (%s)', $parentId));
                }
                if (isset($params[$parentId]['removed'])) {
                    continue;
                }
                $items[$parentId]->getChildren()->add($item, null, $sortOrder);
            }
        }

        return $menu;
    }

    /**
     * Retrieve param by name or default value
     *
     * @param array $params
     * @param string $paramName
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function _getParam($params, $paramName, $defaultValue = null)
    {
        return isset($params[$paramName]) ? $params[$paramName] : $defaultValue;
    }
}