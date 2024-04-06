<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Command;

use ForestCityLabs\Framework\GraphQL\Diff\SchemaComparator;
use ForestCityLabs\Framework\GraphQL\Diff\SchemaDiff;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ClassDiscoveryInterface;
use ForestCityLabs\Framework\Utility\CodeGenerator;
use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLCodeManager;
use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLFile;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType as PhpGeneratorEnumType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\Property;
use Psr\Cache\CacheItemPoolInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GraphQLGenerateFromSchema extends Command
{
    private GraphQLCodeManager $manager;

    public function __construct(
        private string $schema_file,
        private string $services_file,
        private string $entity_dir,
        private string $entity_namespace,
        private ClassDiscoveryInterface $entity_discovery,
        private string $controller_dir,
        private string $controller_namespace,
        private ClassDiscoveryInterface $controller_discovery,
        private Printer $printer,
        private MetadataProvider $metadata_provider,
        private Schema $schema,
        private CacheItemPoolInterface $cache
    ) {
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
        $new = BuildSchema::build(
            Parser::parse(file_get_contents($this->schema_file))
        );

        // The current schema is found in code.
        $old = $this->schema;

        // Diff the schemas.
        $diff = SchemaComparator::compareSchemas($old, $new);

        // If the schemas are identical report as such.
        if (!$diff->isDifferent()) {
            $io->success('GraphQL schema is in sync with code!');
            return Command::SUCCESS;
        }

        // Initialize the code manager.
        $this->manager = new GraphQLCodeManager($this->entity_discovery, $this->controller_discovery);
        $this->manager->initialize();

        // Create new types.
        $this->createNewTypes($diff, $io);

        // Create new controller methods.
        $this->createNewControllerMethods($diff, $io);

        // Save the changes made to the code.
        $this->saveFiles();

        // Return a successful output.
        $io->success('GraphQL sync complete!');
        return Command::SUCCESS;
    }

    private function createNewTypes(SchemaDiff $diff, StyleInterface $io): void
    {
        // Create all the new interface types.
        foreach ($diff->getNewInterfaces() as $interface) {
            $this->createInterfaceType($interface);
        }

        // Create all the new object types.
        foreach ($diff->getNewTypes() as $type) {
            // Skip controller types.
            if (in_array($type->name(), ['Query', 'Mutation', 'Subscription'])) {
                continue;
            }

            // Create the new object.
            $this->createObjectType($type);
        }

        // Create all new enum types.
        foreach ($diff->getNewEnums() as $enum) {
            $this->createEnumType($enum);
        }

        // Create all new inputs.
        foreach ($diff->getNewInputs() as $input) {
            $choices = ['Create new'];
            foreach ($this->manager->getTypes() as $file) {
                $choices[] = $file->getClassLike()->getName();
            }

            // Determine how to proceed.
            $choice = $io->choice(sprintf('Create new input for "%s" or map to existing class?', $input->name()), $choices);

            // Create a new file.
            $args = [];
            if ($choice === 'Create new') {
                $class = $this->createInputType($input);
            } else {
                $file = $this->manager->getType($choice);
                $class = $file->getClassLike();
                $args['name'] = $class->getName() . 'Input';
                $this->manager->addType($args['name'], $file);
            }

            $class->addAttribute(GraphQL\InputType::class, $args);
        }

        // Now that types are created we can create the fields to go with them.
        foreach ($diff->getNewInterfaces() as $interface) {
            $class = $this->type_map[$interface->name]['class'];
            foreach ($interface->getFields() as $field) {
                assert($field instanceof FieldDefinition);
                if (count($field->args) > 0) {
                    $this->addMethodField($class, $field);
                } else {
                    $this->addPropertyField($class, $field);
                }
            }
        }
        foreach ($diff->getNewTypes() as $type) {
            if (in_array($type->name, ['Query', 'Mutation', 'Subscription'])) {
                continue;
            }
            $info = $this->manager->getType($type->name);
            foreach ($type->getFields() as $field) {
                assert($field instanceof FieldDefinition);
                if (count($field->args) > 0) {
                    $this->addMethodField($info, $field);
                } else {
                    $this->addPropertyField($info, $field);
                }
            }
        }
        foreach ($diff->getNewInputs() as $input) {
            foreach ($input->getFields() as $field) {
                $this->addPropertyArgument($input, $field);
            }
        }
    }

    private function createObjectType(ObjectType $type): ClassType
    {
        // Create the new file.
        $file = new PhpFile();

        // Add namespace and use statements.
        $namespace = $file->addNamespace(new PhpNamespace($this->entity_namespace));
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Create the class in that file.
        $class = $namespace->addClass($type->name());
        $class->addAttribute(GraphQL\ObjectType::class);

        // Add the file to the file collection.
        $this->manager->addType($type->name, new GraphQLFile($this->entity_dir . $class->getName() . '.php', $file, $namespace, $class));
        return $class;
    }

    private function createInterfaceType(InterfaceType $interface): ClassType
    {
        $file = new PhpFile();

        $namespace = $file->addNamespace(new PhpNamespace($this->entity_namespace));
        $namespace->addUse(GraphQL::class, 'GraphQL');

        $class = $namespace->addClass($interface->name());
        $class->setAbstract(true);
        $class->addAttribute(GraphQL\InterfaceType::class);

        $this->manager->addType($interface->name, new GraphQLFile($this->entity_dir . $class->getName() . '.php', $file, $namespace, $class));
        return $class;
    }

    private function createEnumType(EnumType $enum): PhpGeneratorEnumType
    {
        $file = new PhpFile();

        $namespace = $file->addNamespace(new PhpNamespace($this->entity_namespace));
        $namespace->addUse(GraphQL::class, 'GraphQL');

        $php_enum = $namespace->addEnum($enum->name());
        $php_enum->addAttribute(GraphQL\EnumType::class);

        // Add enum values based on passed in values.
        foreach ($enum->getValues() as $value) {
            $case = $php_enum->addCase($value->name, $value->value);
            $case->addAttribute(GraphQL\Value::class);
        }

        // Add the file to the collection.
        $this->manager->addType($enum->name, new GraphQLFile($this->entity_dir . $php_enum->getName() . '.php', $file, $namespace, $php_enum));
        return $php_enum;
    }

    private function createInputType(InputType $input): ClassType
    {
        // Create the new file.
        $file = new PhpFile();

        // Add namespace and use statements.
        $namespace = $file->addNamespace(new PhpNamespace($this->entity_namespace));
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Create the class in that file.
        $class = $namespace->addClass($input->name);
        $class->addAttribute(GraphQL\InputType::class);

        $this->manager->addType($input->name, new GraphQLFile($this->entity_dir . $class->getName() . '.php', $file, $namespace, $class));
        return $class;
    }

    private function addPropertyField(GraphQLFile $info, FieldDefinition $field): Property
    {
        $class = $info->getClass();
        $args = [];

        // Create the property if needed.
        if (!$class->hasProperty($field->name)) {
            $property = $class->addProperty($field->name);
        } else {
            $property = $class->getProperty($field->name);
        }

        $property->setVisibility('protected');

        // Is the property not null?
        $not_null = $field->getType() instanceof NonNull;
        $list_of = false;

        // Unwrap the type.
        $field_type = $field->getType();
        while ($field_type instanceof WrappingType) {
            if ($field_type instanceof ListOfType) {
                $list_of = true;
            }
            $field_type = $field_type->getWrappedType();
        }

        // If the type is a list, the type of the property is an array.
        if ($list_of) {
            $property->setType('array');
            $args['type'] = $field_type->name;
        } else {
            $property->setType($this->mapType($field_type));
        }

        // If this is an ID property we should add the use to the namespace.
        if ($property->getType() === UuidInterface::class) {
            $info->getNamespace()->addUse(UuidInterface::class);
        }

        // If this is not null annotate it as such.
        $property->setNullable(!$not_null);

        // Create attribute.
        $property->addAttribute(GraphQL\Field::class, $args);

        // Add appropriate getters and setters.
        CodeGenerator::addGetter($class, $property);
        if ($list_of) {
            CodeGenerator::addHasser($class, $property);
        }

        return $property;
    }

    private function addMethodField(GraphQLFile $info, FieldDefinition $field): Method
    {
        $class = $info->getClass();
        $info->getNamespace()->addUse(GraphQL::class, 'GraphQL');
        $method = $class->addMethod($field->getName());

        $args = [];

        // Is the property not null?
        $not_null = $field->getType() instanceof NonNull;
        $list_of = false;

        // Unwrap the type.
        $field_type = $field->getType();
        while ($field_type instanceof WrappingType) {
            if ($field_type instanceof ListOfType) {
                $list_of = true;
            }
            $field_type = $field_type->getWrappedType();
        }

        // If the type is a list, the type of the property is an array.
        if ($list_of) {
            $method->setReturnType('array');
            $args['type'] = $field_type->name;
        } else {
            $type = $this->mapType($field_type);
            if (str_contains($type, '\\')) {
                $info->getNamespace()->addUse($type);
            }
            $method->setReturnType($this->mapType($field_type));
        }
        $method->setReturnNullable(!$not_null);
        $method->addAttribute(GraphQL\Field::class, $args);

        foreach ($field->args as $arg) {
            $this->addParameterArgument($info, $method, $arg);
        }

        return $method;
    }

    private function addPropertyArgument(InputType $input, InputObjectField $field): Property
    {
        $class = $this->manager->getType($input->name)->getClass();
        $args = [];

        // Create the property if needed.
        if (!$class->hasProperty($field->name)) {
            $property = $class->addProperty($field->name);
        } else {
            $property = $class->getProperty($field->name);
        }

        $property->setVisibility('protected');

        // Is the property not null?
        $not_null = $field->getType() instanceof NonNull;
        $list_of = false;

        // Unwrap the type.
        $field_type = $field->getType();
        while ($field_type instanceof WrappingType) {
            if ($field_type instanceof ListOfType) {
                $list_of = true;
            }
            $field_type = $field_type->getWrappedType();
        }

        // If the type is a list, the type of the property is an array.
        if ($list_of) {
            $property->setType('array');
            $args['type'] = $field_type->name;
        } else {
            $property->setType($this->mapType($field_type));
        }

        // If this is an ID property we should add the use to the namespace.
        if ($property->getType() === UuidInterface::class) {
            $namespace = $this->type_map[$input->name]['file']->getNamespaces()[$this->entity_namespace];
            $namespace->addUse(UuidInterface::class);
        }

        // If this is not null annotate it as such.
        $property->setNullable(!$not_null);

        // Create attribute.
        $property->addAttribute(GraphQL\Argument::class, $args);

        // Add appropriate getters and setters.
        CodeGenerator::addSetter($class, $property);
        if ($list_of) {
            CodeGenerator::addAdder($class, $property);
            CodeGenerator::addRemover($class, $property);
        }

        return $property;
    }

    private function addParameterArgument(GraphQLFile $info, Method $method, Argument $argument): Parameter
    {
        $args = [];
        $parameter = $method->addParameter($argument->name);
        $info->getNamespace()->addUse(GraphQL::class, 'GraphQL');

        // Is the property not null?
        $not_null = $argument->getType() instanceof NonNull;
        $list_of = false;

        // Unwrap the type.
        $argument_type = $argument->getType();
        while ($argument_type instanceof WrappingType) {
            if ($argument_type instanceof ListOfType) {
                $list_of = true;
            }
            $argument_type = $argument_type->getWrappedType();
        }
        $argument_type = Type::getNamedType($argument_type);

        if ($list_of) {
            $parameter->setType('array');
            $args['type'] = $argument_type->name;
        } else {
            $parameter->setType($this->mapType($argument_type));
        }

        $parameter->setNullable(!$not_null);

        $parameter->addAttribute(GraphQL\Argument::class, $args);
        return $parameter;
    }

    private function createNewControllerMethods(SchemaDiff $diff, StyleInterface $io): void
    {
        // Check for new query and mutation types.
        foreach ($diff->getNewTypes() as $type) {
            if (!in_array($type->name, ['Query', 'Mutation'])) {
                continue;
            }
            foreach ($type->getFields() as $field) {
                $choices = ['Create new'];
                foreach ($this->manager->getControllers() as $info) {
                    $choices[] = $info->getClass()->getName();
                }
                $choice = $io->choice(sprintf('The "%s" field "%s" is new, create a new controller or add to an existing controller?', $type->name, $field->name), $choices);

                if ($choice === 'Create new') {
                    $name = $io->ask('What is the name of the class (without the namespace)?');
                    $file = new PhpFile();
                    $namespace = $file->addNamespace($this->controller_namespace);
                    $class = $namespace->addClass($name);
                    $filename = $this->controller_dir . $class->getName() . '.php';
                    $this->manager->addController($class->getName(), new GraphQLFile($filename, $file, $namespace, $class));
                } else {
                    $class = $this->manager->getController($choice)->getClass();
                }

                $this->addMethodField($this->manager->getController($class->getName()), $field);
            }
        }
    }

    private function mapType(Type $type): string
    {
        if ($type instanceof ScalarType) {
            switch ($type::class) {
                case IDType::class:
                    return UuidInterface::class;
                case StringType::class:
                    return 'string';
                case IntType::class:
                    return 'int';
                case BooleanType::class:
                    return 'bool';
                case FloatType::class:
                    return 'float';
            }
        }

        $file = $this->manager->getType(Type::getNamedType($type)->name);
        return $file->getNamespace()->getName() . '\\' . $file->getClassLike()->getName();
    }

    private function saveFiles(): void
    {
        foreach ($this->manager->getTypes() as $type) {
            file_put_contents($type->getFilename(), $this->printer->printFile($type->getFile()));
        }

        foreach ($this->manager->getControllers() as $controller) {
            file_put_contents($controller->getFilename(), $this->printer->printFile($controller->getFile()));
        }
    }
}
