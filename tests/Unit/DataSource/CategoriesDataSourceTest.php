<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusChatbotBundle\Tests\Unit\DataSource;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use FluffyDiscord\SyliusChatbotBundle\Channel\ChannelResolver;
use FluffyDiscord\SyliusChatbotBundle\Contract\ProductIndexabilityInterface;
use FluffyDiscord\SyliusChatbotBundle\Cursor\CursorCodec;
use FluffyDiscord\SyliusChatbotBundle\DataSource\CategoriesDataSource;
use FluffyDiscord\SyliusChatbotBundle\DTO\SourceQuery;
use FluffyDiscord\SyliusChatbotBundle\Enum\DocumentKind;
use FluffyDiscord\SyliusChatbotBundle\Text\HtmlToText;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository;
use Sylius\Bundle\TaxonomyBundle\Doctrine\ORM\TaxonRepository;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use FluffyDiscord\SyliusChatbotBundle\Routing\LocalizedUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class CategoriesDataSourceTest extends TestCase
{
    /** @var list<string> */
    private array $capturedDqls = [];

    private function createDataSource(
        array $taxons,
        array $subtreePairs = [],
        ?TaxonInterface $menuTaxon = null,
        ?\Closure $isIndexable = null,
    ): CategoriesDataSource {
        $taxonRepository = $this->createStub(TaxonRepository::class);
        $taxonRepository->method('getClassName')->willReturn(Taxon::class);
        $taxonRepository->method('createQueryBuilder')->willReturnCallback(
            fn (string $alias): QueryBuilder => (new QueryBuilder($this->createEntityManager($taxons)))
                ->select($alias)
                ->from(Taxon::class, $alias),
        );

        $productEntityManager = $this->createProductEntityManager($subtreePairs);
        $productRepository = $this->createStub(ProductRepository::class);
        $productRepository->method('createQueryBuilder')->willReturnCallback(
            fn (string $alias): QueryBuilder => (new QueryBuilder($productEntityManager))
                ->select($alias)
                ->from(Product::class, $alias),
        );

        $productIndexability = $this->createStub(ProductIndexabilityInterface::class);
        $productIndexability->method('isIndexable')->willReturnCallback(
            $isIndexable ?? static fn (): bool => true,
        );

        $channel = $this->createStub(ChannelInterface::class);
        $channel->method('getMenuTaxon')->willReturn($menuTaxon);

        $channelResolver = $this->createStub(ChannelResolver::class);
        $channelResolver->method('getChannel')->willReturn($channel);

        $router = $this->createStub(RouterInterface::class);
        $router->method('getContext')->willReturn(new RequestContext());
        $router->method('generate')->willReturn('https://shop.example/taxons/clothing/t-shirts');

        return new CategoriesDataSource(
            $taxonRepository,
            $productRepository,
            $productIndexability,
            $channelResolver,
            new CursorCodec(),
            new HtmlToText(),
            new LocalizedUrlGenerator($router),
            new NullLogger(),
        );
    }

    private function createProductEntityManager(array $subtreePairs): EntityManagerInterface
    {
        $products = [];
        foreach ($subtreePairs as $pair) {
            $products[(string) $pair['productId']] = $this->createProduct((int) $pair['productId']);
        }

        $pairsQuery = $this->createResultQuery($subtreePairs);
        $productsQuery = $this->createResultQuery(array_values($products));

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('createQuery')->willReturnCallback(
            function (string $dql) use ($pairsQuery, $productsQuery): Query {
                $this->capturedDqls[] = $dql;
                $isSubtreePairQuery = str_contains($dql, 'ancestorTaxon');

                return $isSubtreePairQuery ? $pairsQuery : $productsQuery;
            },
        );

        return $entityManager;
    }

    private function createResultQuery(array $result): Query
    {
        $query = $this->createStub(Query::class);
        $query->method('setParameters')->willReturnSelf();
        $query->method('setFirstResult')->willReturnSelf();
        $query->method('setMaxResults')->willReturnSelf();
        $query->method('getResult')->willReturn($result);

        return $query;
    }

    private function createProduct(int $id): ProductInterface
    {
        $variant = $this->createStub(ProductVariantInterface::class);
        $variant->method('getCode')->willReturn('PRODUCT-' . $id . '_default');

        $product = $this->createStub(ProductInterface::class);
        $product->method('getId')->willReturn($id);
        $product->method('getCode')->willReturn('PRODUCT-' . $id);
        $product->method('getVariants')->willReturn(new ArrayCollection([$variant]));
        $variant->method('getProduct')->willReturn($product);

        return $product;
    }

    private function createMenuTaxon(): TaxonInterface
    {
        $root = $this->createStub(TaxonInterface::class);

        $menuTaxon = $this->createStub(TaxonInterface::class);
        $menuTaxon->method('getRoot')->willReturn($root);
        $menuTaxon->method('getLeft')->willReturn(1);
        $menuTaxon->method('getRight')->willReturn(40);

        return $menuTaxon;
    }

    private function createEntityManager(array $result): EntityManagerInterface
    {
        $query = $this->createResultQuery($result);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('createQuery')->willReturnCallback(
            function (string $dql) use ($query): Query {
                $this->capturedDqls[] = $dql;

                return $query;
            },
        );

        return $entityManager;
    }

    private function createTaxon(
        int $id,
        string $code,
        string $name,
        string $slug,
        ?TaxonInterface $parent = null,
        ?string $description = null,
    ): TaxonInterface {
        $translation = $this->createStub(TaxonTranslationInterface::class);
        $translation->method('getName')->willReturn($name);
        $translation->method('getSlug')->willReturn($slug);
        $translation->method('getDescription')->willReturn($description);

        $taxon = $this->createStub(TaxonInterface::class);
        $taxon->method('getId')->willReturn($id);
        $taxon->method('getCode')->willReturn($code);
        $taxon->method('getTranslation')->willReturn($translation);
        $taxon->method('getParent')->willReturn($parent);
        $taxon->method('getUpdatedAt')->willReturn(new \DateTime('2026-02-01T00:00:00+00:00'));

        return $taxon;
    }

    public function testDocumentShapeCarriesPathUrlAndProductCount(): void
    {
        $parent = $this->createTaxon(1, 'CLOTHING', 'Clothing', 'clothing');
        $taxon = $this->createTaxon(2, 'T_SHIRTS', 'T-Shirts', 'clothing/t-shirts', $parent, '<p>Comfortable t-shirts</p>');

        $subtreePairs = [];
        for ($id = 1; $id <= 7; ++$id) {
            $subtreePairs[] = ['taxonCode' => 'T_SHIRTS', 'productId' => $id];
        }
        $dataSource = $this->createDataSource([$taxon], $subtreePairs);

        $page = $dataSource->getDocuments(new SourceQuery('cs_CZ'));

        self::assertCount(1, $page->documents);
        $document = $page->documents[0];
        self::assertSame('T_SHIRTS', $document->id);
        self::assertSame('T-Shirts', $document->title);
        self::assertSame(DocumentKind::Category, $document->kind);
        self::assertSame([
            'code' => 'T_SHIRTS',
            'name' => 'T-Shirts',
            'path' => 'Clothing / T-Shirts',
            'url' => 'https://shop.example/taxons/clothing/t-shirts',
            'productCount' => 7,
        ], $document->metadata);
        self::assertStringStartsWith('Clothing / T-Shirts', $document->text);
        self::assertStringContainsString('Comfortable t-shirts', $document->text);
        self::assertNull($page->nextCursor);
    }

    public function testOnlyEnabledTaxonsOfTheLocaleAreQueried(): void
    {
        $dataSource = $this->createDataSource([]);

        $dataSource->getDocuments(new SourceQuery('cs_CZ'));

        self::assertNotSame([], $this->capturedDqls);
        $taxonDql = $this->capturedDqls[0];
        self::assertStringContainsString('taxon.enabled = :enabled', $taxonDql);
        self::assertStringContainsString('taxonTranslation.locale = :locale', $taxonDql);
        self::assertStringContainsString('taxon.parent IS NOT NULL', $taxonDql);
    }

    public function testIdLookupFiltersByCodeAndReturnsNoCursor(): void
    {
        $taxon = $this->createTaxon(2, 'T_SHIRTS', 'T-Shirts', 'clothing/t-shirts');
        $dataSource = $this->createDataSource([$taxon]);

        $page = $dataSource->getDocuments(new SourceQuery(
            locale: 'cs_CZ',
            cursor: base64_encode('1'),
            ids: ['T_SHIRTS'],
        ));

        $taxonDql = $this->capturedDqls[0];
        self::assertStringContainsString('taxon.code IN (:codes)', $taxonDql);
        self::assertStringNotContainsString('taxon.id > :lastId', $taxonDql);
        self::assertNull($page->nextCursor);
        self::assertSame(0, $page->documents[0]->metadata['productCount']);
    }

    public function testAMenuTaxonRestrictsTheQueryToItsNestedSetSubtree(): void
    {
        $dataSource = $this->createDataSource([], [], $this->createMenuTaxon());

        $dataSource->getDocuments(new SourceQuery('cs_CZ'));

        $taxonDql = $this->capturedDqls[0];
        self::assertStringContainsString('taxon.root = :treeRoot', $taxonDql);
        self::assertStringContainsString('taxon.left >= :treeLeft', $taxonDql);
        self::assertStringContainsString('taxon.right <= :treeRight', $taxonDql);
        self::assertStringNotContainsString('taxon.parent IS NOT NULL', $taxonDql);
    }

    public function testProductCountSpansTheNestedSetSubtree(): void
    {
        $taxon = $this->createTaxon(2, 'CLOTHING', 'Clothing', 'clothing');
        $subtreePairs = [
            ['taxonCode' => 'CLOTHING', 'productId' => 1],
            ['taxonCode' => 'CLOTHING', 'productId' => 2],
        ];
        $dataSource = $this->createDataSource([$taxon], $subtreePairs);

        $page = $dataSource->getDocuments(new SourceQuery('cs_CZ'));

        self::assertSame(2, $page->documents[0]->metadata['productCount']);
        $subtreeDql = $this->capturedDqls[1];
        self::assertStringContainsString('SELECT DISTINCT', $subtreeDql);
        self::assertStringContainsString('ancestorTaxon.root = descendantTaxon.root', $subtreeDql);
        self::assertStringContainsString('ancestorTaxon.left <= descendantTaxon.left', $subtreeDql);
        self::assertStringContainsString('ancestorTaxon.right >= descendantTaxon.right', $subtreeDql);
    }

    public function testProductCountExcludesNonIndexableProducts(): void
    {
        $taxon = $this->createTaxon(2, 'CLOTHING', 'Clothing', 'clothing');
        $subtreePairs = [
            ['taxonCode' => 'CLOTHING', 'productId' => 1],
            ['taxonCode' => 'CLOTHING', 'productId' => 2],
        ];
        $dataSource = $this->createDataSource(
            [$taxon],
            $subtreePairs,
            null,
            static fn (ProductVariantInterface $variant): bool => $variant->getProduct()->getId() !== 2,
        );

        $page = $dataSource->getDocuments(new SourceQuery('cs_CZ'));

        self::assertSame(1, $page->documents[0]->metadata['productCount']);
    }
}
