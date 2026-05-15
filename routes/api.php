<?php

use App\Http\Controllers\Api\AdminDirectoryController;
use App\Http\Controllers\Api\AdminModerationController;
use App\Http\Controllers\Api\AdminSubAdminController;
use App\Http\Controllers\Api\AdminSubscriptionPlanController;
use App\Http\Controllers\Api\AdminSubscriptionsController;
use App\Http\Controllers\Api\AccountDeletionController;
use App\Http\Controllers\Api\CompanyProfileController;
use App\Http\Controllers\Api\CompanyJobPostingController;
use App\Http\Controllers\Api\CompanyUserLookupController;
use App\Http\Controllers\Api\JobSeekerJobApplicationController;
use App\Http\Controllers\Api\JobSeekerJobPostingController;
use App\Http\Controllers\Api\JobSeekerProfileController;
use App\Http\Controllers\Api\JobSeekerSubscriptionController;
use App\Http\Controllers\Api\JobSeekerSubscriptionPlanController;
use App\Http\Controllers\Api\PublicCompanyController;
use App\Http\Controllers\AuthController;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin'])
    ->middleware('throttle:10,1');

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:6,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:6,1');

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/companies', [PublicCompanyController::class, 'index']);
    Route::get('/companies/{company}', [PublicCompanyController::class, 'show']);
    Route::get('/subscription-plans', [JobSeekerSubscriptionPlanController::class, 'index']);
});

Route::middleware('guest_or_job_seeker')->group(function () {
    Route::get('/job-postings', [JobSeekerJobPostingController::class, 'index']);
    Route::get('/job-postings/{job_posting}', [JobSeekerJobPostingController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('account_active')->group(function () {
        Route::get('/user', function (Request $request) {
            $user = $request->user();

            if ($user !== null && $user->isJobSeeker()) {
                $user->loadMissing([
                    'jobSeekerProfile',
                    'skills',
                    'educations',
                    'experiences',
                    'certificates',
                    'activeSeekerSubscription.plan',
                    'activeSeekerSubscription.payment',
                ]);
            }

            if ($user !== null && $user->isCompany()) {
                $user->loadMissing(['companyProfile']);
            }

            if ($user !== null && $user->isAdmin()) {
                $user->loadMissing(['admin']);
            }

            return ApiResponse::data(
                (new UserResource($user))->resolve($request),
            );
        });

        Route::middleware('company')->group(function () {
            Route::get('/company/applications', [CompanyJobPostingController::class, 'allApplications']);
            Route::get('/company/job-postings', [CompanyJobPostingController::class, 'index']);
            Route::post('/company/job-postings', [CompanyJobPostingController::class, 'store']);
            Route::get('/company/job-postings/{job_posting}/applications', [CompanyJobPostingController::class, 'applications']);
            Route::patch('/company/job-postings/{job_posting}/applications/{job_application}', [CompanyJobPostingController::class, 'updateApplicationStatus']);
            Route::get('/company/job-postings/{job_posting}', [CompanyJobPostingController::class, 'show']);
            Route::put('/company/job-postings/{job_posting}', [CompanyJobPostingController::class, 'update']);
            Route::patch('/company/job-postings/{job_posting}', [CompanyJobPostingController::class, 'update']);
            Route::delete('/company/job-postings/{job_posting}', [CompanyJobPostingController::class, 'destroy']);
            Route::get('/company/users/{user}', [CompanyUserLookupController::class, 'show']);
            Route::get('/company/profile', [CompanyProfileController::class, 'show']);
            Route::post('/company/profile', [CompanyProfileController::class, 'update']);
            Route::put('/company/profile', [CompanyProfileController::class, 'update']);
            Route::patch('/company/profile', [CompanyProfileController::class, 'update']);
            Route::delete('/company/profile', [AccountDeletionController::class, 'destroy']);
        });

        Route::middleware('job_seeker')->group(function () {
            Route::post('/profile', [JobSeekerProfileController::class, 'update']);
            Route::patch('/profile', [JobSeekerProfileController::class, 'update']);
            Route::delete('/profile', [AccountDeletionController::class, 'destroy']);
            Route::get('/applications', [JobSeekerJobApplicationController::class, 'index']);
            Route::post('/job-postings/{job_posting}/applications', [JobSeekerJobApplicationController::class, 'store']);

            Route::post('/subscriptions', [JobSeekerSubscriptionController::class, 'store'])
                ->middleware('throttle:30,1');
        });
    });

    Route::middleware('admin')->group(function () {
        Route::get('/admin/ping', function () {
            return ApiResponse::message('Admin OK.');
        });

        Route::get('/admin/summary', [AdminDirectoryController::class, 'summary']);
        Route::get('/admin/users', [AdminDirectoryController::class, 'users']);
        Route::get('/admin/companies', [AdminDirectoryController::class, 'companies']);
        Route::get('/admin/job-postings', [AdminDirectoryController::class, 'jobPostings']);
        Route::get('/admin/applications', [AdminDirectoryController::class, 'applications']);
        Route::get('/admin/job-seekers/{job_seeker}/applications', [AdminDirectoryController::class, 'seekerApplications']);
        Route::get('/admin/companies/{company}/job-postings', [AdminDirectoryController::class, 'companyJobPostings']);

        Route::patch('/admin/job-seekers/{user}/status', [AdminModerationController::class, 'updateJobSeekerStatus']);
        Route::patch('/admin/companies/{company}/status', [AdminModerationController::class, 'updateCompanyStatus']);
        Route::delete('/admin/job-postings/{job_posting}', [AdminModerationController::class, 'destroyJobPosting']);

        Route::get('/admin/subscription-plans', [AdminSubscriptionPlanController::class, 'index']);
        Route::post('/admin/subscription-plans', [AdminSubscriptionPlanController::class, 'store']);
        Route::get('/admin/subscription-plans/{subscription_plan}', [AdminSubscriptionPlanController::class, 'show']);
        Route::patch('/admin/subscription-plans/{subscription_plan}', [AdminSubscriptionPlanController::class, 'update']);
        Route::delete('/admin/subscription-plans/{subscription_plan}', [AdminSubscriptionPlanController::class, 'destroy']);

        Route::get('/admin/subscriptions', [AdminSubscriptionsController::class, 'index']);
        Route::patch('/admin/subscriptions/{subscription}/suspend', [AdminSubscriptionsController::class, 'suspend']);

        Route::middleware('super_admin')->group(function () {
            Route::get('/admin/super/ping', function () {
                return ApiResponse::message('Super admin OK.');
            });

            Route::get('/admin/admins', [AdminSubAdminController::class, 'index']);
            Route::post('/admin/admins', [AdminSubAdminController::class, 'store']);
            Route::get('/admin/admins/{user}', [AdminSubAdminController::class, 'show']);
            Route::patch('/admin/admins/{user}', [AdminSubAdminController::class, 'update']);
            Route::post('/admin/admins/{user}/activate', [AdminSubAdminController::class, 'activate']);
            Route::post('/admin/admins/{user}/deactivate', [AdminSubAdminController::class, 'deactivate']);
        });
    });
});
