<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\GraphQL\Diff;

use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

class SchemaComparator
{
    public static function compareSchemas(Schema $old, Schema $new): SchemaDiff
    {
        $args = [
            'old_schema' => $old,
            'new_schema' => $new,
            'new_types' => [],
            'altered_types' => [],
            'dropped_types' => [],
            'new_inputs' => [],
            'altered_inputs' => [],
            'dropped_inputs' => [],
            'new_interfaces' => [],
            'altered_interfaces' => [],
            'dropped_interfaces' => [],
            'new_enums' => [],
            'altered_enums' => [],
            'dropped_enums' => [],
        ];

        // Iterate over the new type map collection new and altered types.
        foreach ($new->getTypeMap() as $new_type) {
            // Skip hidden types.
            if (substr($new_type->name, 0, 2) == "__") {
                continue;
            }

            // This is a new type.
            if (null === $old_type = $old->getType($new_type->name)) {
                $args['new_' . self::detectType($new_type)][] = $new_type;

            // Type mismatch means this is a new type.
            } elseif ($old_type::class !== $new_type::class) {
                $args['dropped_' . self::detectType($old_type)][] = $old_type;
                $args['new_' . self::detectType($new_type)][] = $new_type;

            // This is an altered type.
            } elseif (null !== $altered_type = self::compareTypes($old_type, $new_type)) {
                $args['altered_' . self::detectType($new_type)][] = $altered_type;
            }
        }

        // Iterate over the old type map and extract dropped types.
        foreach ($old->getTypeMap() as $old_type) {
            if (null === $new->getType($old_type->name)) {
                $args['dropped_' . self::detectType($old_type)][] = $old_type;
            }
        }

        // Return the schema diff.
        return new SchemaDiff(...$args);
    }

    public static function detectType(object $type): ?string
    {
        switch ($type::class) {
            case ObjectType::class:
            case ObjectTypeDiff::class:
                return 'types';
            case InputObjectType::class:
            case InputObjectTypeDiff::class:
                return 'inputs';
            case EnumType::class:
            case EnumTypeDiff::class:
                return 'enums';
            case InterfaceType::class:
            case InterfaceTypeDiff::class:
                return 'interfaces';
            default:
                return null;
        }
    }

    public static function compareObjectTypes(ObjectType $old, ObjectType $new): ?ObjectTypeDiff
    {
        $args = [
            'old_type' => $old,
            'new_type' => $new,
            'new_fields' => [],
            'altered_fields' => [],
            'dropped_fields' => [],
        ];

        // Extract new and altered fields.
        foreach ($new->getFields() as $new_field) {
            try {
                $old_field = $old->getField($new_field->getName());

                // There is a type mismatch, the fields are different.
                if (Type::getNamedType($new_field->getType())->name !== Type::getNamedType($old_field->getType())->name) {
                    $args['dropped_fields'][] = $old_field;
                    $args['new_fields'][] = $new_field;

                    // Compare the fields.
                } elseif (null !== $altered_field = self::compareFields($old_field, $new_field)) {
                    $args['altered_fields'][] = $altered_field;
                }
                // This is a new field.
            } catch (InvariantViolation) {
                $args['new_fields'][] = $new_field;
            }
        }

        // Determine dropped fields.
        foreach ($old->getFields() as $old_field) {
            try {
                $new->getField($old_field->name);
            } catch (InvariantViolation) {
                $args['dropped_fields'][] = $old_field;
            }
        }

        // Create the object diff.
        $diff = new ObjectTypeDiff(...$args);

        // If this is different return it, otherwise return null.
        if ($diff->isDifferent()) {
            return $diff;
        }
        return null;
    }

    public static function compareInputTypes(InputObjectType $old, InputObjectType $new): ?InputObjectTypeDiff
    {
        $args = [
            'old_input' => $old,
            'new_input' => $new,
            'new_fields' => [],
            'altered_fields' => [],
            'dropped_fields' => [],
        ];

        // Extract new and altered fields.
        foreach ($new->getFields() as $new_field) {
            try {
                $old_field = $old->getField($new_field->name);

                // There is a type mismatch, the fields are different.
                if (Type::getNamedType($new_field->getType())->name !== Type::getNamedType($old_field->getType())->name) {
                    $args['dropped_fields'][] = $old_field;
                    $args['new_fields'][] = $new_field;

                    // Compare the fields.
                } elseif (null !== $altered_field = self::compareInputFields($old_field, $new_field)) {
                    $args['altered_fields'][] = $altered_field;
                }
                // This is a new field.
            } catch (InvariantViolation) {
                $args['new_fields'][] = $new_field;
            }
        }

        // Determine dropped fields.
        foreach ($old->getFields() as $old_field) {
            try {
                $new->getField($old_field->name);
            } catch (InvariantViolation) {
                $args['dropped_fields'][] = $old_field;
            }
        }

        // Create the input diff.
        $diff = new InputObjectTypeDiff(...$args);

        // If this is different return it, otherwise return null.
        if ($diff->isDifferent()) {
            return $diff;
        }
        return null;
    }

