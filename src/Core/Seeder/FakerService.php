<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Seeder;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Faker\Generator;
use Windwalker\Core\Cache\RuntimeCacheTrait;

/**
 * The FakerService class.
 *
 * @since  __DEPLOY_VERSION__
 */
class FakerService
{
    use RuntimeCacheTrait;

    /**
     * create
     *
     * @param string $locale
     *
     * @return  FakerGenerator
     *
     * @since  __DEPLOY_VERSION__
     */
    public function create(string $locale = FakerFactory::DEFAULT_LOCALE): FakerGenerator
    {
        $locale = str_replace('-', '_', $locale);

        return FakerFactory::create($locale);
    }

    /**
     * getInstance
     *
     * @param string $locale
     * @param bool   $new
     *
     * @return  Generator
     *
     * @throws \Psr\Cache\InvalidArgumentException
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getInstance(string $locale = FakerFactory::DEFAULT_LOCALE, bool $new = false): Generator
    {
        return $this->fetch($locale, function () use ($locale) {
            return $this->create($locale);
        }, $new);
    }
}
