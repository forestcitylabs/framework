<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility\CodeGenerator;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

class GraphQLFile
{
    public function __construct(
        private string $filename,
        private PhpFile $file,
        private PhpNamespace $namespace,
        private ClassLike $class,
    ) {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getFile(): PhpFile
    {
        return $this->file;
    }

    public function getNamespace(): PhpNamespace
    {
        return $this->namespace;
    }

    public function getClassLike(): ClassLike
    {
        return $this->class;
    }

    public function getClass(): ?ClassType
    {
        if ($this->class->isClass()) {
            return $this->class;
        }
        return null;
    }

    public function getInterface(): ?InterfaceType
    {
        if ($this->class->isInterface()) {
            return $this->class;
        }
        return null;
    }

    public function getEnum(): ?EnumType
    {
        if ($this->class->isEnum()) {
            return $this->class;
        }
        return null;
    }
}
