<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Navigation;

final class Breadcrumbs
{
    /**
     * Normalizuje elementy do postaci akceptowanej przez JSON-LD (ListItem).
     * @param array<int,array{name?:string,url?:string,item?:string,position?:int}> $items
     * @return array<int,array{name?:string,item?:string,position:int}>
     */
    public function normalizeItems(array $items): array
    {
        $out = [];
        foreach (array_values($items) as $i => $it) {
            $out[] = [
                'name'     => $it['name'] ?? null,
                'item'     => $it['item'] ?? ($it['url'] ?? null),
                'position' => $it['position'] ?? ($i + 1),
            ];
        }
        return $out;
    }

    /**
     * Render prostego HTML <nav aria-label="breadcrumb">…</nav>
     * @param array<int,array{name:string,url?:string}> $items
     */
    public function render(array $items): string
    {
        $li = [];
        $last = count($items) - 1;
        foreach ($items as $i => $it) {
            $name = e($it['name'] ?? '');
            $url  = $it['url'] ?? null;
            if ($i === $last || empty($url)) {
                $li[] = '<li class="breadcrumb-item active" aria-current="page">'.$name.'</li>';
            } else {
                $li[] = '<li class="breadcrumb-item"><a href="'.e($url).'">'.$name.'</a></li>';
            }
        }
        return '<nav aria-label="breadcrumb"><ol class="breadcrumb">'.implode('', $li).'</ol></nav>';
    }
}
