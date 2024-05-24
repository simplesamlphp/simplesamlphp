<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\core\Auth\UserPassOrgBase;

/**
 */
#[CoversClass(UserPassOrgBase::class)]
class UserPassOrgBaseTest extends TestCase
{
    /**
     */
    public function testRememberOrganizationEnabled(): void
    {
        $config = [
            'ldap:LDAPMulti',

            'remember.organization.enabled' => true,
            'remember.organization.checked' => false,

            'my-org' => [
                'description' => 'My organization',
                // The rest of the options are the same as those available for
                // the LDAP authentication source.
                'hostname' => 'ldap://ldap.myorg.com',
                'dnpattern' => 'uid=%username%,ou=employees,dc=example,dc=org',
                // Whether SSL/TLS should be used when contacting the LDAP server.
                'enable_tls' => false,
            ],
        ];

        $userPassOrgBase = new class (['AuthId' => 'my-org'], $config) extends UserPassOrgBase {
            protected function login(string $username, string $password, string $organization): array
            {
                return [];
            }


            protected function getOrganizations(): array
            {
                return [];
            }
        };
        $this->assertTrue($userPassOrgBase->getRememberOrganizationEnabled());
    }
}