    public static function compareEnumTypes(EnumType $old, EnumType $new): ?EnumTypeDiff
    {
        $args = [
            'old_enum' => $old,
            'new_enum' => $new,
            'new_values' => [],
            'altered_values' => [],
            'dropped_values' => [],
        ];

        // Extract new and altered values.
        foreach ($new->getValues() as $new_value) {
            try {
                $old_value = $old->getValue($new_value->name);
                if (null !== $altered_value = self::compareEnumValues($old_value, $new_value)) {
                    $args['altered_values'][] = $altered_value;
                }
            } catch (InvariantViolation) {
                $args['new_values'][] = $new_value;
            }

            // Determine dropped values.
            foreach ($old->getValues() as $old_value) {
                try {
                    $new->getValue($old_value->name);
                } catch (InvariantViolation) {
                    $args['dropped_values'][] = $old_value;
                }
            }

            // Create the enum diff.
            $diff = new EnumTypeDiff(...$args);

            // If this is different return it, otherwise return null.
            if ($diff->isDifferent()) {
                return $diff;
            }
            return null;
        }
    }

    public static function compareInterfaceTypes(InterfaceType $old, InterfaceType $new): ?InterfaceTypeDiff
    {
        $args = [
            'old_interface' => $old,
            'new_interface' => $new,
            'new_fields' => [],
            'altered_fields' => [],
            'dropped_fields' => [],
        ];

        // Extract new and altered fields.
        foreach ($new->getFields() as $new_field) {
            try {
                // Get the old field.
                $old_field = $old->getField($new_field->name);

                // There is a type mismatch, the fields are different.
                if (Type::getNamedType($new_field->getType())->name !== Type::getNamedType($old_field->getType())->name) {
                    $args['dropped_fields'][] = $old_field;
                    $args['new_fields'][] = $new_field;

                // Compare the fields.
                } elseif (null !== $altered_field = self::compareFields($old_field, $new_field)) {
                    $args['altered_fields'][] = $altered_field;
                }
            } catch (InvariantViolation) {
                // An invariant violation means that this is a new field.
                $args['new_fields'][] = $new_field;
            }
        }

        // Determine dropped fields.
        foreach ($old->getFields() as $old_field) {
            try {
                $new->getField($old_field->name);
            } catch (InvariantViolation) {
                $args['dropped_fields'][] = $old_field;
            }
        }

        // Create the object diff.
        $diff = new InterfaceTypeDiff(...$args);

        // If this is different return it, otherwise return null.
        if ($diff->isDifferent()) {
            return $diff;
        }
        return null;
    }

    public static function compareTypes(Type $old, Type $new): InputObjectTypeDiff|ObjectTypeDiff|EnumTypeDiff|InterfaceTypeDiff|null
    {
        switch ($old::class) {
            case ObjectType::class:
                return self::compareObjectTypes($old, $new);
            case InputObjectType::class:
                return self::compareInputTypes($old, $new);
            case InterfaceType::class:
                return self::compareInterfaceTypes($old, $new);
            case EnumType::class:
                return self::compareEnumTypes($old, $new);
            default:
                return null;
        }
    }

    public static function compareInputFields(InputObjectField $old, InputObjectField $new): ?InputFieldDiff
    {
        $diff = new InputFieldDiff($old, $new);

        if ($diff->isDifferent()) {
            return $diff;
        }
        return null;
    }

    public static function compareEnumValues(EnumValueDefinition $old, EnumValueDefinition $new): ?ValueDiff
    {
        $diff = new ValueDiff($old, $new);
        if ($diff->isDifferent()) {
            return $diff;
        }
        return null;
    }

    public static function compareFields(FieldDefinition $old, FieldDefinition $new): ?FieldDiff
    {
        $args = [
            'old_field' => $old,
            'new_field' => $new,
            'new_arguments' => [],
            'altered_arguments' => [],
            'dropped_arguments' => [],
        ];

        foreach ($new->args as $new_argument) {
            try {
                $old_argument = $old->getArg($new_argument->name);
                if (Type::getNamedType($new_argument->getType())->name !== Type::getNamedType($old_argument->getType())->name) {
                    $args['dropped_arguments'][] = $old_argument;
                    $args['new_arguments'][] = $new_argument;
                } elseif (null !== $altered_argument = self::compareArguments($old_argument, $new_argument)) {
                    $args['altered_arguments'][] = $altered_argument;
                }
            } catch (InvariantViolation) {
                $args['new_arguments'][] = $new_argument;
            }
        }

        foreach ($old->args as $old_argument) {
            try {
                ($new->getArg($old_argument->name));
            } catch (InvariantViolation) {
                $args['dropped_arguments'][] = $old_argument;
            }
        }

        $diff = new FieldDiff(...$args);

        if ($diff->isDifferent()) {
            return $diff;
        }
        return null;
    }

    public static function compareArguments(Argument $old, Argument $new): ?ArgumentDiff
    {
        $diff = new ArgumentDiff($old, $new);
        if ($diff->isDifferent()) {
            return $diff;
        }
        return null;
    }
}
