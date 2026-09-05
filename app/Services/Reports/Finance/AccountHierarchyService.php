<?php

namespace App\Services\Reports\Finance;

use Illuminate\Support\Collection;

final class AccountHierarchyService
{
    public function tree(Collection $accounts): array
    {
        $nodes = [];
        foreach ($accounts as $account) {
            $id = (int)data_get($account,'id');
            $nodes[$id] = [
                'account' => $account,
                'children' => [],
            ];
        }

        $roots = [];
        foreach ($nodes as $id => &$node) {
            $parentId = data_get($node['account'],'parent_id');
            if ($parentId && isset($nodes[(int)$parentId])) {
                $nodes[(int)$parentId]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);

        $sort = function (&$branch) use (&$sort): void {
            usort($branch, fn ($a,$b) => strcmp(
                (string)data_get($a['account'],'code'),
                (string)data_get($b['account'],'code')
            ));
            foreach ($branch as &$node) {
                $sort($node['children']);
            }
        };
        $sort($roots);

        return $roots;
    }

    public function flatten(Collection $accounts): array
    {
        $rows = [];
        $walk = function (array $nodes, int $depth = 0) use (&$walk, &$rows): void {
            foreach ($nodes as $node) {
                $account = clone $node['account'];
                $account->depth = $depth;
                $account->display_name = str_repeat('↳ ', $depth).$account->name;
                $rows[] = $account;
                $walk($node['children'], $depth + 1);
            }
        };

        $walk($this->tree($accounts));

        return $rows;
    }

    public function rollup(Collection $accounts, array $valueKeys): Collection
    {
        $byId = $accounts->keyBy(fn ($a) => (int)$a->id);
        $direct = [];

        foreach ($accounts as $account) {
            $id = (int)$account->id;
            foreach ($valueKeys as $key) {
                $direct[$id][$key] = (float)($account->{$key} ?? 0);
                $account->{$key} = $direct[$id][$key];
            }
        }

        foreach ($accounts as $account) {
            $sourceId = (int)$account->id;
            $parentId = $account->parent_id ? (int)$account->parent_id : null;
            $guard = [];

            while ($parentId && isset($byId[$parentId]) && ! isset($guard[$parentId])) {
                $guard[$parentId] = true;
                $parent = $byId[$parentId];
                foreach ($valueKeys as $key) {
                    $parent->{$key} += $direct[$sourceId][$key];
                }
                $parentId = $parent->parent_id ? (int)$parent->parent_id : null;
            }
        }

        return $accounts;
    }
}
