<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData as ProductFixture;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductRepositoryTest extends WebTestCase
{
    /**
     * @var ProductRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductImageData',
        ]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('oro_product.entity.product.class')
        );
    }

    public function testFindOneBySku()
    {
        $this->assertNull($this->getRepository()->findOneBySku(uniqid('_fake_sku_', true)));

        $product = $this->getProduct(ProductFixture::PRODUCT_1);
        $expectedProduct = $this->getRepository()->findOneBySku(ucfirst(ProductFixture::PRODUCT_1));

        $this->assertEquals($product->getSku(), $expectedProduct->getSku());
    }

    /**
     * @dataProvider getSearchQueryBuilderDataProvider
     * @param string $search
     * @param int $firstResult
     * @param int $maxResult
     * @param array $expected
     */
    public function testGetSearchQueryBuilder($search, $firstResult, $maxResult, array $expected)
    {
        $queryBuilder = $this->getRepository()->getSearchQueryBuilder($search, $firstResult, $maxResult);
        $result = array_map(
            function ($product) {
                return $product['sku'];
            },
            $queryBuilder->getQuery()->getArrayResult()
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getSearchQueryBuilderDataProvider()
    {
        return [
            'product, 0, 10' => [
                'search' => 'duct',
                'firstResult' => 0,
                'maxResult' => 10,
                'expected' => [
                    'product-1',
                    'product-2',
                    'product-3',
                    'product-4',
                    'product-5',
                    'product-6',
                    'product-7',
                    'product-8',
                ],
            ],
            'product, 1, 1' => [
                'search' => 'oduct',
                'firstResult' => 1,
                'maxResult' => 1,
                'expected' => [
                    'product-2',
                ],
            ],
            'product, 0, 2' => [
                'search' => 'product',
                'firstResult' => 0,
                'maxResult' => 2,
                'expected' => [
                    'product-1',
                    'product-2',
                ],
            ],
        ];
    }

    /**
     * @dataProvider patternsAndSkuListProvider
     * @param string $pattern
     * @param array $expectedSkuList
     */
    public function testFindAllSkuByPattern($pattern, array $expectedSkuList)
    {
        $actualSkuList = $this->getRepository()->findAllSkuByPattern($pattern);

        $this->assertCount(count($expectedSkuList), $actualSkuList);
        foreach ($expectedSkuList as $expectedSku) {
            $this->assertContains($expectedSku, $actualSkuList);
        }
    }

    /**
     * @return array
     */
    public function patternsAndSkuListProvider()
    {
        $allProducts = [
            ProductFixture::PRODUCT_1,
            ProductFixture::PRODUCT_2,
            ProductFixture::PRODUCT_3,
            ProductFixture::PRODUCT_4,
            ProductFixture::PRODUCT_5,
            ProductFixture::PRODUCT_6,
            ProductFixture::PRODUCT_7,
            ProductFixture::PRODUCT_8,
        ];

        return [
            'exact search 1' => [ProductFixture::PRODUCT_1, [ProductFixture::PRODUCT_1]],
            'exact search 2' => [ProductFixture::PRODUCT_2, [ProductFixture::PRODUCT_2]],
            'not found' => [uniqid('_fake_', true), []],
            'mask all products 1' => ['product-%', $allProducts],
            'mask all products 2' => ['pro%', $allProducts],
            'product suffixed with 1' => ['%-1', [ProductFixture::PRODUCT_1]],
            'product suffixed with 2' => ['%2', [ProductFixture::PRODUCT_2]],
        ];
    }

    public function testGetProductsQueryBuilder()
    {
        /** @var Product $product */
        $product = $this->getRepository()->findOneBy(['sku' => 'product-1']);
        $builder = $this->getRepository()->getProductsQueryBuilder([$product->getId()]);
        $result = $builder->getQuery()->getResult();
        $this->assertCount(1, $result);
        $this->assertEquals($product, $result[0]);
    }

    /**
     * @param string $reference
     * @return Product
     */
    protected function getProduct($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->repository;
    }

    public function testGetProductsIdsBySku()
    {
        $product1 = $this->getProduct(ProductFixture::PRODUCT_1);
        $product2 = $this->getProduct(ProductFixture::PRODUCT_2);
        $product3 = $this->getProduct(ProductFixture::PRODUCT_3);

        $this->assertEquals(
            [
                $product1->getSku() => $product1->getId(),
                $product2->getSku() => $product2->getId(),
                $product3->getSku() => $product3->getId(),
            ],
            $this->getRepository()->getProductsIdsBySku(
                [
                    $product3->getSku(),
                    strtoupper($product1->getSku()),
                    strtolower($product2->getSku()),
                ]
            )
        );
    }

    /**
     * @dataProvider getProductsNamesBySkuDataProvider
     *
     * @param array $productSkus
     * @param array $expectedData
     */
    public function testGetProductsNamesBySku(array $productSkus, array $expectedData)
    {
        $result = $this->getRepository()->getProductWithNamesBySku($productSkus);
        $expectedData = $this->referencesToEntities($expectedData);
        $this->assertCount(count($expectedData), $result);
        foreach ($expectedData as $expectedProduct) {
            $this->assertContains($expectedProduct, $result);
        }
    }

    /**
     * @return array
     */
    public function getProductsNamesBySkuDataProvider()
    {
        return [
            [
                'skus' => [
                    ProductFixture::PRODUCT_1,
                    strtoupper(ProductFixture::PRODUCT_2),
                    strtolower(ProductFixture::PRODUCT_3),
                    'not a sku',
                ],
                'expectedData' => [
                    ProductFixture::PRODUCT_1,
                    ProductFixture::PRODUCT_2,
                    ProductFixture::PRODUCT_3,
                ],
            ],
            [
                'skus' => [
                    'not a sku',
                ],
                'expectedData' => [],
            ]
        ];
    }

    public function testGetFilterSkuQueryBuilder()
    {
        /** @var Product $product */
        $product = $this->getRepository()->findOneBy(['sku' => 'product-1']);

        $builder = $this->getRepository()->getFilterSkuQueryBuilder([$product->getSku()]);
        $result = $builder->getQuery()->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals($product->getSku(), $result[0]['sku']);
    }

    /**
     * @dataProvider getListingImagesFilesByProductIdsDataProvider
     *
     * @param array $products
     * @param array $expectedImages
     */
    public function testGetListingImagesFilesByProductIds(array $products, array $expectedImages)
    {
        $result = $this->repository->getListingImagesFilesByProductIds($this->referencesToEntities($products));

        $this->assertEquals($this->referencesToEntities($expectedImages), array_values($result));
    }

    /**
     * @return array
     */
    public function getListingImagesFilesByProductIdsDataProvider()
    {
        return [
            [
                'products' => [
                    'product-1',
                    'product-2',
                    'product-3',
                    'product-4',
                    'product-5',
                    'product-6',
                    'product-7',
                    'product-8',
                ],
                'expectedImages' => [
                    'img.product-1',
                    'img.product-2',
                ],
            ],
            [
                'products' => [
                    'product-1',
                    'product-2',
                ],
                'expectedImages' => [
                    'img.product-1',
                    'img.product-2',
                ],
            ],
        ];
    }

    public function testGetPrimaryUnitPrecisionCode()
    {
        /** @var Product $product */
        $product = $this->getRepository()->findOneBy(['sku' => ProductFixture::PRODUCT_1]);

        $result = $this->repository->getPrimaryUnitPrecisionCode(ucfirst($product->getSku()));
        $this->assertEquals($product->getPrimaryUnitPrecision()->getProductUnitCode(), $result);
    }

    /**
     * @param array $references
     * @return array
     */
    protected function referencesToEntities(array $references)
    {
        return array_map(function ($reference) {
            return $this->getReference($reference);
        }, $references);
    }

    public function testGetProductsByIds()
    {
        $product1 = $this->getProduct(ProductFixture::PRODUCT_1);
        $product2 = $this->getProduct(ProductFixture::PRODUCT_2);
        $product3 = $this->getProduct(ProductFixture::PRODUCT_3);

        $this->assertEquals(
            [
                $product1,
                $product2,
                $product3,
            ],
            $this->getRepository()->getProductsByIds(
                [
                    $product1->getId(),
                    $product2->getId(),
                    $product3->getId(),
                ]
            )
        );
    }

    /**
     * @param array $criteria
     * @param array $expectedSkus
     * @dataProvider findByCaseInsensitiveDataProvider
     */
    public function testFindByCaseInsensitive(array $criteria, array $expectedSkus)
    {
        $actualProducts = $this->repository->findByCaseInsensitive($criteria);

        $actualSkus = [];
        foreach ($actualProducts as $product) {
            $actualSkus[] = $product->getSku();
        }

        $this->assertEquals($expectedSkus, $actualSkus);
    }

    /**
     * @return array
     */
    public function findByCaseInsensitiveDataProvider()
    {
        return [
            'regular sku' => [
                'criteria' => ['sku' => ProductFixture::PRODUCT_1],
                'expectedSkus' => [ProductFixture::PRODUCT_1]
            ],
            'upper sku' => [
                'criteria' => ['sku' => strtoupper(ProductFixture::PRODUCT_2)],
                'expectedSkus' => [ProductFixture::PRODUCT_2]
            ],
            'lower sku' => [
                'criteria' => ['sku' => strtolower(ProductFixture::PRODUCT_3)],
                'expectedSkus' => [ProductFixture::PRODUCT_3]
            ],
            'undefined sku' => [
                'criteria' => ['sku' => 'UndefinedSku'],
                'expectedSkus' => []
            ],
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Value of testField must be string
     */
    public function testFindByCaseInsensitiveWithInvalidCriteria()
    {
        $this->repository->findByCaseInsensitive(['testField' => new \DateTime()]);
    }

    public function testGetFeaturedProductsQueryBuilder()
    {
        $queryBuilder = $this->getRepository()->getFeaturedProductsQueryBuilder(2);
        $result = $queryBuilder->getQuery()->getResult();
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Product::class, $result[0]);
    }

    public function testFindByAttributeValue()
    {
        $result = $this->repository->findByAttributeValue(Product::TYPE_SIMPLE, 'sku', 'product-1', false);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Product::class, $result[0]);

        $result = $this->repository->findByAttributeValue(Product::TYPE_SIMPLE, 'inventory_status', 'in_stock', false);
        $this->assertCount(5, $result);
        $this->assertInstanceOf(Product::class, $result[0]);

        $localizedFallbackRepository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('oro_locale.entity.localized_fallback_value.class')
        );

        $name = $localizedFallbackRepository->findOneBy(['string' => 'product-1.names.default']);
        $result = $this->repository->findByAttributeValue(Product::TYPE_SIMPLE, 'names', $name->getId(), true);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Product::class, $result[0]);
    }
}
