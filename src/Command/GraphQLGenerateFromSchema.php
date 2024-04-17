<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Command;

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
use Kint\Zval\EnumValue;
use LogicException;
use Nette\PhpGenerator\ClassType;
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

    public function createNewType(NamedType $type, StyleInterface $io): void
    {
        $io->text(sprintf('Creating type "%s".', $type->name));

        // Determine the annotation and type name.
        switch ($type::class) {
            case InterfaceType::class:
                $type_name = "interface";
                $attribute = GraphQL\InterfaceType::class;
                break;
            case ObjectType::class:
                $type_name = "object";
                $attribute = GraphQL\ObjectType::class;
                break;
            case InputObjectType::class:
                $type_name = "input";
                $attribute = GraphQL\InputType::class;
                break;
            case EnumType::class:
                $type_name = "enum";
                $attribute = GraphQL\EnumType::class;
                break;
            default:
                throw new LogicException(sprintf('Cannot map type "%s"!', $type::class));
        }

        // Ask whether to map input to existing class.
        if (
            $type_name === 'input'
            && count($this->manager->getTypes()) > 0
            && 'Create new' !== $class_name = $io->choice(
                sprintf('Create class for %s "%s" or map to existing class?', $type_name, $type->name),
                array_values(['Create new'] + array_map(fn(GraphQLFile $info): string => $info->getFullName(), $this->manager->getTypes())),
                'Create new',
            )
        ) {
            // Get the existing class.
            $io->text(sprintf('Mapping %s "%s" to class "%s".', $type_name, $type->name, $class_name));
            $info = $this->manager->getTypeByClass($class_name);
            $class = $info->getClassLike();
        } else {
            // Ask what to name the class.
            $io->text(sprintf('Creating new class for %s "%s".', $type_name, $type->name));

            // Create the file and the namespace.
            $file = new PhpFile();
            $namespace = $file->addNamespace($this->entity_namespace);

            // Create the class (or enum).
            if ($type_name === 'enum') {
                $class = $namespace->addEnum($type->name);
            } else {
                $class = $namespace->addClass($type->name);
            }

            // Add the graphql file.
            $info = new GraphQLFile($this->entity_dir . $class->getName() . '.php', $file, $namespace, $class);
            $this->manager->addType($info);
        }

        // If this is an interface mark it as abstract.
        if ($type_name == 'interface') {
            assert($class instanceof ClassType);
            $class->setAbstract();
        }

        // Generate attribute arguments.
        $args = [];
        if ($type->name !== $class->getName()) {
            $args['name'] = $type->name;
        }
        if (!empty($type->description)) {
            $args['description'] = $type->description;
        }

        // Annotate the class.
        $class->addAttribute($attribute, $args);

        // Add sub-types.
        switch ($type::class) {
            case InterfaceType::class:
            case ObjectType::class:
                assert($type instanceof HasFieldsType);
                foreach ($type->getFields() as $field) {
                    $this->createNewField($field, $info, $io);
                }
                break;
            case EnumType::class:
                assert($type instanceof EnumType);
                foreach ($type->getValues() as $value) {
                    $this->createNewValue($value, $info, $io);
                }
                break;
            case InputObjectType::class:
                assert($type instanceof InputObjectType);
                foreach ($type->getFields() as $field) {
                    $this->createNewInputField($field, $info, $io);
                }
                break;
        }
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
        // Get the info for this type.
        $info = $this->manager->getType($type->name);

        if ($io->confirm(sprintf('The type "%s" is no longer in your schema, would you like to remove the class "%s" from the filesystem?', $type->name, $info->getClassLike()->getName()))) {
            unlink($info->getFilename());
            $io->text(sprintf('Class "%s" removed from the filesystem.', $info->getClassLike()->getName()));
        }

        // Remove the type from the manager regardless.
        $this->manager->removeType($type->name);
    }

    private function createNewControllerType(ObjectType $type, StyleInterface $io): void
    {
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
            list($info, ) = $this->manager->getControllerForField($diff->getOldType()->name, $field->name);
            $this->updateAlteredField($field_diff, $info, $io);
        }
    }

    private function removeDroppedControllerType(NamedType $type, StyleInterface $io): void
    {
        // Warn the user about removing controller types.
        $io->warning(sprintf('The controller type "%s" has been removed from your schema!', $type->name));

        // Ask whether to remove all methods for this type.
        if (!$io->confirm(sprintf('Would you like to remove all methods for controller type "%s"?', $type->name))) {
            return;
        }

        // Remove all methods for this type.
        assert($type instanceof HasFieldsType);
        foreach ($type->getFields() as $field) {
            list($info, $method) = $this->manager->getControllerForField($type->name, $field->name);
            assert($info instanceof GraphQLFile);
            $io->text(sprintf('Removing method "%s" from class "%s".', $info->getClass()->getName(), $method->getName()));
            $info->getClass()->removeMethod($method->getName());
        }
    }

    private function createNewField(FieldDefinition $field, GraphQLFile $info, StyleInterface $io): void
    {
        // Ensure the return type exists before making the field.
        $this->ensureType($field->getType(), $io);

        // If the field has arguments add it as a method field.
        if (count($field->args) > 0) {
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
        } else {
            $io->text(sprintf('Adding property to class "%s" for field "%s".', $info->getClassLike()->getName(), $field->name));
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
    }

    private function updateAlteredField(FieldDiff $diff, GraphQLFile $info, StyleInterface $io): void
    {
        // Determine whether this is a method of property field.
        if (count($diff->getNewField()->args) > 0) {
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

        // Determine field type.
        if (count($field->args) > 0) {
            $method = GraphQLCodeHelper::extractFieldMethod($info->getClass(), $field);
            $info->getClass()->removeMethod($method->getName());
        } else {
            $property = GraphQLCodeHelper::extractFieldProperty($info->getClass(), $field);
            $info->getClass()->removeProperty($property->getName());
        }
    }

    private function createNewControllerField(FieldDefinition $field, ObjectType $type, StyleInterface $io): void
    {
        // Ensure the field type exists.
        $this->ensureType($field->getType(), $io);

        // Determine whether to create a new controller or map to existing controller.
        if (
            count($this->manager->getControllers()) > 0
            && 'Create new' !== $class_name = $io->choice(
                sprintf('Create a new controller for "%s" field "%s" or map to existing controller?', $type->name, $field->name),
                array_values(['Create new'] + array_map(fn(GraphQLFile $info): string => $info->getFullName(), $this->manager->getControllers())),
                'Create new'
            )
        ) {
            $info = $this->manager->getController($class_name);
        } else {
            // Create the new controller.
            $file = new PhpFile();
            $namespace = $file->addNamespace($this->controller_namespace);
            $class = $namespace->addClass($io->ask('What should the new controller be called?'));

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
        $parameter = GraphQLCodeHelper::extractArgumentParameter($method, $arg);
        $method->removeParameter($parameter->getName());
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
        $property = GraphQLCodeHelper::extractArgumentProperty($info->getClass(), $field);

        // If other annotations exist on this property confirm deletion.
        if (count($property->getAttributes()) == 1 || $io->ask(sprintf('Would you like to remove the property "%s"?', $property->getName()))) {
            $info->getClass()->removeProperty($property->getName());
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
        $case = GraphQLCodeHelper::extractValueCase($info->getEnum(), $value);
        $info->getEnum()->removeCase($case->getName());
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

    private function saveFiles(): void
    {
        foreach ($this->manager->getTypes() as $type) {
            d($type->getFilename());
            file_put_contents($type->getFilename(), $this->printer->printFile($type->getFile()));
        }

        foreach ($this->manager->getControllers() as $controller) {
            file_put_contents($controller->getFilename(), $this->printer->printFile($controller->getFile()));
        }
    }
}
