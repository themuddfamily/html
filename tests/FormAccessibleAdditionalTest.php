<?php

use LaravelLux\Html\Eloquent\FormAccessible;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;

#[\AllowDynamicProperties]
class FormAccessibleAdditionalTest extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Capsule::table('models')->truncate();
        Model::unguard();
    }

    public function testGetAllDateCastableAttributes()
    {
        $model = new ModelWithDateCasts();

        $expected = ['foo_at', 'bar_at', 'created_at', 'updated_at'];
        $this->assertEqualsCanonicalizing(
            $expected,
            $model->getAllDateCastableAttributes()
        );
    }

    public function testIsNestedModelDetectsLoadedRelation()
    {
        $model = new ModelWithDateCasts();

        $this->assertFalse($model->isNestedModel('child'));

        $model->setRelation('child', new ModelWithDateCasts());
        $this->assertTrue($model->isNestedModel('child'));
    }

    public function testGetFormValueReturnsNullForMissingRelation()
    {
        $model = new ModelWithDateCasts();

        $this->assertNull($model->getFormValue('child.name'));

        $model->setRelation('child', null);
        $this->assertNull($model->getFormValue('child.name'));
    }
}

class ModelWithDateCasts extends Model
{
    use FormAccessible;

    protected $table = 'models';

    protected $casts = [
        'foo_at' => 'datetime',
        'bar_at' => 'date',
    ];
}
