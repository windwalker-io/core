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
use Windwalker\Core\Cache\RuntimeCacheTrait;
use Windwalker\Event\Dispatcher;
use Windwalker\Event\DispatcherAwareTrait;
use function Windwalker\tap;

/**
 * The FakerService class.
 *
 * @since  3.5
 */
class FakerService
{
    use RuntimeCacheTrait;
    use DispatcherAwareTrait;

    /**
     * FakerService constructor.
     */
    public function __construct()
    {
        $this->dispatcher = new Dispatcher();
    }

    /**
     * create
     *
     * @param string $locale
     *
     * @return  FakerGenerator
     *
     * @since  3.5
     */
    public function create(string $locale = FakerFactory::DEFAULT_LOCALE): FakerGenerator
    {
        $locale = str_replace('-', '_', $locale);

        return tap(FakerFactory::create($locale), function (FakerGenerator $faker) use ($locale) {
            $this->dispatcher->triggerEvent('afterFakerCreated', ['faker' => $faker, 'locale' => $locale]);
        });
    }

    /**
     * getInstance
     *
     * @param string $locale
     * @param bool   $new
     *
     * @return  FakerGenerator
     *
     * @throws \Psr\Cache\InvalidArgumentException
     *
     * @since  3.5
     */
    public function getInstance(string $locale = FakerFactory::DEFAULT_LOCALE, bool $new = false): FakerGenerator
    {
        return $this->once($locale, function () use ($locale) {
            return $this->create($locale);
        }, $new);
    }
}
