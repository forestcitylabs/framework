<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\HasFieldsType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use LogicException;

class SchemaComparator
{
    public static function compareSchemas(?Schema $from, ?Schema $to): SchemaDiff
    {
        // If both are null throw an error.
        if ($from === null && $to === null) {
            throw new LogicException('Cannot compare two null schemas!');
        }

        // Start a new schema diff.
        $diff = new SchemaDiff();

        // Iterate over the new schema and compare types.
        if ($to instanceof Schema) {
            foreach ($to->getTypeMap() as $to_type) {
                // Skip built-in types.
                if ($to_type->isBuiltInType()) {
                    continue;
                }
                if ($from instanceof Schema && $from->hasType($to_type->name())) {
                    $from_type = $from->getType($to_type->name());
                } else {
                    $from_type = null;
                }

                // Compare the types.
                $diff->addTypeDiff(self::compareTypes($from_type, $to_type));
            }
        }

        // Iterate over the old schema as well.
        if ($from instanceof Schema) {
            foreach ($from->getTypeMap() as $from_type) {
                if ($from_type->isBuiltInType()) {
                    continue;
                }

                // We have already compared this type.
                if ($to instanceof Schema && $to->hasType($from_type->name())) {
                    continue;
                }

                $diff->addTypeDiff(self::compareTypes($from_type, null));
            }
        }

        return $diff;
    }

    public static function compareTypes(?Type $from, ?Type $to): TypeDiff
    {
        // If both are null throw an error.
        if ($from === null && $to === null) {
            throw new LogicException('Cannot compare two null types!');
        }

        // Begin a new diff.
        $diff = new TypeDiff();
        $to_non_null = false;
        $from_non_null = false;
        $to_list = false;
        $from_list = false;

        // Unwrap the to type.
        while ($to instanceof WrappingType) {
            if ($to instanceof ListOfType) {
                $to_list = true;
            }
            if ($to instanceof NonNull) {
                $to_non_null = true;
            }
            $to = $to->getWrappedType();
        }

        // Unwrap the from type.
        while ($from instanceof WrappingType) {
            if ($from instanceof ListOfType) {
                $from_list = true;
            }
            if ($from instanceof NonNull) {
                $from_non_null = true;
            }
            $from = $from->getWrappedType();
        }

        $diff->setNameDiff(
            ($from === null) ? null : $from->name(),
            ($to === null) ? null : $to->name()
        );
        $diff->setNonNullDiff(
            ($from === null) ? null : $from_non_null,
            ($to === null) ? null : $to_non_null
        );
        $diff->setListDiff(
            ($from === null) ? null : $from_list,
            ($to === null) ? null : $to_list
        );
        $diff->setHasFieldsDiff(
            ($from === null) ? null : ($from instanceof HasFieldsType),
            ($to === null) ? null : ($to instanceof HasFieldsType)
        );

        // Iterate over fields if this type has them.
        if ($to instanceof HasFieldsType) {
            // Compare all fields to one another.
            foreach ($to->getFields() as $to_field) {
                if ($from instanceof HasFieldsType && $from->hasField($to_field->getName())) {
                    $from_field = $from->getField($to_field->getName());
                } else {
                    $from_field = null;
                }

                // Compare the fields.
                $diff->addFieldDiff(self::compareFields($from_field, $to_field));
            }
        }

        // Iterate over the old fields as well.
        if ($from instanceof HasFieldsType) {
            foreach ($from->getFields() as $from_field) {
                if ($to instanceof HasFieldsType && $to->hasField($from_field->getName())) {
                    continue;
                }
                $diff->addFieldDiff(self::compareFields($from_field, null));
            }
        }

        // Return the diff for this type.
        return $diff;
    }

    public static function compareFields(?FieldDefinition $from, ?FieldDefinition $to): FieldDiff
    {
        // If both are null throw an error.
        if ($from === null && $to === null) {
            throw new LogicException('Cannot compare two null fields!');
        }

        // Begin a new diff.
        $diff = new FieldDiff();
        $diff->setNameDiff(
            ($from === null) ? null : $from->getName(),
            ($to === null) ? null : $to->getName()
        );
        $diff->setTypeDiff(self::compareTypes(
            ($from === null) ? null : $from->getType(),
            ($to === null) ? null : $to->getType()
        ));

        // Compare arguments for this field.
        if ($to instanceof FieldDefinition) {
            foreach ($to->args as $to_arg) {
                if ($from instanceof FieldDefinition) {
                    $from_arg = $from->getArg($to_arg->name);
                } else {
                    $from_arg = null;
                }

                // Compare the arguments.
                $diff->addArgumentDiff(self::compareArguments($from_arg, $to_arg));
            }
        }

        // Compare old arguments as well.
        if ($from instanceof FieldDefinition) {
            foreach ($from->args as $from_arg) {
                if ($to instanceof FieldDefinition && null !== $to->getArg($from_arg->name)) {
                    continue;
                }
                $diff->addArgumentDiff(self::compareArguments($from_arg, null));
            }
        }

        return $diff;
    }

    public static function compareArguments(?Argument $from, ?Argument $to): ArgumentDiff
    {
        // If both are null throw an error.
        if ($from === null && $to === null) {
            throw new LogicException('Cannot compare two null arguments!');
        }

        // Start the diff.
        $diff = new ArgumentDiff();
        $diff->setNameDiff(
            ($from === null) ? null : $from->name,
            ($to === null) ? null : $to->name
        );
        $diff->setTypeDiff(self::compareTypes(
            ($from === null) ? null : $from->getType(),
            ($to === null) ? null : $to->getType()
        ));

        // Return the diff.
        return $diff;
    }
}
