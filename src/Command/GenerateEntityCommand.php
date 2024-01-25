<?php

namespace ForestCityLabs\Framework\Command;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Inflector\Inflector;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use ForestCityLabs\Framework\Utility\CodeGenerator;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\Property;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateEntityCommand extends Command
{
    private array $files = [];

    public function __construct(
        private string $directory,
        private string $namespace,
        private Printer $printer,
        private EntityManagerInterface $em,
        private Inflector $inflector
    ) {
        parent::__construct('generate:entity');
    }

    protected function configure()
    {
        $this
            ->addArgument(
                'class',
                InputArgument::REQUIRED,
                'The short class name to create or modify (ie "Group").'
            )
            ->addOption(
                'directory',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The directory where entity php files are stored.',
                default: $this->directory
            )
            ->addOption(
                'namespace',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The namespace for application entities.',
                default: $this->namespace
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Set the io and global directory and namespace.
        $io = new SymfonyStyle($input, $output);
        $this->directory = $input->getOption('directory');
        $this->namespace = $input->getOption('namespace');

        // Welcome the user to entity generator.
        $io->title('Welcome to the Entity Generator!');

        // Get the file, namespace and class.
        list($file, $namespace, $class) = $this->load($input->getArgument('class'), true);
        assert($file instanceof PhpFile);
        assert($namespace instanceof PhpNamespace);
        assert($class instanceof ClassType);

        // Ensure we have the ORM use statement.
        $namespace->addUse(ORM::class, 'ORM');

        // Annotate the class if required.
        if (!self::hasAttribute($class, ORM\Entity::class)) {
            $class->addAttribute(ORM\Entity::class);
        }

        // Add an ID property if needed.
        if (!self::hasProperty($class, 'id')) {
            $this->addIdProperty($class, $namespace);
        }

        // Create property name question.
        $question = new Question('Enter new property name (press <return> to finish adding properties)');
        $question->setValidator(function ($name) {
            // Blank should return null.
            if ($name == "") {
                return null;
            }

            // Validate that this is a valid php property name.
            if (!(bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name, $matches)) {
                throw new RuntimeException(sprintf('"%s" is not a valid property name!', $name));
            }

            // Return the property name.
            return $name;
        });

        // Add properties.
        $io->section('Add a property');
        while (null !== $name = $io->askQuestion($question)) {
            $type = $io->askQuestion((new Question('Select field type'))->setAutocompleterValues([
                'relation',
                'string',
                'text',
                'integer',
                'float',
                'boolean',
                'json_array',
                'datetime',
            ]));

            if ($type === 'relation') {
                $this->addRelationProperty($name, $class, $namespace, $io);
            } elseif ($type === 'json_array') {
                $this->addArrayProperty($name, $class, $io);
            } else {
                $this->addScalarProperty($name, $type, $class, $namespace, $io);
            }
            $io->section('Add another property');
        }

        // Confirm generation.
        if (!$io->confirm('Do you confirm generation of the entitie(s)?')) {
            $io->warning('Entity generation aborted!');
            return Command::SUCCESS;
        }

        // Iterate over files and write them out.
        foreach ($this->files as $filename => $file) {
            file_put_contents($filename, $this->printer->printFile($file));
        }

        // Report success.
        $io->success(sprintf('Entity "%s" created!', $class->getName()));
        return Command::SUCCESS;
    }

    private function load(string $class, bool $create = false): array
    {
        // Construct the filename.
        $filename = $this->directory
            . DIRECTORY_SEPARATOR
            . $class . '.php';

        // Do not double load a file.
        if (isset($this->files[$filename])) {
            $file = $this->files[$filename];
        } elseif (file_exists($filename)) {
            $file = PhpFile::fromCode(file_get_contents($filename));
        } elseif ($create) {
            $file = new PhpFile();
            $file->addNamespace($this->namespace)->addClass($class);
        } else {
            throw new RuntimeException(sprintf('File "%s" not found!', $filename));
        }

        // Get namespace and class.
        $namespace = $file->getNamespaces()[$this->namespace];
        $class = $namespace->getClasses()[$class];

        // Add to files array for processing.
        $this->files[$filename] = $file;
        return [$file, $namespace, $class];
    }

    private function addIdProperty(ClassType $class, PhpNamespace $namespace): void
    {
        $namespace->addUse(Uuid::class);
        $namespace->addUse(UuidOrderedTimeGenerator::class);
        $class->addProperty('id')
            ->setPrivate()
            ->setType(Uuid::class)
            ->addAttribute(ORM\Id::class)
            ->addAttribute(
                ORM\GeneratedValue::class,
                ['strategy' => 'CUSTOM']
            )
            ->addAttribute(
                ORM\CustomIdGenerator::class,
                ['class' => new Literal('UuidOrderedTimeGenerator::class')]
            )
            ->addAttribute(ORM\Column::class, [
                'type' => 'uuid_binary_ordered_time',
                'unique' => true,
            ]);
        $class->addMethod('getId')
            ->setReturnType(Uuid::class)
            ->addBody('return $this->id;');
    }

    private function addRelationProperty(
        string $name,
        ClassType $class,
        PhpNamespace $namespace,
        SymfonyStyle $io
    ): void {
        // Create the property for the relation.
        $property = $class->addProperty($name)->setPrivate();

        // Determine the target entity.
        $target = $io->askQuestion((new Question('Target entity'))
            ->setAutocompleterCallback(function () use ($class, $namespace) {
                $classes = [$class->getName()];
                foreach ($this->em->getMetadataFactory()->getAllMetadata() as $metadata) {
                    $classes[] = ltrim(str_replace($namespace->getName(), '', $metadata->getName()), '\\');
                }
                return $classes;
            }));

        // Determine the relation type attribute.
        $this->printRelationHelp($io, $class->getName(), $target);
        $association = $io->choice('What type of relation?', ['ManyToOne', 'OneToOne', 'OneToMany', 'ManyToMany']);

        // Process additional annotation options.
        $args = ['targetEntity' => $target];
        if ($association === 'ManyToOne') {
            // Construct the property.
            $property->setType($namespace->getName() . '\\' . $target);
            $property->setNullable($this->isNullable($io));

            // Determine if we should inverse this annotation.
            if (null !== $inverse = $this->mapInverseOneToMany($target, $class->getName(), $property->getName(), $io)) {
                $args['inversedBy'] = $inverse;
            }

            // Annotate the property.
            $property->addAttribute(ORM\ManyToOne::class, $args);

            // Nullable needs a join column.
            if ($property->isNullable()) {
                $property->setInitialized();
                $property->addAttribute(ORM\JoinColumn::class, ['nullable' => true]);
            }

            // Add methods.
            $this->addGetter($class, $property);
            $this->addSetter($class, $property);
        } elseif ($association === 'OneToOne') {
            // Construct the property.
            $property->setType($namespace->getName() . '\\' . $target);
            $property->setNullable($this->isNullable($io));

            // Determine if we should inverse this annotation.
            if (null !== $inverse = $this->mapInverseOneToOne($target, $class->getName(), $property->getName(), $io)) {
                $args['inversedBy'] = $inverse;
            }

            // Annotate the property.
            $property->addAttribute(ORM\OneToOne::class, $args);

            // Nullable needs a join column.
            if ($property->isNullable()) {
                $property->setInitialized();
                $property->addAttribute(ORM\JoinColumn::class, ['nullable' => true]);
            }

            // Add methods.
            $this->addGetter($class, $property);
            $this->addSetter($class, $property);
        } elseif ($association === 'OneToMany') {
            // Add namespace use statements.
            $namespace->addUse(Collection::class);
            $namespace->addUse(ArrayCollection::class);

            // Create the property on the class.
            $property->setType(Collection::class)
                ->addAttribute(ORM\OneToMany::class, [
                    'targetEntity' => $target,
                    'mappedBy' => $this->mapInverseManyToOne($target, $class->getName(), $property->getName(), $io),
                ]);

            // Update the constructor.
            if (!$class->hasMethod('__construct')) {
                $constructor = $class->addMethod('__construct');
            } else {
                $constructor = $class->getMethod('__construct');
            }
            $constructor->addBody('$this->' . $property->getName() . ' = new ArrayCollection();');

            // Add access methods.
            CodeGenerator::addGetter($class, $property);
            CodeGenerator::addAdder($class, $property, $namespace->getName() . '\\' . $target);
            CodeGenerator::addRemover($class, $property, $namespace->getName() . '\\' . $target);
            CodeGenerator::addHasser($class, $property, $namespace->getName() . '\\' . $target);
        } elseif ($association === 'ManyToMany') {
            // Add namespace use statements.
            $namespace->addUse(Collection::class);
            $namespace->addUse(ArrayCollection::class);

            // Create the property on the class.
            $property->setType(Collection::class);

            // Determine if we should inverse this annotation.
            if (null !== $inverse = $this->mapInverseManyToMany($target, $class->getName(), $property->getName(), $io)) {
                $args['inversedBy'] = $inverse;
            }

            // Annotate the property.
            $property->addAttribute(ORM\ManyToMany::class, $args);

            // Update the constructor.
            if (!$class->hasMethod('__construct')) {
                $constructor = $class->addMethod('__construct');
            } else {
                $constructor = $class->getMethod('__construct');
            }
            $constructor->addBody('$this->' . $property->getName() . ' = new ArrayCollection();');

            // Add access methods.
            $this->addGetter($class, $property);
            $this->addAdder($class, $property, $namespace->getName() . '\\' . $target);
            $this->addRemover($class, $property, $namespace->getName() . '\\' . $target);
            $this->addHasser($class, $property, $namespace->getName() . '\\' . $target);
        }
    }

    private function mapInverseOneToMany(string $class, string $target, string $target_property, SymfonyStyle $io): ?string
    {
        if (!$io->confirm(sprintf('Add inverse OneToMany relationship to "%s"?', $class))) {
            return null;
        }

        // Load the file.
        list( , $namespace, $class) = $this->load($class);
        assert($namespace instanceof PhpNamespace);
        assert($class instanceof ClassType);

        // Create property name question.
        $question = new Question('Enter inverse relation property name');
        $question->setValidator(function ($name) {
            // Validate that this is a valid php property name.
            if (!(bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name, $matches)) {
                throw new RuntimeException(sprintf('"%s" is not a valid property name!', $name));
            }

            // Return the property name.
            return $name;
        });

        // Add namespace use statements.
        $namespace->addUse(Collection::class);
        $namespace->addUse(ArrayCollection::class);

        // Create the property on the class.
        $property = $class->addProperty($io->askQuestion($question))
            ->setPrivate()
            ->setType(Collection::class)
            ->addAttribute(ORM\OneToMany::class, [
                'targetEntity' => $target,
                'mappedBy' => $target_property,
            ]);

        // Update the constructor.
        if (!$class->hasMethod('__construct')) {
            $constructor = $class->addMethod('__construct');
        } else {
            $constructor = $class->getMethod('__construct');
        }
        $constructor->addBody('$this->' . $property->getName() . ' = new ArrayCollection();');

        // Add access methods.
        $this->addGetter($class, $property);
        $this->addAdder($class, $property, $namespace->getName() . '\\' . $target);
        $this->addRemover($class, $property, $namespace->getName() . '\\' . $target);
        $this->addHasser($class, $property, $namespace->getName() . '\\' . $target);

        // Return the property name.
        return $property->getName();
    }

    private function mapInverseOneToOne(string $class, string $target, string $target_property, SymfonyStyle $io): ?string
    {
        if (!$io->confirm(sprintf('Add inverse OneToOne relationship to "%s"?', $class))) {
            return null;
        }

        // Load the file.
        list( , $namespace, $class) = $this->load($class);
        assert($namespace instanceof PhpNamespace);
        assert($class instanceof ClassType);

        // Create property name question.
        $question = new Question('Enter inverse relation property name');
        $question->setValidator(function ($name) {
            // Validate that this is a valid php property name.
            if (!(bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name, $matches)) {
                throw new RuntimeException(sprintf('"%s" is not a valid property name!', $name));
            }

            // Return the property name.
            return $name;
        });

        // Create the property on the class.
        $property = $class->addProperty($io->askQuestion($question))
            ->setPrivate()
            ->setType($namespace->getName() . '\\' . $target)
            ->setNullable($this->isNullable($io))
            ->addAttribute(ORM\OneToMany::class, [
                'targetEntity' => $target,
                'mappedBy' => $target_property,
            ]);

        // Nullable needs a join column.
        if ($property->isNullable()) {
            $property->setInitialized();
            $property->addAttribute(ORM\JoinColumn::class, ['nullable' => true]);
        }

        // Add methods.
        $this->addGetter($class, $property);
        $this->addSetter($class, $property);

        // Return the property name.
        return $property->getName();
    }

    private function mapInverseManyToOne(string $class, string $target, string $target_property, SymfonyStyle $io): ?string
    {
        // Load the file.
        list( , $namespace, $class) = $this->load($class);
        assert($namespace instanceof PhpNamespace);
        assert($class instanceof ClassType);

        // Create property name question.
        $question = new Question('Enter inverse relation property name');
        $question->setValidator(function ($name) {
            // Validate that this is a valid php property name.
            if (!(bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name, $matches)) {
                throw new RuntimeException(sprintf('"%s" is not a valid property name!', $name));
            }

            // Return the property name.
            return $name;
        });

        // Construct the property.
        $property = $class->addProperty($io->askQuestion($question))
            ->setPrivate()
            ->setType($namespace->getName() . '\\' . $target)
            ->setNullable($this->isNullable($io));

        // Annotate the property.
        $property->addAttribute(ORM\ManyToOne::class, [
            'targetEntity' => $target,
            'inversedBy' => $target_property,
        ]);

        // Nullable needs a join column.
        if ($property->isNullable()) {
            $property->setInitialized();
            $property->addAttribute(ORM\JoinColumn::class, ['nullable' => true]);
        }

        // Add methods.
        $this->addGetter($class, $property);
        $this->addSetter($class, $property);

        // Return the property name.
        return $property->getName();
    }

    private function mapInverseManyToMany(string $class, string $target, string $target_property, SymfonyStyle $io): ?string
    {
        if (!$io->confirm(sprintf('Add inverse ManyToMany relationship to "%s"?', $class))) {
            return null;
        }

        // Load the file.
        list( , $namespace, $class) = $this->load($class);
        assert($namespace instanceof PhpNamespace);
        assert($class instanceof ClassType);

        // Create property name question.
        $question = new Question('Enter inverse relation property name');
        $question->setValidator(function ($name) {
            // Validate that this is a valid php property name.
            if (!(bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name, $matches)) {
                throw new RuntimeException(sprintf('"%s" is not a valid property name!', $name));
            }

            // Return the property name.
            return $name;
        });

        // Add namespace use statements.
        $namespace->addUse(Collection::class);
        $namespace->addUse(ArrayCollection::class);

        // Create the property on the class.
        $property = $class->addProperty($io->askQuestion($question))
            ->setPrivate()
            ->setType(Collection::class)
            ->addAttribute(ORM\OneToMany::class, [
                'targetEntity' => $target,
                'mappedBy' => $target_property,
            ]);

        // Update the constructor.
        if (!$class->hasMethod('__construct')) {
            $constructor = $class->addMethod('__construct');
        } else {
            $constructor = $class->getMethod('__construct');
        }
        $constructor->addBody('$this->' . $property->getName() . ' = new ArrayCollection();');

        // Add access methods.
        $this->addGetter($class, $property);
        $this->addAdder($class, $property, $namespace->getName() . '\\' . $target);
        $this->addRemover($class, $property, $namespace->getName() . '\\' . $target);
        $this->addHasser($class, $property, $namespace->getName() . '\\' . $target);

        // Return the property name.
        return $property->getName();
    }

    private function addScalarProperty(
        string $name,
        string $type,
        ClassLike $class,
        PhpNamespace $namespace,
        SymfonyStyle $io
    ): void {
        $property = $class->addProperty($name)->setPrivate();
        $args = [];
        switch ($type) {
            case 'text':
                $args['type'] = Types::TEXT;
            case 'string':
                $property->setType('string');
                break;
            case 'integer':
                $property->setType('int');
                break;
            case 'float':
                $property->setType('float');
                break;
            case 'boolean':
                $property->setType('bool');
                break;
            case 'datetime':
                $namespace->addUse(DateTimeImmutable::class);
                $property->setType(DateTimeImmutable::class);
                break;
        }

        // Get nullable and unique.
        if ($io->confirm('Nullable?', false)) {
            $args['nullable'] = true;
            $property->setType('?' . $property->getType());
            $property->setInitialized();
        }
        if ($io->confirm('Unique?', false)) {
            $args['unique'] = true;
        }

        // Add attribute.
        $property->addAttribute(ORM\Column::class, $args);

        // Add methods.
        $this->addGetter($class, $property);
        $this->addSetter($class, $property);
    }

    private function addArrayProperty(string $name, ClassType $class, SymfonyStyle $io): void
    {
        // Create property.
        $property = $class->addProperty($name)
            ->setPrivate()
            ->setType('array')
            ->setValue([])
            ->addAttribute(ORM\Column::class);

        // Create getter method.
        $this->addGetter($class, $property);

        // Check if there is a scalar sub-type.
        if ($io->confirm('Is this a standard scalar sub-type (ie string, integer, etc)?')) {
            // Get the sub-type and try to sub-name it.
            $sub_type = $io->choice('Please select sub-type', ['string', 'int', 'bool', 'float']);

            // Create methods.
            $this->addAdder($class, $property, $sub_type);
            $this->addRemover($class, $property, $sub_type);
            $this->addHasser($class, $property, $sub_type);
        } else {
            // Add setter.
            $this->addSetter($class, $property);
        }
    }

    private static function camelCase(string $string): string
    {
        $parts = explode('_', $string);
        array_walk($parts, function (&$part) {
            $part = ucfirst($part);
        });
        return implode($parts);
    }

    private static function hasAttribute(ClassLike|Property $object, string $check): bool
    {
        foreach ($object->getAttributes() as $attribute) {
            if ($attribute->getName() === $check) {
                return true;
            }
        }

        return false;
    }

    private static function hasProperty(ClassLike $object, string $check): bool
    {
        foreach ($object->getProperties() as $property) {
            if ($property->getName() === $check) {
                return true;
            }
        }

        return false;
    }

    private static function printRelationHelp(SymfonyStyle $io, string $entity, string $target): void
    {
        // Add information about relations.
        $io->definitionList(
            'Relation types',
            new TableSeparator(),
            ['ManyToOne' => sprintf(
                "Each <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>.\nEach <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> entities.",
                $entity,
                $target,
                $target,
                $entity
            )],
            new TableSeparator(),
            ['OneToOne' => sprintf(
                "Each <comment>%s</comment> relates to (has) exactly <info>one</info> <comment>%s</comment>.\nEach <comment>%s</comment> also relates to (has) exactly <info>one</info> <comment>%s</comment>.",
                $entity,
                $target,
                $target,
                $entity
            )],
            new TableSeparator(),
            ['OneToMany' => sprintf(
                "Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> entities.\nEach <comment>%s</comment> relates to (has) <info>one</info> <comment>%s</comment>.",
                $entity,
                $target,
                $target,
                $entity
            )],
            new TableSeparator(),
            ['ManyToMany' => sprintf(
                "Each <comment>%s</comment> can relate to (can have) <info>many</info> <comment>%s</comment> entities.\nEach <comment>%s</comment> can also relate to (can also have) <info>many</info> <comment>%s</comment> entities.",
                $entity,
                $target,
                $target,
                $entity
            )],
        );
    }

    /**
     * Helper function to ask if a property is nullable.
     *
     * @param  StyleInterface $io The IO interface.
     * @return boolean            Whether the response was true or not.
     */
    private function isNullable(StyleInterface $io): bool
    {
        return $io->confirm('Is this property nullable?', false);
    }

    /**
     * Add a getter method for a given property to a given class.
     *
     * @param ClassType $class    The class to add a getter method to.
     * @param Property  $property The property to get.
     */
    private function addGetter(ClassType $class, Property $property): void
    {
        $class->addMethod('get' . self::camelCase($property->getName()))
            ->setReturnType(($property->isNullable() ? '?' : '') . $property->getType())
            ->addBody('return $this->' . $property->getName() . ';');
    }

    /**
     * Add a setter method for a given property to a given class.
     *
     * @param ClassType $class    The class to add a setter method to.
     * @param Property  $property The property to set.
     */
    private function addSetter(ClassType $class, Property $property): void
    {
        $class->addMethod('set' . self::camelCase($property->getName()))
            ->setReturnType('self')
            ->addBody('$this->' . $property->getName() . ' = $' . $property->getName() . ';')
            ->addBody('return $this;')
            ->addParameter($property->getName())
            ->setType(($property->isNullable() ? '?' : '') . $property->getType());
    }

    /**
     * Add an adder method for a given property to a given class.
     *
     * @param ClassType $class    The class to add an adder method to.
     * @param Property  $property The property to add to.
     */
    private function addAdder(ClassType $class, Property $property, ?string $type = null): void
    {
        // Singularize the sub name.
        $sub_name = $this->inflector->singularize($property->getName());

        // Create adder method.
        $method = $class->addMethod('add' . self::camelCase($sub_name))
            ->setReturnType('self');

        // If this is a collection use api, otherwise treat as array.
        if ($property->getType() === Collection::class) {
            $method->addBody('$this->' . $property->getName() . '->add($' . $sub_name . ');');
        } else {
            $method->addBody('$this->' . $property->getName() . '[] = $' . $sub_name . ';');
        }

        // Add the return and parameter to the method.
        $method->addBody('return $this;')
            ->addParameter($sub_name)
            ->setType($type);
    }

    /**
     * Add a remover method for a given property to a given class.
     *
     * @param ClassType $class    The class to add a remover method to.
     * @param Property  $property The property to remove from.
     */
    private function addRemover(ClassType $class, Property $property, ?string $type = null): void
    {
        // Singularize the sub name.
        $sub_name = $this->inflector->singularize($property->getName());

        // Create remover method.
        $method = $class->addMethod('remove' . self::camelCase($sub_name))
            ->setReturnType('self');

        // If this is a collection use api, otherwise treat as array.
        if ($property->getType() === Collection::class) {
            $method->addBody('$this->' . $property->getName() . '->removeElement($' . $sub_name . ');');
        } else {
            $method->addBody('unset($this->' . $property->getName() . '[array_search($' . $sub_name . ', $this->' . $property->getName() . ')]);');
        }

        // Add the return and parameter to the method.
        $method->addBody('return $this;')
            ->addParameter($sub_name)
            ->setType($type);
    }

    /**
     * Add a hasser method for a given property to a given class.
     *
     * @param ClassType $class    The class to add a hasser method to.
     * @param Property  $property The property to has from.
     */
    private function addHasser(ClassType $class, Property $property, ?string $type = null): void
    {
        // Singularize the sub name.
        $sub_name = $this->inflector->singularize($property->getName());

        // Create hasser method.
        $method = $class->addMethod('has' . self::camelCase($sub_name))
            ->setReturnType('bool');

        // If this is a collection use api, otherwise treat as array.
        if ($property->getType() === Collection::class) {
            $method->addBody('return $this->' . $property->getName() . '->contains($' . $sub_name . ');');
        } else {
            $method->addBody('return in_array($' . $sub_name . ', $this->' . $property->getName() . ');');
        }

        // Add the parameter to the method.
        $method->addParameter($sub_name)
            ->setType($type);
    }
}
