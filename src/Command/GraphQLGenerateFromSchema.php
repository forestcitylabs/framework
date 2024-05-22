<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Command;

use Doctrine\Inflector\InflectorFactory;
use Doctrine\Inflector\Language;
use ForestCityLabs\Framework\GraphQL\Diff\SchemaComparator;
use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use ForestCityLabs\Framework\GraphQL\Diff\ArgumentDiff;
use ForestCityLabs\Framework\GraphQL\Diff\EnumTypeDiff;
use ForestCityLabs\Framework\GraphQL\Diff\FieldDiff;
use ForestCityLabs\Framework\GraphQL\Diff\InputFieldDiff;
use ForestCityLabs\Framework\GraphQL\Diff\InputObjectTypeDiff;
use ForestCityLabs\Framework\GraphQL\Diff\InterfaceTypeDiff;
use ForestCityLabs\Framework\GraphQL\Diff\ObjectTypeDiff;
use ForestCityLabs\Framework\GraphQL\Diff\TypeDiff;
use ForestCityLabs\Framework\GraphQL\MetadataProvider;
use ForestCityLabs\Framework\Utility\ClassDiscovery\ClassDiscoveryInterface;
use ForestCityLabs\Framework\Utility\CodeGenerator;
use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLCodeHelper;
use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLCodeManager;
use ForestCityLabs\Framework\Utility\CodeGenerator\GraphQLFile;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use LogicException;
use Nette\PhpGenerator\Attribute;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType as PhpGeneratorEnumType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
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

        // Remove dropped types.
        foreach ($diff->getDroppedTypes() as $type) {
            if (in_array($type->name, ['Query', 'Mutation', 'Subscription'])) {
                $this->removeDroppedControllerType($type, $io);
            } else {
                $this->removeDroppedType($type, $io);
            }
        }

        // Create new types.
        foreach ($diff->getNewTypes() as $type) {
            if (in_array($type->name, ['Query', 'Mutation', 'Subscription'])) {
                $this->createNewControllerType($type, $io);
            } else {
                $this->ensureType($type, $io);
            }
        }

        // Update altered types.
        foreach ($diff->getAlteredTypes() as $diff) {
            if (in_array($diff->getOldType()->name, ['Query', 'Mutation', 'Subscription'])) {
                $this->updateAlteredControllerType($diff, $io);
            } else {
                $this->updateAlteredType($diff, $io);
            }
        }

        // Confirm persistence of files.
        if (!$io->confirm('Are you sure you would like to persist the changes?')) {
            $io->error('Changes aborted!');
            return Command::FAILURE;
        }

        // Save the changes made to the code.
        $this->saveFiles();

        // Return a successful output.
        $io->success('GraphQL sync complete!');
        return Command::SUCCESS;
    }

    private function createNewObjectType(ObjectType $type, StyleInterface $io): void
    {
        // Determine whether or not to map this to an existing class.
        $choices = array_values(['Create new'] + array_map(fn (GraphQLFile $info): string => $info->getFullName(), $this->manager->getUnmappedTypes(GraphQL\ObjectType::class)));
        if (count($choices) > 1 && 'Create new' !== $choice = $io->choice(sprintf('Create a new class for object type "%s" or map to existing class?', $type->name), $choices, $this->inferDefaultTypeChoice($choices, $type->name))) {
            $info = $this->manager->getTypeByClass($choice);
        } else {
            $file = new PhpFile();
            $namespace = $file->addNamespace($this->entity_namespace);
            $class = $namespace->addClass($type->name);
            $info = new GraphQLFile($this->entity_dir . $class->getName() . '.php', $file, $namespace, $class);
        }

        // Build the type attribute.
        $class = GraphQLCodeHelper::buildObjectType($info->getNamespace(), $info->getClass(), $type);

        // Implement interfaces.
        foreach ($type->getInterfaces() as $interface) {
            $class->setExtends($this->mapType($interface));
        }
        $this->manager->addType($info);

        // Add fields for this type.
        foreach ($type->getFields() as $field) {
            $this->createNewField($field, $info, $io);
        }
    }

    private function createNewInterfaceType(InterfaceType $type, StyleInterface $io): void
    {
        // Determine whether or not to map this to an existing class.
        $choices = array_values(['Create new'] + array_map(fn (GraphQLFile $info): string => $info->getFullName(), $this->manager->getUnmappedTypes(GraphQL\InterfaceType::class)));
        if (count($choices) > 1 && 'Create new' !== $choice = $io->choice(sprintf('Create a new class for interface type "%s" or map to existing class?', $type->name), $choices, $this->inferDefaultTypeChoice($choices, $type->name))) {
            $info = $this->manager->getTypeByClass($choice);
        } else {
            $file = new PhpFile();
            $namespace = $file->addNamespace($this->entity_namespace);
            $class = $namespace->addClass($type->name);
            $class->setAbstract();
            $info = new GraphQLFile($this->entity_dir . $class->getName() . '.php', $file, $namespace, $class);
        }

        // Build the type attribute.
        GraphQLCodeHelper::buildInterfaceType($info->getNamespace(), $info->getClass(), $type);
        $this->manager->addType($info);

        // Add fields for this type.
        foreach ($type->getFields() as $field) {
            $this->createNewField($field, $info, $io);
        }
    }

    private function createNewInputType(InputObjectType $type, StyleInterface $io): void
    {
        // Determine whether or not to map this to an existing class.
        $choices = array_values(['Create new'] + array_map(fn (GraphQLFile $info): string => $info->getFullName(), $this->manager->getUnmappedTypes(GraphQL\InputType::class)));
        if (count($choices) > 1 && 'Create new' !== $choice = $io->choice(sprintf('Create a new class for input type "%s" or map to existing class?', $type->name), $choices, $this->inferDefaultTypeChoice($choices, $type->name))) {
            $info = $this->manager->getTypeByClass($choice);
        } else {
            $file = new PhpFile();
            $namespace = $file->addNamespace($this->entity_namespace);
            $class = $namespace->addClass($type->name);
            $info = new GraphQLFile($this->entity_dir . $class->getName() . '.php', $file, $namespace, $class);
        }

        // Build the type attribute.
        GraphQLCodeHelper::buildInputType($info->getNamespace(), $info->getClass(), $type);
        $this->manager->addType($info);

        // Add fields for this type.
        foreach ($type->getFields() as $field) {
            $this->createNewInputField($field, $info, $io);
        }
    }

    private function createNewEnumType(EnumType $type, StyleInterface $io): void
    {
        // Determine whether or not to map this to an existing class.
        $choices = array_values(['Create new'] + array_map(fn (GraphQLFile $info): string => $info->getFullName(), $this->manager->getUnmappedTypes(GraphQL\EnumType::class, PhpGeneratorEnumType::class)));
        if (count($choices) > 1 && 'Create new' !== $choice = $io->choice(sprintf('Create a new class for enum type "%s" or map to existing class?', $type->name), $choices, $this->inferDefaultTypeChoice($choices, $type->name))) {
            $info = $this->manager->getTypeByClass($choice);
        } else {
            $file = new PhpFile();
            $namespace = $file->addNamespace($this->entity_namespace);
            $class = $namespace->addEnum($type->name);
            $info = new GraphQLFile($this->entity_dir . $class->getName() . '.php', $file, $namespace, $class);
        }

        // Build the type attribute.
        GraphQLCodeHelper::buildEnumType($info->getNamespace(), $info->getEnum(), $type);
        $this->manager->addType($info);

        // Add values for this type.
        foreach ($type->getValues() as $value) {
            $this->createNewValue($value, $info, $io);
        }
    }

    private function createNewType(NamedType $type, StyleInterface $io): void
    {
        $io->section(sprintf('Creating type "%s"', $type->name));

        // Determine the annotation and type name.
        switch ($type::class) {
            case InterfaceType::class:
                $this->createNewInterfaceType($type, $io);
                break;
            case ObjectType::class:
                $this->createNewObjectType($type, $io);
                break;
            case InputObjectType::class:
                $this->createNewInputType($type, $io);
                break;
            case EnumType::class:
                $this->createNewEnumType($type, $io);
                break;
            default:
                throw new LogicException(sprintf('Cannot map type "%s"!', $type::class));
        }

        $io->text(sprintf('Type "%s" created!', $type->name));
    }

    private function updateAlteredType(TypeDiff $diff, StyleInterface $io): void
    {
        // Get the info for the type.
        $info = $this->manager->getType($diff->getOldType()->name);

        // Alter the type attribute.
        GraphQLCodeHelper::updateType($info->getClassLike(), $diff->getNewType());

        // Update sub-types.
        switch ($diff::class) {
            case ObjectTypeDiff::class:
            case InterfaceTypeDiff::class:
                assert($diff instanceof ObjectTypeDiff || $diff instanceof InterfaceTypeDiff);

                // Remove dropped fields.
                foreach ($diff->getDroppedFields() as $field) {
                    $this->removeDroppedField($field, $info, $io);
                }

                // Create new fields.
                foreach ($diff->getNewFields() as $field) {
                    $this->createNewField($field, $info, $io);
                }

                // Update altered fields.
                foreach ($diff->getAlteredFields() as $diff) {
                    $this->updateAlteredField($diff, $info, $io);
                }
                break;
            case InputObjectTypeDiff::class:
                assert($diff instanceof InputObjectTypeDiff);

                // Remove dropped fields.
                foreach ($diff->getDroppedFields() as $field) {
                    $this->removeDroppedInputField($field, $info, $io);
                }

                // Create new fields.
                foreach ($diff->getNewFields() as $field) {
                    $this->createNewInputField($field, $info, $io);
                }

                // Update altered fields.
                foreach ($diff->getAlteredFields() as $diff) {
                    $this->updateAlteredInputField($diff, $info, $io);
                }
                break;
            case EnumTypeDiff::class:
                assert($diff instanceof EnumTypeDiff);

                // Remove dropped values.
                foreach ($diff->getDroppedValues() as $value) {
                    $this->removeDroppedValue($value, $info, $io);
                }

                // Create new values.
                foreach ($diff->getNewValues() as $value) {
                    $this->createNewValue($value, $info, $io);
                }

                // Update altered values.
                foreach ($diff->getAlteredValues() as $diff) {
                    $this->updateAlteredValue($diff, $info, $io);
                }
                break;
        }
    }

    private function removeDroppedType(NamedType $type, StyleInterface $io): void
    {
        $info = $this->manager->getType($type->name);
        $class = $info->getClassLike();

        $io->text(sprintf('Removing dropped type "%s".', $type->name));

        // Remove the entire type.
        if (count($class->getAttributes()) === 1 && $io->confirm(sprintf('Remove the entire class "%s"?', $class->getName()), false)) {
            $this->manager->removeType($type->name);

        // Remove just the attribute.
        } else {
            $attr_type = null;
            switch ($type::class) {
                case InterfaceType::class:
                    $attr_type = GraphQL\InterfaceType::class;
                    break;
                case ObjectType::class:
                    $attr_type = GraphQL\ObjectType::class;
                    break;
                case EnumType::class:
                    $attr_type = GraphQL\EnumType::class;
                    break;
                case InputObjectType::class:
                    $attr_type = GraphQL\InputType::class;
                    break;
            }
            $class->setAttributes(array_filter($class->getAttributes(), function (Attribute $attr) use ($class, $type, $attr_type): bool {
                $name = $attr->getArguments()['name'] ?? $class->getName();
                if ($attr->getName() === $attr_type && $name === $type->name) {
                    return false;
                } else {
                    return true;
                }
            }));
        }
    }

    private function createNewControllerType(ObjectType $type, StyleInterface $io): void
    {
        $io->section(sprintf('Creating type "%s"."', $type->name));

        // Iterate over fields, creating new controller methods.
        foreach ($type->getFields() as $field_definition) {
            $this->createNewControllerField($field_definition, $type, $io);
        }
    }

    private function updateAlteredControllerType(ObjectTypeDiff $diff, StyleInterface $io): void
    {
        // Remove dropped fields.
        foreach ($diff->getDroppedFields() as $field) {
            list($info, ) = $this->manager->getControllerForField($diff->getOldType()->name, $field->name);
            $this->removeDroppedField($field, $info, $io);
        }

        // Create new fields.
        foreach ($diff->getNewFields() as $field) {
            $this->createNewControllerField($field, $diff->getNewType(), $io);
        }

        // Update altered fields.
        foreach ($diff->getAlteredFields() as $field_diff) {
            d($field_diff->getOldField()->name);
            list($info, ) = $this->manager->getControllerForField($diff->getOldType()->name == 'Query' ? GraphQL\Query::class : GraphQL\Mutation::class, $field->name);
            $this->updateAlteredField($field_diff, $info, $io);
        }
    }

    private function removeDroppedControllerType(NamedType $type, StyleInterface $io): void
    {
        // Remove all methods for this type.
        assert($type instanceof HasFieldsType);
        foreach ($type->getFields() as $field) {
            list($info, ) = $this->manager->getControllerForField($type->name, $field->name);
            $this->removeDroppedField($field, $info, $io);
        }
    }

    private function createNewField(FieldDefinition $field, GraphQLFile $info, StyleInterface $io): void
    {
        // Ensure the return type exists before making the field.
        $this->ensureType($field->getType(), $io);

        // Determine the field type to add.
        $type_options = ['Method'];
        if (count($field->args) === 0) {
            $type_options[] = 'Property';
        }

        // Determine type for field.
        if (count($type_options) > 1 && 'Property' === $io->choice(sprintf('What type of field is "%s"?', $field->name), $type_options, 'Property')) {
            $io->text(sprintf('Adding property to class "%s" for field "%s".', $info->getClassLike()->getName(), $field->name));
            $options = ['Create new'] + array_values(array_map(fn(Property $prop): string => $prop->getName(), $this->getUnmappedProperties($info->getClass(), GraphQL\Field::class)));
            if (count($options) > 1 && 'Create new' !== $choice = $io->choice(sprintf('Create a new property for field "%s"?', $field->name), $options, $this->inferDefaultFieldChoice($options, $field->name))) {
                $property = $info->getClass()->getProperty($choice);
                GraphQLCodeHelper::buildPropertyField($info->getNamespace(), $property, $field, $this->mapType($field->getType()));
            } else {
                $property = GraphQLCodeHelper::addPropertyField(
                    $info->getNamespace(),
                    $info->getClass(),
                    $field,
                    $field->name,
                    $this->mapType($field->getType())
                );

                // Add getters and setters.
                CodeGenerator::addGetter($info->getClass(), $property);
                if ($property->getType() == 'array') {
                    CodeGenerator::addHasser($info->getClass(), $property);
                    CodeGenerator::addAdder($info->getClass(), $property);
                    CodeGenerator::addRemover($info->getClass(), $property);
                } else {
                    CodeGenerator::addSetter($info->getClass(), $property);
                }
            }
        } else {
            $io->text(sprintf('Adding method to class "%s" for field "%s".', $info->getClassLike()->getName(), $field->name));
            $method = GraphQLCodeHelper::addMethodField(
                $info->getNamespace(),
                $info->getClass(),
                $field,
                $field->name,
                $this->mapType($field->getType())
            );

            // Add arguments to method.
            foreach ($field->args as $arg) {
                $this->createNewArgument($arg, $method, $info, $io);
            }
        }
    }

    private function updateAlteredField(FieldDiff $diff, GraphQLFile $info, StyleInterface $io): void
    {
        // Find the field.
        $field = $this->findField($diff->getOldField(), $info);

        // Determine whether this is a method of property field.
        if ($field instanceof Method) {
            // Update the method.
            $method = GraphQLCodeHelper::updateMethodField(
                $info->getNamespace(),
                $info->getClass(),
                $diff->getNewField(),
                $this->mapType($diff->getNewField()->getType())
            );

            // Remove unused arguments.
            foreach ($diff->getDroppedArguments() as $arg) {
                $this->removeDroppedArgument($arg, $method, $io);
            }

            // Create new arguments.
            foreach ($diff->getNewArguments() as $arg) {
                $this->createNewArgument($arg, $method, $info, $io);
            }

            // Update altered arguments.
            foreach ($diff->getAlteredArguments() as $diff) {
                $this->updateAlteredArgument($diff, $method, $info, $io);
            }
        } else {
            // Update the property.
            GraphQLCodeHelper::updatePropertyField(
                $info->getNamespace(),
                $info->getClass(),
                $diff->getNewField(),
                $this->mapType($diff->getNewField()->getType())
            );
        }
    }

    private function removeDroppedField(FieldDefinition $field, GraphQLFile $info, StyleInterface $io): void
    {
        $io->text(sprintf('Removing dropped field "%s".', $field->name));
        $php_field = $this->findField($field, $info);

        // Remove the entire parameter.
        if (count($php_field->getAttributes()) === 1 && $io->confirm(sprintf('Remove the entire %s "%s"?', $php_field instanceof Method ? 'method' : 'parameter', $php_field->getName()), false)) {
            if ($php_field instanceof Method) {
                $info->getClass()->removeMethod($php_field->getName());
            } else {
                $info->getClass()->removeProperty($php_field->getName());
            }

        // Remove just the attribute.
        } else {
            $php_field->setAttributes(array_filter($php_field->getAttributes(), function (Attribute $attr) use ($php_field, $field): bool {
                $name = $attr->getArguments()['name'] ?? $php_field->getName();
                if ($attr->getName() === GraphQL\Field::class && $name === $field->name) {
                    return false;
                } else {
                    return true;
                }
            }));
        }
    }

    private function createNewControllerField(FieldDefinition $field, ObjectType $type, StyleInterface $io): void
    {
        // Ensure the field type exists.
        $this->ensureType($field->getType(), $io);

        $io->text(sprintf('Creating field "%s" for controller "%s".', $field->name, $type->name));

        // Build options for controller selection.
        $options = array_values(['Create new'] + array_map(fn(GraphQLFile $info): string => $info->getFullName(), $this->manager->getControllers()));
        $default = $this->inferDefaultControllerChoice($options, $field->name);

        // Determine whether to create a new controller or map to existing controller.
        if (
            count($this->manager->getControllers()) > 0
            && 'Create new' !== $class_name = $io->choice(
                sprintf('Create a new controller for "%s" field "%s" or map to existing controller?', $type->name, $field->name),
                $options,
                $default
            )
        ) {
            $info = $this->manager->getController($class_name);
        } else {
            // Create the new controller.
            $file = new PhpFile();
            $namespace = $file->addNamespace($this->controller_namespace);
            $class = $namespace->addClass($io->ask('What should the new controller be called?', 'GraphQLController'));

            // Add to the manager controllers.
            $info = new GraphQLFile($this->controller_dir . $class->getName() . '.php', $file, $namespace, $class);
            $this->manager->addController($info);
        }

        // Add a method to the controller.
        $method = GraphQLCodeHelper::addMethodField(
            $info->getNamespace(),
            $info->getClass(),
            $field,
            $field->name,
            $this->mapType($field->getType())
        );

        // Add controller annotation.
        switch ($type->name) {
            case 'Query':
                $method->addAttribute(GraphQL\Query::class);
                break;
            case 'Mutation':
                $method->addAttribute(GraphQL\Mutation::class);
                break;
        }

        // Add arguments.
        foreach ($field->args as $arg) {
            $this->createNewArgument($arg, $method, $info, $io);
        }
    }

    private function createNewArgument(Argument $arg, Method $method, GraphQLFile $info, StyleInterface $io): void
    {
        // Ensure the argument type exists.
        $this->ensureType($arg->getType(), $io);
        $io->text(sprintf('Adding parameter to method "%s" for argument "%s".', $method->getName(), $arg->name));
        GraphQLCodeHelper::addParameterArgument(
            $info->getNamespace(),
            $method,
            $arg,
            $arg->name,
            $this->mapType($arg->getType())
        );
    }

    private function updateAlteredArgument(ArgumentDiff $diff, Method $method, GraphQLFile $info, StyleInterface $io): void
    {
        // Update the annotation.
        GraphQLCodeHelper::updateParameterArgument(
            $info->getNamespace(),
            $method,
            $diff->getNewArgument(),
            $this->mapType($diff->getNewArgument()->getType())
        );
    }

    private function removeDroppedArgument(Argument $arg, Method $method, StyleInterface $io): void
    {
        $io->text(sprintf('Removing dropped argument "%s".', $arg->name));
        $parameter = GraphQLCodeHelper::extractArgumentParameter($method, $arg);

        // Remove the entire parameter.
        if (count($parameter->getAttributes()) === 1 && $io->confirm(sprintf('Remove the entire parameter "%s"?', $parameter->getName()), false)) {
            $method->removeParameter($parameter->getName());

        // Remove just the attribute.
        } else {
            $parameter->setAttributes(array_filter($parameter->getAttributes(), function (Attribute $attr) use ($parameter, $arg): bool {
                $name = $attr->getArguments()['name'] ?? $parameter->getName();
                if ($attr->getName() === GraphQL\Argument::class && $name === $arg->name) {
                    return false;
                } else {
                    return true;
                }
            }));
        }
    }

    private function createNewInputField(InputObjectField $field, GraphQLFile $info, StyleInterface $io): void
    {
        // Ensure the type exists.
        $this->ensureType($field->getType(), $io);
        if ($info->getClass()->hasProperty($field->name)) {
            $property = $info->getClass()->getProperty($field->name);
            $property->addAttribute(GraphQL\Argument::class);
        } else {
            $io->text(sprintf('Adding property to class "%s" for field "%s".', $info->getClass()->getName(), $field->name));
            $property = GraphQLCodeHelper::addPropertyArgument(
                $info->getNamespace(),
                $info->getClass(),
                $field,
                $field->name,
                $this->mapType($field->getType())
            );

            // Add getters and setters.
            CodeGenerator::addGetter($info->getClass(), $property);
            if ($property->getType() == 'array') {
                CodeGenerator::addHasser($info->getClass(), $property);
                CodeGenerator::addAdder($info->getClass(), $property);
                CodeGenerator::addRemover($info->getClass(), $property);
            } else {
                CodeGenerator::addSetter($info->getClass(), $property);
            }
        }
    }

    private function updateAlteredInputField(InputFieldDiff $field, GraphQLFile $info, StyleInterface $io): void
    {
        GraphQLCodeHelper::updatePropertyArgument(
            $info->getNamespace(),
            $info->getClass(),
            $field->getNewField(),
            $this->mapType($field->getNewField()->getType()),
        );
    }

    private function removeDroppedInputField(InputObjectField $field, GraphQLFile $info, StyleInterface $io): void
    {
        $io->text(sprintf('Removing dropped input field "%s".', $field->name));
        $property = GraphQLCodeHelper::extractArgumentProperty($info->getClass(), $field);

        // Remove the entire parameter.
        if (count($property->getAttributes()) == 1 && $io->confirm(sprintf('Remove the entire property "%s"?', $property->getName()), false)) {
            $info->getClass()->removeProperty($property->getName());

        // Remove just the attribute.
        } else {
            $property->setAttributes(array_filter($property->getAttributes(), function (Attribute $attr) use ($property, $field): bool {
                $name = $attr->getArguments()['name'] ?? $property->getName();
                if ($attr->getName() === GraphQL\Argument::class && $name === $field->name) {
                    return false;
                } else {
                    return true;
                }
            }));
        }
    }

    private function createNewValue(EnumValueDefinition $value, GraphQLFile $info, StyleInterface $io): void
    {
        // Get the info from the code manager.
        $enum = $info->getEnum();

        // Create a new case.
        $io->note(sprintf('Adding case to enum "%s" for value "%s".', $enum->getName(), $value->name));
        GraphQLCodeHelper::addCaseValue(
            $info->getNamespace(),
            $info->getEnum(),
            $value,
            $value->name
        );
    }

    private function updateAlteredValue(EnumValueDefinition $value, GraphQLFile $info, StyleInterface $io): void
    {
        GraphQLCodeHelper::updateCaseValue($info->getEnum(), $value);
    }

    private function removeDroppedValue(EnumValueDefinition $value, GraphQLFile $info, StyleInterface $io): void
    {
        $io->text(sprintf('Removing dropped enum value "%s".', $value->name));
        $case = GraphQLCodeHelper::extractValueCase($info->getEnum(), $value);

        // Remove the entire case.
        if (count($case->getAttributes()) === 1 && $io->confirm(sprintf('Remove the entire case "%s"?', $case->getName()), false)) {
            $info->getEnum()->removeCase($case->getName());

        // Remove just the attribute.
        } else {
            $case->setAttributes(array_filter($case->getAttributes(), function (Attribute $attr) use ($case, $value): bool {
                $name = $attr->getArguments()['name'] ?? $case->getName();
                if ($attr->getName() === GraphQL\Value::class && $name === $value->name) {
                    return false;
                } else {
                    return true;
                }
            }));
        }
    }

    private function ensureType(Type $type, StyleInterface $io): void
    {
        $type = Type::getNamedType($type);
        if (
            !$type instanceof ScalarType
            && !in_array($type->name, ['Query', 'Mutation', 'Subscription'])
            && null === $this->manager->getType($type->name)
        ) {
            $this->createNewType($type, $io);
        }
    }

    private function mapType(Type $type): string
    {
        $type = Type::getNamedType($type);
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

        $info = $this->manager->getType(Type::getNamedType($type)->name);
        return $info->getNamespace()->getName() . '\\' . $info->getClassLike()->getName();
    }

    private function inferDefaultTypeChoice(array $choices, string $name): string
    {
        $best = str_replace(['Input', 'Type', 'Enum', 'Interface'], '', $name);
        foreach ($choices as $candidate) {
            if (substr($candidate, (strrpos($candidate, '\\') ?: 0) + 1) === $best) {
                return $candidate;
            }
        }
        return 'Create new';
    }

    private function inferDefaultFieldChoice(array $choices, string $name): string
    {
        foreach ($choices as $choice) {
            if ($choice === $name) {
                return $choice;
            }
        }
        return 'Create new';
    }

    private function inferDefaultControllerChoice(array $choices, string $name): string
    {
        preg_match_all('/[A-Z][a-z]+/', $name, $matches);
        $inflector = InflectorFactory::createForLanguage(Language::ENGLISH)->build();
        foreach ($choices as $choice) {
            $candidate = substr($choice, (strrpos($choice, '\\') ?: 0) + 1);
            foreach ($matches[0] as $match) {
                if (stristr($candidate, $inflector->singularize($match))) {
                    return $choice;
                }
            }
        }
        return $choices[1] ?? 'Create new';
    }

    /**
     * @return array<Property>
     */
    private function getUnmappedProperties(ClassType $class, string $attribute_type): array
    {
        return array_filter($class->getProperties(), function (Property $prop) use ($attribute_type): bool {
            foreach ($prop->getAttributes() as $attr) {
                if ($attr->getName() === $attribute_type) {
                    return false;
                }
            }
            return true;
        });
    }

    private function findField(FieldDefinition $field, GraphQLFile $info): Method|Property|null
    {
        // Loop over properties first.
        foreach ($info->getClass()->getProperties() as $prop) {
            foreach ($prop->getAttributes() as $attr) {
                $name = $attr->getArguments()['name'] ?? $prop->getName();
                if ($attr->getName() === GraphQL\Field::class && $field->name === $name) {
                    return $prop;
                }
            }
        }

        // Check methods next.
        foreach ($info->getClass()->getMethods() as $meth) {
            foreach ($meth->getAttributes() as $attr) {
                $name = $attr->getArguments()['name'] ?? $meth->getName();
                if ($attr->getName() === GraphQL\Field::class && $field->name === $name) {
                    return $meth;
                }
            }
        }

        // Return null.
        return null;
    }

    private function saveFiles(): void
    {
        foreach ($this->manager->getRemoved() as $removed) {
            unlink($removed->getFilename());
        }

        foreach ($this->manager->getTypes() as $type) {
            file_put_contents($type->getFilename(), $this->printer->printFile($type->getFile()));
        }

        foreach ($this->manager->getControllers() as $controller) {
            file_put_contents($controller->getFilename(), $this->printer->printFile($controller->getFile()));
        }
    }
}
