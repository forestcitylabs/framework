<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Command;

use ForestCityLabs\Framework\GraphQL\Diff\FieldDiff;
use ForestCityLabs\Framework\GraphQL\Diff\SchemaComparator;
use ForestCityLabs\Framework\GraphQL\Diff\SchemaDiff;
use ForestCityLabs\Framework\GraphQL\Diff\TypeDiff;
use ForestCityLabs\Framework\Utility\ServicesPrinter;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use LogicException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\Printer;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FirstFindingVisitor;
use PhpParser\ParserFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GraphQLGenerateFromSchema extends Command
{
    private array $type_map = [];
    private array $files = [];
    private array $new_controllers = [];
    private array $new_entities = [];

    public function __construct(
        private string $schema_file,
        private string $services_file,
        private string $entity_dir,
        private string $entity_namespace,
        private array $entities,
        private string $controller_dir,
        private string $controller_namespace,
        private array $controllers,
        private Printer $printer,
        private MetadataProvider $metadata_provider,
        private Schema $schema,
        private CacheItemPoolInterface $cache
    ) {
        // Create a type map.
        foreach ($metadata_provider->getAllTypeMetadata() as $metadata) {
            $this->type_map[$metadata->getName()] = $metadata->getClassName();
        }
        parent::__construct('graphql:generate');
    }

    protected function configure()
    {
        $this->addOption(
            'entity-dir',
            mode: InputOption::VALUE_REQUIRED,
            default: $this->entity_dir
        );
        $this->addOption(
            'entity-namespace',
            mode: InputOption::VALUE_REQUIRED,
            default: $this->entity_namespace
        );
        $this->addOption(
            'controller-dir',
            mode: InputOption::VALUE_REQUIRED,
            default: $this->controller_dir
        );
        $this->addOption(
            'controller-namespace',
            mode: InputOption::VALUE_REQUIRED,
            default: $this->controller_namespace
        );
        $this->addOption(
            'services-file',
            mode: InputOption::VALUE_REQUIRED,
            default: $this->services_file
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Reset our namespaces for use throughout this command.
        $this->entity_dir = $input->getOption('entity-dir');
        $this->entity_namespace = $input->getOption('entity-namespace');
        $this->controller_dir = $input->getOption('controller-dir');
        $this->controller_namespace = $input->getOption('controller-namespace');

        // Set up IO and start output.
        $io = new SymfonyStyle($input, $output);
        $io->title('GraphQL Code Generator');

        // The desired schema is loaded from a graphql file.
        $to_schema = BuildSchema::build(
            Parser::parse(file_get_contents($this->schema_file))
        );

        // The current schema is found in code.
        $from_schema = $this->schema;

        // Diff the schemas.
        $diff = SchemaComparator::compareSchemas($from_schema, $to_schema);

        // If the schemas are identical report as such.
        if (!$diff->isDifferent()) {
            $io->success('GraphQL schema is in sync with code!');
            return Command::SUCCESS;
        }

        // Start asking how to modify things.
        $this->syncSchema($diff, $io);

        // Save the changes made to the code.
        $this->saveFiles();

        // Modify the services file, adding new files.
        $this->modifyServices($io);

        // Return a successful output.
        $io->success('GraphQL sync complete!');
        return Command::SUCCESS;
    }

    private function syncSchema(SchemaDiff $diff, StyleInterface $io)
    {
        foreach ($diff->getTypeDiffs() as $type_diff) {
            // Don't worry about types with no difference.
            if (!$type_diff->isDifferent()) {
                continue;
            }

            // Type is missing from the schema.
            if ($type_diff->getToName() === null) {
                $io->warning(sprintf('"%s" type is present in code, but not in the schema!', $type_diff->getFromName()));
                continue;
            }

            // Report a difference found.
            $io->section(sprintf('"%s" Type', $type_diff->getToName()));

            // Determine if this is a controller or an entity.
            if (in_array($type_diff->getToName(), ['Query', 'Mutation', 'Subscription'])) {
                $this->syncControllerType($type_diff, $io);
            } else {
                $this->syncEntityType($type_diff, $io);
            }
        }
    }

    private function syncControllerType(TypeDiff $diff, StyleInterface $io): void
    {
        foreach ($diff->getFieldDiffs() as $field_diff) {
            // Skip fields that are the same.
            if (!$field_diff->isDifferent()) {
                continue;
            }

            // Report difference found.
            $io->text(sprintf('"%s" field', $field_diff->getToName()));

            // The field does not exist in code.
            if (null === $field_diff->getFromName()) {
                // Determine which controller to add to.
                $controller_name = $io->choice('Which controller should we add this field to?', array_merge(['new'], $this->controllers));

                // Generate a new controller for this field.
                if ($controller_name === 'new') {
                    $file = $this->generateController($field_diff, $io);
                } else {
                    $file = $this->loadFile($controller_name);
                }

                // Generate the method on the controller.
                $method = $this->generateControllerField($file, $field_diff, $diff, $io);
            } else {
                $file = $this->loadFile($this->controllers[$diff->getFromName()]);
                $classes = $file->getClasses();
                $class = array_pop($classes);
                assert($class instanceof ClassType);
                $method = $class->getMethod($field_diff->getFromName());
            }

            // Sync arguments for this method.
            $this->syncArguments($field_diff, $method, $io);
        }
    }

    private function syncEntityType(TypeDiff $diff, StyleInterface $io): void
    {
        // This type does not exist in the system at all.
        if ($diff->getFromName() === null) {
            if ($io->confirm(sprintf('The "%s" type is missing, generate an entity?', $diff->getToName()))) {
                $file = $this->generateEntity($diff, $io);
            }
        } else {
            $metadata = $this->metadata_provider->getTypeMetadata($diff->getToName());
            $file = $this->loadFile($metadata->getClassName());
        }

        // Sync the fields for this entity.
        foreach ($diff->getFieldDiffs() as $field_diff) {
            $this->syncEntityField($field_diff, $file);
        }
    }

    private function generateEntity(TypeDiff $diff, StyleInterface $io): PhpFile
    {
        $class_name = $io->ask('What should the class name be?', default: $diff->getToName());

        // Build the file, namespace and class.
        $file = $this->loadFile($this->entity_namespace . '\\' . $class_name);
        $namespace = $file->addNamespace($this->entity_namespace);
        $class = $namespace->addClass($class_name);

        // Declare strict types.
        $file->setStrictTypes();

        // Add the correct use statements to the class.
        $namespace->addUse(GraphQL::class, 'GraphQL');
        $namespace->addUse(ORM::class, 'ORM');

        // Add the attributes to the class.
        $class->addAttribute(GraphQL\ObjectType::class, ['name' => $diff->getToName()]);
        $class->addAttribute(ORM\Entity::class);

        // Store the type map locally.
        $this->type_map[$diff->getToName()] = $namespace->getName() . '\\' . $class->getName();
        $this->new_entities[] = $namespace->getName() . '\\' . $class->getName();

        // Return the entity file.
        return $file;
    }

    private function generateController(FieldDiff $diff, StyleInterface $io): PhpFile
    {
        // Generate the class.
        $class_name = $io->ask('What is the class for this controller?', default: ucfirst($diff->getToName()) . 'Controller');
        $file = $this->loadFile($this->controller_namespace . '\\' . $class_name);
        $namespace = $file->addNamespace($this->controller_namespace);
        $class = $namespace->addClass($class_name);

        // Set strict types.
        $file->setStrictTypes();

        // Store the controller locally.
        $this->controllers[] = $namespace->getName() . '\\' . $class->getName();
        $this->new_controllers[] = $namespace->getName() . '\\' . $class->getName();

        return $file;
    }

    private function generateControllerField(
        PhpFile $file,
        FieldDiff $diff,
        TypeDiff $type_diff,
        StyleInterface $io
    ): Method {
        // Get the namespace and class.
        $namespace = $file->getNamespaces()[$this->controller_namespace];
        $classes = $file->getClasses();
        $class = array_pop($classes);

        // Get some user input.
        $io->text(sprintf('Generating controller method for "%s".', $diff->getToName()));
        $method = $class->addMethod($io->ask('What should the method be called?', default: $diff->getToName()));

        // Add the correct use to the namespace.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Build the return type.
        $return_type = $diff->getTypeDiff()->getToNonNull() ? '' : '?';
        if ($diff->getTypeDiff()->getToList()) {
            $return_type .= 'array';
        } elseif (array_key_exists($diff->getTypeDiff()->getToName(), $this->type_map)) {
            $return_type .= $this->type_map[$diff->getTypeDiff()->getToName()];
            $namespace->addUse($this->type_map[$diff->getTypeDiff()->getToName()]);
        } else {
            $return_type .= $this->metadata_provider->getTypeMetadata($diff->getTypeDiff()->getToName())->getClassName();
        }
        $method->setReturnType($return_type);
        $method->setBody('// TODO: Controller logic goes here.');

        // Annotate the method appropriately.
        $method->addAttribute(GraphQL\ObjectField::class);
        $method->addAttribute("GraphQL\\{$type_diff->getToName()}");

        // Return the method.
        return $method;
    }

    private function syncEntityField(FieldDiff $diff, PhpFile $file): void
    {
        $classes = $file->getClasses();
        $class = array_pop($classes);
        $namespace = $file->getNamespaces()[$this->entity_namespace];
        assert($class instanceof ClassType);

        // Create the property.
        if ($diff->getFromName() === null) {
            $property = $class->addProperty($diff->getToName())->setPrivate();
        } else {
            $property = $class->getProperty($diff->getFromName());
        }

        // Set the property type appropriately.
        $type_diff = $diff->getTypeDiff();
        if ($type_diff->getToList()) {
            $namespace->addUse(Collection::class);
            $property->setType(Collection::class);
        }
    }

    private function syncArguments(FieldDiff $diff, Method $method, StyleInterface $io): void
    {
        foreach ($diff->getArgumentDiffs() as $arg_diff) {
            if (!$arg_diff->isDifferent()) {
                continue;
            }

            $io->text(sprintf('Syncing argument "%".', $arg_diff->getToName()));

            // Create the argument.
            if (null === $arg_diff->getFromName()) {
                $io->text('Argument is missing from schema.');
                $arg = $method->addParameter(
                    $io->ask('What should the argument be called?', default: $arg_diff->getFromName())
                );
                $arg->addAttribute(GraphQL\Argument::class, ['name' => $arg->getName()]);
            } else {
                $arg = $method->getParameter($arg_diff->getFromName());
            }

            // Modify the argument according to the differences.
            $arg->setType($this->type_map[$arg_diff->getToName()]);
        }
    }

    private function loadFile(string $class_name): PhpFile
    {
        // Determine directory to look for the file in.
        if (stristr($class_name, $this->entity_namespace)) {
            $dir = $this->entity_dir;
        } elseif (stristr($class_name, $this->controller_namespace)) {
            $dir = $this->controller_dir;
        } else {
            throw new LogicException('Can\'t determine where to load the file from.');
        }

        // Construct the filename.
        $filename = $dir . DIRECTORY_SEPARATOR . substr($class_name, strrpos($class_name, '\\') + 1) . '.php';

        // Check for the file in our cache.
        if (array_key_exists($filename, $this->files)) {
            return $this->files[$filename];
        }

        // Load the php file from disk.
        if (file_exists($filename)) {
            $this->files[$filename] = PhpFile::fromCode(file_get_contents($filename));
        } else {
            $this->files[$filename] = new PhpFile();
        }

        return $this->files[$filename];
    }

    private function saveFiles(): void
    {
        foreach ($this->files as $filename => $file) {
            file_put_contents($filename, $this->printer->printFile($file));
        }
    }

    private function modifyServices(StyleInterface $io): void
    {
        if (count($this->new_controllers) == 0 && count($this->new_entities) == 0) {
            return;
        }

        if (!$io->confirm('Should the new entities and controllers be added to your services file?')) {
            return;
        }

        // Create reusable components.
        $parser = (new ParserFactory())->createForHostVersion();
        $printer = new ServicesPrinter();
        $code = file_get_contents($this->services_file);

        // Handle new controllers.
        if (count($this->new_controllers) > 0) {
            // Build the finder and traverser.
            $finder = new FirstFindingVisitor(function (Node $node) {
                if ($node instanceof ArrayItem) {
                    if ($node->key instanceof String_ && $node->key->value === 'graphql.controllers') {
                        return true;
                    }
                }
            });
            $traverser = new NodeTraverser();
            $traverser->addVisitor($finder);

            // Parse the code and extract the node to modify.
            $stmts = $parser->parse($code);
            $traverser->traverse($stmts);
            $node = $finder->getFoundNode();

            // Add the new controllers to the node.
            foreach ($this->new_controllers as $controller) {
                $node->value->args[0]->value->items[] = new ArrayItem(
                    new ClassConstFetch(
                        new FullyQualified($controller),
                        'class'
                    )
                );
            }

            // Modify the code.
            $code = substr($code, 0, $node->getAttribute('startFilePos'))
                . $printer->prettyPrintExpr($node)
                . substr($code, $node->getAttribute('endFilePos') + 1);
        }

        // Handle new entities.
        if (count($this->new_entities) > 0) {
            // Build the finder and traverser.
            $finder = new FirstFindingVisitor(function (Node $node) {
                if ($node instanceof ArrayItem) {
                    if ($node->key instanceof String_ && $node->key->value === 'graphql.types') {
                        return true;
                    }
                }
            });
            $traverser = new NodeTraverser();
            $traverser->addVisitor($finder);

            // Parse the code and extract the node to modify.
            $stmts = $parser->parse($code);
            $traverser->traverse($stmts);
            $node = $finder->getFoundNode();

            // Add the new entities to the node.
            foreach ($this->new_entities as $entity) {
                $node->value->args[0]->value->items[] = new ArrayItem(
                    new ClassConstFetch(
                        new FullyQualified($entity),
                        'class'
                    )
                );
            }

            // Modify the code.
            $code = substr($code, 0, $node->getAttribute('startFilePos'))
                . $printer->prettyPrintExpr($node)
                . substr($code, $node->getAttribute('endFilePos') + 1);
        }

        // Write out the services file.
        file_put_contents($this->services_file, $code);

        // Flush the caches.
        $io->caution('Changes made, flushing caches!');
        $this->cache->clear();
    }
}
