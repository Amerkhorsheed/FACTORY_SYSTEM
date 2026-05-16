<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_performs_basic_crud_operations(): void
    {
        $repository = new UserTestRepository;

        $created = $repository->create([
            'name' => 'Original User',
            'email' => 'original@example.test',
            'password' => 'secret-password',
        ]);

        $this->assertInstanceOf(User::class, $created);
        $this->assertSame($created->id, $repository->findByIdOrFail($created->id)->id);

        $updated = $repository->update($created, ['name' => 'Updated User']);

        $this->assertSame('Updated User', $updated->name);

        $repository->delete($updated);

        $this->assertNull($repository->findById($updated->id));
    }

    /**
     * @test
     */
    public function it_paginates_queries_with_explicit_page_size(): void
    {
        User::factory()->count(3)->create();

        $repository = new UserTestRepository;
        $paginator = $repository->paginate($repository->builder()->orderBy('id'), 2);

        $this->assertSame(2, $paginator->perPage());
        $this->assertSame(3, $paginator->total());
    }
}

class UserTestRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new User);
    }

    public function builder(): Builder
    {
        return $this->query();
    }
}
