<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Auth\Method;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Windwalker\Authentication\AuthResult;
use Windwalker\Authentication\Method\MethodInterface;
use Windwalker\Core\Runtime\Config;
use Windwalker\Data\Collection;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Query\Query;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Options\OptionsResolverTrait;

/**
 * The DatabaseMethod class.
 */
class DatabaseMethod implements MethodInterface
{
    use OptionsResolverTrait;

    /**
     * DatabaseMethod constructor.
     *
     * @param  DatabaseAdapter  $db
     * @param  Config           $config
     * @param  array            $options
     */
    public function __construct(protected DatabaseAdapter $db, protected Config $config, array $options = [])
    {
        $this->resolveOptions(
            $options,
            [$this, 'configureOptions']
        );
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'id_name' => 'id',
                'login_name' => 'username',
                'password_algo' => \PASSWORD_BCRYPT,
            ]
        )
            ->setRequired(
                [
                    'table',
                ]
            );
    }

    /**
     * authenticate
     *
     * @param  array  $credential
     *
     * @return AuthResult
     */
    public function authenticate(array $credential): AuthResult
    {
        $loginNames = (array) $this->getOption('login_name');
        $table      = $this->getOption('table');
        $password   = $credential['password'] ?? '';

        $usernames = Arr::only($credential, $loginNames);

        $password = $credential['password'];

        if ($usernames === [] || (string) $password === '') {
            return new AuthResult(AuthResult::EMPTY_CREDENTIAL, $credential);
        }

        $user = $this->db->select('*')
            ->from($table)
            ->orWhere(
                function (Query $query) use ($usernames) {
                    foreach ($usernames as $field => $username) {
                        $query->where($field, $username);
                    }
                }
            )
            ->get();

        if (!$user) {
            return new AuthResult(AuthResult::USER_NOT_FOUND, $credential);
        }

        if (!password_verify($password, $user->password ?? '')) {
            return new AuthResult(AuthResult::INVALID_PASSWORD, $credential);
        }

        $this->rehash($user, $credential);

        $credential = array_merge($credential, $user->dump());

        return new AuthResult(AuthResult::SUCCESS, $credential);
    }

    /**
     * rehash
     *
     * @param  Collection  $user
     * @param  array       $credential
     *
     * @return  void
     *
     * @throws \JsonException
     * @since  1.4.6
     */
    protected function rehash(Collection $user, array $credential): void
    {
        if (password_needs_rehash($user->password, $this->getOption('password_algo'))) {
            $user->password = password_hash(
                $credential['password'] ?? '',
                $this->getOption('password_algo')
            );

            $this->db->getWriter()
                ->updateOne(
                    $this->getOption('table'),
                    $user,
                    $this->getOption('id_name'),
                );
        }
    }
}
