<?php

namespace MattRabe\BladeLint\Rules;

use Illuminate\Filesystem\Filesystem;
use MattRabe\BladeLint\Rule;

class Indent extends Rule
{
    private $file = null;

    private $spaces = 4;
    private $tab = false;

    private $lines = [];

    public function __construct(string $file, array $options) {
        $this->file = $file;

        if (isset($options['spaces'])) {
            $this->spaces = $options['spaces'];
        }

        if (isset($options['tab'])) {
            $this->tab = $options['tab'];
        }
    }

    public function test() {
        $lines = file($this->file);

        $this->lines = collect($lines)->map(function($line) {
            return [
                'content' => $line,
            ];
        })->toArray();

        $level = 0;
        $tagTree = [];
        foreach ($this->lines as $i => $line) {
            if (preg_match('/^[ \t]*@/', $line['content']) && count($tagTree) && preg_match('/^[ \t]*@end'.$tagTree[count($tagTree) - 1].'/', $line['content'])) {
                $level--;
                array_pop($tagTree);
            }

            $this->lines[$i]['level'] = $level;

            if (preg_match('/^[ \t]*@(?!end)/', $line['content'])) {
                $tag = preg_replace('/^[ \t]*@([^(]*).*$/', '$1', $line['content']);

                if (collect($lines)->first(function($otherLine, $otherI) use ($i, $tag) {
                    return $otherI > $i && preg_match('/^[ \t]*@end'.$tag.'/', $otherLine);
                })) {
                    $level++;
                    array_push($tagTree, $tag);
                }
            }
        }

        $indent = $this->tab ? "\t" : str_repeat(' ', $this->spaces);

        foreach ($this->lines as $i => $line) {
            $fixedContent = str_repeat($indent, $line['level']).preg_replace('/^[ \t]*/', '', $line['content']);

            $this->lines[$i]['fixedContent'] = $fixedContent;
            $this->lines[$i]['valid'] = $fixedContent === $line['content'];
            $this->lines[$i]['column'] = 0;
        }

        return [ 'lines' => collect($this->lines)->filter(function($line) {
            return !$line['valid'];
        })->toArray() ];
    }

    public function fix() {
        if (!collect($this->lines)->filter(function($line) {
            return !$line['valid'];
        })->count()) {
            return false;
        }

        $output = '';
        foreach ($this->lines as $line) {
            $output .= $line['fixedContent'];
        }

        $filesystem = new Filesystem;
        $filesystem->put($this->file, $output);

        return true;
    }
}
