<?php

use FastModelFactory\FastModelFactory as Factory;

class FastModelFactoryTest extends Illuminate\Foundation\Testing\TestCase
{

    public function createApplication()
    {
        $app = require __DIR__.'/../../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'testing');

        $this->app['config']->set('database.connections.testing', array(
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ));
        $this->request = $this->app['request'];

        $I = $this->getMockForAbstractClass('FastMigrate\FastMigrator');
        $I->wantATable('users')->
            withStrings('first_name', 'last_name');
        $I->wantATable('roles')->
            withStrings('title');
        $I->wantATable('profiles')->
            withStrings('title');
        $I->wantATable('posts')->
            withStrings('title', 'content');
        $I->want('users')->belongsTo('roles');
        $I->want('users')->toHaveOne('profiles');
        $I->want('users')->toHaveMany('posts');
        $I->amReadyForMigration();
    }

    public function testCreate()
    {
        $data = ['first_name' => 'John', 'last_name' => 'Doe'];
        Input::merge($data);
        $user = Factory::create(User::class);
        $this->seeInDatabase('users', $data);
    }

    public function testUpdate()
    {
        $data = ['first_name' => 'John', 'last_name' => 'Doe'];
        $user = Factory::create(User::class, $data);
        $update_data = ['id' => $user->id, 'first_name' => 'Moe', 'last_name' => 'Smith'];
        $user = Factory::update(User::class, $update_data);
        $this->seeInDatabase('users', $update_data);
    }

    public function testCreateWithBelongsTo()
    {
        $data = ['first_name' => 'John', 'last_name' => 'Doe',
        'role' => ['title' => 'super user']];
        Input::merge($data);
        $user = Factory::create(User::class);
        $this->assertNotNull($user->role);
        $this->seeInDatabase('users', array_only($data, ['first_name', 'last_name']));
        $this->seeInDatabase('roles', array_get($data, 'role'));
    }

    public function testUpdateWithBelongsTo()
    {
        $data = ['first_name' => 'John', 'last_name' => 'Doe',
        'role' => ['title' => 'super user']];
        $user = Factory::create(User::class, $data);
        $update_data = ['id' => $user->id, 'first_name' => 'John', 'last_name' => 'Doe',
        'role' => ['id' => $user->role->id, 'title' => 'super user update']];
        $user = Factory::update(User::class, $update_data);

        $this->assertNotNull($user->role);
        $this->seeInDatabase('users', array_only($update_data, ['id', 'first_name', 'last_name']));
        $this->seeInDatabase('roles', array_get($update_data, 'role'));
    }

    public function testCreateWithHasOne()
    {
        $data = ['first_name' => 'John', 'last_name' => 'Doe',
        'profile' => ['title' => 'Big Boss']];
        Input::merge($data);
        $user = Factory::create(User::class);
        $this->assertNotNull($user->profile);
        $this->seeInDatabase('users', array_only($data, ['first_name', 'last_name']));
        $this->seeInDatabase('profiles', array_get($data, 'profile'));
    }

    public function testUpdateWithHasOne()
    {
        $data = ['first_name' => 'John', 'last_name' => 'Doe',
        'profile' => ['title' => 'Big Boss']];
        $user = Factory::create(User::class, $data);
        $update_data = ['id' => $user->id, 'first_name' => 'John', 'last_name' => 'Doe',
        'profile' => ['id' => $user->profile->id, 'title' => 'Big Boss update']];
        $user = Factory::update(User::class, $update_data);

        $this->assertNotNull($user->profile);
        $this->seeInDatabase('users', array_only($update_data, ['id', 'first_name', 'last_name']));
        $this->seeInDatabase('profiles', array_get($update_data, 'profile'));
    }

    public function testCreateWithHasMany()
    {
        $data = ['first_name' => 'John', 'last_name' => 'Doe',
            'posts' => [['title' => 'Post 1'], ['title' => 'Post 2' ]]];
        Input::merge($data);
        $user = Factory::create(User::class);
        $this->assertNotNull($user->posts);
        $this->seeInDatabase('users', array_only($data, ['first_name', 'last_name']));
        $this->seeInDatabase('posts', array_get($data, 'posts.0'));
        $this->seeInDatabase('posts', array_get($data, 'posts.1'));
    }

    public function testUpdateWithHasMany()
    {
        $data = ['first_name' => 'John', 'last_name' => 'Doe',
            'posts' => [['title' => 'Post 1'], ['title' => 'Post 2' ]]];
        $user = Factory::create(User::class, $data);
        $update_data = ['id' => $user->id, 'first_name' => 'Moe', 'last_name' => 'Smith',
            'posts' => [
                ['id' => $user->posts[0]->id, 'title' => 'Post 1 update'],
                ['id' => $user->posts[1]->id, 'title' => 'Post 2 udpate']]];
        $user = Factory::update(User::class, $update_data);

        $this->assertNotNull($user->posts);
        $this->seeInDatabase('users', array_only($update_data, ['id', 'first_name', 'last_name']));
        $this->seeInDatabase('posts', array_get($update_data, 'posts.0'));
        $this->seeInDatabase('posts', array_get($update_data, 'posts.1'));
    }

}

class User extends Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['first_name', 'last_name', 'role', 'profile', 'posts'];
    public function role()
    {
        return $this->belongsTo('Role');
    }
    public function profile()
    {
        return $this->hasOne('Profile');
    }
    public function posts()
    {
        return $this->hasMany('Post');
    }
}
class Profile extends Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['title'];
}
class Role extends Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['title'];
}
class Post extends Illuminate\Database\Eloquent\Model
{
    protected $fillable = ['title'];
}
