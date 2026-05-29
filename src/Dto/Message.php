<?php

declare(strict_types=1);

namespace Wfl\Dto;

use Waffle\Commons\Contracts\Attribute\Dto;
use Waffle\Exception\ValidationException;

#[Dto]
final class Message
{
    public function __construct(
        private(set) string $author {
            set(string $value) {
                $clean = trim($value);

                if ($clean === '' || preg_match('/^\p{L}+$/u', $clean) !== 1) {
                    throw new ValidationException(
                        message: 'Le champ « name » doit être une chaîne non vide composée uniquement de lettres.',
                        field: 'name',
                    );
                }

                $this->author = $clean;
            }
        },
    ) {}
}