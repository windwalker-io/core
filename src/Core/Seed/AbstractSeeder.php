<?php

declare(strict_types=1);

namespace Windwalker\Core\Seed;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use SplFileInfo;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\ORM\ORM;
use Windwalker\Utilities\Attributes\AttributesAccessor;
use Windwalker\Utilities\StrNormalize;

abstract class AbstractSeeder
{
    use CountingOutputTrait;

    public protected(set) string $name;

    public protected(set) string $prettyName;

    public protected(set) SplFileInfo $file;

    public protected(set) DatabaseAdapter $db;

    public ORM $orm {
        get => $this->db->orm();
    }

    protected FakerService $faker;

    public function init(
        SplFileInfo $file,
        DatabaseAdapter $db,
        FakerService $faker
    ): static {
        $this->file = $file;
        $this->db = $db;
        $this->faker = $faker;
        $this->name = $this->file->getBasename('.php');
        $this->prettyName = ucwords(StrNormalize::toSpaceSeparated($this->name));

        return $this;
    }

    public function faker(string $locale = FakerFactory::DEFAULT_LOCALE): FakerGenerator
    {
        return $this->faker->create($locale);
    }

    public function truncate(string ...$tables): static
    {
        foreach ($tables as $table) {
            $this->db->getTableManager($table)->truncate();
        }

        return $this;
    }

    public function getImportHandler(): ?\Closure
    {
        if (!$found = $this->getReflectionMethod(SeedImport::class)) {
            return null;
        }

        return $found[0]->getClosure($this);
    }

    public function getClearHandler(): ?\Closure
    {
        if (!$found = $this->getReflectionMethod(SeedClear::class)) {
            return null;
        }

        return $found[0]->getClosure($this);
    }

    /**
     * @return  array{ \ReflectionMethod, \ReflectionAttribute<SeedImport> }|null
     */
    protected function getReflectionMethod(string $attr): ?array
    {
        return AttributesAccessor::getFirstMemberWithAttribute(
            $this,
            $attr,
            \ReflectionAttribute::IS_INSTANCEOF,
            \ReflectionMethod::class
        );
    }
}
