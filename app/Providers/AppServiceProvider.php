<?php

namespace App\Providers;

use App\Repositories\Contracts\ApprovalRepositoryInterface;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\UserManagementRepositoryInterface;
use App\Repositories\Contracts\WorkflowRepositoryInterface;
use App\Repositories\Eloquent\ApprovalRepository;
use App\Repositories\Eloquent\DocumentRepository;
use App\Repositories\Eloquent\UserManagementRepository;
use App\Repositories\Eloquent\WorkflowRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
        $this->app->bind(ApprovalRepositoryInterface::class, ApprovalRepository::class);
        $this->app->bind(WorkflowRepositoryInterface::class, WorkflowRepository::class);
        $this->app->bind(UserManagementRepositoryInterface::class, UserManagementRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
