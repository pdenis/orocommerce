<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\FinderDatabaseStrategy;
use Oro\Bundle\ProductBundle\Twig\ProductExtension;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait, EntityTrait;

    /** @var AutocompleteFieldsProvider */
    protected $autocompleteFieldsProvider;

    /** @var ProductExtension */
    protected $extension;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var FinderDatabaseStrategy|\PHPUnit_Framework_MockObject_MockObject */
    protected $finderDatabaseStrategy;

    protected function setUp()
    {
        $this->autocompleteFieldsProvider = $this->getMockBuilder(AutocompleteFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->finderDatabaseStrategy = $this->getMockBuilder(FinderDatabaseStrategy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_product.autocomplete_fields_provider', $this->autocompleteFieldsProvider)
            ->add('oro_entity.doctrine_helper', $this->doctrineHelper)
            ->add('oro.product.related_item.related_product.finder_strategy', $this->finderDatabaseStrategy)
            ->getContainer($this);

        $this->extension = new ProductExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(ProductExtension::NAME, $this->extension->getName());
    }

    public function testIsConfigurableSimple()
    {
        $this->assertFalse(
            self::callTwigFunction($this->extension, 'is_configurable_product_type', [Product::TYPE_SIMPLE])
        );
    }

    public function testIsConfigurable()
    {
        $this->assertTrue(
            self::callTwigFunction($this->extension, 'is_configurable_product_type', [Product::TYPE_CONFIGURABLE])
        );
    }

    /**
     * @param array $relatedProducts
     * @param array $expectedIds
     * @dataProvider dataProviderRelatedProducts
     */
    public function testGetRelatedProductsIds(array $relatedProducts, array $expectedIds)
    {
        $this->finderDatabaseStrategy->expects($this->once())
            ->method('find')
            ->willReturn($relatedProducts);

        $this->assertSame($expectedIds, $this->extension->getRelatedProductsIds(new Product()));
    }

    public function dataProviderRelatedProducts()
    {
        return [
            [[
                $this->getEntity(Product::class, ['id' => 2]),
                $this->getEntity(Product::class, ['id' => 3]),
                $this->getEntity(Product::class, ['id' => 4]),
            ], [2, 3, 4]],
            [[],[]]
        ];
    }
}
