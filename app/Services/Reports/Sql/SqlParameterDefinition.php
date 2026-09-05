<?php

namespace App\Services\Reports\Sql;

use InvalidArgumentException;

final class SqlParameterDefinition
{
    public const TYPES = ['string','integer','decimal','date','datetime','boolean','lookup','business_unit'];

    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type,
        public readonly bool $required = false,
        public readonly mixed $default = null,
        public readonly ?string $table = null,
        public readonly bool $sensitive = false,
    ) {}

    public static function fromArray(array $row): self
    {
        $name = trim((string)($row['name'] ?? ''));
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('SQL parameter names may contain only letters, numbers, and underscore, and must start with a letter.');
        }
        $type = strtolower((string)($row['type'] ?? 'string'));
        if (! in_array($type,self::TYPES,true)) {
            throw new InvalidArgumentException("Unsupported SQL parameter type: {$type}");
        }
        $table = isset($row['table']) && trim((string)$row['table']) !== '' ? trim((string)$row['table']) : null;
        if ($type === 'lookup' && $table === null) {
            throw new InvalidArgumentException("Lookup parameter {$name} requires a table.");
        }
        return new self(
            $name,
            trim((string)($row['label'] ?? '')) ?: ucwords(str_replace('_',' ',$name)),
            $type,
            (bool)($row['required'] ?? false),
            $row['default'] ?? null,
            $table,
            (bool)($row['sensitive'] ?? false),
        );
    }

    public function reportSchema(): array
    {
        $schema = [
            'type'=>$this->type,
            'label'=>$this->label,
            'nullable'=>!$this->required,
            'default'=>$this->default,
            'sensitive'=>$this->sensitive,
        ];
        if ($this->table !== null) $schema['table']=$this->table;
        return $schema;
    }
}
