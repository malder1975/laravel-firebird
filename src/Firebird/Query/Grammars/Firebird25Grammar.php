<?php

namespace Firebird\Query\Grammars;

use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Builder;

class Firebird25Grammar extends Grammar
{
    protected $operators = [
      '=', '<', '>', '<=', '>=', '<>', '!=', '!>', '!<', '~=', '~>', '~<', '^=', '^<', '^>',
        'LIKE', 'NOT LIKE', 'BETWEEN', 'CONTAINING', 'STARTING WITH', 'SIMILAR TO', 'NOT SIMILAR TO',
        'IS NULL', 'IS NOT NULL',
    ];

    protected function compileAggregate(Builder $query, $aggregate)
    {
        //return parent::compileAggregate($query, $aggregate); // TODO: Change the autogenerated stub
        $column = $this->columnize($aggregate['columns']);

        if ($query->distinct && $column != '*') {
            $column = 'DISTINCT '.$column;
        }

        return 'SELECT '.$aggregate['function'].'('.$column.') as "aggregate"';
    }

    public function compileGetContext(Builder $query, $namespace, $name)
    {
        return "SELECT RDB\$GET_CONTEXT('{$namespace}', '{$name}' AS VALUE FROM RDB\$DATABASE";
    }

    public function compileExecFunction(Builder $query, $function, array $values = null)
    {
        $function = $this->wrap($function);

        return "SELECT {$function} (" . $this->parameterize($values) . ") AS VAL FROM RDB\$DATABASE";
    }

    public function compileExecProcedure(Builder $query, $procedure, array $values = null)
    {
        $procedure = $this->wrap($procedure);

        return "EXECUTE PROCEDURE {$$procedure} ( " . $this->parameterize($values) . ' )';
    }

    public function compileInsertGetId(Builder $query, $values, $sequence)
    {
        //return parent::compileInsertGetId($query, $values, $sequence); // TODO: Change the autogenerated stub

        if (is_null($sequence)) {
            $sequence = 'ID';
        }

        return $this->compileInsert($query, $values) . ' RETURNING ' . $this->wrap($sequence);
    }

    protected function compileLimit(Builder $query, $limit)
    {
        //return parent::compileLimit($query, $limit); // TODO: Change the autogenerated stub

        if ($query->offset) {
            $first = (int) $query->offset + 1;
            return ' ROWS ' . $first;
        } else {
            return ' ROWS ' . $limit;
        }
    }

    protected function compileLock(Builder $query, $value)
    {
        //return parent::compileLock($query, $value); // TODO: Change the autogenerated stub

        if (is_string($value)) {
            return $value;
        }

        return $value ? 'FOR UPDATE' : '';
    }

    public function compileNextSequenceValue(Builder $query, $sequence = null, $increment = null)
    {
        if (!$sequence) {
            $sequence = $this->wrap(substr('GEN_' . $query->from . '_ID', 0, 31));
        }

        if ($increment) {
            return "SELECT GEN_ID({$sequence}, {$increment}) AS ID FROM RDB\$DATABASE";
        }

        return "SELECT GEN_ID({$sequence}, {$increment}) AS ID FROM RDB\$DATABASE";
    }

    protected function compileOffset(Builder $query, $offset)
    {
        //return parent::compileOffset($query, $offset); // TODO: Change the autogenerated stub

        if ($query->limit) {
            if ($offset) {
                $end = (int) $query->limit + (int) $offset;
                return 'TO ' . $end;
            } else {
                return '';
            }
        } else {
            $begin = (int) $offset + 1;
            return 'ROWS ' . $begin . ' TO 2147483647';
        }
    }

    public function compileUpdate(Builder $query, $values)
    {
        //return parent::compileUpdate($query, $values); // TODO: Change the autogenerated stub

        $table = $this->wrapTable($query->from);


        $columns = $this->compileUpdateColumns($query, $values);

        $where = $this->compileUpdateWheres($query);


        return trim("UPDATE ($table} SET {$columns} $where");
    }

    protected function compileUpdateColumns(Builder $query, $values)
    {
        $columns = [];

        foreach ($values as $key => $value) {
            $columns[] = $this->wrap($key) . ' = ' . $this->parameter($value);
        }

        return implode(', ', $columns);
    }

    protected function compileUpdateWheres(Builder $query)
    {
        $baseWhere = $this->compileWheres($query);

        return $baseWhere;
    }

    protected function dateBasedWhere($type, Builder $query, $where)
    {
        //return parent::dateBasedWhere($type, $query, $where); // TODO: Change the autogenerated stub

        $value = $this->parameter($where['value']);

        return 'EXTRACT(' . $type . ' FROM ' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }

    protected function wrapValue($value)
    {
        //return parent::wrapValue($value); // TODO: Change the autogenerated stub

        if ($value === '*') {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }
}