<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Tests\Functional\Hydrator;

use Overblog\GraphQLBundle\Tests\Functional\TestCase;

class HydratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel(['test_case' => 'hydrator']);
    }

    /**
     * @test
     */
    public function simpleHydration(): void
    {
        $query = <<<'QUERY'
        mutation {
            createUser(input: {
                username: "murtukov"
                firstName: "Timur"
                lastName: "Murtukov"
                address: {
                    street: "Proletarskaya 28"
                    city: "Izberbash"
                    zipCode: 368500
                }
                friends: [
                    {
                        username: "Clay007"
                        firstName: "Clay"
                        lastName: "Jensen",
                        friends: []
                    },
                    {
                        username: "frodo37"
                        firstName: "Frodo"
                        lastName: "Baggins"
                        friends: []
                    }
                ]
                postId: 13
            })
        }
        QUERY;

        $result = self::executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['noValidation']);
    }

    /**
     * @test
     */
    public function updateEntity()
    {
        $query = <<<'QUERY'
        mutation {
            updateUser(
                input: {
                    id: 15
                    username: "murtukov"
                    firstName: "Timur"
                    lastName: "Murtukov"
                    address: {
                        street: "Proletarskaya 28"
                        city: "Izberbash"
                        zipCode: 368500
                    }
                    friends: [
                        {
                            username: "Clay007"
                            firstName: "Clay"
                            lastName: "Jensen",
                            friends: []
                        },
                        {
                            username: "frodo37"
                            firstName: "Frodo"
                            lastName: "Baggins"
                            friends: []
                        }
                    ]
                    posts: [
                        
                    ]
            })
        }
        QUERY;

        $result = self::executeGraphQLRequest($query);

        $this->assertTrue(empty($result['errors']));
        $this->assertTrue($result['data']['noValidation']);
    }
}
