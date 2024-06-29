<?php

declare(strict_types=1);

namespace DouglasGreen\PhpLinter\Nikic\Checker;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Scalar\LNumber;

class ArrayChecker extends NodeChecker
{
    /**
     * @var array<string|int, bool>
     */
    protected $duplicateKeys = [];

    /**
     * @return array<string, bool>
     */
    public function check(): array
    {
        if (! $this->node instanceof Array_) {
            return [];
        }

        $keys = [];
        foreach ($this->node->items as $item) {
            if ($item !== null && $item->key !== null) {
                if ($item->key instanceof String_) {
                    $key = $item->key->value;
                } elseif ($item->key instanceof LNumber) {
                    $key = $item->key->value;
                } else {
                    // For other types of keys (like variables or expressions), we'll use a
                    // placeholder.  In a real scenario, you might want to handle these differently.
                    $key = 'dynamic_key_' . spl_object_id($item->key);
                }

                if (isset($keys[$key])) {
                    $this->addIssue('Duplicated key in array: ' . $key);
                } else {
                    $keys[$key] = true;
                }
            }
        }

        return $this->getIssues();
    }
}
