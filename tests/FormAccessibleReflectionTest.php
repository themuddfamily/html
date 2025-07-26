<?php

use LaravelLux\Html\Eloquent\FormAccessible;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

#[\AllowDynamicProperties]
class FormAccessibleReflectionTest extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Capsule::table('models')->truncate();
        Model::unguard();
    }

    public function testReflectionIsCachedForMutators()
    {
        $model = new DummyReflectionModel(['string' => 'foo']);

        $model->getFormValue('string');
        $firstReflection = $model->getReflectionInstance();
        $this->assertSame(1, $model->getReflectionCount());

        $model->getFormValue('string');
        $secondReflection = $model->getReflectionInstance();

        $this->assertSame(1, $model->getReflectionCount());
        $this->assertSame($firstReflection, $secondReflection);
    }
}

class DummyReflectionModel extends Model
{
    use FormAccessible { getReflection as protected baseGetReflection; }

    protected $table = 'models';

    public int $reflectionCount = 0;

    protected function getReflection(): \ReflectionClass
    {
        if (! $this->reflection) {
            $this->reflectionCount++;
        }

        return $this->baseGetReflection();
    }

    public function getReflectionCount(): int
    {
        return $this->reflectionCount;
    }

    public function getReflectionInstance(): ?\ReflectionClass
    {
        return $this->reflection;
    }

    public function formStringAttribute($value)
    {
        return strtoupper($value);
    }
}
