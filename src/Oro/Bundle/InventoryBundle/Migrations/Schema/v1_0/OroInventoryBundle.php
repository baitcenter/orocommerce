<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class OroInventoryBundle implements Installation, ExtendExtensionAwareInterface
{
    use MigrationConstraintTrait;

    const INVENTORY_LEVEL_TABLE_NAME = 'oro_inventory_level';
    const OLD_WAREHOUSE_INVENTORY_TABLE = 'oro_warehouse_inventory_lev';
    const ORO_B2B_WAREHOUSE_INVENTORY_TABLE = 'orob2b_warehouse_inventory_lev';

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addManageInventoryFieldToProduct($schema);
        $this->addManageInventoryFieldToCategory($schema);
        $this->addInventoryThresholdFieldToProduct($schema);
        $this->addInventoryThresholdFieldToCategory($schema);

        /** Tables generation **/
        $this->createOroInventoryLevelTable($schema);

        /** Foreign keys generation **/
        $this->addOroInventoryLevelForeignKeys($schema);

        $queries->addPostQuery(
            new RenameInventoryConfigSectionQuery('oro_warehouse', 'oro_inventory', 'manage_inventory')
        );
    }

    /**
     * Create oro_inventory_level table
     *
     * @param Schema $schema
     */
    protected function createOroInventoryLevelTable(Schema $schema)
    {
        $table = $schema->createTable(self::INVENTORY_LEVEL_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quantity', 'decimal', ['precision' => 20, 'scale' => 10]);
        $table->addColumn('product_id', 'integer');
        $table->addColumn('product_unit_precision_id', 'integer');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_inventory_level foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroInventoryLevelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::INVENTORY_LEVEL_TABLE_NAME);

        /** PRODUCT */
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        /** PRODUCT UNIT PRECISION */
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit_precision'),
            ['product_unit_precision_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addManageInventoryFieldToProduct(Schema $schema)
    {
        $productTable = $schema->getTable('oro_product');
        $this->addManageInventoryField($schema, $productTable);
    }

    /**
     * @param Schema $schema
     */
    protected function addManageInventoryFieldToCategory(Schema $schema)
    {
        $categoryTable = $schema->getTable('oro_catalog_category');
        $this->addManageInventoryField($schema, $categoryTable, ParentCategoryFallbackProvider::FALLBACK_ID);
    }

    /**
     * @param Schema $schema
     */
    public function addInventoryThresholdFieldToProduct(Schema $schema)
    {
        $productTable = $schema->getTable('oro_product');
        $this->addInventoryThresholdField($schema, $productTable);
    }

    /**
     * @param Schema $schema
     */
    public function addInventoryThresholdFieldToCategory(Schema $schema)
    {
        $categoryTable = $schema->getTable('oro_catalog_category');
        $this->addInventoryThresholdField($schema, $categoryTable, ParentCategoryFallbackProvider::FALLBACK_ID);
    }

    /**
     * @param QueryBag $queries
     */
    protected function addEntityConfigUpdateQueries(QueryBag $queries)
    {
        $configData = [
            'id' => 'oro.inventory.inventorylevel.id.label',
            'product' => 'oro.inventory.inventorylevel.product.label',
            'quantity' => 'oro.inventory.inventorylevel.quantity.label',
            'productUnitPrecision' => 'oro.inventory.inventorylevel.product_unit_precision.label',
            'warehouse' => 'oro.inventory.inventorylevel.warehouse.label',
        ];
        $this->addEntityFieldLabelConfigs($queries, InventoryLevel::class, $configData);


        $configData = ['manageInventory' => 'oro.inventory.manage_inventory.label'];
        $this->addEntityFieldLabelConfigs($queries, Product::class, $configData);
        $this->addEntityFieldLabelConfigs($queries, Category::class, $configData);

        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            InventoryLevel::class,
            'entity',
            'label',
            'oro.inventory.inventorylevel.entity_label'
        ));

        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            InventoryLevel::class,
            'entity',
            'plural_label',
            'oro.inventory.inventorylevel.entity_plural_label'
        ));

        $queries->addPostQuery(new UpdateEntityConfigExtendClassQuery(
            InventoryLevel::class,
            'Extend\Entity\EX_OroWarehouseBundle_WarehouseInventoryLevel',
            'Extend\Entity\EX_OroInventoryBundle_InventoryLevel'
        ));

        $queries->addPostQuery(new UpdateFallbackEntitySystemOptionConfig(
            Product::class,
            'manageInventory',
            'oro_inventory.manage_inventory'
        ));
        $queries->addPostQuery(new UpdateFallbackEntitySystemOptionConfig(
            Category::class,
            'manageInventory',
            'oro_inventory.manage_inventory'
        ));
    }

    /**
     * @param QueryBag $queries
     * @param $class
     * @param $data
     */
    protected function addEntityFieldLabelConfigs(QueryBag $queries, $class, $data)
    {
        foreach ($data as $fieldName => $value) {
            $queries->addPostQuery(new UpdateEntityConfigFieldValueQuery(
                $class,
                $fieldName,
                'entity',
                'label',
                $value
            ));
        }
    }

    /**
     * @inheritDoc
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * @param Schema $schema
     * @param Table $table
     */
    private function addInventoryThresholdField(
        Schema $schema,
        Table $table,
        $defaultFallback = CategoryFallbackProvider::FALLBACK_ID
    ) {
        $fallbackTable = $schema->getTable('oro_entity_fallback_value');
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table,
            'inventoryThreshold',
            $fallbackTable,
            'id',
            [
                'entity' => [
                    'label' => 'oro.inventory.inventory_threshold.label',
                ],
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                ],
                'form' => [
                    'is_enabled' => false,
                ],
                'view' => [
                    'is_displayable' => false,
                ],
                'datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE,
                ],
                'fallback' => [
                    'fallbackList' => [
                        $defaultFallback => ['fieldName' => 'inventoryThreshold'],
                        SystemConfigFallbackProvider::FALLBACK_ID => [
                            'configName' => 'oro_inventory.inventory_threshold'
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @param Schema $schema
     * @param Table $table
     */
    private function addManageInventoryField(
        Schema $schema,
        Table $table,
        $defaultFallback = CategoryFallbackProvider::FALLBACK_ID
    ) {
        $fallbackTable = $schema->getTable('oro_entity_fallback_value');
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table,
            'manageInventory',
            $fallbackTable,
            'id',
            [
                'entity' => [
                    'label' => 'oro.inventory.manage_inventory.label',
                ],
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                ],
                'form' => [
                    'is_enabled' => false,
                ],
                'view' => [
                    'is_displayable' => false,
                ],
                'datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_FALSE,
                ],
                'fallback' => [
                    'fallbackList' => [
                        $defaultFallback => ['fieldName' => 'manageInventory'],
                        SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'oro_inventory.manage_inventory'],
                    ],
                ],
            ]
        );
    }
}
