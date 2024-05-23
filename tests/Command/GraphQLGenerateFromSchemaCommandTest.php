<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Tests\Command;

use ForestCityLabs\Framework\Command\GraphQLGenerateFromSchema;
use ForestCityLabs\Framework\GraphQL\Diff\FieldDiff;
use ForestCityLabs\Framework\GraphQL\Diff\ObjectTypeDiff;
use ForestCityLabs\Framework\GraphQL\Diff\SchemaComparator;
use ForestCityLabs\Framework\GraphQL\Diff\SchemaDiff;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ForestCityLabs\Framework\GraphQL\MethodFieldResolver;
use ForestCityLabs\Framework\GraphQL\PropertyFieldResolver;
use ForestCityLabs\Framework\GraphQL\TypeRegistry;
use ForestCityLabs\Framework\GraphQL\ValueTransformer\DateTimeValueTransformer;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ScanDirectoryDiscovery;
use ForestCityLabs\Framework\Utility\CodeGenerator;
use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLCodeHelper;
use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLCodeManager;
use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLFile;
use ForestCityLabs\Framework\Utility\ParameterConverter\DateTimeParameterConverter;
use ForestCityLabs\Framework\Utility\ParameterProcessor;
use ForestCityLabs\Framework\Utility\ParameterResolver\IndexedParameterResolver;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PropertyAccess\PropertyAccessor;

#[CoversClass(GraphQLGenerateFromSchema::class)]
#[UsesClass(FieldDiff::class)]
#[UsesClass(ObjectTypeDiff::class)]
#[UsesClass(SchemaComparator::class)]
#[UsesClass(SchemaDiff::class)]
#[UsesClass(CodeGenerator::class)]
#[UsesClass(GraphQLCodeHelper::class)]
#[UsesClass(GraphQLCodeManager::class)]
#[UsesClass(GraphQLFile::class)]
#[UsesClass(MetadataProvider::class)]
#[UsesClass(MethodFieldResolver::class)]
#[UsesClass(PropertyFieldResolver::class)]
#[UsesClass(TypeRegistry::class)]
#[UsesClass(ScanDirectoryDiscovery::class)]
#[UsesClass(ParameterProcessor::class)]
#[Group('graphql')]
#[Group('command')]
class GraphQLGenerateFromSchemaCommandTest extends TestCase
{
    use MatchesSnapshots;

    private MetadataProvider $metadata_provider;
    private TypeRegistry $registry;
    private Schema $schema;
    private CacheItemPoolInterface $cache;

    protected function setUp(): void
    {
        $item = $this->createStub(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);
        $item->method('set')->willReturnSelf();
        $this->cache = $this->createStub(CacheItemPoolInterface::class);
        $this->cache->method('getItem')->willReturn($item);
        $this->metadata_provider = new MetadataProvider(
            new ScanDirectoryDiscovery([__DIR__ . '/../Fixture/Generated/Entity']),
            new ScanDirectoryDiscovery([__DIR__ . '/../Fixture/Generated/Controller']),
            $this->cache
        );
        $this->registry = new TypeRegistry(
            $this->metadata_provider,
            new PropertyFieldResolver(
                new PropertyAccessor(),
                new DateTimeValueTransformer(),
            ),
            new MethodFieldResolver(
                $this->createStub(ContainerInterface::class),
                new ParameterProcessor(
                    new IndexedParameterResolver(),
                    new DateTimeParameterConverter(),
                ),
                new DateTimeValueTransformer(),
            )
        );
        $registry = $this->registry;
        $this->schema = new Schema([
            'query' => $this->registry->getType('Query'),
            'mutation' => $this->registry->getType('Mutation'),
            'typeLoader' => static fn (string $name): ?Type => $registry->getType($name)
        ]);
    }

    #[Test]
    public function generateNew(): void
    {
        $command = new GraphQLGenerateFromSchema(
            __DIR__ . '/../Fixture/schema.graphql',
            __DIR__ . '/../Fixture/Generated/Entity/',
            'ForestCityLabs\\Framework\\Tests\\Fixture\\Generated\\Entity',
            new ScanDirectoryDiscovery([__DIR__ . '/../Fixture/Generated/Entity']),
            __DIR__ . '/../Fixture/Generated/Controller/',
            'ForestCityLabs\\Framework\\Tests\\Fixture\\Generated\\Controller',
            new ScanDirectoryDiscovery([__DIR__ . '/../Fixture/Generated/Controller']),
            new PsrPrinter(),
            $this->metadata_provider,
            $this->schema,
            $this->cache,
        );
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->execute(['command' => 'graphql:generate --no-interaction']);
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Entity/Basket.php');
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Entity/Apple.php');
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Entity/AppleType.php');
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Entity/Fruit.php');
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Controller/GraphQLController.php');
    }

    #[Test]
    #[Depends('generateNew')]
    public function update(): void
    {
        $command = new GraphQLGenerateFromSchema(
            __DIR__ . '/../Fixture/schema-updated.graphql',
            __DIR__ . '/../Fixture/Generated/Entity/',
            'ForestCityLabs\\Framework\\Tests\\Fixture\\Generated\\Entity',
            new ScanDirectoryDiscovery([__DIR__ . '/../Fixture/Generated/Entity']),
            __DIR__ . '/../Fixture/Generated/Controller/',
            'ForestCityLabs\\Framework\\Tests\\Fixture\\Generated\\Controller',
            new ScanDirectoryDiscovery([__DIR__ . '/../Fixture/Generated/Controller']),
            new PsrPrinter(),
            $this->metadata_provider,
            $this->schema,
            $this->cache,
        );
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->execute(['command' => 'graphql:generate --no-interaction']);
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Entity/Basket.php');
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Entity/Apple.php');
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Entity/AppleType.php');
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Entity/Fruit.php');
        $this->assertMatchesFileSnapshot(__DIR__ . '/../Fixture/Generated/Controller/GraphQLController.php');
    }

    public static function setUpBeforeClass(): void
    {
        mkdir(__DIR__ . '/../Fixture/Generated/Entity', recursive: true);
        mkdir(__DIR__ . '/../Fixture/Generated/Controller', recursive: true);
    }

    public static function tearDownAfterClass(): void
    {
        $files = glob(__DIR__ . '/../Fixture/Generated/*/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir(__DIR__ . '/../Fixture/Generated/Entity');
        rmdir(__DIR__ . '/../Fixture/Generated/Controller');
        rmdir(__DIR__ . '/../Fixture/Generated');
    }
}
