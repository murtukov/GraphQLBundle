<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator\Model;

use Overblog\GraphQLBundle\Hydrator\Annotation\Model;

/**
 * @Model
 */
class User
{
    public string $username;
    public string $firstName;
    public string $lastName;
    public Birthdate $birthdate;
    public ?Address $address = null;
    public array $friends = [];
}